<?php

namespace App\Controllers;

class AuthController extends BaseController {
    public function showLogin() {
        $this->render('login.twig');
    }

    public function login() {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: /dashboard');
            exit();
        } else {
            $this->render('login.twig', ['error' => 'Identifiants invalides']);
        }
    }

    public function logout() {
        session_destroy();
        header('Location: /login');
        exit();
    }
}
