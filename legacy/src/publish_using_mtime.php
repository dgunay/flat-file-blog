<?php

require_once(__DIR__ . '/post_functions.php');

if (count($argv) < 2) {
	exit('Usage: php ' . __FILE__ . ' [file(s)_to_publish] [override_timestamp]');
}

$files = array_slice($argv, 1);

foreach($files as $file) {
	if (preg_match('/.+\.md$/', $file)) {
		echo 'Publishing ' . $file . PHP_EOL;
		publish_post($file, filemtime($file));
	}
}
