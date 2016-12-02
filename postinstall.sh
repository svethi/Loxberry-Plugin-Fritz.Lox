# Command line options
ARGV2=$2 # Second argument is real Plugin name
ARGV3=$3 # Third argument is Plugin installation folder
ARGV5=$5 # Fifth argument is Base folder of LoxBerry

/bin/sed -i "s:REPLACEFOLDERNAME:$ARGV3:g" $ARGV5/system/daemons/plugins/$ARGV2
/bin/sed -i "s:REPLACEINSTALLFOLDER:$ARGV5:g" $ARGV5/system/daemons/plugins/$ARGV2
/bin/sed -i "s:REPLACEFOLDERNAME:$ARGV3:g" $ARGV5/webfrontend/cgi/plugins/$ARGV2/bin/callmonitor
/bin/sed -i "s:REPLACEINSTALLFOLDER:$ARGV5:g" $ARGV5/webfrontend/cgi/plugins/$ARGV2/bin/callmonitor