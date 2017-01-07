<?php

$FBIP = $_GET['fbIP'];
$FBLogin = $_GET['fbLogin'];
$FBPass = $_GET['fbPass'];

$client = new SoapClient(
		null,
		array(
			'location'   => "http://".$FBIP.":49000/upnp/control/x_contact",
			'uri'        => "urn:dslforum-org:service:X_AVM-DE_OnTel:1",
			'noroot'     => True,
			'login'      => $FBLogin,
			'password'   => $FBPass
    )
);
try {
	$result = $client->GetPhonebook(new SoapParam(0,"NewPhonebookID"));
	print "{check:1}";
} catch (SoapFault $fault) {
	print "{check:0}";
}
?>