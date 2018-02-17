#!/usr/bin/perl

use File::HomeDir;
use CGI qw/:standard/;
use Config::Simple;
use Cwd 'abs_path';
use warnings;
use strict;
no strict "refs"; # we need it for template system

my  $home = File::HomeDir->my_home;
my  $installfolder;
my  $cfg;
my  $conf;
our $psubfolder;
our $MSUDPPort;
our $MSenabled;
our $MS;
our $namef;
our $value;
our %query;
our $do;

# Read Settings
$cfg             = new Config::Simple("$home/config/system/general.cfg");
$installfolder   = $cfg->param("BASE.INSTALLFOLDER");

# Everything from URL
foreach (split(/&/,$ENV{'QUERY_STRING'}))
{
  ($namef,$value) = split(/=/,$_,2);
  $namef =~ tr/+/ /;
  $namef =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
  $value =~ tr/+/ /;
  $value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
  $query{$namef} = $value;
}
	
# Figure out in which subfolder we are installed
$psubfolder = abs_path($0);
$psubfolder =~ s/(.*)\/(.*)\/(.*)$/$2/g;

# read fritzlox configs
$conf = new Config::Simple("$home/config/plugins/$psubfolder/fritzlox.conf");

print "Content-Type: application/json\n\n";
$MS = $query{'MS'};
$MSUDPPort = $conf->param("MINISERVER$MS.UDPPort");
$MSenabled = $conf->param("MINISERVER$MS.SendData");
if (length($MSUDPPort) == 0) {$MSUDPPort = 7000;}
if (length($MSenabled) == 0) {$MSenabled = 0;}
print "{\"UDPPort\": \"$MSUDPPort\",\n";
print "\"SendData\": \"$MSenabled\"}";

exit;
