<?php
/**
 * @project		Framework 1.0
 *
 * @author		Olivier Gaillard <olivier.gaillard@larep.com>
 * @version		1.0 du 01/01/2009
 * @lastmod		24/12/2009 14:50
 * @desc	   	Gestion de l'identification des utilisateurs
 */

require_once("libs/prepend.php");
// Déclaration des variables
$action			= '';
$identifiant            = '';
$email			= '';
$mdp			= '';
$tbl_utilisateurs       = $config['table_prefix']."utilisateurs";
// Récupération des variables
$action			= get_input('action','both');
$identifiant            = addslashes(get_input('identifiant','both'));
$email			= addslashes(get_input('email','both'));
$mdp			= addslashes(get_input('mdp','both'));

switch ($action)
	{
	case "submit" :
		$sql = "SELECT * FROM $tbl_utilisateurs WHERE identifiant='$identifiant' and mdp=MD5('$mdp')";
		$data = $db->getArray($sql);
		if ($data) {
			foreach ($data as $key => $value)
				$_SESSION[APP_NAME][$key] 	= $value;

			// Met à  jour la dernière connexion de l'utilisateur
			$db->query("UPDATE fw_utilisateurs 
				SET last_cnx = CURRENT_TIMESTAMP WHERE id_utilisateur =".$data['id_utilisateur']);
			
			loginfo("Connexion de l'utilisateur ".$identifiant, "Identification");
			// TODO : Arranger ça
                        dialog("Bienvenue !");
			// Redirection en fonction du profil
			if ($data['profil'] == '0') redirection("index.php");
			if ($data['profil'] == '1') redirection("index.php?action=log");
			if ($data['profil'] == '2') redirection("index.php");
			die();
			} 
		else {
			// Echec d'identification
			loginfo("Echec d'identification de l'utilisateur ".$identifiant."|".$mdp, "identification", $db);
			$_SESSION["action"] = "login";
			dialog("Identifiant ou mot de passe incorrect !", "erreur");
			redirection("identification.php");
			}

	break;

	case "logout" :
		session_destroy();
		unset($_SESSION);
		redirection("identification.php");
	break;

	case "submit_mail" :
		$data = $db->getRow("SELECT * FROM $tbl_utilisateurs WHERE email='$email'");
		if (!$data) {
			dialog("Cette adresse email n'est pas reconnue par le système.", "erreur");
		}
		else {
			$new_mdp = substr(MD5(date("YmdHis")),0,8);
			$txt = "Bonjour $data->prenom $data->nom,<br />Suite Ã  votre demande nous vous communiquons votre identifiant<br />"; 
			$txt .= "et un nouveau mot de passe pour vous connecter sur Scribe.<br />"; 
			$txt .= "Votre identifiant est : <strong>".$data->identifiant."</strong><br />Votre nouveau mot de passe est : <b>".$new_mdp."</b><br />"; 
			$txt .= "Nous vous recommandons lors de votre prochaine connexion de personnaliser votre mot de passe.<br />"; 
			$txt .= "Cordialement.<br />L'administrateur Scribe"; 
			if (send_mail("adm.scribe@larep.com", $email, "[APP_NAME] Rappel identifiant et nouveau mot de passe", $txt)) {
				$db->query("UPDATE $tbl_utilisateurs SET mdp = MD5('$new_mdp') WHERE email = '$email'");
				$_SESSION["msg"] = "Un message contenant votre nouveau mot de passe vous a été envoyé.";
				}
			else dialog("Une erreur s'est produit de l'envoi d'un message à '$email'");
			}
		redirection("identification.php");
	break;

	case "lost_mdp" :
		$smarty->assign("action", "submit_mail");
		$smarty->assign("titre", "Identification");
		$smarty->assign("content", "identification.tpl.html");								  
		$smarty->display("main_wide.tpl.html");
	break;

	default:
		$smarty->assign("titre", "Identification");
		$smarty->assign("content", "identification.tpl.html");								  
		$smarty->display("main_wide.tpl.html");
	}
require_once("libs/append.php");
?>