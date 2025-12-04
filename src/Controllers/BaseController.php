<?php

namespace App\Controllers;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class BaseController {
    protected $twig;
    protected $db;

    public function __construct() {
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader, [
            'cache' => false, // Set to __DIR__ . '/../../cache' for production
            'debug' => true,
        ]);
        
        // Add global session variable to twig
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->twig->addGlobal('session', $_SESSION);

        require_once __DIR__ . '/../db.php';
        $this->db = getDbConnection();
    }

    protected function render($template, $data = []) {
        echo $this->twig->render($template, $data);
    }
}
