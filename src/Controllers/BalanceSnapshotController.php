<?php

namespace App\Controllers;

class BalanceSnapshotController extends BaseController {
    
    /**
     * Calculate and store daily balance snapshot
     */
    public function updateSnapshot($date = null) {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        
        // Calculate total balance for all accounts at this date
        $totalBalance = $this->calculateTotalBalanceAtDate($date);
        
        // Insert or update snapshot
        $stmt = $this->db->prepare("
            INSERT INTO balance_snapshots (snapshot_date, total_balance) 
            VALUES (:date, :balance)
            ON DUPLICATE KEY UPDATE total_balance = VALUES(total_balance)
        ");
        
        $stmt->execute([
            'date' => $date,
            'balance' => $totalBalance
        ]);
        
        return $totalBalance;
    }
    
    /**
     * Recalculate all snapshots from first transaction to today
     */
    public function rebuildAllSnapshots() {
        // Get date range
        $stmt = $this->db->query("SELECT MIN(date) as first_date FROM transactions");
        $result = $stmt->fetch();
        $firstDate = $result['first_date'] ?? date('Y-m-d');
        
        $currentDate = new \DateTime($firstDate);
        $endDate = new \DateTime();
        $count = 0;
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $this->updateSnapshot($dateStr);
            $currentDate->modify('+1 day');
            $count++;
        }
        
        return $count;
    }
    
    /**
     * Calculate total balance at a specific date
     */
    private function calculateTotalBalanceAtDate($date) {
        // Get all accounts
        $stmt = $this->db->query("SELECT id, initial_balance FROM accounts");
        $accounts = $stmt->fetchAll();
        
        $totalBalance = 0;
        
        foreach ($accounts as $account) {
            // Get sum of transactions up to this date for this account
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
            
            // Account balance at this date = initial + transactions up to that date
            $accountBalance = $account['initial_balance'] + $transactionsTotal;
            $totalBalance += $accountBalance;
        }
        
        return $totalBalance;
    }
}
