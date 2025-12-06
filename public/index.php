<?php

// Start session at the very beginning
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Controllers/BaseController.php';
require_once __DIR__ . '/../src/Controllers/AboutController.php';
require_once __DIR__ . '/../src/Controllers/OperationController.php';

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\BankController;
use App\Controllers\AccountController;
use App\Controllers\TransactionController;
use App\Controllers\OperationController;
use App\Controllers\ImportController;
use App\Controllers\MaintenanceController;
use App\Controllers\BalanceSnapshotController;
use App\Controllers\AboutController;

$router = new \Bramus\Router\Router();

// Auth Middleware
$router->before('GET|POST', '/.*', function() {
    // List of public routes that don't require authentication
    $publicRoutes = ['/login', '/logout'];
    
    $currentRoute = $_SERVER['REQUEST_URI'];
    // Remove query string if present
    if (($pos = strpos($currentRoute, '?')) !== false) {
        $currentRoute = substr($currentRoute, 0, $pos);
    }
    
    if (!in_array($currentRoute, $publicRoutes) && !isset($_SESSION['user_id'])) {
        header('Location: /login');
        exit();
    }
});

// Routes
$router->get('/', function() {
    header('Location: /dashboard');
    exit();
});


$router->get('/about', 'App\Controllers\AboutController@index');

$router->get('/login', 'App\Controllers\AuthController@showLogin');
$router->post('/login', 'App\Controllers\AuthController@login');
$router->get('/logout', 'App\Controllers\AuthController@logout');

// Dashboard
$router->get('/dashboard', 'App\Controllers\DashboardController@index');
$router->get('/api/account-balance', 'App\Controllers\DashboardController@getAccountBalanceData');

// Banks CRUD
$router->get('/banks', 'App\Controllers\BankController@index');
$router->get('/banks/create', 'App\Controllers\BankController@create');
$router->post('/banks/create', 'App\Controllers\BankController@store');
$router->get('/banks/(\d+)/edit', 'App\Controllers\BankController@edit');
$router->post('/banks/(\d+)/edit', 'App\Controllers\BankController@update');
$router->post('/banks/(\d+)/delete', 'App\Controllers\BankController@delete');
$router->get('/banks/(\d+)', 'App\Controllers\BankController@show');

// Accounts CRUD
$router->get('/accounts/create', 'App\Controllers\AccountController@create');
$router->post('/accounts/create', 'App\Controllers\AccountController@store');
$router->get('/accounts/(\d+)', 'App\Controllers\AccountController@show');
$router->get('/accounts/(\d+)/edit', 'App\Controllers\AccountController@edit');
$router->post('/accounts/(\d+)/edit', 'App\Controllers\AccountController@update');
$router->post('/accounts/(\d+)/delete', 'App\Controllers\AccountController@delete');
$router->post('/accounts/update-order', 'App\Controllers\AccountController@updateOrder');

// Transactions CRUD
$router->get('/accounts/(\d+)/transactions/create', 'App\Controllers\TransactionController@create');
$router->post('/accounts/(\d+)/transactions/create', 'App\Controllers\TransactionController@store');
$router->get('/transactions/(\d+)/edit', 'App\Controllers\TransactionController@edit');
$router->post('/transactions/(\d+)/edit', 'App\Controllers\TransactionController@update');
$router->post('/transactions/(\d+)/delete', 'App\Controllers\TransactionController@delete');

// Operations page
$router->get('/operations', 'App\Controllers\OperationController@index');
$router->get('/api/transactions', function() {
    $controller = new \App\Controllers\OperationController();
    $controller->getTransactions();
});

$router->get('/import', 'App\Controllers\ImportController@index');
$router->post('/import/parse', 'App\Controllers\ImportController@parse');
$router->post('/import/import', 'App\Controllers\ImportController@import');

// Maintenance
$router->get('/maintenance', 'App\Controllers\MaintenanceController@index');
$router->post('/maintenance/backup/create', 'App\Controllers\MaintenanceController@createBackup');
$router->post('/maintenance/backup/restore', 'App\Controllers\MaintenanceController@restoreBackup');
$router->get('/maintenance/backup/download/(.+)', 'App\Controllers\MaintenanceController@downloadBackup');
$router->post('/maintenance/backup/delete', 'App\Controllers\MaintenanceController@deleteBackup');
$router->post('/maintenance/snapshots/rebuild', 'App\Controllers\MaintenanceController@rebuildSnapshots');



$router->run();
