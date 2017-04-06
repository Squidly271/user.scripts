<?PHP
  $command = "ls -al /boot";  # Bash Command/script To Run... Change To Suit
  
  $output = shell_exec($command);
  $output = str_replace("\n","<br>",$output);
  echo $output;
?>
