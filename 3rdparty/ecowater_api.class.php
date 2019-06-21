<?php
class ecowater_api {

	##protected $url_api_im = 'https://iam-api.dss.ecowatergroup.net/api/v3/';
	##protected $url_api_track = 'https://amc-api.dss.ecowatergroup.net/v1/';
	protected $username;
	protected $password;
	protected $token;
	protected $provider;

    function login($username, $password)
	{
        $this->username = $username;
        $this->password = $password;
		$fields["user"]["email"] = $this->username;
		$fields["user"]["password"] = $this->password;
		$fields["user"]["application"]["app_id"]="ecowater-mobile-id";
		$fields["user"]["application"]["app_secret"]="ecowater-mobile-9026832";
		$this->token = "";
		$url = "https://user-field.aylanetworks.com/users/sign_in.json";
		$result = $this->post_api($url,"token", $fields);
		if ( $result !== false )
		{
			$this->token = $result->access_token;
			return true;
		}
		return false;
	}
    function GetSofteners()
	{
		$url = "https://ads-field.aylanetworks.com/apiv1/devices.json";
		$result = $this->get_api($url,"token", $fields);
		return $result;
	}
    function PostProperty($dsn,$property,$valuestring,$valueint,$valuetype)
	{
        log::add('ecowater','info','PostProperty');
		$url = "https://ads-field.aylanetworks.com/apiv1/dsns/".$dsn."/properties/".$property."/datapoints.json";
		##if ($valuetype == "STRING")
		##	$fields["datapoint"]["value"] = $valuestring;
		##if (($valuetype <> "STRING"))
		##        $fields["datapoint"]["value"] = 1;
		$fields["datapoint"]["value"] = "1";
		$result = $this->post_api($url,"token", $fields);
        log::add('ecowater','info',$result);
        log::add('ecowater','info',$url);
		return $result;
	}
    function RefreshSofteners()
	{
		$url = "https://ads-field.aylanetworks.com/apiv1/dsns/AC000W000064014/properties/get_frequent_data/datapoints.json";
		$fields["datapoint"]["value"] = 1;
		$result = $this->post_api($url,"token", $fields);
		return $result;
	}
    function GetDetailSofteners($dsn)
	{
		$url = "https://ads-field.aylanetworks.com/apiv1/dsns/".$dsn."/data.json";
		$result = $this->get_api($url,"token", $fields);
		return $result;
	}
    function GetData($key)
	{
		$url = "https://ads-field.aylanetworks.com/apiv1/devices/".$key."/properties.json?";
		$url = $url."names[]=get_frequent_data&";
		$url = $url."names[]=avg_daily_use_gals&";
		$url = $url."names[]=gallons_used_today&";
		$url = $url."names[]=treated_water_avail_gals&";
		$url = $url."names[]=model_id&";
		$url = $url."names[]=system_type&";
		$url = $url."names[]=out_of_salt_estimate_days&";
		$url = $url."names[]=regen_enable_enum&";
		$url = $url."names[]=regen_status_enum&";
		$url = $url."names[]=regen_time_secs&";
		$url = $url."names[]=salt_level_tenths&";
		$url = $url."names[]=volume_unit_enum&";
		$url = $url."names[]=weight_unit_enum&";
		$url = $url."names[]=avg_daily_gal_tenths&";
		$url = $url."names[]=days_in_operation&";
		$url = $url."names[]=dispensed_gal&";
		$url = $url."names[]=filter_life_remaining_days&";
		$url = $url."names[]=tds_removal_percent";
		$result = $this->get_api($url,"token", $fields);
		return $result;
	}



	private function get_headers($fields = null)
	{
		if ( isset($this->token) )
		{
			$generique_headers = array(
			   'Content-type: application/json',
			   'Accept: application/json',
			   'Authorization: auth_token '.$this->token
			);
		}
		else
		{
			$generique_headers = array(
			   'Content-type: application/json',
			   'Accept: application/json'
			   );
		}
		if ( isset($fields) )
		{
			$custom_headers = array('Content-Length: '.strlen(json_encode ($fields)));
		}
		else
		{
			$custom_headers = array();
		}
		return array_merge($generique_headers, $custom_headers);
	}

	private function post_api($url,$page, $fields = null)
	{
		$session = curl_init();

		curl_setopt($session, CURLOPT_URL, $url);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers($fields));
		curl_setopt($session, CURLOPT_POST, true);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$input_json = json_encode ($fields);
        log::add('ecowater','info',$input_json);
		if ( isset($fields) )
		{
			curl_setopt($session, CURLOPT_POSTFIELDS, $input_json);
		}
		$json = curl_exec($session);
		curl_close($session);
        log::add('ecowater','info',$json);
		return json_decode($json);
	}

	private function get_api($url,$page, $fields = null)
	{
		$session = curl_init();

		curl_setopt($session, CURLOPT_URL, $url);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers($fields));
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		if ( isset($fields) )
		{
			curl_setopt($session, CURLOPT_POSTFIELDS, json_encode($fields));
		}
		$json = curl_exec($session);
		curl_close($session);
		return $json;
	}

	private function del_api($page)
	{
		$session = curl_init();

		curl_setopt($session, CURLOPT_URL, $this->url_api_im . $page);
		curl_setopt($session, CURLOPT_HTTPHEADER, $this->get_headers());
		curl_setopt($session, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		$json = curl_exec($session);
		curl_close($session);
		return json_decode($json);
	}

    function logout()
	{
		$result = $this->del_api("token/".$this->token);
		if ( $result !== false )
		{
			unset($this->token);
			unset($this->provider);
			return true;
		}
		return false;
	}
	
	function get_status($mover_id)
	{
		
		$url = "https://ads-field.aylanetworks.com/apiv1/devices.json";
		return $this->get_api($url,"mowers/".$mover_id."/status");
	}

	function get_Token()
	{
		return $this->token;
	}

	function set_Token($tok)
	{
		$this->token = $tok;
	}

	function control($mover_id, $command)
	{
		if ( in_array($command, array('REGEN', 'REGEN-NEXT') ) )
		{
		$url = "https://ads-field.aylanetworks.com/apiv1/devices.json";
			return $this->get_api($url,"mowers/".$mover_id."/control", array("action" => $command));
		}
	}
}
?>
