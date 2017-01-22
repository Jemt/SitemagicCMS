<?php

$debug = false;

if ($debug === true)
{
	$start = microtime(true);

	error_reporting(E_ALL | E_STRICT);
	ini_set("display_errors", 1);
}
else
{
	error_reporting(0);
	ini_set("display_errors", 0);
}



if (is_dir("base") === false) // If no base folder is found, then index.php executes from within a subsite, e.g. sites/demo/index.php
	chdir(dirname(__FILE__) . "/../.."); // Change working directory to main site which contains the framework and extensions



require_once("base/SMController.class.php");
$controller = new SMController();
$controller->Execute();



if ($debug === true)
{
	$end = microtime(true);

	if (isset($_REQUEST["dump"]) === true)
	{
		$time = $end - $start;

		echo "<br>Memory usage: " . memory_get_usage(true) / 1024 . " KB";
		echo "<br>Time usage: " . $time . " seconds";

		/*echo "
		<br><h3>POST</h3>
		<pre>" . print_r($_POST, true) . "</pre>

		<br><h3>GET</h3>
		<pre>" . print_r($_GET, true) . "</pre>

		<br><h3>COOKIE</h3>
		<pre>" . print_r($_COOKIE, true) . "</pre>

		<br><h3>SESSION</h3>
		<pre>" . print_r($_SESSION, true) . "</pre>
		";*/
	}
}

?>
