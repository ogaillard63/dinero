<?php
/**
 * Front Controller for Shared Hosting
 * Simple redirect to public/index.php
 */

// Change to public directory
chdir(__DIR__ . '/public');

// Set correct paths for the router
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';

// Load the real front controller
require __DIR__ . '/public/index.php';
