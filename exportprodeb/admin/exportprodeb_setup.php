<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/exportprodeb.php
 * 	\ingroup	exportprodeb
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */

// Libraries
require '../config.php';
require_once '../lib/exportprodeb.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Translations
$langs->load("exportprodeb@exportprodeb");

// Access control
if (! $user->admin) {
    accessforbidden();
}

$action=__get('action','');

if($action=='save') {
	
	foreach($_REQUEST['TParamProDeb'] as $name=>$param) {
		
		dolibarr_set_const($db, $name, $param);

	}
	
}

$page_name = "exportprodebSetup";
llxHeader('', $langs->trans($page_name));
// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);
// Configuration header
$head = exportprodebAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104994Name"),
    0,
    "exportprodeb@exportprodeb"
);

showParameters();

function showParameters()
{
	global $db,$conf,$langs;
	
	$langs->load('exportprodeb@exportprodeb');
	
	$form=new Form($db);
	$formother=new FormOther($db);
	$atmForm=new TFormCore;
	
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="save">';

	$var=false;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").' (DEB)</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
	print '</tr>';


	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("EXPORT_PRO_DEB_NUM_AGREMENT").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print $atmForm->texte('','TParamProDeb[EXPORT_PRO_DEB_NUM_AGREMENT]',$conf->global->EXPORT_PRO_DEB_NUM_AGREMENT,30,255);
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("EXPORT_PRO_DEB_TYPE_ACTEUR").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print $atmForm->combo('','TParamProDeb[EXPORT_PRO_DEB_TYPE_ACTEUR]', array(''=>'', 'PSI'=>'Déclarant pour son compte', 'TDP'=>'Tiers déclarant'), $conf->global->EXPORT_PRO_DEB_TYPE_ACTEUR);
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("EXPORT_PRO_DEB_ROLE_ACTEUR").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print $atmForm->combo('','TParamProDeb[EXPORT_PRO_DEB_ROLE_ACTEUR]', array(''=>'', 'sender'=>'Emetteur', 'PSI'=>'Déclarant'), $conf->global->EXPORT_PRO_DEB_ROLE_ACTEUR);
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("EXPORT_PRO_DEB_NIV_OBLIGATION_INTRODUCTION").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print $atmForm->combo('','TParamProDeb[EXPORT_PRO_DEB_NIV_OBLIGATION_INTRODUCTION]', array(0=>'', 1=>'Seuil de 460 000 €', 2=>'En dessous de 460 000 €'), $conf->global->EXPORT_PRO_DEB_NIV_OBLIGATION_INTRODUCTION);
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("EXPORT_PRO_DEB_NIV_OBLIGATION_EXPEDITION").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print $atmForm->combo('','TParamProDeb[EXPORT_PRO_DEB_NIV_OBLIGATION_EXPEDITION]', array(0=>'', 3=>'Seuil de 460 000 €', 4=>'En dessous de 460 000 €'), $conf->global->EXPORT_PRO_DEB_NIV_OBLIGATION_EXPEDITION);
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("EXPORT_PRO_DEB_CATEG_FRAISDEPORT").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print $formother->select_categories(0, $conf->global->EXPORT_PRO_DEB_CATEG_FRAISDEPORT, 'TParamProDeb[EXPORT_PRO_DEB_CATEG_FRAISDEPORT]');
	print '</td></tr>';

	print '</table>';

	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction">';
	print '<input type="submit" name="bt_save" class="butAction" value="'.$langs->trans('Save').'" />';
	print '</div>';
	print '</div>';

	print '</form>';
	
	
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="save">';

	$var=false;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameters").' (DES)</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
	print '</tr>';
	
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("EXPORT_PRO_DES_NUM_DECLARATION").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print $atmForm->texte('','TParamProDeb[EXPORT_PRO_DES_NUM_DECLARATION]',$conf->global->EXPORT_PRO_DES_NUM_DECLARATION,30,255);
	print '</td></tr>';
	
	print '</table>';
	
	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction">';
	print '<input type="submit" name="bt_save" class="butAction" value="'.$langs->trans('Save').'" />';
	print '</div>';
	print '</div>';
	
	print '</form>';
	
}

llxFooter();
$db->close();