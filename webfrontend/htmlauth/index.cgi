#!/usr/bin/perl

# This is a sample Script file
# It does not much:
#   * Loading configuration
#   * including header.htmlfooter.html
#   * and showing a message to the user.
# That's all.

use LoxBerry::Web;
use LoxBerry::Log;
use CGI qw/:standard/;
use JSON qw( decode_json );
use Config::Simple qw/-strict/;
use warnings;
use strict;
no strict "refs"; # we need it for template system

our $lang;
my  $cfg;
my  $conf;
our $curl;
our $localip;
our $dotest;
our $helptext;
our $helplink;
my $helptemplate;
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
our $PBselected;
our $MSUDPPort;
our $savedata;
our $MSselectlist;
our $MiniServer;
our $restartMsg;
our $DECTSwitchessellist;
our $DECTHkrssellist;
our $lxbUser;

my $log = LoxBerry::Log->new (
        name => 'Fritz.Lox',
        logdir => "$lbplogdir",
);
LOGSTART "Fritz.Lox configuration UI";

LOGINF "Read system settings";
# Read Settings
$cfg             = new Config::Simple("$lbhomedir/config/system/general.cfg");
$lang            = $cfg->param("BASE.LANG");
$curl            = $cfg->param("BINARIES.CURL");
LOGOK "system settings readed";

# Everything from URL
LOGINF "load query values";
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
LOGOK "query values loaded";

# read fritzlox configs
LOGINF "read Fritz.Lox settings";
$conf = new Config::Simple("$lbpconfigdir/fritzlox.conf");
$FritzboxIP = $conf->param('general.FritzboxIP');
$FBLogin = $conf->param('general.FBLogin');
$FBPass = $conf->param('general.FBPass');
$FBusePb = $conf->param('general.FBusePhonebook');
$PBselected = $conf->param('general.Phonebook');
if ( !$PBselected ) {$PBselected = 0;}
$MSUDPPort = "7000";
$lxbUser = $ENV{'REMOTE_USER'};

for (my $i = 1; $i <= $cfg->param('BASE.MINISERVERS');$i++) {
	if ($i == $MiniServer) {
		$MSselectlist .= '																<option selected value="'.$i.'">'.$cfg->param("MINISERVER$i.NAME")."</option>\n";
	} else {
		$MSselectlist .= '																<option value="'.$i.'">'.$cfg->param("MINISERVER$i.NAME")."</option>\n";
	}
}
LOGOK "Fritz.Box settings readed";

LOGINF "Retrieving DECT switches list";
my $json = `php -f ./FBHelper.php`;
LOGDEB "Response: $json";
my $decoded = eval{decode_json($json)};
if ($@) {
	LOGERR "invalid JSON: $@";
} else {
	my @Switches = @{$decoded->{'Switches'}};
	my $i;
	foreach (@Switches) {
		if ($i == 0) {
			$DECTSwitchessellist .= '																<option selected value="'.$_->{'AIN'}.'">'.$_->{'name'}."</option>\n";
		} else {
			$DECTSwitchessellist .= '																<option value="'.$_->{'AIN'}.'">'.$_->{'name'}."</option>\n";
		}
	}
}
LOGOK "DECT switches list retrieved";

LOGINF "Retrieving DECT Hkrs list";
my $json = `php -f ./FBHelper.php DECTgetHkrList`;
LOGDEB "Response: $json";
my $decoded = eval{decode_json($json)};
if ($@) {
	LOGERR "invalid JSON: $@";
} else {
	my @Switches = @{$decoded->{'Hkrs'}};
	my $i;
	foreach (@Switches) {
		if ($i == 0) {
			$DECTHkrssellist .= '																<option selected value="'.$_->{'AIN'}.'">'.$_->{'name'}."</option>\n";
		} else {
			$DECTHkrssellist .= '																<option value="'.$_->{'AIN'}.'">'.$_->{'name'}."</option>\n";
		}
	}
}
LOGOK "DECT Hkrs list retrieved";

# Get Local IP and GW IP
LOGINF "retrieve the local ip";
#patch for missing in LB < 1.2.4
require IO::Socket::INET;
my $localip = LoxBerry::System::get_localip();
LOGDEB "localIP: $localip";
LOGOK "local IP retrieved";

LOGINF "retrieve the defaul gateway";
my $gw = `netstat -nr`;
$gw =~ m/0.0.0.0\s+([0-9]+.[0-9]+.[0-9]+.[0-9]+)/g;
my $gwip = $1;
LOGDEB "gateway: $gwip";
LOGOK "default gateway retrieved";

if ( $FritzboxIP eq "" ) {
	$FritzboxIP = $gwip;
}

# Clean up savedata variable
$savedata =~ tr/0-1//cd;
$savedata = substr($savedata,0,1);

#save data?
if ($savedata == 1) {
	LOGINF "Saving UI Settings";
  if ( !$query{'fritzboxip'} )   { if ( param('fritzboxip')   ) { $FritzboxIP   = param('fritzboxip');              } else { $FritzboxIP   = $FritzboxIP;} } else { $FritzboxIP = quotemeta($query{'fritzboxip'});   }
  if ( !$query{'msudpport'} )    { if ( param('msudpport')    ) { $MSUDPPort    = param('msudpport');               } else { $MSUDPPort    = "7000";     } } else { $MSUDPPort  = $query{'msudpport'};               }
  if ( !$query{'fblogin'} )      { if ( param('fblogin')      ) { $FBLogin      = param('fblogin');                 } else { $FBLogin      = "";         } } else { $FBLogin  = $query{'fblogin'}; }
  if ( !$query{'fbpass'} )       { if ( param('fbpass')       ) { $FBPass       = param('fbpass');                  } else { $FBPass       = "";         } } else { $FBPass  = $query{'fbpass'};  }
  if ( !$query{'fbusepb'} )      { if ( param('fbusepb')      ) { $FBusePb      = param('fbusepb');                 } else { $FBusePb      = "0";        } } else { $FBusePb  = $query{'fbusepb'};  }
  if ( !$query{'pblist'} )       { if ( param('pblist')       ) { $PBselected   = param('pblist');                  } else { $PBselected   = "0";        } } else { $PBselected = $query{'pblist'};  }
	$conf->param('general.FritzboxIP',"$FritzboxIP");
	$conf->param('general.FBLogin',"$FBLogin");
	$conf->param('general.FBPass',"$FBPass");
	$conf->param('general.FBusePhonebook',"$FBusePb");
	$conf->param('general.Phonebook',"$PBselected");
	$conf->param("MINISERVER$MiniServer.UDPPort","$MSUDPPort");
	$conf->param("MINISERVER$MiniServer.SendData",param('msenabled'));
	$conf->save();
	$restartMsg = '													<table><tr>
														<td width="25%">&nbsp</td>
														<td width="50%">
															<span style="color: red;"><!--noticerestartmsg--></span>
														</td>
														<td width="25%">
															&nbsp;
														</td>
													</tr></table>
													';
	LOGOK "UI settings saved";
}
if ($FBusePb==1) {
	$FBusePbyes='selected="selected"';
} else {
	$FBusePbno='selected="selected"';
}

LOGINF "create the page - beginn";
# Title

$template_title = "Fritz.Lox";

# Create help page
$helplink = "http://www.loxwiki.eu/display/LOXBERRY/Fritz.Lox";
$helptemplate = "help.html";

LOGINF "print out the header";
LoxBerry::Web::lbheader(undef, $helplink, $helptemplate);

LOGINF "create the content";

# Load content from template
my $maintemplate = HTML::Template->new(
    filename => "$lbptemplatedir/content.html",
    global_vars => 1,
    loop_context_vars => 1,
    die_on_bad_params => 0,
);
%L = LoxBerry::System::readlanguage($maintemplate, "language.ini");
$restartMsg =~ s/<!--noticerestartmsg-->/$L{'LABEL.RESTARTMSG'}/g;
my $logurl = LoxBerry::Web::loglist_url ();
$logurl =~ s/\s+$//;

$maintemplate->param("psubfolder",$lbpplugindir);
$maintemplate->param("FritzboxIP", $FritzboxIP);
$maintemplate->param("FBLogin", $FBLogin);
$maintemplate->param("lang",lblanguage());
$maintemplate->param("FBPass",$FBPass);
$maintemplate->param("MSselectlist",$MSselectlist);
$maintemplate->param("restartMsg",$restartMsg);
$maintemplate->param("lxbUser",$lxbUser);
$maintemplate->param("localip",$localip);
$maintemplate->param("DECTSwitchessellist",$DECTSwitchessellist);
$maintemplate->param("DECTHkrssellist", $DECTHkrssellist);
$maintemplate->param("logdir",$lbplogdir);
$maintemplate->param("FBusePbno",$FBusePbno);
$maintemplate->param("FBusePbyes",$FBusePbyes);
$maintemplate->param("PBSELECTED",$PBselected);
$maintemplate->param("LOGURL",$logurl);
  
print $maintemplate->output;

LOGINF "print out the footer";
LoxBerry::Web::lbfooter();
LOGEND "Done";

exit;
