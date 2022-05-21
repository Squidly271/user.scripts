#!/bin/bash
DIR="$(dirname "$(readlink -f ${BASH_SOURCE[0]})")"
tmpdir=/tmp/tmp.$(( $RANDOM * 19318203981230 + 40 ))
plugin=$(basename ${DIR})
archive="$(dirname $(dirname ${DIR}))/archive"
version=$(date +"%Y.%m.%d")$1
ACE_VERSION=1.4.14

mkdir -p $tmpdir

cp --parents -f $(find . -type f ! \( -iname "pkg_build.sh" -o -iname "sftp-config.json"  \) ) $tmpdir/

#Install Ace Editor
mkdir -p $tmpdir/usr/local/emhttp/plugins/user.scripts/javascript/ace/
wget --no-check-certificate https://github.com/ajaxorg/ace-builds/archive/refs/tags/v${ACE_VERSION}.zip
mkdir -p /tmp/ace
unzip v${ACE_VERSION}.zip "ace-builds-${ACE_VERSION}/src-min-noconflict/*" -d "/tmp/ace"
cp /tmp/ace/ace-builds-${ACE_VERSION}/src-min-noconflict/*.js $tmpdir/usr/local/emhttp/plugins/compose.manager/javascript/ace/

chmod -R +x $tmpdir/usr/local/emhttp/plugins/compose.manager/javascript/ace/
rm -R /tmp/ace
rm v${ACE_VERSION}.zip

cd $tmpdir
makepkg -l y -c y ${archive}/${plugin}-${version}-x86_64-1.txz
rm -rf $tmpdir
echo "MD5:"
md5sum ${archive}/${plugin}-${version}-x86_64-1.txz

