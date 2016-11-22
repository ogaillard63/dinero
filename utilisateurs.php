<?php
/** * @project		Framework 1.0
 * * @author		Olivier Gaillard <olivier.gaillard@larep.com>
 * @version		1.0 du 01/01/2009
 * @lastmod		21/02/2009 12:54
 * @desc	   	Gestion des utilisateurs
 */
 
require_once( "libs/prepend.php" );
if (!isset($_SESSION[APP_NAME]["id_utilisateur"])) redirection("identification.php");

// Déclaration des variables$action 			= '';
$id					= '';
$nom				= '';
$prenom				= '';
$identifiant		= '';
$email				= '';
$profil				= '';
$mdp1				= '';
$mdp2				= '';
$tbl_utilisateurs       = $config['table_prefix']."utilisateurs";
// Récupération des variables$action 			= get_input('action','both');
$id	 				= get_input('id','both');
$nom 				= addslashes(get_input('nom','both'));
$prenom 			= addslashes(get_input('prenom','both'));
$identifiant 		= addslashes(get_input('identifiant','both'));
$email 				= addslashes(get_input('email','both'));
$profil 			= get_input('profil','both');
$mdp1 				= addslashes(get_input('mdp1','both'));
$mdp2 				= addslashes(get_input('mdp2','both'));

switch($action) {    case "new" :        $smarty->assign("form",	array("action" => "insert", "nom" => "Ajouter un utilisateur", "bouton" => "Enregistrer"));
        $smarty->assign("content", "utilisateur_edit.tpl.html");
        $smarty->display("main.tpl.html");
        break;
    case "edit" :        $smarty->assign("titre", "Modification d'un utilisateur");
        $sql = "SELECT * FROM $tbl_utilisateurs WHERE id_utilisateur='$id'";
        $smarty->assign("data", $db->getArray($sql));
        $smarty->assign("form",	array("action" => "update", "nom" => "Modification d'un utilisateur", "bouton" => "Enregistrer les modifications"));
        $smarty->assign("content","utilisateur_edit.tpl.html");
        $smarty->display("main.tpl.html");
        break;
    case "insert" :        if (($mdp1 <> "") && ($mdp1 = $mdp2)) $sql  = "INSERT INTO $tbl_utilisateurs (nom, prenom, identifiant, email, profil, mdp) VALUES ('$nom', '$prenom', '$identifiant', '$email', '$profil', MD5('$mdp1'))";
        else $sql  = "INSERT INTO $tbl_utilisateurs (nom, prenom, identifiant, email, profil) VALUES ('$nom', '$prenom', '$identifiant', '$email', '$profil')";
        $result = $db->query($sql);
        //debug($sql);
        dialog("Le nouvel utilisateur a été enregistré.");
        redirection("utilisateurs.php");
        break;
    case "update" :        if (($mdp1 <> "") && ($mdp1 = $mdp2)) {            $sql  = "UPDATE $tbl_utilisateurs SET nom ='$nom',  prenom ='$prenom',  identifiant ='$identifiant', email ='$email', profil ='$profil', mdp = MD5('$mdp1') WHERE id_utilisateur='$id'";
            dialog("Le profil est le mot de passse ont été modifiés avec succés.");
        }        else {            $sql  = "UPDATE $tbl_utilisateurs SET nom ='$nom',  prenom ='$prenom',  identifiant ='$identifiant', email ='$email', profil ='$profil' WHERE id_utilisateur='$id'";
            dialog("Le profil a été modifié avec succés.<br />Le mot de passe est inchangé.");
        }        $result = $db->query($sql);
        //debug($sql);
        dialog("Les modifications du profil utilisateur ont été sauvegardées.");
        loginfo("Le profil utilisateur '$nom $prenom' a été modifié.");
        redirection("utilisateurs.php");
        break;
    case "delete" :        $db->query("DELETE FROM $tbl_utilisateurs WHERE id='$id'");
        info("Le profil de l'utilisateur a été effacé !");
        redirection("utilisateurs.php");
        break;
    case "edit_profil" :        $smarty->assign("titre", "Profil utilisateur");
        $sql = "SELECT * FROM $tbl_utilisateurs WHERE id_utilisateur='".$_SESSION[APP_NAME]["id_utilisateur"]."'";
        $smarty->assign("data", $db->getArray($sql));
        $smarty->assign("form",	array("action" => "update_profil", "nom" => "Profil utilisateur", "bouton" => "Enregistrer les modifications"));
        $smarty->assign("content","utilisateur_edit.tpl.html");
        $smarty->display("main.tpl.html");
        break;
    case "update_profil" :        if (($mdp1 <> "") && ($mdp1 = $mdp2)) $sql  = "UPDATE $tbl_utilisateurs SET nom ='$nom',  prenom ='$prenom',  identifiant ='$identifiant', email ='$email', profil ='$profil', mdp = MD5('$mdp1') WHERE id_utilisateur='$id'";
        else $sql  = "UPDATE $tbl_utilisateurs SET nom ='$nom',  prenom ='$prenom',  identifiant ='$identifiant', email ='$email' WHERE id_utilisateur='$id'";
        $result = $db->query($sql);
        $_SESSION[APP_NAME]["prenom"] = $prenom;
        $_SESSION[APP_NAME]["nom"] = $nom;
        dialog("Les modifications du profil utilisateur ont été sauvegardées.");
        loginfo("Le profil utilisateur '$nom $prenom' a été modifié.");
        redirection("index.php");
        break;
// test    
	default:        $smarty->assign("titre", "Liste des utilisateurs");
        $smarty->assign("data", $db->getAll("SELECT * FROM $tbl_utilisateurs"));
        $smarty->assign("form",	array("nom" => "Liste des utilisateurs", "action" => "new", "title" => "Ajouter un utilisateur", "bouton" => "Nouveau"));
        $smarty->assign("content", "utilisateurs_liste.tpl.html");
        $smarty->display("main.tpl.html");
}

require_once( "libs/append.php" );
?>