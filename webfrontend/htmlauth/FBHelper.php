<?php

require_once "loxberry_system.php";
require_once "loxberry_log.php";

$mylog = LBLog::newLog(array("name" => "FBHelper", "logdir" => $lbplogdir));

if (php_sapi_name() != 'cli') {
	LOGSTART("Web Request");
} else {
	LOGSTART("CLI Request");
}

//Get conffile
LOGINF("reading configuration");
$fritzloxconf = parse_ini_file("$lbpconfigdir/fritzlox.conf",True);

$FBIP = $fritzloxconf['general']['FritzboxIP'];
$FBLogin = $fritzloxconf['general']['FBLogin'];
$FBPass = urldecode($fritzloxconf['general']['FBPass']);
LOGINF("Done");

$WLAN = (isset($_GET['WLAN'])) ? $_GET['WLAN'] : "";
$cmd = (isset($_GET['cmd'])) ? $_GET['cmd'] : "";
$FBDECTAIN = (isset($_GET['DECTAIN'])) ? $_GET['DECTAIN'] : "";
$NewValue = (isset($_GET['value'])) ? $_GET['value'] : "";

if (php_sapi_name() == 'cli') {
	if ($argc == 2) {
		$cmd = $argv[1];
	} else {
		$cmd = "DECTgetSwitchList";
	}
}

function print_error($fault) {
	if ( isset($fault->detail->UPnPError)) {
		$errmsg = $fault->detail->UPnPError->errorDescription." (".$fault->detail->UPnPError->errorCode.")\n";
	} else {
		$errmsg = $fault->faultstring . " (" .$fault->faultcode . ")\n";
	}
	//print_r($fault);
	LOGERR("error while retrieving WAN infos");
	LOGDEB(print_r($fault,true));
	print "Fehler: ".$errmsg;
}

		function GetSessID () {
			global $FBIP, $FBSPort, $FBLogin, $FBPass;
			LOGINF("getting Session ID from Fritzbox");
			$stream = stream_context_create(array( 
				"ssl"=>array(     
					"verify_peer"=> false,     
					"verify_peer_name"=> false, ),
					'http' => array(     
						'timeout' => 30     
						)
					)
				);
			$cont = file_get_contents("https://$FBIP:$FBSPort/login_sid.lua",0,$stream);
			$cont = new SimpleXMLElement($cont);
			if ($cont->{"SID"} == 0) {
				$challenge = $cont->{"Challenge"};
				$response = $challenge."-".md5(mb_convert_encoding($challenge."-".$FBPass, "UTF-16LE", "UTF-8"));
				$cont = file_get_contents("https://$FBIP:$FBSPort/login_sid.lua?username=$FBLogin&response=$response",0,$stream);
				$cont = new SimpleXMLElement($cont);
				LOGDEB("FB Session ID: ".$cont->{"SID"});
				return $cont->{"SID"};
			}
		}
		
		function GetHTTPSPort () {
			global $FBIP, $FBLogin, $FBPass;
			LOGINF("getting https-Port from Fritzbox");
			$client = new SoapClient(
			null,
			array(
				'location' => "http://".$FBIP.":49000/upnp/control/x_remote",
				'uri'      => "urn:dslforum-org:service:X_AVM-DE_RemoteAccess:1",
				'noroot'   => False,
				'login'    => $FBLogin,
				'password' => $FBPass
			)
		);
		try {
		$res = $client->GetInfo();
		return $res["NewPort"];
		} catch (SoapFault $fault) {
			print_error($fault);
			return -1;
		}
	}
	
	function setHkrTemp ($ain, $temp, $system) {
			global $FBIP, $FBLogin, $FBPass, $FBSPort;
			if ($system == 1) $system = "sethkrtsoll";
			if ($system == 2) $system = "sethkrtsoll";
			if ($system == 3) $system = "sethkrtsoll";
			LOGINF("setting Hkr temperature");
			$FBSPort = GetHTTPSPort();

			if ($FBSPort == -1) {
				LOGERR("can't get HTTPS Port. Giving up");
				exit;
			}
			
			$SID = GetSessID();
			$stream = stream_context_create(array( 
				"ssl"=>array(     
					"verify_peer"=> false,     
					"verify_peer_name"=> false, ),
					'http' => array(     
						'timeout' => 30     
						)
					)
				);
				try {
					$cont = file_get_contents("https://$FBIP:$FBSPort/webservices/homeautoswitch.lua?ain=$ain&switchcmd=$system&param=$temp&sid=$SID",0,$stream);
					print $cont;
					LOGINF("Hkr temperature seams to be setted");
				} catch (Exception $fault) {
					LOGERR("retrieving error while setting Hkr temperature");
					LOGDEB($fault);
				}
		}

if (strlen($cmd) > 0) {
	switch ($cmd) {
	case "enableWLAN":
		LOGINF("enableWLAN triggered");
		if (strlen($WLAN) > 0) {
			$client = new SoapClient(
				null,
				array(
					'location' => "http://".$FBIP.":49000/upnp/control/wlanconfig".$WLAN,
					'uri'      => "urn:dslforum-org:service:WLANConfiguration:".$WLAN,
					'noroot'   => True,
					'login'    => $FBLogin,
					'password' => $FBPass
				)
			);
			try {
				$client->SetEnable(new SoapParam(true,'NewEnable'));
				//$res = $client->{"X_AVM-DE_GetWLANExtInfo"}();
				//print_r($res);
				LOGOK("WLAN should be enabled");
				print "OK";
			} catch (SoapFault $fault) {
				print_error($fault);
			}
		}
		break;
	case "disableWLAN":
		LOGINF("disableWLAN triggered");
		if (strlen($WLAN) > 0) {
			$client = new SoapClient(
				null,
				array(
					'location' => "http://".$FBIP.":49000/upnp/control/wlanconfig".$WLAN,
					'uri'      => "urn:dslforum-org:service:WLANConfiguration:".$WLAN,
					'noroot'   => True,
					'login'    => $FBLogin,
					'password' => $FBPass
				)
			);
			try {
				$client->SetEnable(new SoapParam(false,'NewEnable'));
				//$res = $client->{"X_AVM-DE_GetWLANExtInfo"}();
				//print_r($res);
				LOGOK("WLAN should be disabled.");
				print "OK";
			} catch (SoapFault $fault) {
					print_error($fault);
			}
		}
		break;
	case "DECTswitchOFF":
		LOGINF("DECTswitchOFF triggered");
		if (strlen($FBDECTAIN) > 0) {
			$client = new SoapClient(
				null,
				array(
					'location' => "http://".$FBIP.":49000/upnp/control/x_homeauto",
					'uri'      => "urn:dslforum-org:service:X_AVM-DE_Homeauto:1",
					'noroot'   => True,
					'login'    => $FBLogin,
					'password' => $FBPass
				)
			);
			try {
				$client->SetSwitch(new SoapParam("$FBDECTAIN",'NewAIN'),new SoapParam(OFF,'NewSwitchState'));
				LOGOK("Actor should be off.");
				print "OK";
			} catch (SoapFault $fault) {
					print_error($fault);
			}
		}
		break;
	case "DECTswitchON":
		LOGINF("DECTswitchON");
		if (strlen($FBDECTAIN) > 0) {
			$client = new SoapClient(
				null,
				array(
					'location' => "http://".$FBIP.":49000/upnp/control/x_homeauto",
					'uri'      => "urn:dslforum-org:service:X_AVM-DE_Homeauto:1",
					'noroot'   => True,
					'login'    => $FBLogin,
					'password' => $FBPass
				)
			);
			try {
				$client->SetSwitch(new SoapParam("$FBDECTAIN",'NewAIN'),new SoapParam(ON,'NewSwitchState'));
				LOGOK("Actor should be on.");
				print "OK";
			} catch (SoapFault $fault) {
					print_error($fault);
			}
		}
		break;
	case "DECTgetInfo":
		LOGINF("DECTgetInfo triggered");
		if (strlen($FBDECTAIN) > 0) {
			$client = new SoapClient(
				null,
				array(
					'location' => "http://".$FBIP.":49000/upnp/control/x_homeauto",
					'uri'      => "urn:dslforum-org:service:X_AVM-DE_Homeauto:1",
					'noroot'   => True,
					'login'    => $FBLogin,
					'password' => $FBPass
				)
			);
			try {
				$res = $client->GetSpecificDeviceInfos(new SoapParam($FBDECTAIN,'NewAIN'));
				LOGOK("DECT info retrieved.");
				LOGDEB(print_r($res,true));
				print_r($res);
			} catch (SoapFault $fault) {
					print_error($fault);
			}
		}
		break;
	case "DECTgetSwitchList":
		LOGINF("DECTgetSwitchList triggered");
		$client = new SoapClient(
			null,
			array(
				'location' => "http://".$FBIP.":49000/upnp/control/x_homeauto",
				'uri'      => "urn:dslforum-org:service:X_AVM-DE_Homeauto:1",
				'noroot'   => True,
				'login'    => $FBLogin,
				'password' => $FBPass
			)
		);
		$err=0;
		$first = true;
		print "{\n\t".'"Switches":'."\n\t[";
		for ($i = 0; $i < 1000; $i++) {
			try {
				//$res = $client->GetSpecificDeviceInfos(new SoapParam($FBDECTAIN,'NewAIN'));
				$res = $client->GetGenericDeviceInfos(new SoapParam($i,'NewIndex'));
				LOGDEB(print_r($res,true));
				if ($res['NewSwitchIsEnabled'] == "ENABLED") {
					if ($first != true) print ",\n";
					print "\n\t\t{\n\t\t\t".'"name":"'.utf8_encode($res['NewDeviceName']).'",';
					print "\n\t\t\t".'"AIN":"'.str_replace(" ","+",$res['NewAIN']).'"'."\n\t\t}";
					$first = false;
				}
			} catch (SoapFault $fault) {
				//print_r($fault);
				$i = 1000;
				if (isset($fault->detail)) {
					if ($fault->detail->UPnPError->errorCode == 713) {
						//keine weiteren Geräte
						LOGINF("Keine weiteren Geräte.");
					} else {
						$err=1;
						LOGDEB(print_r($fault,true));
						//print "Fehler: ".$fault->detail->UPnPError->errorDescription." (".$fault->detail->UPnPError->errorCode.")\n";
					}
				} else {
					//print_r($fault);
					LOGDEB(print_r($fault,true));
				}
			}
		}
		if ($err == 1) {
			LOGERR("error while retrieving DECTList");
		} else {
			LOGOK("DECTList retrieved");
		}
		print "\n\t]\n}\n";
		break;
	case "DECTgetHkrList":
		$client = new SoapClient(
			null,
			array(
				'location' => "http://".$FBIP.":49000/upnp/control/x_homeauto",
				'uri'      => "urn:dslforum-org:service:X_AVM-DE_Homeauto:1",
				'noroot'   => True,
				'login'    => $FBLogin,
				'password' => $FBPass
			)
		);
		$err=0;
		$first = true;
		print "{\n\t".'"Hkrs":'."\n\t[";
		for ($i = 0; $i < 1000; $i++) {
			try {
				//$res = $client->GetSpecificDeviceInfos(new SoapParam($FBDECTAIN,'NewAIN'));
				$res = $client->GetGenericDeviceInfos(new SoapParam($i,'NewIndex'));
				LOGDEB(print_r($res,true));
				if ($res['NewHkrIsEnabled'] == "ENABLED") {
					if ($first != true) print ",\n";
					print "\n\t\t{\n\t\t\t".'"name":"'.utf8_encode($res['NewDeviceName']).'",';
					print "\n\t\t\t".'"AIN":"'.str_replace(" ","+",$res['NewAIN']).'"'."\n\t\t}";
					$first = false;
				}
			} catch (SoapFault $fault) {
				//print_r($fault);
				$i = 1000;
				if (isset($fault->detail)) {
					if ($fault->detail->UPnPError->errorCode == 713) {
						//keine weiteren Geräte
						LOGINF("Keine weiteren Geräte.");
					} else {
						$err=1;
						LOGDEB(print_r($fault,true));
						//print "Fehler: ".$fault->detail->UPnPError->errorDescription." (".$fault->detail->UPnPError->errorCode.")\n";
					}
				} else {
					//print_r($fault);
					LOGDEB(print_r($fault,true));
				}
			}
		}
		if ($err == 1) {
			LOGERR("error while retrieving HkrList");
		} else {
			LOGOK("HkrList retrieved");
		}
		print "\n\t]\n}\n";
		break;
	case "DECTsetHkrTemp":
		LOGINF("DECTsetHkrTemp triggered");
		if ( $NewValue === "" ) {
			LOGINF("no temperature given");
		} else {
			if($NewValue < 8) {
				$NewValue = 253;
			} elseif ($NewValue > 28) {
				$NewValue = 254;
			} else {
				$NewValue = $NewValue * 2;
			}
			setHkrTemp($FBDECTAIN,$NewValue,1);
		} 
		break;
	case "DECTsetHkrComfTemp":
		LOGINF("DECTsetHkrTemp triggered");
		if ( $NewValue === "" ) {
			LOGINF("no temperature given");
		} else {
			if($NewValue < 8) {
				$NewValue = 253;
			} elseif ($NewValue > 28) {
				$NewValue = 254;
			} else {
				$NewValue = $NewValue * 2;
			}
			setHkrTemp($FBDECTAIN,$NewValue,2);
		} 
		break;
	case "DECTsetHkrRedTemp":
		LOGINF("DECTsetHkrTemp triggered");
		if ( $NewValue === "" ) {
			LOGINF("no temperature given");
		} else {
			if($NewValue < 8) {
				$NewValue = 253;
			} elseif ($NewValue > 28) {
				$NewValue = 254;
			} else {
				$NewValue = $NewValue * 2;
			}
			setHkrTemp($FBDECTAIN,$NewValue,3);
		} 
		break;
	case "WANgetInfo":
		LOGINf("WANgetInfo triggered");
			$client = new SoapClient(
				null,
				array(
					'location' => "http://".$FBIP.":49000/igdupnp/control/WANCommonIFC1",
					'uri'      => "urn:schemas-upnp-org:service:WANCommonInterfaceConfig:1",
					'noroot'   => True,
					'login'    => $FBLogin,
					'password' => $FBPass
				)
			);
			try {
				$res = $client->GetCommonLinkProperties();
				LOGDEB(print_r($res,true));
				print_r($res);
				$res = $client->GetAddonInfos();
				LOGDEB(print_r($res,true));
				print_r($res);
				LOGOK("WAN Info retrieved");
				$errmsg = "";
			} catch (SoapFault $fault) {
					print_error($fault);
			}
		break;
	case "WLANgetInfo":
		LOGINF("WLANgetInfo triggered");
		if (strlen($WLAN) > 0) {
			$client = new SoapClient(
				null,
				array(
					'location' => "http://".$FBIP.":49000/upnp/control/wlanconfig".$WLAN,
					'uri'      => "urn:dslforum-org:service:WLANConfiguration:".$WLAN,
					'noroot'   => True,
					'login'    => $FBLogin,
					'password' => $FBPass
				)
			);
			try {
				$res = $client->GetInfo();
				LOGOK("WLAN info retrieved");
				LOGDEB(print_r($res,true));
				print_r($res);
			} catch (SoapFault $fault) {
					print_error($fault);
			}
		}
		break;
	case "WANgetIPInfo":
			LOGINF("WANgetIPInfo triggered");
			$client = new SoapClient(
				null,
				array(
					'location' => "http://".$FBIP.":49000/igdupnp/control/WANIPConn1",
					'uri'      => "urn:schemas-upnp-org:service:WANIPConnection:1",
					'noroot'   => True,
					'login'    => $FBLogin,
					'password' => $FBPass
				)
			);
			try {
				$res = $client->GetExternalIPAddress();
				LOGOK("WAN IP retrieved");
				LOGDEB(print_r($res,true));
				print_r($res);
			} catch (SoapFault $fault) {
					print_error($fault);
			}
		break;
	}
} else {
	LOGWARN("no command given.");
	echo "kein Befehl angegeben.";
}
LOGEND("FBHelper finished.");
?>
