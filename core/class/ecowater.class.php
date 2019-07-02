<?php

/* This file is part of Jeedom.
 *
 * JEEDOM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__ . '/../../../../core/php/core.inc.php';
require_once __DIR__ . '/../../3rdparty/ecowater_api.class.php';

class ecowater extends eqLogic {
    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */
    public static function postConfig_password() {
        ecowater::force_detect_softners();
    }

    public static function force_detect_softners() {
        // Initialisation de la connexion
        log::add('ecowater','info','force_detect_softners');
        if ( config::byKey('account', 'ecowater') != "" || config::byKey('password', 'ecowater') != "" )
        {
            $session_ecowater = new ecowater_api();
	    $session_ecowater->login(config::byKey('account', 'ecowater'), config::byKey('password', 'ecowater'));
	    $tok = $session_ecowater->get_Token();
	    $content = $session_ecowater->GetSofteners();
            $ListOfSoftners = json_decode($content, true);

	    $i=0;
	    if (!is_array($ListOfSoftners) ) {
                log::add('ecowater','info','No Softtner associated to this account ??');
		return true;
	    }
						                  }
            foreach ($ListOfSoftners as $Softner )
            {
                $dsn=$Softner["device"]["dsn"];
		$id=$Softner["device"]["key"];
	        $details = $session_ecowater->GetDetailSofteners($dsn);
                $LisDetailsSoftner = json_decode($details, true);
            	foreach ($LisDetailsSoftner as $DetailsSoftner )
		{
			if ($DetailsSoftner["datum"]["key"] == "nickname")
		            $nickname = $DetailsSoftner["datum"]["value"];
			if ($DetailsSoftner["datum"]["key"] == "modelDesc")
		            $modelDesc = $DetailsSoftner["datum"]["value"];
			if ($DetailsSoftner["datum"]["key"] == "systemType")
		            $systemType = $DetailsSoftner["datum"]["value"];
		}
                if ( ! is_object(self::byLogicalId($id, 'ecowater')) ) {
                log::add('ecowater','info','adding new Softtner : '.$dsn);
                    $eqLogic = new ecowater();
                    $eqLogic->setLogicalId($id);
                    $eqLogic->setName($nickname);
                    $eqLogic->setEqType_name('ecowater');
                    $eqLogic->setIsEnable(1);
                    $eqLogic->setIsVisible(1);
		    $eqLogic->setConfiguration('DSN',$dsn);
                log::add('ecowater','info','adding new Softtner .. ');
                    $eqLogic->setConfiguration('Maxtreated',10);
                log::add('ecowater','info','adding new Softtner ... ');
		    $eqLogic->setConfiguration('Token',$tok);
		    //$eqLogic->setStatus('DSN', $dsn);
		    $eqLogic->setConfiguration('modelDesc',$modelDesc);
		    $eqLogic->setConfiguration('systemType',$systemType);
                    $eqLogic->save();
                    $eqLogic->pull();
                    $eqLogic->save();
		}

            }
        }

    public function postInsert()
    {
        $this->postUpdate();
        $this->scan();
    }

    private function getListeDefaultCommandes()
    {
        return array(   "out_of_salt_estimate_days" => array('Recharger Sel', 'info', 'numeric', "jours", 0, "GENERIC_INFO", 'jauge', 'jauge', 'A',1,365),
			"gallons_used_today" => array('Ce jour', 'info', 'numeric', "litres", 0, "GENERIC_INFO", 'jauge', 'jauge', 'B',3.785,1000),
                        "avg_daily_use_gals" => array('Moyenne', 'info', 'numeric', "litres", 0, "GENERIC_INFO", 'jauge', 'jauge', 'C',3.785,1000),
			"salt_level_tenths" => array('Niveau Sel', 'info', 'numeric', "/10", 0, "GENERIC_INFO", 'jauge', 'jauge', 'D',0.1,10),
			"treated_water_avail_gals" => array('Eau Disponible','info','numeric',"litres",0,"GENERIC_INFO",'jauge','jauge','E',3.785,4000),
			"connection_status" => array('Connection','info','binary','',0,"GENERIC_INFO",'badge','badge','F',0,0),
			"regen_status_enum" => array('Commande', 'action', 'select', "", 0, "GENERIC_ACTION", '', '', '1|'.__('progammer une régénération',__FILE__).';2|'.__('Régénérer maintenant',__FILE__))

        );
    }

    public function postUpdate()
    {
        foreach( $this->getListeDefaultCommandes() as $id => $data)
	{
	    list($name, $type, $subtype, $unit, $invertBinary, $generic_type, $template_dashboard, $template_mobile, $listValue, $multiplier,$maxValue) = $data;
            $cmd = $this->getCmd(null, $id);
            if ( ! is_object($cmd) ) {
                $cmd = new ecowaterCmd();
                $cmd->setName($name);
                $cmd->setEqLogic_id($this->getId());
		$cmd->setType($type);
		$cmd->setUnite($unit);
		$cmd->setIsHistorized(1);
                $cmd->setSubType($subtype);
                $cmd->setLogicalId($id);
                       // $cmd->setCollectDate('');
                if ( $listValue != "" )
                {
                    $cmd->setConfiguration('listValue', $listValue);
                }
                $cmd->setDisplay('invertBinary',$invertBinary);
                $cmd->setConfiguration('maxValue',$maxValue);
		$cmd->setDisplay('generic_type', $generic_type);
                $cmd->setTemplate('dashboard', $template_dashboard);
                $cmd->setTemplate('mobile', $template_mobile);
                $cmd->save();
            }
            else
            {
                if ( $cmd->getType() == "" )
                {
                    $cmd->setType($type);
                }
                if ( $cmd->getSubType() == "" )
                {
                    $cmd->setSubType($subtype);
                }
                if ( $cmd->getDisplay('invertBinary') == "" )
                {
                    $cmd->setDisplay('invertBinary',$invertBinary);
                }
                if ( $cmd->getDisplay('generic_type') == "" )
                {
                    $cmd->setDisplay('generic_type', $generic_type);
                }
                if ( $cmd->getDisplay('dashboard') == "" )
                {
                    $cmd->setTemplate('dashboard', $template_dashboard);
                }
                if ( $cmd->getDisplay('mobile') == "" )
                {
                    $cmd->setTemplate('mobile', $template_mobile);
                }
                if ( $listValue != "" )
                {
                    $cmd->setConfiguration('listValue', $listValue);
                }
                $cmd->save();
            }
        }
    }

    public function preRemove() {
    }

    public static function pull() {
            foreach (self::byType('ecowater') as $eqLogic) {
                $eqLogic->scan();
            }
    }

    public function scan() {
	    	
        $session_ecowater = new ecowater_api();
        $session_ecowater->set_Token($this->getConfiguration('Token'));
        if ( $this->getIsEnable() ) {
	    $session_ecowater->login(config::byKey('account', 'ecowater'), config::byKey('password', 'ecowater'));
	    $status = $session_ecowater->get_status($this->getLogicalId());
	    $this->setConfiguration('Token', $session_ecowater->get_Token());
	    $DataEcowater = $session_ecowater->GetData($this->getLogicalId());
	    $Properties = json_decode($DataEcowater, true);
             
            foreach ($Properties as $property )
	    {
                foreach( $this->getListeDefaultCommandes() as $id => $data)
                {
	            list($name, $type, $subtype, $unit, $invertBinary, $generic_type, $template_dashboard, $template_mobile, $listValue, $multiplier,$maxValue) = $data;
	            if ($id == $property["property"]["name"]){
                        $cmd = $this->getCmd(null, $id);
                        if ($id == "avg_daily_use_gals"){
                           $max_avg_daily_use_gals = (intval(intval($property["property"]["value"] * $multiplier)/100) + 1)*100;
                           $cmd->setConfiguration('maxValue',$max_avg_daily_use_gals);
	                }
	                if ($id == "gallons_used_today"){
	                   if ( $max_avg_daily_use_gals > 0){
                              $cmd->setConfiguration('maxValue',$max_avg_daily_use_gals);
	                   } else {
                              $max_gallons_used_today = (intval(intval($property["property"]["value"] * $multiplier)/100) + 1)*100;
			      $cmd->setConfiguration('maxValue',$gallons_used_today);
			   }
	                }
			if ($id == "treated_water_avail_gals"){
                                $previous_max_treated_water_avail = intval($this->getConfiguration('Maxtreated'));
				$max_treated_water_avail_gals = (intval(intval($property["property"]["value"] * $multiplier)/100) + 1)*100;
				if ($max_treated_water_avail_gals > $previous_max_treated_water_avail){
                                    $this->setConfiguration('Maxtreated',$max_treated_water_avail_gals);
                                    $cmd->setConfiguration('maxValue',$max_treated_water_avail_gals);
				    log::add('ecowater','info',"  set max =  ".$max_treated_water_avail_gals);
                                    $this->save();
				}
			}

                        if ( $type != "action" )
                        {
                            if ($id == "gallons_used_today"){
	                        log::add('ecowater','info',"  gallons_used_today =  ".intval($property["property"]["value"] * $multiplier));
	                    }
                                        
	                    if ($ubtype = 'numeric'){
	                        $this->checkAndUpdateCmd($id,intval($property["property"]["value"] * $multiplier));
	                    } else {
	                        $this->checkAndUpdateCmd($id,intval($property["property"]["value"]));
	                    }
  	                }
		    }
	        }
	    }
	    $content = $session_ecowater->PostProperty($this->getConfiguration('DSN'),"get_frequent_data","",1,"ENUM");
            $ListOfSoftners = json_decode($content, true);

            foreach ($ListOfSoftners as $Softner )
            {
		if ($this->getLogicalId() == $Softner["device"]["key"]){
			    if ($Softner["device"]["connection_status"] == "Online")
				    $this->checkAndUpdateCmd("connection_status",1);
			    else
				    $this->checkAndUpdateCmd("connection_status",0);
                        log::add('ecowater','info',"connection_status =  ".$Softner["device"]["connection_status"]);
                }
            }
	    $content = $session_ecowater->RefreshSofteners();
            $this->save();
	    }
        //$session_ecowater->logOut();
    }
}

class ecowaterCmd extends cmd 
{
    /*     * *************************Attributs****************************** */
	public function execute($_options = null) {

        if ( $this->getLogicalId() == 'regen_status_enum' && $_options['select'] != "" )
        {
            log::add('ecowater','info',"Commande execute ".$this->getLogicalId()." ".$_options['select']);
            $eqLogic = $this->getEqLogic();
            $mydsn = $eqLogic->getConfiguration('DSN');
            $session_ecowater = new ecowater_api();
	    $session_ecowater->login(config::byKey('account', 'ecowater'), config::byKey('password', 'ecowater'));
            $session_ecowater->set_Token($this->getConfiguration('Token'));
            $order = $session_ecowater->PostProperty($mydsn,$this->getLogicalId(),"",$_options['select'],"ENUM");
        }
    }


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*     * **********************Getteur Setteur*************************** */
}
 
