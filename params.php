<?php
/**
 * @project		Framework 1.0
 *
 * @author		Olivier Gaillard <olivier.gaillard@larep.com>
 * @version		1.0 du 01/01/2009
 * @lastmod		21/02/2009 12:54
 * @desc	   	Gestion des utilisateurs
 */

require_once( "libs/prepend.php" );
if (!isset($_SESSION[APP_NAME]["id_utilisateur"])) redirection("identification.php");

// Déclaration des variables
$action 			= '';
$id					= '';
// Utilisateur
$nom				= '';
$prenom				= '';
$identifiant		= '';
$email				= '';
$profil				= '';
$mdp1				= '';
$mdp2				= '';
// Banque
$etablissement  	= '';
$logo				= '';
$couleur			= '';
// Compte
$intitule			= '';
$banque_id			= '';
$user_id			= '';
$num_cpt			= '';
$ordre				= '';
$etat				= '';
$initial			= '';


$tbl_comptes       = $config['table_prefix']."comptes";
$tbl_banques       = $config['table_prefix']."banques";

// Récupération des variables
$action 			= get_input('action','both');
$id	 				= get_input('id','both');
// Utilisateur
$nom 				= addslashes(get_input('nom','post'));
$prenom 			= addslashes(get_input('prenom','post'));
$identifiant 		= addslashes(get_input('identifiant','post'));
$email 				= addslashes(get_input('email','post'));
$profil 			= get_input('profil','post');
$mdp1 				= addslashes(get_input('mdp1','post'));
$mdp2 				= addslashes(get_input('mdp2','post'));
// Banque
$etablissement  	= addslashes(get_input('etablissement','post'));
$logo				= addslashes(get_input('logo','post'));
$couleur			= get_input('couleur','both');
// Compte
$intitule			= addslashes(get_input('intitule','post'));
$banque_id			= get_input('banque_id','post');
$user_id			= get_input('user_id','post');
$num_cpt			= get_input('num_cpt','post');
$ordre				= get_input('ordre','post');
$etat				= get_input('etat','post');
$initial			= get_input('initial','post');

switch($action) {
	case "edit" :
		$smarty->assign("titre", "Modification d'un utilisateur");
		$sql = "SELECT * FROM $tbl_utilisateurs WHERE id_utilisateur='$id'";
		$smarty->assign("data", $db->getArray($sql));
		$smarty->assign("form",	array("action" => "update", "nom" => "Modification d'un utilisateur", "bouton" => "Enregistrer les modifications"));
		$smarty->assign("content","utilisateur_edit.tpl.html");
		$smarty->display("main.tpl.html");
		break;

	case "insert" :
		if (($mdp1 <> "") && ($mdp1 = $mdp2)) $sql  = "INSERT INTO $tbl_utilisateurs (nom, prenom, identifiant, email, profil, mdp) VALUES ('$nom', '$prenom', '$identifiant', '$email', '$profil', MD5('$mdp1'))";
		else $sql  = "INSERT INTO $tbl_utilisateurs (nom, prenom, identifiant, email, profil) VALUES ('$nom', '$prenom', '$identifiant', '$email', '$profil')";
		$result = $db->query($sql);
		//debug($sql);
		dialog("Le nouvel utilisateur a Ã©tÃ© enregistrÃ©.");
		redirection("utilisateurs.php");
		break;

	case "update" :
		if (($mdp1 <> "") && ($mdp1 = $mdp2)) {
			$sql  = "UPDATE $tbl_utilisateurs SET nom ='$nom',  prenom ='$prenom',  identifiant ='$identifiant', email ='$email', profil ='$profil', mdp = MD5('$mdp1') WHERE id_utilisateur='$id'";
			dialog("Le profil est le mot de passse ont Ã©tÃ© modifiÃ©s avec succÃ©s.");
		}
		else {
			$sql  = "UPDATE $tbl_utilisateurs SET nom ='$nom',  prenom ='$prenom',  identifiant ='$identifiant', email ='$email', profil ='$profil' WHERE id_utilisateur='$id'";
			dialog("Le profil a Ã©tÃ© modifiÃ© avec succÃ©s.<br />Le mot de passe est inchangÃ©.");
		}
		$result = $db->query($sql);
		//debug($sql);
		dialog("Les modifications du profil utilisateur ont Ã©tÃ© sauvegardÃ©es.");
		loginfo("Le profil utilisateur '$nom $prenom' a Ã©tÃ© modifiÃ©.");
		redirection("utilisateurs.php");
		break;

	case "delete" :
		$db->query("DELETE FROM $tbl_utilisateurs WHERE id='$id'");
		info("Le profil de l'utilisateur a Ã©tÃ© effacÃ© !");
		redirection("utilisateurs.php");
		break;

	case "edit_profil" :
		$smarty->assign("titre", "Profil utilisateur");
		$sql = "SELECT * FROM $tbl_utilisateurs WHERE id_utilisateur='".$_SESSION[APP_NAME]["id_utilisateur"]."'";
		$smarty->assign("data", $db->getArray($sql));
		$smarty->assign("form",	array("action" => "update_profil", "nom" => "Profil utilisateur", "bouton" => "Enregistrer les modifications"));
		$smarty->assign("content","utilisateur_edit.tpl.html");
		$smarty->display("main.tpl.html");
		break;

	case "update_profil" :
		if (($mdp1 <> "") && ($mdp1 = $mdp2)) $sql  = "UPDATE $tbl_utilisateurs SET nom ='$nom',  prenom ='$prenom',  identifiant ='$identifiant', email ='$email', profil ='$profil', mdp = MD5('$mdp1') WHERE id_utilisateur='$id'";
		else $sql  = "UPDATE $tbl_utilisateurs SET nom ='$nom',  prenom ='$prenom',  identifiant ='$identifiant', email ='$email' WHERE id_utilisateur='$id'";
		$result = $db->query($sql);
		$_SESSION[APP_NAME]["prenom"] = $prenom;
		$_SESSION[APP_NAME]["nom"] = $nom;
		dialog("Les modifications du profil utilisateur ont Ã©tÃ© sauvegardÃ©es.");
		loginfo("Le profil utilisateur '$nom $prenom' a Ã©tÃ© modifiÃ©.");
		redirection("index.php");
		break;

		// Gestion des banques
	case "banques" :
		$smarty->assign("titre", "Liste des comptes");
		$smarty->assign("data", $db->getAll("SELECT * FROM $tbl_banques"));
		$smarty->assign("form",	array("nom" => "Liste des banques", "action" => "new", "title" => "Ajouter une banques", "bouton" => "Nouveau"));
		$smarty->assign("content", "banques_liste.tpl.html");
		$smarty->display("main.tpl.html");
		break;

		// Gestion des comptes
	case "comptes" :
		$smarty->assign("titre", "Liste des comptes");
		$smarty->assign("data", $db->getAll("SELECT intitule, num_cpt, etablissement, user_id, initial, courant, etat, ordre, id_compte
			                                 FROM $tbl_comptes AS c JOIN $tbl_banques AS b ON c.banque_id = b.id_banque ORDER BY ordre"));
		$smarty->assign("form",	array("nom" => "Liste des comptes", "action" => "new", "title" => "Ajouter un compte", "bouton" => "Nouveau"));
		$smarty->assign("content", "comptes_liste.tpl.html");
		$smarty->display("main.tpl.html");
		break;

	case "edit_compte" :
		$smarty->assign("titre", "Modification d'un compte");
		$sql = "SELECT * FROM $tbl_comptes WHERE id_compte=".$id;
		$smarty->assign("data", $db->getArray($sql));
		$smarty->assign("banques", $db->getPop("SELECT id_banque, etablissement FROM $tbl_banques"));
		$smarty->assign("form",	array("action" => "update_compte", "nom" => "Compte", "bouton" => "Enregistrer les modifications"));
		$smarty->assign("content","compte_edit.tpl.html");
		$smarty->display("main.tpl.html");
		break;

	case "update_compte" :
		$sql  = "UPDATE $tbl_comptes SET intitule ='$intitule', banque_id ='$banque_id', num_cpt ='$num_cpt',  ordre ='$ordre',
			     etat ='$etat',  initial ='$initial' WHERE id_compte='$id'";
		//echo $sql;
		$result = $db->query($sql);
		dialog("Les modifications du compte en banque ont Ã©tÃ© sauvegardÃ©es.");
		loginfo("Le compte en banque '$nom ' a Ã©tÃ© modifiÃ©.");
		redirection("params.php?action=comptes");
		break;


	default:
		$smarty->assign("titre", "Liste des utilisateurs");
	$smarty->assign("data", $db->getAll("SELECT * FROM $tbl_utilisateurs"));
	$smarty->assign("form",	array("nom" => "Liste des utilisateurs", "action" => "new", "title" => "Ajouter un utilisateur", "bouton" => "Nouveau"));
	$smarty->assign("content", "utilisateurs_liste.tpl.html");
	$smarty->display("main.tpl.html");
}
require_once( "libs/append.php" );
?>