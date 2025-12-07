<?php

namespace App\Controllers;

class AboutController extends BaseController {
    
    private function getVersion() {
        $swFile = __DIR__ . '/../../sw.js';
        if (!file_exists($swFile)) {
            return '1.0.0';
        }
        
        $content = file_get_contents($swFile);
        
        // Extraire la version depuis CACHE_NAME = 'dinero-vX.X.X'
        if (preg_match("/CACHE_NAME\s*=\s*'dinero-v([\d.]+)'/", $content, $matches)) {
            return $matches[1];
        }
        
        return '1.0.0';
    }
    
    private function getBuildDate() {
        $swFile = __DIR__ . '/../../sw.js';
        if (file_exists($swFile)) {
            return date('d/m/Y', filemtime($swFile));
        }
        return date('d/m/Y');
    }
    
    public function index() {
        $this->render('about.twig', [
            'version' => $this->getVersion(),
            'buildDate' => $this->getBuildDate()
        ]);
    }
}
