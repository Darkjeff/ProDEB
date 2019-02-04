<?php
/*
 * Script créant et vérifiant que les champs requis s'ajoutent bien
 */

if(!defined('INC_FROM_DOLIBARR')) {
	define('INC_FROM_CRON_SCRIPT', true);

	require('../config.php');

}

dol_include_once('/exportprodeb/class/deb_prodouane.class.php');

$PDOdb=new TPDOdb;

$o=new TDebProdouane($PDOdb);
$o->init_db_by_vars($PDOdb);

$sql = 'UPDATE '.$o->get_table().' SET exporttype="deb" WHERE exporttype IS NULL';
$PDOdb->Execute($sql);