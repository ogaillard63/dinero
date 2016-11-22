<?php
/**
 * @project		Framework 1.0
 *
 * @author		Olivier Gaillard <olivier.gaillard@larep.com>
 * @version		1.0 du 29/01/2010
 * @lastmod		24/12/2009 15:36
 * @desc	   	Gestion des comptes en banque
 */
require_once( "libs/prepend.php" );
// Renvoi vers la page d'identification si la session est vide
if (!isset($_SESSION[APP_NAME]["id_utilisateur"])) redirection("identification.php");
//$smarty->assign("menu_select", 2); // Id du menu selectionné
$smarty->assign("is_ipad", (bool) strpos($_SERVER['HTTP_USER_AGENT'],'iPad'));

//SELECT DATE_FORMAT(date, '%Y-%m') AS periode, sum(montant)FROM `dinero_transactions`  WHERE montant>0 AND compte_id=10 GROUP BY periode ORDER BY periode

// Déclaration des variables
$action				= '';
$id					= '';
$etat				= '';
$date 				= '';
$libelle 			= '';
$note 				= '';
$rub_id 			= '';
$montant 			= '';
$data				= '';
$compte_id			= '';
$tbl_operations   = $config['table_prefix']."operations";
$tbl_rubriques      = $config['table_prefix']."rubriques";
$tbl_comptes        = $config['table_prefix']."comptes";

// Récupération des variables
$action				= get_input('action','both');
$id					= get_input('id','both');
$etat				= get_input('etat','post');
$date 				= date2sql(get_input('date','post'));
$libelle 			= addslashes(get_input('libelle','post'));
$note 				= addslashes(get_input('note','post'));
$rub_id 			= get_input('rub_id','post');
$montant 			= get_input('montant','post');
$data 				= get_input('data','post');
$compte_id			= get_input('compte_id','post');

switch ($action)
	{
	case "insert" :
		$data = $data.chr(13);
		$nb_lignes = ceil(substr_count($data, chr(13)));
		$order   = array("##", "\r\n", "\n", "\r", "\t");
		$replace = '#';
		$data = str_replace($order, $replace, $data);
		$parts = explode("#", $data);
		$nb_parts =  floor(sizeof($parts)/$nb_lignes);
		//print_r($parts);
		//RAZ des imports
		$db->query("UPDATE  $tbl_operations SET  import =  '0'");
		for($i=0;$i<$nb_lignes;$i++) {
			$offset = $i*$nb_parts;
			//date
			list($d,$m,$y) = explode("/", $parts[$offset]);
			$date =  date("Y-m-d", mktime(0,0,0,$m,$d,$y));
			// libelle
			$libelle = trim($parts[$offset+1]);
			// montant
			$montant = v2p($parts[$offset+2]);
			$montant = str_replace(array("+", " ", "EUR", "Eur", "é"), "", $montant);
			// Construction de la requete SQL
			$sql  = "INSERT INTO $tbl_operations (compte_id, date, libelle, montant, import) VALUES ('$compte_id', '$date', '$libelle', '$montant', '1')";
			//echo $sql."<hr />";
			$result = $db->query($sql);
			}
		$_SESSION["msg"] = $nb_lignes." opération(s) ajoutée(s) avec succés.";
		redirection("comptes.php?id=$compte_id");
	break;

    case "delete_imports" :
		$nb_imports = $db->getOne("SELECT count(*) FROM $tbl_operations WHERE import = '1'");
    	if ($nb_imports > 0) {
			$sql  = "DELETE FROM $tbl_operations WHERE import = '1'";
			$result = $db->query($sql);
			dialog("Les $nb_imports dernières opérations importées ont été effacées !");
    		}
		else {
			dialog("Il n'y a aucune opération récement importée !", "erreur");	
		}    		
		redirection("index.php");
	break;
	
    case "edit" :
		$smarty->assign("rubriques", $db->getAll("SELECT * FROM $tbl_rubriques"));
		$smarty->assign("data", $db->getArray("SELECT * FROM $tbl_operations WHERE id_transaction=$id"));
		$smarty->assign("form",	array("action" => "update", "nom" => "Modification d'une transaction", "bouton" => "Enregistrer les modifications"));
		$smarty->assign("content","operation_edit.tpl.html");
		$smarty->display("main.tpl.html");
	break;

	case "update" :
		$id_compte = $db->getOne("SELECT compte_id FROM $tbl_operations WHERE id_transaction=$id");
		$sql  = "UPDATE $tbl_operations SET libelle ='$libelle', date ='$date', note ='$note', rub_id ='$rub_id', montant ='$montant' WHERE id_transaction=$id";
		$result = $db->query($sql);
		redirection("comptes.php?id=$id_compte");
	break;

	case "delete" :
		$id_compte = $db->getOne("SELECT compte_id FROM $tbl_operations WHERE id_transaction=$id");
		$sql  = "DELETE FROM $tbl_operations WHERE id_transaction=$id";
		$result = $db->query($sql);
		redirection("comptes.php?id=$id_compte");
	break;

	case "pointage" :
		$id_compte = $db->getOne("SELECT compte_id FROM $tbl_operations WHERE id_transaction=$id");
		if ($etat == 0) {
			$db->query("UPDATE $tbl_operations SET pointage = '1' WHERE id_transaction=$id");
			info("Pointage de la transaction");
			}
		//else $db->query("UPDATE $tbl_operations SET pointage = '0' WHERE id_transaction=$id");
		redirection("comptes.php?id=$id_compte");
	break;

	default :
		$smarty->assign("titre", "Compte en banque");
		$infos = $db->getArray("SELECT * FROM $tbl_comptes WHERE id_compte = $id");
		$solde = $infos["initial"];
		$sql = "SELECT t.*, r.librub FROM $tbl_operations AS t LEFT JOIN $tbl_rubriques AS r ON t.rub_id = r.id_rub WHERE t.compte_id = $id ORDER BY t.date ASC";
		//echo $sql;
		$result = $db->query($sql);
		 while ($pos = $db->fetch_array($result)) {
		 	$solde = $solde + $pos["montant"] ;
			$smarty->append("data", array("id_transaction" => $pos["id_transaction"], "date" => $pos["date"], 
			"libelle" => $pos["libelle"], "note" => $pos["note"], "librub" => $pos["librub"], "montant" => $pos["montant"], 
			"solde" => $solde, "pointage" => $pos["pointage"], "import" => $pos["import"]));
			}
		$solde= v2p($solde); // remplace la virgule par un point
		//echo $infos["courant"]." - ".$solde;
		if ($infos["courant"] <> $solde) {
			$db->query("UPDATE $tbl_comptes SET courant = '$solde' WHERE id_compte = $id"); 	// Mis a jour le solde du compte
			$infos["courant"] = $solde; // Solde courant du compte
			$_SESSION["msg"] = "Mise é jour du solde courant effectuée.";
			}
		$smarty->assign("compte_id", $id); // Id du compte en cours
		$smarty->assign("infos", $infos);
		$smarty->assign("annees", $db->getAll("SELECT DATE_FORMAT(date, '%Y') AS an FROM $tbl_operations WHERE compte_id=$id GROUP BY an ORDER BY date"));
		$smarty->assign("content", "operations_list.tpl.html");
		$smarty->display("main.tpl.html");
	break;
	}
require_once("libs/append.php");
?>