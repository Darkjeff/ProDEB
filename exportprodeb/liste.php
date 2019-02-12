<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
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
 *      \file       htdocs/custom/exportprodeb/liste.php
 *      \ingroup    Exportprodeb
 *      \brief      Page liste des invoice UE
 */

$res = 0;
if (!$res && file_exists("../main.inc.php"))
    $res = @include("../main.inc.php");
if (!$res && file_exists("../../main.inc.php"))
    $res = @include("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php"))
    $res = @include("../../../main.inc.php");
if (!$res && file_exists("../../../../main.inc.php"))
    $res = @include("../../../../main.inc.php");
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php"))
    $res = @include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (!$res && file_exists("../../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (!$res)
    die("Include of main fails");
    
    

//require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

//dol_include_once('/lcr/class/bonlcr.class.php');
//dol_include_once('/lcr/class/lignelcr.class.php');

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// langs 

$langs->loadLangs(array('bills','banks','companies','categories'));


// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;


// Get supervariables

$mesg = '';
$action = GETPOST('action','aZ09');
$cancel = GETPOST('cancel','alpha');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'invoicedeblist';   // To manage different context of search





$page = GETPOST('page','int');
$sortorder = ((GETPOST('sortorder','alpha')=="")) ? "DESC" : GETPOST('sortorder','alpha');
$sortfield = ((GETPOST('sortfield','alpha')=="")) ? "f.datef" : GETPOST('sortfield','alpha');
$search_line = GETPOST('search_ligne','alpha');
$search_invoice = GETPOST('$search_invoice','alpha');
$search_total = GETPOST('search_total','alpha');
$search_societe = GETPOST('search_societe','alpha');
$statut = GETPOST('statut','int');
$search_day=GETPOST("search_day","int");
$search_month=GETPOST("search_month","int");
$search_year=GETPOST("search_year","int");


// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All test are required to be compatible with all browsers
	{
		$search_line = '';
		$search_bon = '';
		$search_code = '';
		$search_societe = '';
		$search_day = '';
		$search_month = '';
		$search_year = '';
		$search_invoice = '';
	}







// add choice column
$arrayfields=array(
	'f.facnumber'=>array('label'=>$langs->trans("ref"), 'checked'=>1),
    'f.total'=>array('label'=>$langs->trans("InvoiceTotal"), 'checked'=>1),
	'f.datef'=>array('label'=>$langs->trans("DateInvoice"), 'checked'=>1),
    'f.facnumber'=>array('label'=>$langs->trans("BankdraftBills"), 'checked'=>1),
	's.nom'=>array('label'=>$langs->trans("Name"), 'checked'=>1),
	's.tva_intra'=>array('label'=>$langs->trans("TVA_INTRA"), 'checked'=>1),
	'c.label'=>array('label'=>$langs->trans("country"), 'checked'=>1)
	
	);

//////////////


//$bon=new BonLcr($db,"");
//$ligne=new LigneLcr($db,$user);

$offset = $conf->liste_limit * $page ;

//actions

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha')) { $massaction=''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

/**
 *  View
 */
$form = new Form($db);
$formother = new FormOther($db);

llxHeader('',$langs->trans("BankdraftLines"));

$sql = "SELECT f.rowid, f.facnumber, f.total as total_ht, f.datef ";
$sql.= " , s.rowid as id_client, s.nom, s.zip, s.fk_pays, s.tva_intra ";
$sql.= " , c.code, c.label ";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql.= " , ".MAIN_DB_PREFIX."societe as s";
$sql.= " , ".MAIN_DB_PREFIX."c_country as c";
$sql.= " WHERE s.rowid = f.fk_soc";
$sql.= " AND c.rowid = s.fk_pays";
$sql.= " AND (c.code ='AT' OR c.code ='BE' OR c.code ='BG' OR c.code ='CY' OR c.code ='CZ' ";
$sql.= " OR c.code ='DE' OR c.code ='DK' OR c.code ='EE' OR c.code ='ES' OR c.code ='FI' ";
$sql.= " OR c.code ='GB' OR c.code ='GR' OR c.code ='HR' OR c.code ='NL' OR c.code ='HU' ";
$sql.= " OR c.code ='IE' OR c.code ='IM' OR c.code ='IT' OR c.code ='LT' OR c.code ='LU' ";
$sql.= " OR c.code ='LV' OR c.code ='MC' OR c.code ='MT' OR c.code ='PL' OR c.code ='RO' ";
$sql.= " OR c.code ='SE' OR c.code ='SK' OR c.code ='SI' OR c.code ='PT' OR c.code ='UK' )";
$sql.= " AND f.entity = ".$conf->entity;
if ($socid) $sql.= " AND s.rowid = ".$socid;
if ($search_line)
{
    $sql.= " AND f.rowid = '".$search_line."'";
}


if (strlen(trim($search_invoice))) {
    $sql .= natural_search("f.facnumber", $search_invoice);
}
if ($search_total)
{
    $sql.= " AND f.total LIKE '%".$search_total."%'";
}
if ($search_societe)
{
    $sql .= " AND s.nom LIKE '%".$search_societe."%'";
}

if ($search_month > 0)
{
	if ($search_year > 0 && empty($search_day))
		$sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($search_year,$search_month,false))."' AND '".$db->idate(dol_get_last_day($search_year,$search_month,false))."'";
		else if ($search_year > 0 && ! empty($search_day))
			$sql.= " AND f.datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_month, $search_day, $search_year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_month, $search_day, $search_year))."'";
			else
				$sql.= " AND date_format(f.datef, '%m') = '".$db->escape($search_month)."'";
}
else if ($search_year > 0)
{
	$sql.= " AND f.datef BETWEEN '".$db->idate(dol_get_first_day($search_year,1,false))."' AND '".$db->idate(dol_get_last_day($search_year,12,false))."'";
}


$sql.=$db->order($sortfield,$sortorder);
$sql.=$db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);



if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;

    $urladd = "&amp;statut=".$statut;
    $urladd .= "&amp;search_bon=".$search_bon;

    print_barre_liste($langs->trans("ExportProDebLines"), $page, "liste.php", $urladd, $sortfield, $sortorder, '', $num);

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
    $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);
	
	
	print '<form method="POST" id="searchFormList" action="' . $_SERVER["PHP_SELF"] . '">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	
    print"\n<!-- debut table -->\n";
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";
	
	

    print '<tr class="liste_titre_filter">';
    if (! empty($arrayfields['f.rowid']['checked']))				print '<td class="liste_titre">'.$langs->trans("BankdraftLine").'</td>';
    if (! empty($arrayfields['f.facnumber']['checked']))			print_liste_field_titre($langs->trans("BankdraftBills"),$_SERVER["PHP_SELF"],"f.facnumber",'',$urladd);
    if (! empty($arrayfields['s.nom']['checked']))					print_liste_field_titre($langs->trans("BankdraftCompany"),$_SERVER["PHP_SELF"],"s.nom");
    if (! empty($arrayfields['s.tva_intra']['checked']))			print_liste_field_titre($langs->trans("BankdraftCustomerCode"),$_SERVER["PHP_SELF"],"s.tva_intra",'','','align="center"');
	if (! empty($arrayfields['f.datef']['checked']))				print_liste_field_titre($langs->trans("DateInvoice"),$_SERVER["PHP_SELF"],"f.datef","","",'align="center"');
    if (! empty($arrayfields['f.total']['checked']))			print_liste_field_titre($langs->trans("BankdraftRequestAmountTtc"),$_SERVER["PHP_SELF"],"f.total","","",'align="right"');
	if (! empty($arrayfields['c.label']['checked']))			print_liste_field_titre($langs->trans("BCountry"),$_SERVER["PHP_SELF"],"c.label","","",'align="right"');
    print '<td class="liste_titre">&nbsp;</td>';
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
    print '</tr>';

    //print '<form action="liste.php" method="GET">';
    print '<tr class="liste_titre">';
    if (! empty($arrayfields['f.rowid']['checked']))				print '<td class="liste_titre"><input type="text" class="flat" name="search_ligne" value="'. $search_line.'" size="6"></td>';
    if (! empty($arrayfields['f.facnumber']['checked']))			print '<td class="liste_titre"><input type="text" class="flat" name="search_invoice" value="'. $search_invoice.'" size="8"></td>';
	if (! empty($arrayfields['s.nom']['checked']))					print '<td class="liste_titre"><input type="text" class="flat" name="search_societe" value="'. $search_societe.'" size="12"></td>';
    if (! empty($arrayfields['s.tva_intra']['checked']))			print '<td class="liste_titre" align="center"><input type="text" class="flat" name="search_code" value="'. $search_code.'" size="8"></td>';
	    if (! empty($arrayfields['f.datef']['checked']))
	{
	print '<td class="liste_titre"><input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'"size="8">';
	$formother->select_year($search_year,'search_year',1, 20, 5);
	print '</td>';
	}
	/*	{
	print '<td class="liste_titre center nowraponall">';
	print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
   	$formother->select_year($search_year,'search_year',1, 20, 5);
	print '</td>';
	}
	*/
	if (! empty($arrayfields['c.label']['checked']))					print '<td class="liste_titre"><input type="text" class="flat" name="search_societe" value="'. $search_societe.'" size="12"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
    //print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
    print '<td align="right" class="liste_titre">';
	$searchpicto=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
	print $searchpicto;
	print '</td>';
	
	print '</tr>';
	
	print '
	<script type="text/javascript">
		function launch_export() {
			$("div.fiche form input[name=\"action\"]").val("export_file");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
	</script>';
	print '<div class="tabsAction tabsActionNoBottom">';
	//print '<input type="button" class="butAction" name="export_file" value="' . $langs->trans("ExportLCR") . '" onclick="launch_export();" />';
	print '</div>';
	
	
    print '</form>';

    $var=True;

    while ($i < min($num,$conf->liste_limit))
    {
        $obj = $db->fetch_object($result);

        print '<tr class="oddeven">';

		// reference
		if (! empty($arrayfields['f.rowid']['checked']))
		{
			print "<td>";
		
		
        print "<tr ".$bc[$var]."><td>";

        print $ligne->LibStatut($obj->statut_ligne,2);
        print "&nbsp;";
        print '<a href="'.dol_buildpath('/lcr/ligne.php?id='.$obj->rowid_ligne,1).'">';
        print substr('000000'.$obj->rowid_ligne, -6);
        print '</a></td>';
		}
		
		

		// ref facture
		if (! empty($arrayfields['f.facnumber']['checked']))
		{	
        print '<td><a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$obj->facid.'">';
        print img_object($langs->trans("ShowBill"),"bill");
        print '&nbsp;<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$obj->facid.'">'.$obj->facnumber."</a></td>\n";
        print '</a></td>';
		}
		
			
		// societe
		if (! empty($arrayfields['s.nom']['checked']))
		{
        print '<td><a href="'.dol_buildpath('/comm/card.php?socid='.$obj->socid,1).'">'.$obj->nom."</a></td>\n";
		}
		
		//code tva intra
		if (! empty($arrayfields['s.tva_intra']['checked']))
		{
        print '<td align="center"><a href="'.dol_buildpath('/comm/card.php?socid='.$obj->socid,1).'">'.$obj->tva_intra."</a></td>\n";
		}
		
		// countruy
		if (! empty($arrayfields['c.label']['checked']))
		{
        print '<td align="left">'.$obj->label."</td>\n";
		}
		
		// date facture
		if (! empty($arrayfields['f.datef']['checked']))
		{
		print '<td align="left">'.$obj->datef."</td>\n";
		}
		
		// total facture
		if (! empty($arrayfields['f.total']['checked']))
		{
		print '<td align="left">'.$obj->total_ht."</td>\n";
		}
		
		
		


        print '<td>&nbsp;</td>';

        print "</tr>\n";
        $i++;
    }
	
    print "</table>";
    $db->free($result);
}
else
{
    dol_print_error($db);
}

$db->close();

llxFooter();
