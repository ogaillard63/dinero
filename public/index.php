<?php

// Start session at the very beginning
session_start();

require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\BankController;
use App\Controllers\AccountController;
use App\Controllers\ImportController;

$router = new \Bramus\Router\Router();

// Auth Middleware
$router->before('GET|POST', '/admin/.*', function() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit();
    }
});

// Routes
$router->get('/', function() {
    header('Location: /admin/dashboard');
    exit();
});

$router->get('/login', 'App\Controllers\AuthController@showLogin');
$router->post('/login', 'App\Controllers\AuthController@login');
$router->get('/logout', 'App\Controllers\AuthController@logout');

$router->mount('/admin', function() use ($router) {
    $router->get('/dashboard', 'App\Controllers\DashboardController@index');
    
    $router->get('/banks', 'App\Controllers\BankController@index');
    $router->get('/banks/(\d+)', 'App\Controllers\BankController@show');
    $router->get('/accounts/(\d+)', 'App\Controllers\AccountController@show');
    
    $router->get('/import', 'App\Controllers\ImportController@index');
    $router->post('/import', 'App\Controllers\ImportController@upload');
});

$router->run();
