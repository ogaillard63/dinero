<?php

namespace App\Controllers;

class ImportController extends BaseController {
    public function index() {
        // Get all accounts for the dropdown
        $stmt = $this->db->query("
            SELECT a.*, b.name as bank_name 
            FROM accounts a 
            JOIN banks b ON a.bank_id = b.id 
            ORDER BY b.name, a.sort_order ASC, a.id ASC
        ");
        $accounts = $stmt->fetchAll();
        
        $this->render('import.twig', [
            'accounts' => $accounts
        ]);
    }

    public function parse() {
        header('Content-Type: application/json');
        
        $rawData = $_POST['raw_data'] ?? '';
        
        if (empty($rawData)) {
            echo json_encode(['error' => 'Aucune donnée fournie']);
            return;
        }
        
        // Parse the data
        $lines = array_filter(array_map('trim', explode("\n", $rawData)));
        
        if (empty($lines)) {
            echo json_encode(['error' => 'Aucune ligne valide trouvée']);
            return;
        }
        
        // Detect delimiter (tab, semicolon, comma)
        $firstLine = $lines[0];
        $delimiter = $this->detectDelimiter($firstLine);
        
        // Parse all lines
        $rows = [];
        foreach ($lines as $line) {
            $columns = str_getcsv($line, $delimiter);
            $rows[] = array_map('trim', $columns);
        }
        
        // Detect if first row is header
        $hasHeader = $this->detectHeader($rows);
        
        $headers = $hasHeader ? array_shift($rows) : [];
        
        // Auto-detect column mapping
        $mapping = $this->detectColumnMapping($headers, $rows);
        
        echo json_encode([
            'success' => true,
            'rows' => $rows,
            'headers' => $headers,
            'hasHeader' => $hasHeader,
            'suggestedMapping' => $mapping,
            'delimiter' => $delimiter
        ]);
    }
    
    public function import() {
        header('Content-Type: application/json');
        
        $accountId = $_POST['account_id'] ?? null;
        $skipDuplicates = ($_POST['skip_duplicates'] ?? '1') === '1';
        $transactions = json_decode($_POST['transactions'] ?? '[]', true);
        
        if (!$accountId || empty($transactions)) {
            echo json_encode(['error' => 'Données invalides']);
            return;
        }
        
        $imported = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($transactions as $index => $transaction) {
            try {
                // Validate required fields
                if (empty($transaction['date']) || empty($transaction['description']) || !isset($transaction['amount'])) {
                    $errors[] = "Ligne " . ($index + 1) . ": Champs requis manquants";
                    continue;
                }
                
                // Parse date
                $date = $this->parseDate($transaction['date']);
                if (!$date) {
                    $errors[] = "Ligne " . ($index + 1) . ": Date invalide";
                    continue;
                }
                
                // Parse amount
                $amount = $this->parseAmount($transaction['amount']);
                
                // Determine type
                $type = $amount < 0 ? 'debit' : 'credit';
                
                // Check for duplicate only if skip_duplicates is enabled
                if ($skipDuplicates) {
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) as count 
                        FROM transactions 
                        WHERE account_id = :account_id 
                        AND date = :date 
                        AND amount = :amount
                    ");
                    $stmt->execute([
                        'account_id' => $accountId,
                        'date' => $date,
                        'amount' => $amount
                    ]);
                    $result = $stmt->fetch();
                    
                    if ($result['count'] > 0) {
                        $skipped++;
                        continue; // Skip duplicate
                    }
                }
                
                // Insert transaction
                $stmt = $this->db->prepare("
                    INSERT INTO transactions (account_id, date, description, amount, type) 
                    VALUES (:account_id, :date, :description, :amount, :type)
                ");
                
                $stmt->execute([
                    'account_id' => $accountId,
                    'date' => $date,
                    'description' => $transaction['description'],
                    'amount' => $amount,
                    'type' => $type
                ]);
                
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Ligne " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        // Update snapshots for affected dates
        if ($imported > 0) {
            $this->updateAffectedSnapshots($transactions);
        }
        
        echo json_encode([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ]);
    }
    
    private function updateAffectedSnapshots($transactions) {
        $snapshotController = new BalanceSnapshotController();
        $dates = [];
        
        // Collect unique dates
        foreach ($transactions as $transaction) {
            if (!empty($transaction['date'])) {
                $date = $this->parseDate($transaction['date']);
                if ($date && !in_array($date, $dates)) {
                    $dates[] = $date;
                }
            }
        }
        
        // Update snapshot for each affected date and all dates after
        foreach ($dates as $date) {
            $snapshotController->updateSnapshot($date);
            
            // Also update today's snapshot if the transaction is in the past
            if ($date < date('Y-m-d')) {
                $snapshotController->updateSnapshot(date('Y-m-d'));
            }
        }
    }
    
    private function detectDelimiter($line) {
        $delimiters = ["\t" => 0, ';' => 0, ',' => 0];
        
        foreach ($delimiters as $delimiter => $count) {
            $delimiters[$delimiter] = substr_count($line, $delimiter);
        }
        
        arsort($delimiters);
        return key($delimiters);
    }
    
    private function detectHeader($rows) {
        if (empty($rows)) return false;
        
        $firstRow = $rows[0];
        
        // Check if first row contains common header keywords
        $keywords = ['date', 'description', 'montant', 'amount', 'libelle', 'libellé', 'debit', 'credit', 'solde'];
        
        foreach ($firstRow as $cell) {
            $cellLower = strtolower($cell);
            foreach ($keywords as $keyword) {
                if (strpos($cellLower, $keyword) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    private function detectColumnMapping($headers, $rows) {
        $mapping = [
            'date' => null,
            'description' => null,
            'amount' => null
        ];
        
        // If we have headers, use them
        if (!empty($headers)) {
            foreach ($headers as $index => $header) {
                $headerLower = strtolower($header);
                
                if (strpos($headerLower, 'date') !== false) {
                    $mapping['date'] = $index;
                } elseif (strpos($headerLower, 'libelle') !== false || strpos($headerLower, 'libellé') !== false || strpos($headerLower, 'description') !== false) {
                    $mapping['description'] = $index;
                } elseif (strpos($headerLower, 'montant') !== false || strpos($headerLower, 'amount') !== false || strpos($headerLower, 'debit') !== false || strpos($headerLower, 'credit') !== false) {
                    $mapping['amount'] = $index;
                }
            }
        }
        
        // If no headers or mapping incomplete, try to detect from data
        if ($mapping['date'] === null || $mapping['description'] === null || $mapping['amount'] === null) {
            if (!empty($rows)) {
                $firstRow = $rows[0];
                
                foreach ($firstRow as $index => $cell) {
                    // Try to detect date format
                    if ($mapping['date'] === null && $this->parseDate($cell)) {
                        $mapping['date'] = $index;
                    }
                    // Try to detect amount (contains numbers and possibly currency symbols)
                    elseif ($mapping['amount'] === null && preg_match('/[\d\s,.-]+/', $cell) && $this->parseAmount($cell) !== null) {
                        $mapping['amount'] = $index;
                    }
                    // Description is usually the longest text field
                    elseif ($mapping['description'] === null && strlen($cell) > 10) {
                        $mapping['description'] = $index;
                    }
                }
            }
        }
        
        return $mapping;
    }
    
    private function parseDate($dateStr) {
        // Try various date formats
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }
        
        // Try strtotime as fallback
        $timestamp = strtotime($dateStr);
        if ($timestamp) {
            return date('Y-m-d', $timestamp);
        }
        
        return null;
    }
    
    private function parseAmount($amountStr) {
        // Remove currency symbols and spaces
        $cleaned = preg_replace('/[^\d,.-]/', '', $amountStr);
        
        // Replace comma with dot for decimal
        $cleaned = str_replace(',', '.', $cleaned);
        
        // Handle negative amounts
        $isNegative = strpos($amountStr, '-') !== false || strpos($amountStr, '(') !== false;
        
        $amount = floatval($cleaned);
        
        return $isNegative ? -abs($amount) : $amount;
    }
}
