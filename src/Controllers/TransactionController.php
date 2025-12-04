<?php

namespace App\Controllers;

class TransactionController extends BaseController {
    public function create($account_id) {
        // Get account details
        $stmt = $this->db->prepare("
            SELECT a.*, b.name as bank_name 
            FROM accounts a 
            JOIN banks b ON a.bank_id = b.id 
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $account_id]);
        $account = $stmt->fetch();

        if (!$account) {
            header('Location: /admin/banks');
            exit();
        }

        $this->render('transaction_form.twig', [
            'transaction' => null,
            'account' => $account
        ]);
    }

    public function store($account_id) {
        $date = $_POST['date'] ?? '';
        $description = $_POST['description'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $type = $_POST['type'] ?? '';

        if (empty($date) || empty($description) || empty($amount)) {
            $stmt = $this->db->prepare("
                SELECT a.*, b.name as bank_name 
                FROM accounts a 
                JOIN banks b ON a.bank_id = b.id 
                WHERE a.id = :id
            ");
            $stmt->execute(['id' => $account_id]);
            $account = $stmt->fetch();

            $this->render('transaction_form.twig', [
                'transaction' => null,
                'account' => $account,
                'error' => 'Tous les champs sont requis.'
            ]);
            return;
        }

        // Convert amount to negative if it's a debit
        if ($type === 'debit' && $amount > 0) {
            $amount = -abs($amount);
        } elseif ($type === 'credit' && $amount < 0) {
            $amount = abs($amount);
        }

        $stmt = $this->db->prepare("
            INSERT INTO transactions (account_id, date, description, amount, type) 
            VALUES (:account_id, :date, :description, :amount, :type)
        ");
        $stmt->execute([
            'account_id' => $account_id,
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'type' => $type
        ]);

        header('Location: /admin/accounts/' . $account_id);
        exit();
    }

    public function edit($id) {
        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            header('Location: /admin/banks');
            exit();
        }

        // Get account details
        $stmt = $this->db->prepare("
            SELECT a.*, b.name as bank_name 
            FROM accounts a 
            JOIN banks b ON a.bank_id = b.id 
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $transaction['account_id']]);
        $account = $stmt->fetch();

        $this->render('transaction_form.twig', [
            'transaction' => $transaction,
            'account' => $account
        ]);
    }

    public function update($id) {
        $date = $_POST['date'] ?? '';
        $description = $_POST['description'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $type = $_POST['type'] ?? '';

        $stmt = $this->db->prepare("SELECT * FROM transactions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            header('Location: /admin/banks');
            exit();
        }

        if (empty($date) || empty($description) || empty($amount)) {
            $stmt = $this->db->prepare("
                SELECT a.*, b.name as bank_name 
                FROM accounts a 
                JOIN banks b ON a.bank_id = b.id 
                WHERE a.id = :id
            ");
            $stmt->execute(['id' => $transaction['account_id']]);
            $account = $stmt->fetch();

            $this->render('transaction_form.twig', [
                'transaction' => $transaction,
                'account' => $account,
                'error' => 'Tous les champs sont requis.'
            ]);
            return;
        }

        // Convert amount to negative if it's a debit
        if ($type === 'debit' && $amount > 0) {
            $amount = -abs($amount);
        } elseif ($type === 'credit' && $amount < 0) {
            $amount = abs($amount);
        }

        $stmt = $this->db->prepare("
            UPDATE transactions 
            SET date = :date, description = :description, amount = :amount, type = :type 
            WHERE id = :id
        ");
        $stmt->execute([
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'type' => $type,
            'id' => $id
        ]);

        header('Location: /admin/accounts/' . $transaction['account_id']);
        exit();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("SELECT account_id FROM transactions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            header('Location: /admin/banks');
            exit();
        }

        $stmt = $this->db->prepare("DELETE FROM transactions WHERE id = :id");
        $stmt->execute(['id' => $id]);

        header('Location: /admin/accounts/' . $transaction['account_id']);
        exit();
    }
}
