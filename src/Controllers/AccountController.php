<?php

namespace App\Controllers;

class AccountController extends BaseController {
    public function show($id) {
        // Get account details
        $stmt = $this->db->prepare("
            SELECT a.*, b.name as bank_name 
            FROM accounts a 
            JOIN banks b ON a.bank_id = b.id 
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $account = $stmt->fetch();

        if (!$account) {
            header('Location: /banks');
            exit();
        }

        // Calculate current balance: initial_balance + sum of transactions
        $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE account_id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        $transactions_total = $result['total'] ?? 0;
        $account['current_balance'] = $account['initial_balance'] + $transactions_total;

        // Get transactions
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE account_id = :id ORDER BY date DESC LIMIT 50");
        $stmt->execute(['id' => $id]);
        $transactions = $stmt->fetchAll();

        $this->render('account.twig', [
            'account' => $account,
            'transactions' => $transactions
        ]);
    }

    public function create() {
        // Get all banks for the dropdown
        $stmt = $this->db->query("SELECT * FROM banks ORDER BY name");
        $banks = $stmt->fetchAll();

        // Pre-select bank if bank_id is in query string
        $preselected_bank_id = $_GET['bank_id'] ?? null;

        $this->render('account_form.twig', [
            'account' => null,
            'banks' => $banks,
            'preselected_bank_id' => $preselected_bank_id
        ]);
    }

    public function store() {
        $bank_id = $_POST['bank_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $number = $_POST['number'] ?? '';
        $initial_balance = $_POST['initial_balance'] ?? 0;
        $currency = $_POST['currency'] ?? 'EUR';
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($bank_id) || empty($name)) {
            $stmt = $this->db->query("SELECT * FROM banks ORDER BY name");
            $banks = $stmt->fetchAll();

            $this->render('account_form.twig', [
                'account' => null,
                'banks' => $banks,
                'error' => 'La banque et le nom du compte sont requis.'
            ]);
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO accounts (bank_id, name, number, initial_balance, currency, is_active) 
            VALUES (:bank_id, :name, :number, :initial_balance, :currency, :is_active)
        ");
        $stmt->execute([
            'bank_id' => $bank_id,
            'name' => $name,
            'number' => $number,
            'initial_balance' => $initial_balance,
            'currency' => $currency,
            'is_active' => $is_active
        ]);

        header('Location: /banks');
        exit();
    }

    public function edit($id) {
        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $account = $stmt->fetch();

        if (!$account) {
            header('Location: /banks');
            exit();
        }

        $stmt = $this->db->query("SELECT * FROM banks ORDER BY name");
        $banks = $stmt->fetchAll();

        $this->render('account_form.twig', [
            'account' => $account,
            'banks' => $banks
        ]);
    }

    public function update($id) {
        $bank_id = $_POST['bank_id'] ?? '';
        $name = $_POST['name'] ?? '';
        $number = $_POST['number'] ?? '';
        $initial_balance = $_POST['initial_balance'] ?? 0;
        $currency = $_POST['currency'] ?? 'EUR';
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($bank_id) || empty($name)) {
            $stmt = $this->db->prepare("SELECT * FROM accounts WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $account = $stmt->fetch();

            $stmt = $this->db->query("SELECT * FROM banks ORDER BY name");
            $banks = $stmt->fetchAll();

            $this->render('account_form.twig', [
                'account' => $account,
                'banks' => $banks,
                'error' => 'La banque et le nom du compte sont requis.'
            ]);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE accounts 
            SET bank_id = :bank_id, name = :name, number = :number, initial_balance = :initial_balance, currency = :currency, is_active = :is_active 
            WHERE id = :id
        ");
        $stmt->execute([
            'bank_id' => $bank_id,
            'name' => $name,
            'number' => $number,
            'initial_balance' => $initial_balance,
            'currency' => $currency,
            'is_active' => $is_active,
            'id' => $id
        ]);

        header('Location: /accounts/' . $id);
        exit();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM accounts WHERE id = :id");
        $stmt->execute(['id' => $id]);

        header('Location: /banks');
        exit();
    }

    public function updateOrder() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['accounts']) || !is_array($data['accounts'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            return;
        }
        
        foreach ($data['accounts'] as $index => $accountData) {
            $stmt = $this->db->prepare("UPDATE accounts SET sort_order = :order WHERE id = :id");
            $stmt->execute([
                'order' => $index + 1,
                'id' => $accountData['id']
            ]);
        }
        
        echo json_encode(['success' => true]);
    }
}
