<?php


$home = posix_getpwuid(posix_getuid());
$home = $home['dir'];

# Figure out in which subfolder we are installed

$psubfolder = __FILE__;
$psubfolder = preg_replace('/(.*)\/(.*)\/(.*)$/',"$2", $psubfolder);

$myFile = "$home/data/plugins/$psubfolder/caldav_".MD5($calURL).".ical";

//Get conffile
$fritzloxconf = parse_ini_file("$home/config/plugins/$psubfolder/fritzlox.conf",True);

$FBIP = $fritzloxconf['general']['FritzboxIP'];
$FBLogin = $fritzloxconf['general']['FBLogin'];
$FBPass = $fritzloxconf['general']['FBPass'];

$WLAN = (isset($_GET['WLAN'])) ? $_GET['WLAN'] : "";
$cmd = (isset($_GET['cmd'])) ? $_GET['cmd'] : "";

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
	}
} else {
	echo "kein Befehl angegeben.";
}
?>