#!/bin/bash
echo "Script location: <b>$1</b>"
export HOME=$(grep $(whoami) /etc/passwd | cut -d: -f 6)
source ${HOME}/.bashrc
echo "<font color='red'>Note that closing this window will abort the execution of this script</font>"
"$1" "$2" 2>&1
