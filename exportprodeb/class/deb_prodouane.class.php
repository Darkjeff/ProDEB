<?php

class TDebProdouane extends TObjetStd {
	
	static $TType = array(
							'introduction'=>'Introduction'
							,'expedition'=>'Expédition'
						);
	
	function __construct(&$ATMdb) {
		
		$this->ATMdb = $ATMdb;
		$this->errors = array();
		parent::set_table(MAIN_DB_PREFIX.'deb_prodouane');
		parent::add_champs('numero_declaration,entity','type=entier;');
		parent::add_champs('type_declaration,periode,mode','type=chaine;');
		parent::add_champs('content_xml','type=text;');
		parent::add_champs('exporttype', array('type'=>'string', 'size'=>'10'));
		parent::start();
		parent::_init_vars();
		
		$this->exporttype = 'deb';
	}
	
	
	/**
	 * @param $mode O pour création, R pour régénération (apparemment toujours 0 dans la cadre des échanges XML selon la doc)
	 * @param $type introduction ou expedition
	 */
	function getXML($mode='O', $type='introduction', $periode_reference='') {

		global $db, $conf, $mysoc;
		
		/**************Construction de quelques variables********************/
		$party_id = substr(strtr($mysoc->tva_intra, array(' '=>'')), 0, 4).$mysoc->idprof2;
		$declarant = substr($mysoc->managers, 0, 14);
		$id_declaration = self::getNumeroDeclaration($this->numero_declaration);
		/********************************************************************/
		
		/**************Construction du fichier XML***************************/
		$e = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" standalone="yes"?><INSTAT></INSTAT>');
		
		$enveloppe = $e->addChild('Envelope');
		$enveloppe->addChild('envelopeId', $conf->global->EXPORT_PRO_DEB_NUM_AGREMENT);
		$date_time = $enveloppe->addChild('DateTime');
		$date_time->addChild('date', date('Y-m-d'));
		$date_time->addChild('time', date('H:i:s'));
		$party = $enveloppe->addChild('Party');
		$party->addAttribute('partType', $conf->global->EXPORT_PRO_DEB_TYPE_ACTEUR);
		$party->addAttribute('partyRole', $conf->global->EXPORT_PRO_DEB_ROLE_ACTEUR);
		$party->addChild('partyId', $party_id);
		$party->addChild('partyName', $declarant);
		$enveloppe->addChild('softwareUsed', 'Dolibarr');
		$declaration = $enveloppe->addChild('Declaration');
		$declaration->addChild('declarationId', $id_declaration);
		$declaration->addChild('referencePeriod', $periode_reference);
		if($conf->global->EXPORT_PRO_DEB_TYPE_ACTEUR === 'PSI') $psiId = $party_id;
		else $psiId = 'NA';
		$declaration->addChild('PSIId', $psiId);
		$function = $declaration->addChild('Function');
		$functionCode = $function->addChild('functionCode', $mode);
		$declaration->addChild('declarationTypeCode', $conf->global->{'EXPORT_PRO_DEB_NIV_OBLIGATION_'.strtoupper($type)});
		$declaration->addChild('flowCode', ($type == 'introduction' ? 'A' : 'D'));
		$declaration->addChild('currencyCode', $conf->global->MAIN_MONNAIE);
		/********************************************************************/
		
		/**************Ajout des lignes de factures**************************/
		$res = self::addItemsFact($declaration, $type, $periode_reference);
		/********************************************************************/
		
		$this->errors = array_unique($this->errors);

		if(!empty($res)) return $e->asXML();
		else return 0;
		
	}
	
	// $type_declaration tjrs = "expedition" à voir si ça évolue
	function getXMLDes($period_year, $period_month, $type_declaration='expedition')
	{
		global $db, $conf, $mysoc;
		
		
		$e = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><fichier_des></fichier_des>');
		
		$declaration_des = $e->addChild('declaration_des');
		$declaration_des->addChild('num_des', self::getNumeroDeclaration($this->numero_declaration));
		$declaration_des->addChild('num_tvaFr', $mysoc->tva_intra); // /^FR[a-Z0-9]{2}[0-9]{9}$/  // Doit faire 13 caractères
		$declaration_des->addChild('mois_des', $period_month);
		$declaration_des->addChild('an_des', $period_year);
		
		
		/**************Ajout des lignes de factures**************************/
		$res = self::addItemsFact($declaration_des, $type_declaration, $period_year.'-'.$period_month, 'des');
		/********************************************************************/
		
		$this->errors = array_unique($this->errors);

		if(!empty($res)) return $e->asXML();
		else return 0;
	}
	
	function addItemsFact(&$declaration, $type, $periode_reference, $exporttype='deb') {
		
		global $db, $conf;
		
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		
		$sql = $this->getSQLFactLines($type, $periode_reference, $exporttype);
		
		$resql = $db->query($sql);
		
		if($resql) {
			$i=1;
			
			if(empty($resql->num_rows)) {
				$this->errors[] = 'Aucune donnée pour cette période';
				var_dump ($resql) ;
				return 0;
			}
			
			if($exporttype == 'deb' && $conf->global->EXPORT_PRO_DEB_CATEG_FRAISDEPORT > 0) {
				$categ_fraisdeport = new Categorie($db);
				$categ_fraisdeport->fetch($conf->global->EXPORT_PRO_DEB_CATEG_FRAISDEPORT);
				$TLinesFraisDePort = array();
			}
			
			while($res = $db->fetch_object($resql)) {
				
				if ($exporttype == 'des')
				{
					$this->addItemXMlDes($declaration, $res, '', $i);
				}
				else
				{
					if(empty($res->fk_pays)) {
						// On n'arrête pas la boucle car on veut savoir quels sont tous les tiers qui n'ont pas de pays renseigné
						$this->errors[] = 'Pays non renseigné pour le tiers <a href="'.dol_buildpath('/societe/soc.php',1).'?socid='.$res->id_client.'">'.$res->nom.'</a>';
					} else {
						if($conf->global->EXPORT_PRO_DEB_CATEG_FRAISDEPORT > 0 && $categ_fraisdeport->containsObject('product', $res->id_prod)) {
							$TLinesFraisDePort[] = $res;
						} else $this->addItemXMl($declaration, $res, '', $i);
					}	
				}
				
				$i++;
				
			}
			
			if(!empty($TLinesFraisDePort)) $this->addItemFraisDePort($declaration, $TLinesFraisDePort, $type, $categ_fraisdeport, $i);

			if(count($this->errors) > 0) return 0;
			
		}
		
		return 1;
		
	}

	function getSQLFactLines($type, $periode_reference, $exporttype='deb') {
		
		global $mysoc, $conf;
		
		if($type=='expedition' || $exporttype=='des') {
			$sql = 'SELECT f.facnumber, f.total as total_ht';
			$table = 'facture';
			$table_extraf = 'facture_extrafields';
			$tabledet = 'facturedet';
			$field_link = 'fk_facture';
		}
		else { // Introduction
			$sql = 'SELECT f.ref_supplier as facnumber, f.total_ht';
			$table = 'facture_fourn';
			$table_extraf = 'facture_fourn_extrafields';
			$tabledet = 'facture_fourn_det';
			$field_link = 'fk_facture_fourn';
		}
		$sql.= ", l.fk_product, l.qty";
		
		$sql.= ", p.weight, p.rowid as id_prod, p.customcode ";
		
		
		$sql.= ", s.rowid as id_client, s.nom, s.zip, s.fk_pays, s.tva_intra ";
		$sql.= ", c.code ";
		$sql.= " FROM ".MAIN_DB_PREFIX.$tabledet." l ";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX.$table." f ON (f.rowid = l.".$field_link.")";
		
	// if no product for DES   FOR DEB need product 
		if( $exporttype=='des') {
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product p ON (p.rowid = l.fk_product) ";
		} else 	{
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."product p ON (p.rowid = l.fk_product) ";
		}
		
		
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON (s.rowid = f.fk_soc) "; 
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country c ON (c.rowid = s.fk_pays) ";
		$sql.= " WHERE f.fk_statut > 0 ";
		
		
		// add filtary on country adapt to france need to change for other country 
		$sql.= " AND (c.code ='AT' OR c.code ='BE' OR c.code ='BG' OR c.code ='CY' OR c.code ='CZ' ";
		$sql.= " OR c.code ='DE' OR c.code ='DK' OR c.code ='EE' OR c.code ='ES' OR c.code ='FI' ";
		$sql.= " OR c.code ='GB' OR c.code ='GR' OR c.code ='HR' OR c.code ='NL' OR c.code ='HU' ";
		$sql.= " OR c.code ='IE' OR c.code ='IM' OR c.code ='IT' OR c.code ='LT' OR c.code ='LU' ";
		$sql.= " OR c.code ='LV' OR c.code ='MC' OR c.code ='MT' OR c.code ='PL' OR c.code ='RO' ";
		$sql.= " OR c.code ='SE' OR c.code ='SK' OR c.code ='SI' OR c.code ='PT' OR c.code ='UK' )";
		$sql.= " AND f.entity = ".$conf->entity;		
		$sql.= " AND year(f.datef) =2018 ";
		
		///to do correct select year and month
		//$sql.= " AND year(f.datef) =  ".$period_year ;
		
		return $sql;
		
	}
	
	// remove ligne 189  AND l.product_type = '.($exporttype == 'des' ? 1 : 0).'
	// remove ligne 181   , ext.mode_transport
	//  LEFT JOIN '.MAIN_DB_PREFIX.$table_extraf.' ext ON (ext.fk_object = f.rowid)
	
	
	
	
	
	function addItemXMl(&$declaration, &$res, $code_douane_spe='', $i) {
		
		$item = $declaration->addChild('Item');
		$item->addChild('ItemNumber', $i);
		$cn8 = $item->addChild('CN8');
		if(empty($code_douane_spe)) $code_douane = $res->customcode;
		else $code_douane = $code_douane_spe;
		$cn8->addChild('CN8Code', $code_douane);
		if(!empty($res->tva_intra)) $item->addChild('partnerId', $res->tva_intra);
		$item->addChild('MSConsDestCode', $res->code); // code iso pays client
		$item->addChild('netMass', $res->weight * $res->qty); // Poids du produit
		$item->addChild('quantityInSU', $res->qty); // Quantité de produit dans la ligne
		$item->addChild('invoicedAmount', round($res->total_ht)); // Montant total ht de la facture (entier attendu)
		$item->addChild('invoicedNumber', $res->facnumber); // Numéro facture
		$item->addChild('statisticalProcedureCode', '11');
		$nature_of_transaction = $item->addChild('NatureOfTransaction');
		$nature_of_transaction->addChild('natureOfTransactionACode', 1);
		$nature_of_transaction->addChild('natureOfTransactionBCode', 1);
		$item->addChild('modeOfTransportCode', $res->mode_transport);
		$item->addChild('regionCode', substr($res->zip, 0, 2));
		
	}

	function addItemXMlDes($declaration, &$res, $code_douane_spe='', $i)
	{
		$item = $declaration->addChild('ligne_des');
		$item->addChild('numlin_des', $i);
		$item->addChild('valeur', round($res->total_ht)); // Montant total ht de la facture (entier attendu)
		$item->addChild('partner_des', $res->tva_intra); // Représente le numéro TVA du client étranger
	}
	
	/**
	 * Cette fonction ajoute un item en récupérant le code douane du produit ayant le plus haut montant dans la facture
	 */
	function addItemFraisDePort(&$declaration, &$TLinesFraisDePort, $type, &$categ_fraisdeport, $i) {
		
		global $db, $conf;
		
		if($type=='expedition') {
			$table = 'facture';
			$tabledet = 'facturedet';
			$field_link = 'fk_facture';
			$more_sql = 'f.facnumber';
		}
		else { // Introduction
			$table = 'facture_fourn';
			$tabledet = 'facture_fourn_det';
			$field_link = 'fk_facture_fourn';
			$more_sql = 'f.ref_supplier';
		}
		
		foreach($TLinesFraisDePort as $res) {
			
			$sql = 'SELECT p.customcode
					FROM '.MAIN_DB_PREFIX.$tabledet.' d
					INNER JOIN '.MAIN_DB_PREFIX.$table.' f ON (f.rowid = d.'.$field_link.')
					INNER JOIN '.MAIN_DB_PREFIX.'product p ON (p.rowid = d.fk_product)
					WHERE d.fk_product IS NOT NULL
					AND f.entity = '.$conf->entity.'
					AND '.$more_sql.' = "'.$res->facnumber.'"
					AND d.total_ht =
					(
						SELECT MAX(d.total_ht)
						FROM '.MAIN_DB_PREFIX.$tabledet.' d
						INNER JOIN '.MAIN_DB_PREFIX.$table.' f ON (f.rowid = d.'.$field_link.')
						WHERE d.fk_product IS NOT NULL
						AND '.$more_sql.' = "'.$res->facnumber.'"
						AND d.fk_product NOT IN
						(
							SELECT fk_product
							FROM '.MAIN_DB_PREFIX.'categorie_product
							WHERE fk_categorie = '.$categ_fraisdeport->id.'
						) 
					)';
			
			$resql = $db->query($sql);
			$ress = $db->fetch_object($resql);
			
			$this->addItemXMl($declaration, $res, $ress->customcode, $i);
			
			$i++;

		}
		
	}

	function getNextNumeroDeclaration() {
		
		global $db;
		$resql = $db->query('SELECT MAX(numero_declaration) as max_numero_declaration FROM '.$this->get_table().' WHERE exporttype="'.$this->exporttype.'"');
		if($resql) $res = $db->fetch_object($resql);
		
		return ($res->max_numero_declaration + 1);
		
	}

	// La doc impose que le numéro soit un entier positif d'un maximum de 6 caractères
	static function getNumeroDeclaration($numero) {
	
		return str_pad($numero, 6, 0, STR_PAD_LEFT);
		
	}

	function generateXMLFile() {
		
		$name = $this->periode.'.xml';
		$fname = sys_get_temp_dir().'/'.$name;
		$f = fopen($fname, 'w+');
		fwrite($f, $this->content_xml);
		fclose($f);
		
		header('Content-Description: File Transfer');
	    header('Content-Type: application/xml');
	    header('Content-Disposition: attachment; filename="'.$name.'"');
	    header('Expires: 0');
	    header('Cache-Control: must-revalidate');
	    header('Pragma: public');
	    header('Content-Length: ' . filesize($fname));
	    readfile($fname);
		exit;
		
	}
	
}
