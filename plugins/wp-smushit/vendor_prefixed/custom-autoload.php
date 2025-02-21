<?php
/**
 * THIS IS A MANUALLY CREATED FILE AND NEEDS TO BE MANUALLY UPDATED
 *
 * When composer dump-autoload is called autoload_files.php is not generated for some reason, this file manually includes the files from autoload_files.php
 *
 * TODO: look into why autoload_files.php is not generated
 *
 */

$vendorDir = __DIR__;

$load_files = array(
	'7b11c4dc42b3b3023073cb14e519683c' => $vendorDir . '/ralouphie/getallheaders/src/getallheaders.php',
	'6e3fae29631ef280660b3cdad06f25a8' => $vendorDir . '/symfony/deprecation-contracts/function.php',
	'37a3dc5111fe8f707ab4c132ef1dbc62' => $vendorDir . '/guzzlehttp/guzzle/src/functions_include.php',
);

foreach ( $load_files as $load_file ) {
	require $load_file;
}

include __DIR__ . '/autoload.php';
