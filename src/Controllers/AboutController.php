<?php

namespace App\Controllers;

class AboutController extends BaseController {
    public function index() {
        $this->render('about.twig');
    }
}
