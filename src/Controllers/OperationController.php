<?php

namespace App\Controllers;

class OperationController extends BaseController {
    public function index() {
        // Get account_id from query string or use first account
        $selected_account_id = $_GET['account_id'] ?? null;
        
        // Get all accounts for the dropdown
        $stmt = $this->db->query("
            SELECT a.*, b.name as bank_name 
            FROM accounts a 
            JOIN banks b ON a.bank_id = b.id 
            ORDER BY b.name, a.sort_order ASC, a.id ASC
        ");
        $accounts = $stmt->fetchAll();
        
        // Calculate current balance for each account
        foreach ($accounts as &$account) {
            $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE account_id = :id");
            $stmt->execute(['id' => $account['id']]);
            $result = $stmt->fetch();
            $transactions_total = $result['total'] ?? 0;
            $account['current_balance'] = $account['initial_balance'] + $transactions_total;
        }
        
        // If no account selected, use the first one
        if (!$selected_account_id && !empty($accounts)) {
            $selected_account_id = $accounts[0]['id'];
        }
        
        $selected_account = null;
        $transactions = [];
        $total_transactions = 0;
        
        if ($selected_account_id) {
            // Get selected account details
            foreach ($accounts as $account) {
                if ($account['id'] == $selected_account_id) {
                    $selected_account = $account;
                    break;
                }
            }
            
            // Get total count of transactions for this account
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total FROM transactions 
                WHERE account_id = :id
            ");
            $stmt->execute(['id' => $selected_account_id]);
            $result = $stmt->fetch();
            $total_transactions = $result['total'] ?? 0;
            
            // Get transactions for selected account (Limit 50 for lazy loading)
            $stmt = $this->db->prepare("
                SELECT * FROM transactions 
                WHERE account_id = :id 
                ORDER BY date DESC, id DESC
                LIMIT 50
            ");
            $stmt->execute(['id' => $selected_account_id]);
            $transactions = $stmt->fetchAll();
        }
        
        $this->render('operations.twig', [
            'accounts' => $accounts,
            'selected_account' => $selected_account,
            'transactions' => $transactions,
            'total_transactions' => $total_transactions
        ]);
    }

    public function getTransactions() {
        header('Content-Type: application/json');
        
        $account_id = $_GET['account_id'] ?? null;
        $offset = (int)($_GET['offset'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 50);
        $search = $_GET['search'] ?? '';
        
        if (!$account_id) {
            echo json_encode(['error' => 'Missing account_id']);
            return;
        }
        
        try {
            $sql = "
                SELECT * FROM transactions 
                WHERE account_id = :id 
            ";
            
            if (!empty($search)) {
                $sql .= " AND description LIKE :search ";
            }
            
            $sql .= " ORDER BY date DESC, id DESC LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindValue(':id', $account_id);
            if (!empty($search)) {
                $stmt->bindValue(':search', '%' . $search . '%');
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            
            $transactions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode(['transactions' => $transactions]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }
}
