#!/bin/bash
echo "Script location: <b>$1</b>"
echo "<font color='red'>Note that closing this window will abort the execution of this script</font>"
"$1" $2 2>&1
