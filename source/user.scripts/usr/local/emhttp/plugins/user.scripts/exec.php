<?PHP
require_once("/usr/local/emhttp/plugins/user.scripts/helpers.php");

function getElement($element) {
	$return = str_replace(".","-",$element);
	$return = str_replace(" ","",$return);
	return $return;
}

switch ($_POST['action']) {
	case 'convertScript':
		$path = isset($_POST['path']) ? urldecode(($_POST['path'])) : "";
		$variables = getScriptVariables($path);
		$unRaidVars = parse_ini_file("/var/local/emhttp/var.ini");
		if ( ($variables['arrayStarted']??false) && $unRaidVars['mdState'] != "STARTED" ) {
			echo "/usr/local/emhttp/plugins/user.scripts/arrayNotStarted.sh";
			logger("Array must be started to run this script ($path)");
			break;
		}
		$newPath = str_replace("/boot/config/plugins/user.scripts/scripts/","/tmp/user.scripts/tmpScripts/",$path);
		exec("mkdir -p ".escapeshellarg(dirname($newPath)));
		$script = file_get_contents($path);
		if ( ! startsWith($script,"#!") ) {
			$script = "#!/usr/bin/env bash\n".$script;
		}
		$script = str_replace("\r","",$script);
		file_put_contents($newPath,$script);
		exec("chmod +x ".escapeshellarg($newPath));


		echo $newPath;
		break;
	case 'directRunScript':
		$path = isset($_POST['path']) ? urldecode(($_POST['path'])) : "";
		$variables = getScriptVariables($path);
		$unRaidVars = parse_ini_file("/var/local/emhttp/var.ini");
		if (($variables['arrayStarted']??false) && $unRaidVars['mdState'] != "STARTED" ) {
			echo "/usr/local/emhttp/plugins/user.scripts/arrayNotStarted.sh";
			logger("Array must be started to run this script ($path)");
			break;
		}
		$newPath = str_replace("/boot/config/plugins/user.scripts/scripts/","/usr/local/emhttp/plugins/tmp.user.scripts/tmpScripts/",$path);
		exec("mkdir -p ".escapeshellarg(dirname($newPath)));
		$script = file_get_contents($path);

		$script = str_replace("\r","",$script);
		file_put_contents($newPath.".php",$script);
		exec("chmod +x ".escapeshellarg($newPath.".php"));
		$goodPath = str_replace("/usr/local/emhttp","",$newPath);
		echo $goodPath.".php";
		break;
	case 'checkBackground':
		$allScripts = @array_diff(@scandir("/boot/config/plugins/user.scripts/scripts"),array(".",".."));
		if ( ! $allScripts ) {
			$allScripts = array();
		}
		foreach ($allScripts as $script) {
			$scriptArray[$script] = $script;
		}
		if ( ! is_array($scriptArray) ) {
			$scriptArray = array();
		}
		$untouchedScriptArray = $scriptArray;

		$o = "<script>";
		$running = @array_diff(scandir("/tmp/user.scripts/running"),array(".",".."));
		if ( ! is_array($running) ) {
			$running = array();
		}
		foreach ($running as $run) {
			$element = getElement($run);
			$pid = file_get_contents("/tmp/user.scripts/running/$run");
			$o .= "$('#$element').prop('disabled',true);";
			$o .= "$('#foreground$element').prop('disabled',true);";
			$button = '<input type="button" value="Abort Script" onclick="abortScript(&quot;'.$run.'&quot;);">';
			$o .= "$('#status$element').html('Running $button');";
			unset($scriptArray[$run]);
			$o .= "$('#trash$element').hide();";
		}
		$finished = @array_diff(scandir("/tmp/user.scripts/finished"),array(".",".."));
		if ( ! is_array($finished) ) {
			$finished = array();
		}
		foreach ($finished as $fini) {
			$element = getElement($fini);

			$o .= "$('#$element').prop('disabled',false);";
			$o .= "$('#foreground$element').prop('disabled',false);";
			$o .= "$('#status$element').html(' ');";
			unlink("/tmp/user.scripts/finished/$fini");
		}
		foreach ( $scriptArray as $script) {
			$element = getElement($script);
			if ( is_file("/tmp/user.scripts/tmpScripts/$script/log.txt") ) {
				$o .= "$('#trash$element').show();";
			} else {
				$o .= "$('#trash$element').hide();";
			}
		}

		foreach ( $untouchedScriptArray as $script) {
			$element = getElement($script);

			if ( is_file("/tmp/user.scripts/tmpScripts/$script/log.txt") ) {
				$o .= "$('#log$element').show();";
				$o .= "$('#download$element').show();";
			} else {
				$o .= "$('#log$element').hide();";
				$o .= "$('#download$element').hide();";
			}
		}
		$o .= "</script>";
		echo $o;
		break;
	case 'abortScript':
		$script = isset($_POST['name']) ? urldecode(($_POST['name'])) : "";

		$pid = file_get_contents("/tmp/user.scripts/running/$script");
		exec("pkill -TERM -P $pid");
		exec("kill -9 $pid");
		$processListOutput = null;
		exec("ps aux | grep -i '/tmp/user.scripts/tmpScripts/$script' | grep -v grep", $processListOutput);
		foreach ($processListOutput as $emergencyKill) {
			$emergencyKill = str_replace("root","",$emergencyKill);
			$emergencyKill = trim($emergencyKill);
			$rawKill = explode(" ",$emergencyKill);
			logger("Kill pid: ".$rawKill[0]);
			exec("kill -9 ".$rawKill[0]);
		}
		@unlink("/tmp/user.scripts/running/$script");
		file_put_contents("/tmp/user.scripts/finished/$script","aborted");
		file_put_contents("/tmp/user.scripts/tmpScripts/$script/log.txt","Execution was aborted by user\n\n",FILE_APPEND);
		break;

	case 'deleteLog':
		$name = isset($_POST['name']) ? urldecode(($_POST['name'])) : "";
		@unlink("/tmp/user.scripts/tmpScripts/$name/log.txt");
		break;

	case 'applySchedule':
		$schedules = $_POST['schedule'];

		foreach ($schedules as $schedule) {
			$script = str_replace('"',"",$schedule[0]);
			$scriptSchedule['script'] = $script;
			$scriptSchedule['frequency'] = $schedule[1];
			$scriptSchedule['id'] = $schedule[2];
			$scriptSchedule['custom'] = $schedule[3];
			$newSchedule[$script] = $scriptSchedule;

			if ( $scriptSchedule['frequency'] == "custom" && $scriptSchedule['custom'] ) {
				$cronSchedule .= trim($scriptSchedule['custom'])." /usr/local/emhttp/plugins/user.scripts/startCustom.php $script > /dev/null 2>&1\n";
			}
		}
		file_put_contents("/boot/config/plugins/user.scripts/schedule.json",json_encode($newSchedule,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		file_put_contents("/tmp/user.scripts/schedule.json",json_encode($newSchedule,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		if ( $cronSchedule ) {
			$cronSchedule ="# Generated cron schedule for user.scripts\n$cronSchedule\n";
			file_put_contents("/boot/config/plugins/user.scripts/customSchedule.cron",$cronSchedule);
		} else {
			@unlink("/boot/config/plugins/user.scripts/customSchedule.cron");
		}
		exec("/usr/local/sbin/update_cron");

		echo "Schedule Applied";
		break;
	case 'convertLog':
		$script = isset($_POST['script']) ? urldecode(($_POST['script'])) : "";
		exec("todos < ".escapeshellarg("/tmp/user.scripts/tmpScripts/$script/log.txt")." > /tmp/user.scripts/log.txt");
		unlink("/usr/local/emhttp/plugins/user.scripts/log.zip");
		exec("zip -j /usr/local/emhttp/plugins/user.scripts/log.zip /tmp/user.scripts/log.txt");
		unlink("/tmp/user.scripts/log.txt");
		echo "ok";
		break;
	case 'getScriptVariables':
		$script = isset($_POST['script']) ? urldecode(($_POST['script'])) : "";
		$rawVariables = getRawVariables($script);
		if ( is_array($rawVariables) ) {
			$o = "";
			$keys = array_keys($rawVariables);
			foreach ($keys as $eachKey) {
				$o .= "$eachKey={$rawVariables[$eachKey]}\n";
			}
			echo $o;
		} else {
			echo "nothingDefined=nothingDefined";
		}
		break;
	case 'getScriptVariablesString':
		$script = isset($_POST['script']) ? urldecode(($_POST['script'])) : "";
		$rawVariables = getRawVariables($script);
		if ( is_array($rawVariables) ) {
			$o = "";
			$keys = array_keys($rawVariables);
			foreach ($keys as $eachKey) {
				$o .= "$eachKey={$rawVariables[$eachKey]}\n";
			}
		} else {
			$o = "nothingDefined=nothingDefined";
		}
		echo $o;
		break;
	case 'changeName':
		$script = isset($_POST['script']) ? urldecode(($_POST['script'])) : "";
		$newName = isset($_POST['newName']) ? urldecode(($_POST['newName'])) : "";
		file_put_contents("/boot/config/plugins/user.scripts/scripts/$script/name",trim($newName));
		echo "ok";
		break;
	case 'changeDesc':
		$script = isset($_POST['script']) ? urldecode(($_POST['script'])) : "";
		$newDesc = isset($_POST['newDesc']) ? urldecode(($_POST['newDesc'])) : "";
		file_put_contents("/boot/config/plugins/user.scripts/scripts/$script/description",trim($newDesc));
		break;
	case 'getScript':
		$script = isset($_POST['script']) ? urldecode(($_POST['script'])) : "";
		$scriptContents = file_get_contents("/boot/config/plugins/user.scripts/scripts/$script/script");
		$scriptContents = str_replace("\r","",$scriptContents);
		echo $scriptContents;
		if ( ! $scriptContents ) {
			echo "#!/usr/bin/env bash\n";
		}
		break;
	case 'saveScript':
		$script = isset($_POST['script']) ? urldecode(($_POST['script'])) : "";
		$scriptContents = isset($_POST['scriptContents']) ? $_POST['scriptContents'] : "";
//		$scriptContents = preg_replace('/[\x80-\xFF]/', '', $scriptContents);
		file_put_contents("/boot/config/plugins/user.scripts/scripts/$script/script",$scriptContents);
		echo "/boot/config/plugins/user.scripts/scripts/$script/script saved";
		break;
	case 'addScript':
		$scriptName = isset($_POST['scriptName']) ? urldecode(($_POST['scriptName'])) : "";
		$folderName = str_replace('"',"",$scriptName);
		$folderName = str_replace("'","",$folderName);
		$folderName = str_replacE("&","",$folderName);
		$folderName = str_replace("(","",$folderName);
		$folderName = str_replace(")","",$folderName);
		$folderName = preg_replace("/ {2,}/", " ", $folderName);
		$folder = "/boot/config/plugins/user.scripts/scripts/$folderName";
		while ( true ) {
			if ( is_dir($folder) ) {
				$folder .= mt_rand();
			} else {
				break;
			}
		}
		exec("mkdir -p ".escapeshellarg($folder));
		file_put_contents("$folder/script","#!/usr/bin/env bash\n");
		file_put_contents("$folder/name",$scriptName);
		echo "ok";
		break;
	case 'deleteScript':
		$scriptName = isset($_POST['scriptName']) ? urldecode(($_POST['scriptName'])) : "";
		if ( ! $scriptName ) {
			echo "huh?";
			break;
		}
		$folderName = "/boot/config/plugins/user.scripts/scripts/$scriptName";
		exec("rm -rf ".escapeshellarg($folderName));
		echo "deleted";
		break;
}

?>
