<?php

header('Content-Type: application/json');
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
	$result = explode(",",$client->GetPhonebookList());
} catch (SoapFault $fault) {
	//print_r($fault);
	$result = [];
}
if (count($result) == 0) {
	print '{ "Phonebooks": "0" }';
} else {
	print "{\n\t".'"Phonebooks": "'.count($result).'"'.",\n";
	$id = 0;
	foreach ($result as $pbook) {
		$pb = $client->GetPhonebook(new SoapParam($pbook,'NewPhonebookID'));
		print "\t".'"'.$id.'":'."\n\t{\n";
		print "\t\t".'"ID": "'.$pbook.'"'.",\n";
		print "\t\t".'"Name": "'.$pb['NewPhonebookName'].'"'."\n\t}";
		$id ++;
		if ($id < count($result)) { 
			print ",\n";
		} else {
			print "\n";
		}
	}
	print "}\n";
}
?>
