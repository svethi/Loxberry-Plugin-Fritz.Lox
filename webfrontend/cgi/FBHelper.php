<?php

$home = posix_getpwuid(posix_getuid());
$home = $home['dir'];

# Figure out in which subfolder we are installed

$psubfolder = __FILE__;
$psubfolder = preg_replace('/(.*)\/(.*)\/(.*)$/',"$2", $psubfolder);

//Get conffile
$fritzloxconf = parse_ini_file("$home/config/plugins/$psubfolder/fritzlox.conf",True);

$FBIP = $fritzloxconf['general']['FritzboxIP'];
$FBLogin = $fritzloxconf['general']['FBLogin'];
$FBPass = $fritzloxconf['general']['FBPass'];

$WLAN = (isset($_GET['WLAN'])) ? $_GET['WLAN'] : "";
$cmd = (isset($_GET['cmd'])) ? $_GET['cmd'] : "";
$FBDECTAIN = (isset($_GET['DECTAIN'])) ? $_GET['DECTAIN'] : "";
if (php_sapi_name() == 'cli') $cmd = "DECTgetSwitchList";

if (strlen($cmd) > 0) {
	switch ($cmd) {
	case "enableWLAN":
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
				print "OK";
			} catch (SoapFault $fault) {
			print $fault->faultstring;
			}
		}
		break;
	case "disableWLAN":
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
				print "OK";
			} catch (SoapFault $fault) {
				print $fault->faultstring;
			}
		}
		break;
	case "DECTswitchOFF":
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
				print "OK";
			} catch (SoapFault $fault) {
				print $fault->faultstring;
			}
		}
		break;
	case "DECTswitchON":
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
				print "OK";
			} catch (SoapFault $fault) {
				print $fault->faultstring;
			}
		}
		break;
	case "DECTgetInfo":
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
				print_r($res);
			} catch (SoapFault $fault) {
				//print_r($fault);
			print "Fehler: ".$fault->detail->UPnPError->errorDescription." (".$fault->detail->UPnPError->errorCode.")\n";
			}
		}
		break;
	case "DECTgetSwitchList":
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
		print "{\n\t".'"Switches":'."\n\t[";
		for ($i = 0; $i < 1000; $i++) {
			try {
				//$res = $client->GetSpecificDeviceInfos(new SoapParam($FBDECTAIN,'NewAIN'));
				$res = $client->GetGenericDeviceInfos(new SoapParam($i,'NewIndex'));
				if ($res['NewSwitchIsEnabled'] == "ENABLED") {
					if ($i>0) print ",\n";
					print "\n\t\t{\n\t\t\t".'"name":"'.$res['NewDeviceName'].'",';
					print "\n\t\t\t".'"AIN":"'.str_replace(" ","+",$res['NewAIN']).'"'."\n\t\t}";
				}
			} catch (SoapFault $fault) {
				//print_r($fault);
				$i = 1000;
				if (isset($fault->detail)) {
					if ($fault->detail->UPnPError->errorCode == 713) {
						//keine weiteren Geräte
					} else {
						//print "Fehler: ".$fault->detail->UPnPError->errorDescription." (".$fault->detail->UPnPError->errorCode.")\n";
					}
				} else {
					//print_r($fault);
				}
			}
		}
		print "\n\t]\n}\n";
		break;
	case "WANgetInfo":
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
				print_r($res);
				$res = $client->GetAddonInfos();
				print_r($res);
			} catch (SoapFault $fault) {
				//print_r($fault);
			print "Fehler: ".$fault->detail->UPnPError->errorDescription." (".$fault->detail->UPnPError->errorCode.")\n";
			}
		break;
	}
} else {
	echo "kein Befehl angegeben.";
}
?>
