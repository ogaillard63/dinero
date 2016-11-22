<?php
/**
 * @project		Framework 1.0
 *
 * @author		Olivier Gaillard <olivier.gaillard@larep.com>
 * @version		1.0 du 01/01/2009
 * @lastmod		24/12/2009 14:51
 * @desc	   	Initialisation des variables
 */

// Infos de connexion base de données
//$config['db_name']		= "olgsoft";
//$config['db_hostname']	= "sql.free.fr";
//$config['db_username']	= "olgsoft";
//$config['db_password']	= "shiraz";


$config['db_name']		= "olganet1";
$config['db_hostname']	= "cl2-sql4";
$config['db_username']	= "olganet1";
$config['db_password']	= "aok719kc";

define(DEBUG, false);   // Debug du site
define(APP_NAME, "myPortfolio");   // Nom de l'application
define(APP_VERS, "1.0");  // Version de l'application

define(EURO, chr(128));

$today			= date("Y-m-d"); //date("Y-m-d H:i");

?>