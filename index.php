<?php
/**
 * Front Controller Redirect
 * Redirige toutes les requêtes vers public/index.php
 * Utilisé pour les hébergements où la racine web est au-dessus de public/
 */

// Obtenir l'URI demandée
$request_uri = $_SERVER['REQUEST_URI'];

// Supprimer le query string
$path = parse_url($request_uri, PHP_URL_PATH);

// Si c'est déjà dans public/, ne pas rediriger
if (strpos($path, '/public/') === 0) {
    // Charger le fichier depuis public/
    $file = __DIR__ . $request_uri;
    if (file_exists($file) && is_file($file)) {
        // Servir le fichier statique
        $mime = mime_content_type($file);
        header('Content-Type: ' . $mime);
        readfile($file);
        exit;
    }
}

// Changer le répertoire de travail vers public/
chdir(__DIR__ . '/public');

// Définir $_SERVER pour que le routeur fonctionne correctement
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';

// Charger le front controller
require __DIR__ . '/public/index.php';
