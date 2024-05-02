#!/usr/bin/php
<?PHP

require_once("/usr/local/emhttp/plugins/user.scripts/helpers.php");

$selectedSchedule = $argv[1];
exec("mkdir -p /tmp/user.scripts/finished");
exec("mkdir -p /tmp/user.scripts/running");
exec("mkdir -p /tmp/user.scripts/tmpScripts");

if ( ! is_file("/boot/config/plugins/user.scripts/schedule.json") ) { return; }
if ( ! is_file("/tmp/user.scripts/schedule.json") ) {
  exec("cp /boot/config/plugins/user.scripts/schedule.json /tmp/user.scripts/schedule.json");
}

$schedules = json_decode(@file_get_contents("/tmp/user.scripts/schedule.json"),true);
if ( ! $schedules ) { return; }
$unRaidVars = parse_ini_file("/var/local/emhttp/var.ini");
foreach ($schedules as $scheduledScript) {
  if ( $scheduledScript['frequency'] == $selectedSchedule ) {

    $path = $scheduledScript['script'];
    if ( ! is_file($path) ) {
      continue;
    }
    $variables = getScriptVariables($path);
    if ( ($variables['arrayStarted']??false) && $unRaidVars['mdState'] != "STARTED" ) {
      continue;
    }
    if ( $variables['foregroundOnly']??false ) {
      continue;
    }
		if ( ($variables['noParity']??false) && $unRaidVars['mdResyncPos'] ) {
			logger("Parity Check / rebuild in progress.  Not executing $path per variable setting.");
			continue;
		}
    $newPath = str_replace("/boot/config/plugins/user.scripts/scripts/","/tmp/user.scripts/tmpScripts/",$path);
    $newPath = str_replace("/usr/local/emhttp/plugins/user.scripts/scripts/","/tmp/user.scripts/tmpScripts/",$newPath);
    exec("mkdir -p ".escapeshellarg(dirname($newPath)));
    $script = file_get_contents($path);
    if ( ! startsWith($script,"#!") ) {
      $script = "#!/usr/bin/env bash\n".$script;
    }
    $script = str_replace("\r","",$script);
    file_put_contents($newPath,$script);
    exec("chmod +x ".escapeshellarg($newPath));

    $command = '/usr/local/emhttp/plugins/user.scripts/backgroundScript.sh "'.$newPath.'" >/dev/null 2>&1';

    echo $command;
    exec($command);
  }
}
?>
