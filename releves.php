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
require('libs/fpdf/fpdf.php');
require('libs/fpdf/extend_fpdf.php');

define('EURO', chr(128));

// Renvoi vers la page d'identification si la session est vide
if (!isset($_SESSION[APP_NAME]["id_utilisateur"])) redirection("identification.php");

// Déclaration des variables
$action			= '';
$compte_id		= '';
$debut			= '';
$fin			= '';
$tbl_operations       = $config['table_prefix']."operations";
$tbl_banques            = $config['table_prefix']."banques";
$tbl_comptes            = $config['table_prefix']."comptes";

// Récupération des variables
$action			= get_input('action','both');
$compte_id		= get_input('compte_id','both');
$debut			= get_input('debut','both');
$fin			= get_input('fin','both');

switch ($action)
	{

	case "make_pdf" :
		$solde = 0;
		$index = 0;
		$lpp = 38;
		$w = array(21,91,26,26,26);
                $titre = "Relevé des opérations";
                $texte = "Liste des opérations du ".sql2date($debut)." au ".sql2date($fin);
                $banque_id = $db->getOne("SELECT banque_id FROM $tbl_comptes WHERE id_compte = $compte_id");
                $logo = $db->getOne("SELECT logo FROM $tbl_banques WHERE id_banque = $banque_id");
                $couleur = explode(",", $db->getOne("SELECT couleur FROM $tbl_banques WHERE id_banque = $banque_id"));

                $pdf=new PDF();
		$pdf->AliasNbPages();

		//Titres des colonnes
		$header=array("Date", "Nature de l'opération", "Débit", "Crédit", "Solde");
		$sql = "SELECT * FROM $tbl_operations WHERE date >= '$debut' AND date <= '$fin' AND compte_id = $compte_id ORDER BY date ASC";
		$result = $db->query($sql);
			 while ($pos = $db->fetch_array($result)) {
				if ($index == 0) {
					$pdf->AddPage();
					$pdf->SetFont('Arial','',10);
					$pdf->EnteteReleveComptes($titre, $texte, $logo );
					$pdf->EnteteTableau($header, $w, $couleur);
					}
				if ($pos["montant"] > 0) {
					$debit = " ";
					$credit = number_format($pos["montant"],2,',',' ')." ".EURO;
					}
				else {
					$credit = " ";
					$debit = number_format($pos["montant"],2,',',' ')." ".EURO;
					}
				$solde = number_format($solde + $pos["montant"],2,',',' ')." ".EURO;
				$data = array(sql2date($pos["date"]), $pos["libelle"], $debit, $credit, $solde);
				$pdf->FancyCell($data,$w);
				$index++;
				if ($index > $lpp) $index = 0; // RAZ de l'index en base de page
			}

		$pdf->Output();

			/*$smarty->assign("content", "temp.tpl.html");
		$smarty->display("main.tpl.html");*/
	break;

	default :
		$smarty->assign("titre", "Génération de PDF");
		$smarty->assign("form",	array("action" => "make_pdf", "nom" => "Génération de documents en PDF"));
		$smarty->assign("comptes", $db->getAll("SELECT id_compte, banque, intitule FROM $tbl_comptes ORDER BY id_compte"));
		$smarty->assign("content", "releves.tpl.html");
		$smarty->display("main.tpl.html");
	break;
	}
require_once("libs/append.php");
?>