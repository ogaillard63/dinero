<?php

namespace App\Controllers;

class BankController extends BaseController {
    public function index() {
        $stmt = $this->db->query("SELECT * FROM banks ORDER BY name");
        $banks = $stmt->fetchAll();
        
        // Get accounts for each bank with calculated current balance
        foreach ($banks as &$bank) {
            $stmt = $this->db->prepare("SELECT * FROM accounts WHERE bank_id = :id ORDER BY sort_order ASC, id ASC");
            $stmt->execute(['id' => $bank['id']]);
            $accounts = $stmt->fetchAll();
            
            // Calculate current balance and transaction count for each account
            foreach ($accounts as &$account) {
                // Get transaction sum
                $stmt = $this->db->prepare("SELECT SUM(amount) as total FROM transactions WHERE account_id = :id");
                $stmt->execute(['id' => $account['id']]);
                $result = $stmt->fetch();
                $transactions_total = $result['total'] ?? 0;
                $account['current_balance'] = $account['initial_balance'] + $transactions_total;
                
                // Get transaction count
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM transactions WHERE account_id = :id");
                $stmt->execute(['id' => $account['id']]);
                $result = $stmt->fetch();
                $account['transaction_count'] = $result['count'];
            }
            unset($account);
            
            $bank['accounts'] = $accounts;
        }
        unset($bank);

        $this->render('banks.twig', ['banks' => $banks]);
    }

    public function show($id) {
        $stmt = $this->db->prepare("SELECT * FROM banks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $bank = $stmt->fetch();

        if (!$bank) {
            header('Location: /banks');
            exit();
        }

        $stmt = $this->db->prepare("SELECT * FROM accounts WHERE bank_id = :id");
        $stmt->execute(['id' => $id]);
        $accounts = $stmt->fetchAll();

        $this->render('bank_details.twig', ['bank' => $bank, 'accounts' => $accounts]);
    }

    public function create() {
        $this->render('bank_form.twig', ['bank' => null]);
    }

    public function store() {
        $name = $_POST['name'] ?? '';

        if (empty($name)) {
            $this->render('bank_form.twig', [
                'bank' => null,
                'error' => 'Le nom de la banque est requis.'
            ]);
            return;
        }

        $stmt = $this->db->prepare("INSERT INTO banks (name) VALUES (:name)");
        $stmt->execute(['name' => $name]);

        header('Location: /banks');
        exit();
    }

    public function edit($id) {
        $stmt = $this->db->prepare("SELECT * FROM banks WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $bank = $stmt->fetch();

        if (!$bank) {
            header('Location: /banks');
            exit();
        }

        $this->render('bank_form.twig', ['bank' => $bank]);
    }

    public function update($id) {
        $name = $_POST['name'] ?? '';

        if (empty($name)) {
            $stmt = $this->db->prepare("SELECT * FROM banks WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $bank = $stmt->fetch();

            $this->render('bank_form.twig', [
                'bank' => $bank,
                'error' => 'Le nom de la banque est requis.'
            ]);
            return;
        }

        $stmt = $this->db->prepare("UPDATE banks SET name = :name WHERE id = :id");
        $stmt->execute(['name' => $name, 'id' => $id]);

        header('Location: /banks');
        exit();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM banks WHERE id = :id");
        $stmt->execute(['id' => $id]);

        header('Location: /banks');
        exit();
    }
}
