<?php
/* Copyright (C) 2013      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Marcos García				<marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/opensurvey/wizard/create_survey.php
 *	\ingroup    opensurvey
 *	\brief      Page to create a new survey
 */

require_once('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/opensurvey/fonctions.php");

// Security check
if (!$user->rights->opensurvey->write) accessforbidden();

$langs->load("opensurvey");

// On teste toutes les variables pour supprimer l'ensemble des warnings PHP
// On transforme en entites html les données afin éviter les failles XSS
$post_var = array('titre', 'commentaires', 'mailsonde', 'creation_sondage_date', 'creation_sondage_autre');
foreach ($post_var as $var)
{
	$$var = GETPOST($var);
}

// On initialise egalement la session car sinon bonjour les warning :-)
$session_var = array('titre', 'commentaires', 'mailsonde');
foreach ($session_var as $var)
{
	if (isset($_SESSION[$var])) $_SESSION[$var] = null;
}

// On initialise également les autres variables
$cocheplus = '';
$cochemail = '';

// Jump to correct page
if (GETPOST("creation_sondage_date") || GETPOST("creation_sondage_autre"))
{
	$_SESSION["titre"] = $titre;
	$_SESSION["commentaires"] = $commentaires;

	if (GETPOST('mailsonde') == 'on') {
		$_SESSION["mailsonde"] = true;
	} else {
		$_SESSION["mailsonde"] = false;
	}
	
	if (GETPOST('allow_comments') == 'on') {
		$_SESSION['allow_comments'] = true;
	} else {
		$_SESSION['allow_comments'] = false;
	}
	
	if (GETPOST('allow_spy') == 'on') {
		$_SESSION['allow_spy'] = true;
	} else {
		$_SESSION['allow_spy'] = false;
	}
	
	$testdate = false;
	
	if (GETPOST('champdatefin'))
	{
		$registredate=explode("/",$_POST["champdatefin"]);
		if (is_array($registredate) && count($registredate) === 3) {
			$time = mktime(0,0,0,$registredate[1],$registredate[0],$registredate[2]);
			
			if ($time !== false && date('d/m/Y', $time) === $_POST["champdatefin"]) {
				//Expire date is not before today
				if ($time - dol_now() > 0) {
					$testdate = true;
					$_SESSION['champdatefin'] = $time;
				}
			}
		}
	}
	
	if (!$testdate) {
		setEventMessage($langs->trans('ErrorOpenSurveyDateFormat'), 'errors');
	}

	if ($titre && $testdate)
	{
		if (! empty($creation_sondage_date))
		{
			header("Location: choix_date.php");
			exit();
		}

		if (! empty($creation_sondage_autre))
		{
			header("Location: choix_autre.php");
			exit();
		}
	}
}




/*
 * View
 */

$form = new Form($db);

$arrayofjs=array();
$arrayofcss=array('/opensurvey/css/style.css');
llxHeader('', $langs->trans("OpenSurvey"), '', "", 0, 0, $arrayofjs, $arrayofcss);

print_fiche_titre($langs->trans("CreatePoll").' (1 / 2)');

//debut du formulaire
print '<form name="formulaire" action="" method="POST">'."\n";

//Affichage des différents champs textes a remplir
print '<table class="border" width="100%">'."\n";

print '<tr><td class="fieldrequired">'. $langs->trans("PollTitle") .'</td><td><input type="text" name="titre" size="40" maxlength="80" value="'.$_SESSION["titre"].'"></td>'."\n";
if (! $_SESSION["titre"] && (GETPOST('creation_sondage_date') || GETPOST('creation_sondage_autre')))
{
	setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("PollTitle")), 'errors');
}

print '</tr>'."\n";
print '<tr><td>'. $langs->trans("Description") .'</td><td><textarea name="commentaires" rows="7" cols="40">'.$_SESSION["commentaires"].'</textarea></td>'."\n";
print '</tr>'."\n";

print '<tr><td class="fieldrequired">'.  $langs->trans("ExpireDate")  .'</td><td>';

if (!GETPOST('champdatefin')) {
	//172800 = 48 hours
	$champdatefin = time() + 172800;
} else {
	$champdatefin = -1;
}

print $form->select_date($champdatefin,'champdatefin','','','',"add",1,0);

print '</tr>'."\n";
print '</table>'."\n";

//focus javascript sur le premier champ
print '<script type="text/javascript">'."\n";
print 'document.formulaire.titre.focus();'."\n";
print '</script>'."\n";

print '<br>'."\n";

// Check or not

if ($_SESSION["mailsonde"]) $cochemail="checked";

print '<input type="checkbox" name="mailsonde" '.$cochemail.'> '. $langs->trans("ToReceiveEMailForEachVote") .'<br>'."\n";

if ($_SESSION['allow_comments']) $allow_comments = "checked";

print '<input type="checkbox" name="allow_comments" '.$allow_comments.'> '.$langs->trans('CanComment').'<br />'."\n";

if ($_SESSION['allow_spy']) $allow_spy = "checked";

print '<input type="checkbox" name="allow_spy" '.$allow_spy.'> '.$langs->trans('CanSeeOthersVote').'<br />'."\n";

if (GETPOST('choix_sondage'))
{
	if (GETPOST('choix_sondage') == 'date') print '<input type="hidden" name="creation_sondage_date" value="date">';
	else print '<input type="hidden" name="creation_sondage_autre" value="autre">';
	print '<input type="hidden" name="choix_sondage" value="'.GETPOST('choix_sondage').'">';
	print '<br><input type="submit" class="button" name="submit" value="'.$langs->trans("CreatePoll").' ('.(GETPOST('choix_sondage') == 'date'?$langs->trans("TypeDate"):$langs->trans("TypeClassic")).')">';
}
else
{
	//affichage des boutons pour choisir sondage date ou autre
	print '<br><table>'."\n";
	print '<tr><td>'. $langs->trans("CreateSurveyDate") .'</td><td></td> '."\n";
	print '<td><input type="image" name="creation_sondage_date" value="'.$langs->trans('CreateSurveyDate').'" src="../img/calendar-32.png"></td></tr>'."\n";
	print '<tr><td>'. $langs->trans("CreateSurveyStandard") .'</td><td></td> '."\n";
	print '<td><input type="image" name="creation_sondage_autre" value="'.$langs->trans('CreateSurveyStandard').'" src="../img/chart-32.png"></td></tr>'."\n";
	print '</table>'."\n";
}
print '<br><br><br>'."\n";
print '</form>'."\n";

llxFooter();

$db->close();
?>