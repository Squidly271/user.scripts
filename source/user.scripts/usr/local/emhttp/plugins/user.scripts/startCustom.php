#!/usr/bin/php
<?PHP
require_once("/usr/local/emhttp/plugins/user.scripts/helpers.php");

unset($argv[0]);
foreach ($argv as $arg) {
  $command .= "$arg ";
}
$command = trim($command);

exec("mkdir -p /tmp/user.scripts/finished");
exec("mkdir -p /tmp/user.scripts/running");
exec("mkdir -p /tmp/user.scripts/tmpScripts");

if ( ! is_file($command) ) {
#  logger("User Scripts: $command not found");
  exit();
}
$unRaidVars = parse_ini_file("/var/local/emhttp/var.ini");

$variables = getScriptVariables($path);
if ( $variables['arrayStarted'] && $unRaidVars['mdState'] != "STARTED" ) {
  logger("$path requires array to be started to run");
  exit();
}
if ( $variables['foregroundOnly'] ) {
  exit();
}
$newPath = str_replace("/boot/config/plugins/user.scripts/scripts/","/tmp/user.scripts/tmpScripts/",$command);
$newPath = str_replace("/usr/local/emhttp/plugins/user.scripts/scripts/","/tmp/user.scripts/tmpScripts/",$newPath);
exec("mkdir -p ".escapeshellarg(dirname($newPath)));
$script = file_get_contents($command);
if ( ! startsWith($script,"#!") ) {
  $script = "#!/bin/bash\n".$script;
}
$script = str_replace("\r","",$script);
file_put_contents($newPath,$script);
exec("chmod +x ".escapeshellarg($newPath));
$command = '/usr/local/emhttp/plugins/user.scripts/startBackground.php "'.$newPath.'" > /dev/null 2>&1';
echo $command;
exec($command);
?>
