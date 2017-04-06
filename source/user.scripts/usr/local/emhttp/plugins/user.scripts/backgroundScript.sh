#!/bin/bash
echo "Script location: <b>$1</b>"
echo "<font color='red'>Now starting the script in the background</font>"
echo /usr/local/emhttp/plugins/user.scripts/startBackground.php "$1" | at NOW -M > /dev/null 2>&1

