<?php

namespace App\Controllers;

class AccountController extends BaseController {
    // ... existing methods ...
    
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
