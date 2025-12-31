<?php

namespace App\Controllers;

class AuthController extends BaseController {
    public function showLogin() {
        $this->render('login.twig');
    }

    public function login() {
        // Pour débloquer si nécessaire pendant les tests, vous pouvez vider la session
        // session_destroy(); session_start(); 

        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
        }

        $maxAttempts = 10; // Augmenté à 10 comme demandé

        if ($_SESSION['login_attempts'] >= $maxAttempts) {
            $this->render('login.twig', ['error' => "Compte bloqué après $maxAttempts tentatives. Veuillez contacter l'administrateur."]);
            return;
        }

        $pin = $_POST['pin'] ?? '';
        $appPassword = $_ENV['APP_PASSWORD'] ?? '123456';

        if ($pin === $appPassword) {
            $_SESSION['user_id'] = 1; 
            $_SESSION['username'] = 'Admin';
            $_SESSION['login_attempts'] = 0;
            header('Location: /dashboard');
            exit();
        } else {
            $_SESSION['login_attempts']++;
            $remaining = $maxAttempts - $_SESSION['login_attempts'];
            if ($remaining > 0) {
                $this->render('login.twig', ['error' => "Code incorrect. Plus que $remaining tentative(s)."]);
            } else {
                $this->render('login.twig', ['error' => "Compte bloqué après $maxAttempts tentatives."]);
            }
        }
    }



    public function logout() {
        session_destroy();
        header('Location: /login');
        exit();
    }
}
