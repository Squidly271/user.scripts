#!/usr/bin/php -q
<?PHP
/* Copyright 2015, Bergware International.
 * Copyright 2015, Lime Technology
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 */
?>
<?
$handle = popen('/usr/bin/tail -n 40 -f '.escapeshellarg("/tmp/user.scripts/tmpScripts/{$argv[1]}/log.txt").' 2>&1', 'r');
while (!feof($handle)) {
  $line = fgets($handle);
  echo $line;
  flush();
}
pclose($handle);
?>

