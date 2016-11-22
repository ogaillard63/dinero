<?php
/**
 * @project		Framework 1.0
 *
 * @author		Olivier Gaillard <olivier.gaillard@larep.com>
 * @version		1.0 du 01/01/2009
 * @lastmod		24/12/2009 15:36
 * @desc	   	Page d'accueil
 */

require_once( "libs/prepend.php" );
if (!isset($_SESSION[APP_NAME]["id_utilisateur"])) redirection("identification.php");
affiche_rubriques(2);
$smarty->assign("is_ipad", (bool) strpos($_SERVER['HTTP_USER_AGENT'],'iPad'));

// Déclaration des variables
$action				= '';
$debug				= '';
$tbl_operations  	= $config['table_prefix']."operations";
$tbl_comptes        = $config['table_prefix']."comptes";
// Récupération des variables
$action			= get_input('action','both');
$debug			= get_input('debug','both');
// test
switch ($action) {
    case "logs" :
        $smarty->assign("titre", "Journal des opérations");
        $smarty->assign("form", array("nom" => "Journal des opérations"));
        $smarty->assign("data", $db->getAll("SELECT * FROM fw_logs ORDER BY creation DESC"));
        $smarty->assign("content", "logs.tpl.html");
        break;

    case "del_log" :
        $db->query("DELETE FROM fw_logs");
        loginfo("Purge du journal d'activités");
        dialog("Le journal d'activités a été purgé.");
        redirection("index.php?action=log");
        break;

    default :
        $last_solde = 0;
        $smarty->assign("titre", "Panorama Financier");
        $total = 0;
        $result = $db->query("SELECT * FROM $tbl_comptes WHERE etat = '1' ORDER BY ordre DESC");
        while ($pos = $db->fetch_array($result)) {
            $total = $total + $pos["courant"] ;
            $smarty->append("data", array("id_compte" => $pos["id_compte"], "banque" => $pos["banque"], "intitule" => $pos["intitule"], "courant" => $pos["courant"]));
        }
        $smarty->assign("total", $total);
        $smarty->assign("form",	array("action" => "update", "nom" => "Panorama des finances", "bouton" => "Enregistrer les modifications"));

        $initial = $db->getOne("SELECT sum(initial) FROM $tbl_comptes");
        //echo $initial;
        for ($an=2014; $an<2017; $an++) {
            for ($mois=1; $mois<13; $mois+=2) {
                $date = sprintf("%4d-%02d-20",$an,$mois);
                if (($date > "2014-08-31") AND ($date <= date("Y-m-d"))) {
                    $solde = ($db->getOne("SELECT sum(montant) FROM $tbl_operations WHERE date < '$date'") + $initial);
                    if ($debug == 1) {
                        printf("%s : %6.2f (%6.2f) <br/>", $date, $solde, $solde - $last_solde);
                        $last_solde = $solde;
                    }
                    $smarty->append("stats", array("date" => $mois."/".$an, "solde" => $solde/1000));
                }
                //$debit = abs($db->getOne("SELECT sum(montant) AS total FROM $tbl_operations WHERE montant <0 AND date <= '$an-12-31' LIMIT 0,10"));
                //$credit = $db->getOne("SELECT sum(montant) AS total FROM $tbl_operations WHERE montant >0 AND date <= '$an-12-31' LIMIT 0,10");
                //$smarty->append("stats", array("an" => $an, "debit" => $debit, "credit" => $credit));
            }


        }
        $smarty->assign("content","panorama.tpl.html");
        break;
}
$smarty->display("main.tpl.html");
require_once("libs/append.php");
?>