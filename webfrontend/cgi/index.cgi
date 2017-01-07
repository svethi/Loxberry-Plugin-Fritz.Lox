#!/usr/bin/perl

# This is a sample Script file
# It does not much:
#   * Loading configuration
#   * including header.htmlfooter.html
#   * and showing a message to the user.
# That's all.

use File::HomeDir;
use CGI qw/:standard/;
use Config::Simple;
use Cwd 'abs_path';
use IO::Socket::INET;
use warnings;
use strict;
no strict "refs"; # we need it for template system

my  $home = File::HomeDir->my_home;
our $lang;
my  $installfolder;
my  $cfg;
my  $conf;
our $curl;
our $psubfolder;
our $localip;
our $dotest;
our $helptext;
our $helplink;
our $template_title;
our $namef;
our $value;
our %query;
our $do;
our $phrase;
our $phraseplugin;
our $languagefile;
our $languagefileplugin;
our $cache;
our $FritzboxIP;
our $FBLogin;
our $FBPass;
our $FBusePb;
our $FBusePbno;
our $FBusePbyes;
our $MSUDPPort;
our $savedata;
our $MSselectlist;
our $MiniServer;
our $restartMsg;

# Read Settings
$cfg             = new Config::Simple("$home/config/system/general.cfg");
$installfolder   = $cfg->param("BASE.INSTALLFOLDER");
$lang            = $cfg->param("BASE.LANG");
$curl            = $cfg->param("BINARIES.CURL");

print "Content-Type: text/html\n\n";
# Everything from URL
foreach (split(/&/,$ENV{"QUERY_STRING"}))
{
  ($namef,$value) = split(/=/,$_,2);
  $namef =~ tr/+/ /;
  $namef =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
  $value =~ tr/+/ /;
  $value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
  $query{$namef} = $value;
}

# Set parameters coming in - get over post
	if ( !$query{'lang'} )         { if ( param('lang')         ) { $lang         = quotemeta(param('lang'));         } else { $lang         = $lang;  } } else { $lang         = quotemeta($query{'lang'});         }
	if ( !$query{'savedata'} )     { if ( param('savedata')     ) { $savedata     = quotemeta(param('savedata'));     } else { $savedata     = $savedata;} } else { $savedata     = quotemeta($query{'savedata'});         }
	if ( !$query{'do'} )           { if ( param('do')           ) { $do           = quotemeta(param('do'));           } else { $do           = "form"; } } else { $do           = quotemeta($query{'do'});           }
  if ( !$query{'cache'} )        { if ( param('cache')        ) { $cache        = param('cache');                   } else { $cache        = "";     } } else { $cache        = $query{'cache'};                   }
  if ( !$query{'miniserver'} )   { if ( param('miniserver')   ) { $MiniServer   = param('miniserver');              } else { $MiniServer   = "1";        } } else { $MiniServer = $query{'miniserver'};              }
# Figure out in which subfolder we are installed

$psubfolder = abs_path($0);
$psubfolder =~ s/(.*)\/(.*)\/(.*)$/$2/g;

# read fritzlox configs
$conf = new Config::Simple("$home/config/plugins/$psubfolder/fritzlox.conf");
$FritzboxIP = $conf->param('general.FritzboxIP');
$FBLogin = $conf->param('general.FBLogin');
$FBPass = $conf->param('general.FBPass');
$FBusePb = $conf->param('general.FBusePhonebook');
$MSUDPPort = "7000";

for (my $i = 1; $i <= $cfg->param('BASE.MINISERVERS');$i++) {
	if ($i == $MiniServer) {
		$MSselectlist .= '																<option selected value="'.$i.'">'.$cfg->param("MINISERVER$i.NAME")."</option>\n";
	} else {
		$MSselectlist .= '																<option value="'.$i.'">'.$cfg->param("MINISERVER$i.NAME")."</option>\n";
	}
}

if ( $FritzboxIP eq "" ) {
	my $gw = `netstat -nr`;
	$gw =~ m/0.0.0.0\s+([0-9]+.[0-9]+.[0-9]+.[0-9]+)/g;
	$FritzboxIP = $1;
}

# Init Language
	# Clean up lang variable
	$lang         =~ tr/a-z//cd; $lang         = substr($lang,0,2);
  # If there's no language phrases file for choosed language, use german as default
		if (!-e "$installfolder/templates/system/$lang/language.dat") 
		{
  		$lang = "de";
	}
	# Read translations / phrases
		$languagefile 			= "$installfolder/templates/system/$lang/language.dat";
		$phrase 						= new Config::Simple($languagefile);
		$languagefileplugin = "$installfolder/templates/plugins/$psubfolder/$lang/language.dat";
		$phraseplugin 			= new Config::Simple($languagefileplugin);

# Get Local IP and GW IP
my $sock = IO::Socket::INET->new(
                       PeerAddr=> "example.com",
                       PeerPort=> 80,
                       Proto   => "tcp");
$localip = $sock->sockhost;

my $gw = `netstat -nr`;
$gw =~ m/0.0.0.0\s+([0-9]+.[0-9]+.[0-9]+.[0-9]+)/g;
my $gwip = $1;

# Clean up savedata variable
$savedata =~ tr/0-1//cd;
$savedata = substr($savedata,0,1);

#save data?
if ($savedata == 1) {
  if ( !$query{'fritzboxip'} )   { if ( param('fritzboxip')   ) { $FritzboxIP   = param('fritzboxip');              } else { $FritzboxIP   = $FritzboxIP;} } else { $FritzboxIP = quotemeta($query{'fritzboxip'});   }
  if ( !$query{'msudpport'} )    { if ( param('msudpport')    ) { $MSUDPPort    = param('msudpport');               } else { $MSUDPPort    = "7000";     } } else { $MSUDPPort  = $query{'msudpport'};               }
  if ( !$query{'fblogin'} )      { if ( param('fblogin')      ) { $FBLogin      = param('fblogin');                 } else { $FBLogin      = "";         } } else { $FBLogin  = $query{'fblogin'}; }
  if ( !$query{'fbpass'} )       { if ( param('fbpass')       ) { $FBPass       = param('fbpass');                  } else { $FBPass       = "";         } } else { $FBPass  = $query{'fbpass'};  }
  if ( !$query{'fbusepb'} )      { if ( param('fbusepb')      ) { $FBusePb      = param('fbusepb');                 } else { $FBusePb      = "0";        } } else { $FBusePb  = $query{'fbusepb'};  }
	$conf->param('general.FritzboxIP',"$FritzboxIP");
	$conf->param('general.FBLogin',"$FBLogin");
	$conf->param('general.FBPass',"$FBPass");
	$conf->param('general.FBusePhonebook',"$FBusePb");
	$conf->param("MINISERVER$MiniServer.UDPPort","$MSUDPPort");
	$conf->param("MINISERVER$MiniServer.SendData",param('msenabled'));
	$conf->save();
	$restartMsg = '													<table><tr>
														<td width="25%">&nbsp</td>
														<td width="50%">
															<span style="color: red;">Die Einstellungen wurden geändert. Um diese zu übernehmen, muss der Loxberry neugestartet werden.</span>
														</td>
														<td width="25%">
															&nbsp;
														</td>
													</tr></table>'."\n";
}
if ($FBusePb==1) {
	$FBusePbyes='selected="selected"';
} else {
	$FBusePbno='selected="selected"';
}
# Title
$template_title = $phrase->param("TXT0000") . ": Fritz.Lox";

# Create help page
$helplink = "http://www.loxwiki.eu/display/LOXBERRY/Fritz.Lox";
open(F,"$installfolder/templates/plugins/$psubfolder/$lang/help.html") || die "Missing template $lang/help.html";
  while (<F>) {
    $_ =~ s/<!--\$(.*?)-->/${$1}/g;
    $helptext .= $_;
  }
close(F);

# Load header and replace HTML Markup <!--$VARNAME--> with perl variable $VARNAME
open(F,"$installfolder/templates/system/$lang/header.html") || die "Missing template system/$lang/header.html";
  while (<F>) {
    $_ =~ s/<!--\$(.*?)-->/${$1}/g;
    print $_;
  }
close(F);

# Load content from template
open(F,"$installfolder/templates/plugins/$psubfolder/$lang/content.html") || die "Missing template $lang/content.html";
  while (<F>) {
    $_ =~ s/<!--\$(.*?)-->/${$1}/g;
    print $_;
  }
close(F);

# Load footer and replace HTML Markup <!--$VARNAME--> with perl variable $VARNAME
open(F,"$installfolder/templates/system/$lang/footer.html") || die "Missing template system/$lang/header.html";
  while (<F>) {
    $_ =~ s/<!--\$(.*?)-->/${$1}/g;
    print $_;
  }
close(F);

exit;
