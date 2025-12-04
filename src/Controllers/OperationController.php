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
        
        if ($selected_account_id) {
            // Get selected account details
            foreach ($accounts as $account) {
                if ($account['id'] == $selected_account_id) {
                    $selected_account = $account;
                    break;
                }
            }
            
            // Get transactions for selected account
            $stmt = $this->db->prepare("
                SELECT * FROM transactions 
                WHERE account_id = :id 
                ORDER BY date DESC, id DESC
            ");
            $stmt->execute(['id' => $selected_account_id]);
            $transactions = $stmt->fetchAll();
        }
        
        $this->render('operations.twig', [
            'accounts' => $accounts,
            'selected_account' => $selected_account,
            'transactions' => $transactions
        ]);
    }
}
