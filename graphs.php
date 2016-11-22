<?php


/**
 * @project		Framework 1.0
 *
 * @author		Olivier Gaillard <olivier.gaillard@larep.com>
 * @version		1.0 du 29/01/2010
 * @lastmod		24/12/2009 15:36
 * @desc	   	Etats graphiques
 */
require_once( "libs/prepend.php" );
// Renvoi vers la page d'identification si la session est vide
if (!isset($_SESSION[APP_NAME]["id_utilisateur"])) redirection("identification.php");
//$smarty->assign("menu_select", 2); // Id du menu selectionné


//SELECT DATE_FORMAT(date, '%Y-%m') AS periode, sum(montant)FROM `dinero_transactions`  WHERE montant>0 AND compte_id=10 GROUP BY periode ORDER BY periode


// Déclaration des variables
$action				= '';
$id					= '';
$compte_id			= '';
$tbl_operations   = $config['table_prefix']."operations";
$tbl_rubriques      = $config['table_prefix']."rubriques";
$tbl_comptes        = $config['table_prefix']."comptes";

// Récupération des variables
$action				= get_input('action','both');
$id					= get_input('id','both');
$compte_id			= get_input('compte_id','both');

switch ($action)
	{
	case "insert" :
	break;

	default :
		$smarty->assign("titre", "Compte en banque");
		$smarty->assign("form",	array("nom" => "Graphique de répartition des dépenses"));
		$smarty->assign("comptes", $db->getAll("SELECT * FROM $tbl_comptes WHERE etat ='1' ORDER BY ordre"));
		$sql = "SELECT ABS(sum(montant)) AS total, librub  FROM $tbl_operations AS t, dinero_rubriques AS r
				WHERE t.rub_id = r.id_rub 
				AND compte_id = 10 
				AND type = 'D' 
				AND librub <> 'Epargne' 
				GROUP BY librub";
		$smarty->assign("data", $db->getAll($sql));
		$smarty->assign("content", "graphiques.tpl.html");								  
		$smarty->display("main.tpl.html");
	break;
	}
require_once("libs/append.php");
?>