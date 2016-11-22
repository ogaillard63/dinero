<?php

/**

 * @project		Framework 1.0

 *

 * @author		Olivier Gaillard <olivier.gaillard@larep.com>

 * @version		1.0 du 01/01/2009

 * @lastmod		21/02/2009 13:05

 * @desc	   	Initialisation des ressources 

 */

($_SERVER['SERVER_NAME'] == "localhost") ?	require_once("libs/config.dev.php") : require_once("libs/config.prod.php");



setlocale(LC_ALL, 'fr_FR');

session_start();



define('DEBUG', false);   			// Debug du site

define('APP_NAME', $config['application']);   	// Nom de l'application

$today			= date("Y-m-d");        //date("Y-m-d H:i");



require_once("libs/utils.inc.php");			 // Fonctions diverses



if (DEBUG) {

	error_reporting(E_ALL); 

	set_error_handler("myErrorHandler");

}

// Connexion à la base de données

//echo $config['db_name']." / ".$config['db_hostname']." / ".$config['db_username']." / ".$config['db_password'];

require_once("libs/mysql.class.php"); // 

$db = new mySQLdb ($config['db_name'], $config['db_hostname'], $config['db_username'], $config['db_password']);

// Initialisation du gestionnaire de Templates

require_once("libs/smarty/Smarty.class.php"); // Templates Smarty

$smarty = new Smarty;

$smarty->template_dir = "tpl/".$config['template'];  // Templates

$smarty->compile_dir  = "tpl_cache";

//$smarty->register_modifier('addslashes', 'addslashes');

//$smarty->compile_check = true;

//$smarty->debugging = true;

$smarty->force_compile = true;

//$smarty->caching = true;



$smarty->assign("tpl", "tpl/".$config['template']);



// Infos de session

if (isset($_SESSION[APP_NAME]))

    $smarty->assign("session", $_SESSION[APP_NAME]);

if (isset($_SESSION[APP_NAME]["debug"])) unset($_SESSION[APP_NAME]["debug"]);

if (isset($_SESSION[APP_NAME]["message"])) unset($_SESSION[APP_NAME]["message"]);

if (isset($_SESSION[APP_NAME]["erreur"])) unset($_SESSION[APP_NAME]["erreur"]);



if (isset($_SESSION[APP_NAME]["nom"])) $smarty->assign("nom", $_SESSION[APP_NAME]["nom"]);

if (isset($_SESSION[APP_NAME]["prenom"])) $smarty->assign("prenom", $_SESSION[APP_NAME]["prenom"]);



$smarty->assign("application", $config['application']);

$smarty->assign("version", $config['version']);

$smarty->assign("copyright", $config['copyright']);



?>