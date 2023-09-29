#!/bin/bash

mkdir -p "/tmp/GitHub/user.scripts/source/user.scripts/usr/local/emhttp/plugins/user.scripts/"

cp /usr/local/emhttp/plugins/user.scripts/* /tmp/GitHub/user.scripts/source/user.scripts/usr/local/emhttp/plugins/user.scripts -R -v -p
find . -maxdepth 9999 -noleaf -type f -name "._*" -exec rm -v "{}" \;

