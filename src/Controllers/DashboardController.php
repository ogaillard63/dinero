<?php

namespace App\Controllers;

class DashboardController extends BaseController {
    public function index() {
        // Get all accounts
        $stmt = $this->db->query("
            SELECT a.*, b.name as bank_name 
            FROM accounts a 
            JOIN banks b ON a.bank_id = b.id
        ");
        $accounts = $stmt->fetchAll();
        
        // Calculate current balance for each account and total
        $totalBalance = 0;
        foreach ($accounts as &$account) {
            $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE account_id = :id");
            $stmt->execute(['id' => $account['id']]);
            $result = $stmt->fetch();
            $transactions_total = $result['total'] ?? 0;
            $account['current_balance'] = $account['initial_balance'] + $transactions_total;
            $totalBalance += $account['current_balance'];
        }
        
        // Get last transaction date
        $stmt = $this->db->query("SELECT MAX(date) as last_date FROM transactions");
        $result = $stmt->fetch();
        $lastTransactionDate = $result['last_date'] ?? null;
        
        // Get monthly balance evolution (last 12 months)
        $monthlyData = $this->getMonthlyBalanceEvolution();
        
        // Calculate balance variation over 1 month
        $oneMonthAgo = date('Y-m-d', strtotime('-30 days'));
        $balanceOneMonthAgo = $this->calculateTotalBalanceAtDate($oneMonthAgo);
        $balanceVariation = $totalBalance - $balanceOneMonthAgo;
        $balanceVariationPercent = $balanceOneMonthAgo != 0 ? ($balanceVariation / abs($balanceOneMonthAgo)) * 100 : 0;
        
        $this->render('dashboard.twig', [
            'totalBalance' => $totalBalance,
            'accounts' => $accounts,
            'lastTransactionDate' => $lastTransactionDate,
            'monthlyData' => $monthlyData,
            'balanceVariation' => $balanceVariation,
            'balanceVariationPercent' => $balanceVariationPercent
        ]);
    }
    
    public function getAccountBalanceData() {
        header('Content-Type: application/json');
        
        $accountId = $_GET['account_id'] ?? null;
        
        if (!$accountId) {
            echo json_encode(['error' => 'Missing account_id']);
            return;
        }
        
        // Get account info
        $stmt = $this->db->prepare("SELECT id, name, initial_balance FROM accounts WHERE id = :id");
        $stmt->execute(['id' => $accountId]);
        $account = $stmt->fetch();
        
        if (!$account) {
            echo json_encode(['error' => 'Account not found']);
            return;
        }
        
        // Get daily balance for this account over last year
        $stmt = $this->db->query("
            SELECT snapshot_date 
            FROM balance_snapshots 
            WHERE snapshot_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
            ORDER BY snapshot_date ASC
        ");
        $dates = $stmt->fetchAll();
        
        $labels = [];
        $balances = [];
        
        foreach ($dates as $dateRow) {
            $date = $dateRow['snapshot_date'];
            
            // Calculate account balance at this date
            $stmt = $this->db->prepare("
                SELECT SUM(amount) as total 
                FROM transactions 
                WHERE account_id = :account_id 
                AND date <= :date
            ");
            $stmt->execute([
                'account_id' => $accountId,
                'date' => $date
            ]);
            $result = $stmt->fetch();
            $transactionsTotal = $result['total'] ?? 0;
            
            $accountBalance = $account['initial_balance'] + $transactionsTotal;
            
            $dateObj = new \DateTime($date);
            $labels[] = $dateObj->format('d/m/Y');
            $balances[] = round($accountBalance, 2);
        }
        
        echo json_encode([
            'labels' => $labels,
            'balances' => $balances,
            'accountName' => $account['name']
        ]);
    }
    
    public function getBalanceData() {
        header('Content-Type: application/json');
        
        $startDate = $_GET['start'] ?? null;
        $endDate = $_GET['end'] ?? null;
        
        if (!$startDate || !$endDate) {
            echo json_encode(['error' => 'Missing parameters']);
            return;
        }
        
        // Calculate the number of days in the range
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $daysDiff = $start->diff($end)->days;
        
        $labels = [];
        $balances = [];
        
        if ($daysDiff <= 90) {
            // Less than 3 months: daily data
            $stmt = $this->db->prepare("
                SELECT snapshot_date, total_balance 
                FROM balance_snapshots 
                WHERE snapshot_date BETWEEN :start AND :end
                ORDER BY snapshot_date ASC
            ");
            $stmt->execute(['start' => $startDate, 'end' => $endDate]);
            $snapshots = $stmt->fetchAll();
            
            foreach ($snapshots as $snapshot) {
                $date = new \DateTime($snapshot['snapshot_date']);
                $labels[] = $date->format('d/m/Y');
                $balances[] = round($snapshot['total_balance'], 2);
            }
        } else if ($daysDiff <= 365) {
            // 3 months to 1 year: weekly data (last day of each week)
            $stmt = $this->db->prepare("
                SELECT snapshot_date, total_balance 
                FROM balance_snapshots 
                WHERE snapshot_date BETWEEN :start AND :end
                AND DAYOFWEEK(snapshot_date) = 1
                ORDER BY snapshot_date ASC
            ");
            $stmt->execute(['start' => $startDate, 'end' => $endDate]);
            $snapshots = $stmt->fetchAll();
            
            foreach ($snapshots as $snapshot) {
                $date = new \DateTime($snapshot['snapshot_date']);
                $labels[] = $date->format('d/m/Y');
                $balances[] = round($snapshot['total_balance'], 2);
            }
        } else {
            // More than 1 year: monthly data (last day of each month)
            $current = clone $start;
            while ($current <= $end) {
                $monthEnd = $current->format('Y-m-t');
                
                $stmt = $this->db->prepare("
                    SELECT total_balance 
                    FROM balance_snapshots 
                    WHERE snapshot_date = :date
                ");
                $stmt->execute(['date' => $monthEnd]);
                $result = $stmt->fetch();
                
                if ($result) {
                    $labels[] = $current->format('M Y');
                    $balances[] = round($result['total_balance'], 2);
                }
                
                $current->modify('+1 month');
            }
        }
        
        echo json_encode([
            'labels' => $labels,
            'balances' => $balances
        ]);
    }
    
    private function getMonthlyBalanceEvolution() {
        $labels = [];
        $balances = [];
        
        // Get daily snapshots for the last year
        $stmt = $this->db->query("
            SELECT snapshot_date, total_balance 
            FROM balance_snapshots 
            WHERE snapshot_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
            ORDER BY snapshot_date ASC
        ");
        $snapshots = $stmt->fetchAll();
        
        if (empty($snapshots)) {
            // Fallback to monthly calculation if no snapshots
            $currentDate = new \DateTime();
            for ($i = 11; $i >= 0; $i--) {
                $date = clone $currentDate;
                $date->modify("-$i months");
                $labels[] = $date->format('M Y');
                $monthEndDate = $date->format('Y-m-t');
                $balances[] = $this->calculateTotalBalanceAtDate($monthEndDate);
            }
        } else {
            // Use daily snapshots for high resolution
            foreach ($snapshots as $snapshot) {
                $date = new \DateTime($snapshot['snapshot_date']);
                $labels[] = $date->format('d/m/Y');
                $balances[] = round($snapshot['total_balance'], 2);
            }
        }
        
        return [
            'labels' => $labels,
            'balances' => $balances
        ];
    }
    
    private function calculateTotalBalanceAtDate($date) {
        $stmt = $this->db->query("SELECT id, initial_balance FROM accounts");
        $accounts = $stmt->fetchAll();
        
        $totalBalance = 0;
        
        foreach ($accounts as $account) {
            $stmt = $this->db->prepare("
                SELECT SUM(amount) as total 
                FROM transactions 
                WHERE account_id = :account_id 
                AND date <= :date
            ");
            $stmt->execute([
                'account_id' => $account['id'],
                'date' => $date
            ]);
            $result = $stmt->fetch();
            $transactionsTotal = $result['total'] ?? 0;
            
            $accountBalance = $account['initial_balance'] + $transactionsTotal;
            $totalBalance += $accountBalance;
        }
        
        return round($totalBalance, 2);
    }
}
