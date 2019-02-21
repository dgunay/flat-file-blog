<?php

require_once(__DIR__ . '/post_functions.php');

if (count($argv) < 2) {
	exit('Usage: php ' . __FILE__ . ' [file(s)_to_publish] [override_timestamp]');
}

$files = array_slice($argv, 1);

if (isset($argv[2]) && is_numeric($argv[2])) {
	$time = $argv[2];
}
else {
	$time = time();
}

foreach($files as $file) {
	if (preg_match('/.+\.md$/', $file)) {
		echo 'Publishing ' . $file . PHP_EOL;
		publish_post($file, $time++);
	}
}
