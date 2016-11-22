<?php
/**
 * @project		Framework 1.0
 *
 * @author		Olivier Gaillard <olivier.gaillard@larep.com>
 * @version		1.0 du 01/01/2009
 * @lastmod		21/02/2009 12:54
 * @desc	   	Fonctions diverses
 */

/**
 * @return string
 * @param $name string
 * @param $type string
 * @param $validate string
 * @desc Get a POST, GET variable on any system. Possible values for type are: post,get,both.
 *       Use 'int' or 'string' to validate tde input
 */
function get_input ($name="",$type="",$validate = "") {

    global $HTTP_POST_VARS, $HTTP_GET_VARS;
    global $_POST, $_GET;

    $tdis = "";

// -----------------------
// Get magic quote setting
    $magic = get_magic_quotes_gpc();

    if ( ($type == "get") || ($type == "both") ) {
        if (isset($_GET["$name"])) {
            $tdis = $_GET["$name"];
            if ($magic && !is_array($tdis)) {
                $tdis = stripslashes($tdis);
            }
            return $tdis;
        } elseif (isset($HTTP_GET_VARS["$name"])) {
            $tdis = $HTTP_GET_VARS["$name"];
            if ($magic && !is_array($tdis)) {
                $tdis = stripslashes($tdis);
            }
            return $tdis;
        }
    }
    if ( ($type == "post") || ($type == "both") ) {
        if (isset($_POST["$name"])) {
            $tdis = $_POST["$name"];
            if ($magic && !is_array($tdis)) {
                $tdis = stripslashes($tdis);
            }
            return $tdis;
        } elseif (isset($HTTP_POST_VARS["$name"])) {
            $tdis = $HTTP_POST_VARS["$name"];
            if ($magic && !is_array($tdis)) {
                $tdis = stripslashes($tdis);
            }
            return $tdis;
        }
    }
}
//---------------------------------------------------------------------------//
// Formate une date (dd/mmd/yyyy) en (yyyy-mm-dd) 
//---------------------------------------------------------------------------//
function date2sql($date) {
    if ( $date <> '' ) {
        list($d,$m,$y) = explode("/",$date);
        return date("Y-m-d", mktime(0,0,0,$m,$d,$y));
    }
}
function sql2date($date) {
    if ( $date <> '' ) {
        list($y,$m,$d) = explode("-",$date);
        return date("d/m/Y", mktime(0,0,0,$m,$d,$y));
    }
}

//---------------------------------------------------------------------------//
function send_mail($exp_email, $dest_email, $sujet, $message) {
    $mail_mime = "From: $exp_email\n";
    $mail_mime .= "Reply-To: $exp_email\n";
    $mail_mime .= "Return-Path: <$exp_email>\n"; // En cas d' erreurs
    $mail_mime .= "X-Sender: <$exp_email>\n";
    $mail_mime .= "MIME-Version: 1.0\n";
    $mail_mime .= "Content-Type: text/html; charset=\"iso-8859-1\"\n ";
    $mail_mime .= "Content-Transfer-Encoding: 8bit\n";
    ini_set('sendmail_from', 'adm.scribe@larep.com');
    return mail($dest_email, $sujet, $message, $mail_mime);
}

/**
 * @desc 	Redirige le navigateur vers la page $page.
 * @param 	Nom de la page (string)
 * @return 	Rien
 **/
function redirection($page) {
    header("Location: ".$page);
    exit();
}
/******************************************************
* Gestionnaire d'erreurs
 *******************************************************/
function myErrorHandler($errno, $errstr, $errfile, $errline) {
    echo "<small><div style=\"color:#666;margin:5px;padding:5px; border:1px dotted #FF0000;\">";
    switch ($errno) {
        case E_USER_ERROR:
            echo "<b>ERREUR</b> [$errno] $errstr<br />";
            echo "Erreur fatale à la ligne <b>$errline</b> dans le fichier <b>$errfile</b>";
            exit(1);
            break;
        case E_USER_WARNING:
            echo "<b>ALERTE</b> [$errno] $errstr<br />";
            echo "Erreur fatale à la ligne <b>$errline</b> dans le fichier <b>$errfile</b>";
            break;
        case E_USER_NOTICE:
            echo "<b>NOTICE</b> [$errno] $errstr<br />";
            echo "Erreur fatale à la ligne <b>$errline</b> dans le fichier <b>$errfile</b>";
            break;
        default:
            echo "<b>ERREUR</b> [$errno] $errstr<br />";
            echo "Erreur fatale à la ligne <b>$errline</b> dans le fichier <b>$errfile</b>";
            break;
    }
    echo "</div></small>";
}
//---------------------------------------------------------------------------//
// Log des infos dans la bdd
//---------------------------------------------------------------------------//
function loginfo($msg, $ident = "") {
    global $db, $_SESSION;
    if ($ident == "") $ident = $_SESSION[APP_NAME]["nom"]." ".$_SESSION[APP_NAME]["prenom"];
    $db->query("INSERT INTO fw_logs VALUES (CURRENT_TIMESTAMP, '".addslashes($ident)."', '".addslashes($msg)."')");
}
/******************************************************************************************************
* Retourne la chaine de caractère compris entre les chaines $start_str et $stop_str
 ******************************************************************************************************/
function findStr($start_str, $stop_str, $str) {
    $d = strpos($str, $start_str) + strlen($start_str);
    $f = strpos($str, $stop_str, $d);
    return substr($str, $d, $f-$d);
}
//---------------------------------------------------------------------------//
// Gestion des messages de debug
//---------------------------------------------------------------------------//
function debug($msg) {
    $_SESSION["debug"] = $msg;
}
//---------------------------------------------------------------------------//
// Gestion des messages d'erreur
//---------------------------------------------------------------------------//
function erreur($msg) {
    $_SESSION["erreur"] = $msg;
}
//---------------------------------------------------------------------------//
// Gestion des messages d'information
//---------------------------------------------------------------------------//
function info($msg) {
    $_SESSION["msg"] = $msg;
}
//---------------------------------------------------------------------------//
// Gestion des messages
//---------------------------------------------------------------------------//
/**
 * @desc 	Affiche les messages d'informations
 **/
function dialog($msg, $type = "message") {
	$_SESSION[APP_NAME][$type] = $msg;
}
/**
 * @desc 	Affiche un tableau de façon claire (debug)
 **/
function print_tab($tab) {
	echo "<pre>";
	print_r($tab);
	echo "</pre>";
}
//---------------------------------------------------------------------------//
// Remplace la virgule par un point dans une chaine
//---------------------------------------------------------------------------//
function v2p($str) {
    $str = preg_replace("# #", '', $str); // Enleve les espaces
    $str = preg_replace("#,#", '.', $str); // Remplace la virgule par un point
    return $str;
}
//---------------------------------------------------------------------------//
// Test si une date YYYY-MM-JJ n'est pas un WE
//---------------------------------------------------------------------------//
function is_not_we($date) {
    list($a,$m,$j) = explode("-",$date);
    if (date("w", mktime(0,0,0,$m,$j,$a)) == 6) return false; // Samedi
    if (date("w", mktime(0,0,0,$m,$j,$a)) == 0) return false; // Dimanche
    return true;
}

//---------------------------------------------------------------------------//
// Log des infos dans la bdd
//---------------------------------------------------------------------------//
function affiche_rubriques($select) {
    global $db, $smarty;
    $smarty->assign("menu_select", $select); // Id du menu selectionné
    $smarty->assign("rubs", $db->getAll("SELECT * FROM fw_rubriques WHERE sub_id = 0"));
    $smarty->assign("subs", $db->getAll("SELECT * FROM fw_rubriques WHERE sub_id <> 0"));
}
?>