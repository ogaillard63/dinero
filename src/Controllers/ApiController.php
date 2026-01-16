<?php

namespace App\Controllers;

class ApiController extends BaseController {
    
    /**
     * Get all accounts with balances
     */
    public function getAccounts() {
        header('Content-Type: application/json');
        
        try {
            $stmt = $this->db->query("
                SELECT 
                    a.*,
                    b.name as bank_name,
                    (SELECT SUM(amount) FROM transactions WHERE account_id = a.id) as transactions_total
                FROM accounts a
                JOIN banks b ON a.bank_id = b.id
                WHERE a.is_active = 1
                ORDER BY b.name, a.name
            ");
            
            $accounts = $stmt->fetchAll();
            
            // Calculate current balances
            foreach ($accounts as &$account) {
                $account['current_balance'] = $account['initial_balance'] + ($account['transactions_total'] ?? 0);
                unset($account['transactions_total']);
            }
            unset($account);
            
            echo json_encode([
                'success' => true,
                'data' => $accounts,
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get recent operations
     */
    public function getOperations() {
        header('Content-Type: application/json');
        
        try {
            $limit = $_GET['limit'] ?? 100;
            $offset = $_GET['offset'] ?? 0;
            
            $stmt = $this->db->prepare("
                SELECT 
                    t.*,
                    a.name as account_name,
                    b.name as bank_name
                FROM transactions t
                JOIN accounts a ON t.account_id = a.id
                JOIN banks b ON a.bank_id = b.id
                ORDER BY t.date DESC, t.id DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
            $stmt->execute();
            
            $operations = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $operations,
                'limit' => (int)$limit,
                'offset' => (int)$offset,
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get all banks with accounts
     */
    public function getBanks() {
        header('Content-Type: application/json');
        
        try {
            $stmt = $this->db->query("
                SELECT * FROM banks ORDER BY name
            ");
            
            $banks = $stmt->fetchAll();
            
            // Get accounts for each bank
            foreach ($banks as &$bank) {
                $stmt = $this->db->prepare("
                    SELECT 
                        a.*,
                        (SELECT SUM(amount) FROM transactions WHERE account_id = a.id) as transactions_total
                    FROM accounts a
                    WHERE a.bank_id = :bank_id
                    ORDER BY a.sort_order, a.name
                ");
                $stmt->execute(['bank_id' => $bank['id']]);
                $accounts = $stmt->fetchAll();
                
                // Calculate current balances
                foreach ($accounts as &$account) {
                    $account['current_balance'] = $account['initial_balance'] + ($account['transactions_total'] ?? 0);
                    unset($account['transactions_total']);
                }
                unset($account);
                
                $bank['accounts'] = $accounts;
            }
            unset($bank);
            
            echo json_encode([
                'success' => true,
                'data' => $banks,
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get dashboard summary data
     */
    public function getDashboard() {
        header('Content-Type: application/json');
        
        try {
            // Total balance across all accounts
            $stmt = $this->db->query("
                SELECT 
                    SUM(a.initial_balance + COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id), 0)) as total_balance
                FROM accounts a
                WHERE a.is_active = 1
            ");
            $result = $stmt->fetch();
            $totalBalance = $result['total_balance'] ?? 0;
            
            // Count active accounts
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM accounts WHERE is_active = 1");
            $accountsCount = $stmt->fetch()['count'];
            
            // Recent transactions count
            $stmt = $this->db->query("
                SELECT COUNT(*) as count 
                FROM transactions 
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $recentTransactions = $stmt->fetch()['count'];
            
            // Monthly income and expenses
            $stmt = $this->db->query("
                SELECT 
                    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income,
                    SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expenses
                FROM transactions
                WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ");
            $monthly = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'total_balance' => (float)$totalBalance,
                    'accounts_count' => (int)$accountsCount,
                    'recent_transactions' => (int)$recentTransactions,
                    'monthly_income' => (float)($monthly['income'] ?? 0),
                    'monthly_expenses' => (float)($monthly['expenses'] ?? 0)
                ],
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Full sync endpoint - returns all data
     */
    public function sync() {
        header('Content-Type: application/json');
        
        try {
            // This would normally call the other methods, but for efficiency we'll do it in one go
            $data = [
                'accounts' => $this->getAccountsData(),
                'operations' => $this->getOperationsData(50, 0),
                'banks' => $this->getBanksData(),
                'dashboard' => $this->getDashboardData()
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'timestamp' => date('c')
            ]);
            
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    // Helper methods that return data without JSON encoding
    private function getAccountsData() {
        $stmt = $this->db->query("
            SELECT 
                a.*,
                b.name as bank_name,
                (SELECT SUM(amount) FROM transactions WHERE account_id = a.id) as transactions_total
            FROM accounts a
            JOIN banks b ON a.bank_id = b.id
            WHERE a.is_active = 1
            ORDER BY b.name, a.name
        ");
        
        $accounts = $stmt->fetchAll();
        
        foreach ($accounts as &$account) {
            $account['current_balance'] = $account['initial_balance'] + ($account['transactions_total'] ?? 0);
            unset($account['transactions_total']);
        }
        unset($account);
        
        return $accounts;
    }
    
    private function getOperationsData($limit = 100, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT 
                t.*,
                a.name as account_name,
                b.name as bank_name
            FROM transactions t
            JOIN accounts a ON t.account_id = a.id
            JOIN banks b ON a.bank_id = b.id
            ORDER BY t.date DESC, t.id DESC
            LIMIT :limit OFFSET :offset
        ");
        
        $stmt->bindValue(':limit', (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    private function getBanksData() {
        $stmt = $this->db->query("SELECT * FROM banks ORDER BY name");
        $banks = $stmt->fetchAll();
        
        foreach ($banks as &$bank) {
            $stmt = $this->db->prepare("
                SELECT 
                    a.*,
                    (SELECT SUM(amount) FROM transactions WHERE account_id = a.id) as transactions_total
                FROM accounts a
                WHERE a.bank_id = :bank_id
                ORDER BY a.sort_order, a.name
            ");
            $stmt->execute(['bank_id' => $bank['id']]);
            $accounts = $stmt->fetchAll();
            
            foreach ($accounts as &$account) {
                $account['current_balance'] = $account['initial_balance'] + ($account['transactions_total'] ?? 0);
                unset($account['transactions_total']);
            }
            unset($account);
            
            $bank['accounts'] = $accounts;
        }
        unset($bank);
        
        return $banks;
    }
    
    private function getDashboardData() {
        $stmt = $this->db->query("
            SELECT 
                SUM(a.initial_balance + COALESCE((SELECT SUM(amount) FROM transactions WHERE account_id = a.id), 0)) as total_balance
            FROM accounts a
            WHERE a.is_active = 1
        ");
        $result = $stmt->fetch();
        $totalBalance = $result['total_balance'] ?? 0;
        
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM accounts WHERE is_active = 1");
        $accountsCount = $stmt->fetch()['count'];
        
        $stmt = $this->db->query("
            SELECT COUNT(*) as count 
            FROM transactions 
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $recentTransactions = $stmt->fetch()['count'];
        
        $stmt = $this->db->query("
            SELECT 
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as income,
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as expenses
            FROM transactions
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $monthly = $stmt->fetch();
        
        return [
            'total_balance' => (float)$totalBalance,
            'accounts_count' => (int)$accountsCount,
            'recent_transactions' => (int)$recentTransactions,
            'monthly_income' => (float)($monthly['income'] ?? 0),
            'monthly_expenses' => (float)($monthly['expenses'] ?? 0)
        ];
    }
}
