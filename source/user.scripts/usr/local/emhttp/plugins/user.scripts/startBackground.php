#!/usr/bin/php
<?PHP
require_once("/usr/local/emhttp/plugins/user.scripts/helpers.php");

$command = "";
foreach ($argv as $arg) {
  $command .= $arg." ";
}
$command = str_replace($argv[0],"",$command);
$command = trim($command);
$origCommand = $command;
$origlogFile = dirname($command)."/log.txt";
$scriptVariables = getScriptVariables($origCommand);
if ( $scriptVariables['clearLog'] ) {
  @unlink($origlogFile);
}
file_put_contents($origlogFile,"Script Starting ".date("M d, Y  H:i.s")."\n\n",FILE_APPEND);
$command = str_replace(" ","\ ",$command);
$logFile = str_replace(" ","\ ",$origlogFile);
$scriptName = basename(dirname($origCommand));

$command = $command." ".$scriptVariables['argumentDefault'] ?? ""." >> $logFile 2>&1";
file_put_contents("/tmp/user.scripts/running/$scriptName",getmypid());
file_put_contents($origlogFile,"Full logs for this script are available at $origlogFile\n\n",FILE_APPEND);
exec($command);
file_put_contents($origlogFile,"Script Finished ".date("M d, Y  H:i.s")."\n\n",FILE_APPEND);
unlink("/tmp/user.scripts/running/$scriptName");
file_put_contents("/tmp/user.scripts/finished/$scriptName","finished");
file_put_contents($origlogFile,"Full logs for this script are available at $origlogFile\n\n",FILE_APPEND);


?>

