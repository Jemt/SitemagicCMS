<?php

// Make sure all errors/warnings are emitted so they can
// be catched using error handler registered further down.
error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", 1);

// Extend memory and time limit
ini_set("max_execution_time", 600);
ini_set("memory_limit", "1024M");

header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate");

clearstatcache();

// Prevent annoying warning: Strict Standards: date() [function.date]: It is not safe to rely on the system's timezone settings
date_default_timezone_set("UTC");

function fail($msg)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo $msg; // IIS server on Windows must be configured to display custom errors for this to be shown to the user

	logMsg($msg);

	exit;
}

function logMsg($msg)
{
	$file = fopen("install.log", "a");
	fwrite($file, date("Y-m-d H:i:s") . ": " . $msg . "\n");
	fclose($file);
}

function backup($filename, $archive = null, $path = null)
{
	$zip = $archive;

	if ($zip === null)
	{
		$zip = new ZipArchive();
		$zip->open("./" . $filename, ZIPARCHIVE::CREATE);
	}

	$dir = ($path !== null ? $path : ".");
	$objects = scandir($dir);

	foreach ($objects as $object)
	{
		if ($object !== "." && $object !== "..")
		{
			if (is_dir($dir . "/" . $object) === true)
			{
				backup($filename, $zip, $dir . "/" . $object);
			}
			else
			{
				$zip->addFile($dir . "/" . $object, $dir . "/" . $object);
			}
		}
	}

	if ($archive === null)
	{
		$zip->close();

		header("Content-Type: application/zip");
		header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
		header("Content-Length: " . filesize($filename));
		readfile($filename);

		unlink($filename);
		exit; // Make absolutely sure no additional data is sent to the client which would corrupt the file
	}
}

function ensurePermissions($dir, $permission)
{
	if (is_dir($dir) === true)
	{
		if ($permission === "writable" && is_writable($dir) === false)
			fail("All files and folders must be writable - directory '" . $dir . "' is not");
		else if ($permission === "readable" && is_readable($dir) === false)
			fail("All files and folders must be readable - directory '" . $dir . "' is not");

		$objects = scandir($dir);

		foreach ($objects as $object)
		{
			if ($object !== "." && $object !== "..")
			{
				ensurePermissions($dir . "/" . $object, $permission);
			}
		}
	}
	else if (is_file($dir) === true)
	{
		if ($permission === "writable" && is_writable($dir) === false)
			fail("All files and folders must be writable - file '" . $dir . "' is not");
		else if ($permission === "readable" && is_readable($dir) === false)
			fail("All files and folders must be readable - file '" . $dir . "' is not");
	}
	else
	{
		fail("Error - '" . $dir . "' is not a file or directory or is not accessible");
	}
}

function removeDir($dir)
{
	if (is_dir($dir) === true)
	{
		$objects = scandir($dir);

		foreach ($objects as $object)
		{
			if ($object !== "." && $object !== "..")
			{
				if (is_dir($dir . "/" . $object) === true)
				{
					if (removeDir($dir . "/" . $object) === false)
						return false;
				}
				else
				{
					if (unlink($dir . "/" . $object) === false)
						return false;
				}
			}
		}

		return rmdir($dir);
	}
	else
	{
		return false;
	}
}

function getConfig($key)
{
	if (file_exists("config.xml.php") === false)
		return null;

	$content = file_get_contents("config.xml.php");

	preg_match('/key="' . $key . '" value="(.*)"/', $content, $matches, PREG_OFFSET_CAPTURE, 0);

	if (count($matches) === 0)
		return null;

	return $matches[1][0];
}

function getDataSourceType()
{
	$content = @file_get_contents("base/SMDataSource.classes.php");
	preg_match('/return SMDataSourceType::\$Xml/', $content, $matches, PREG_OFFSET_CAPTURE, 0);
	return (count($matches) > 0 ? "XML" : "MySQL");
}

function getUpgradeMode()
{
	$val = ((isset($_GET["UpgradeMode"]) === true) ? strtolower($_GET["UpgradeMode"]) : null);

	if ($val === null)
	{
		$val = (getConfig("UpgradeMode") !== null && getConfig("UpgradeMode") !== "" ? strtolower(getConfig("UpgradeMode")) : "stable");
	}

	if ($val !== "stable" /*&& $val !== "beta"*/ && $val !== "dev")
	{
		fail("Unsupported Upgrade Mode");
	}

	return $val;
}

function removeExtrationDirectories()
{
	$objects = scandir(".");

	foreach ($objects as $object)
	{
		if ($object !== "." && $object !== "..")
		{
			if (is_dir($object) === true && strpos($object, "Jemt-SitemagicCMS-") === 0)
			{
				if (removeDir($object) === false)
				{
					fail("Unable to remove extraction directories");
				}
			}
		}
	}
}

function getExtrationDirectory()
{
	$objects = scandir(".");

	foreach ($objects as $object)
	{
		if ($object !== "." && $object !== "..")
		{
			if (is_dir($object) === true && strpos($object, "Jemt-SitemagicCMS-") === 0)
			{
				return $object;
			}
		}
	}

	return null;
}

function errorHandler($errNo, $errMsg, $errFile, $errLine)
{
	// Data is passed to error handler by PHP in UTF-8 encoding!
	$errMsg = utf8_decode($errMsg);
	$errFile = utf8_decode($errFile);

	fail("Error occurred on line " . $errLine . ".<br>Error message: " . $errMsg);
}
set_error_handler("errorHandler");

function exceptionHandler($exception)
{
	fail("Exception occurred:<br>" . $exception->getMessage()); // $exception->getTraceAsString()
}
set_exception_handler("exceptionHandler");

// Parameters

$op = (isset($_POST["op"]) ? $_POST["op"] : null);
$upgrade = (file_exists("config.xml.php") === true);

// Pre-flight

if ($op === null) // Check on initial page load
{
	logMsg("Pre-flight initiated");
	logMsg("Ensuring that required extensions are loaded");

	// Fail immediately if ZIP extension is not available
	if (extension_loaded("zip") === false)
	{
		fail("The ZIP extension for PHP is required but not loaded - please contact your hosting provider");
	}

	if (getUpgradeMode() === "dev" && extension_loaded("curl") === false)
	{
		fail("The CURL extension for PHP is required to upgrade to 'dev', but the extension was not loaded - please contact your hosting provider");
	}

	logMsg("Ensuring that allow_url_fopen is enabled");

	$urlFopenAllowed = ini_get("allow_url_fopen");

	if ($urlFopenAllowed !== "1" && $urlFopenAllowed !== "On" && $urlFopenAllowed !== "on")
	{
		fail("PHP must have allow_url_fopen enabled to allow automatic installations and upgrades - please contact your hosting provider");
	}
}

if ($op === "start-install" || $op === "backup-init") // Clean install and backup - make sure current folder is writable on initial operation
{
	logMsg("Clean install OR backup");
	logMsg("Ensuring that current folder is writable");

	if (is_writable(".") === false)
	{
		fail("Current folder is not writable - unable to proceed");
	}

	if ($op === "backup-init")
	{
		logMsg("Ensuring that all files and folders are readable prior to backup");
		ensurePermissions(".", "readable");
	}
}

if ($op !== null && $upgrade === true) // Check on all operations when Sitemagic CMS is already installed
{
	// Always make sure user is authorized

	logMsg("Pre-flight for all operations");
	logMsg("Checking authentication");

	if (getConfig("Password") !== (isset($_POST["p"]) ? $_POST["p"] : null))
	{
		fail("Authentication failed - please provide valid password");
	}

	// Make sure server process has sufficient permissions to modify content of root folder

	logMsg("Checking permissions to make sure root folder is writable");

	if ($op === "start-install") // Only check this the first time during upgrading
	{
		if (is_writable(".") === false)
		{
			fail("Current folder is not writable - unable to proceed");
		}
	}
}

// Operations

session_start();

if ($op === "backup-init")
{
	logMsg("Current operation: backup-init");

	$filename = "Backup-" . date("Y-m-d_H_i_s") . ".zip";
	$_SESSION["SMCMSWebInstallBackup"] = $filename;

	echo $filename;
	exit;
}
else if ($op === "backup-monitor")
{
	logMsg("Current operation: backup-monitor");

	$filename = (isset($_POST["filename"]) ? $_POST["filename"] : null);
	$progress = (file_exists($filename) === false ? "done" : "downloading");

	echo $progress;
	exit;
}
else if ($op === "backup")
{
	logMsg("Current operation: backup");

	backup($_SESSION["SMCMSWebInstallBackup"]);
	exit;
}
else if ($op === "start-install") // Download Sitemagic CMS to current directory
{
	logMsg("Current operation: start-install");

	$release = getUpgradeMode();

	logMsg("Upgrade Mode: " . $release);
	logMsg("Checking if existing release file is present");

	if (file_exists("sitemagic-" . $release . ".zip") === true)
	{
		logMsg("Release file found - removing file");
		unlink("sitemagic-" . $release . ".zip");
	}

	logMsg("Downloading Sitemagic CMS");

	if ($release === "dev")
	{
		$fp = fopen("sitemagic-dev.zip", "w+");
		$curl = curl_init("https://api.github.com/repos/Jemt/SitemagicCMS/zipball/master");
		curl_setopt($curl, CURLOPT_TIMEOUT, 60);
		curl_setopt($curl, CURLOPT_FILE, $fp);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("User-Agent: Jemt"));
		$cRes = curl_exec($curl); // Returns boolean indicating success/failure

		if ($cRes === false)
		{
			fail("CURL failed with error '" . curl_error($curl) . "'");
		}

		curl_close($curl);
		fclose($fp);
	}
	else
	{
		//$url = "http://sitemagic.org/files/downloads/sitemagic-" . $release . ".zip";
		$url = "http://sitemagic.org/files/downloads/sitemagic-" . (($release === "stable") ? "latest" : $release) . ".zip";
		logMsg("Downloading new release file: " . $url);
		$data = file_get_contents($url);
		logMsg("Writing release file to: " . "sitemagic-" . $release . ".zip");
		file_put_contents("sitemagic-" . $release . ".zip", $data);
	}

	exit;
}
else if ($op === "extract") // Extract Sitemagic CMS to current directory
{
	logMsg("Current operation: extract");

	$release = getUpgradeMode();
	logMsg("Upgrade Mode: " . $release);

	logMsg("Ensuring that previously used extraction directories are removed");
	removeExtrationDirectories(); // No extraction directories will be found and removed if previous installs/upgrades went well

	$zip = new ZipArchive();
	$ts = time();
	$dsType = null;

	logMsg("Extracting ZIP file");

	$zip->open("sitemagic-" . $release . ".zip");
	$zip->extractTo( (($release === "dev") ? "." : "./Jemt-SitemagicCMS-release") ); // ZIP file from GitHub has a root folder called "Jemt-SitemagicCMS-SHA" (SHA is a variable value) while stable Sitemagic releases are contained in the root of the ZIP file
	$zip->close();
	$extDir = getExtrationDirectory(); // Returns first matching directory (Jemt-Sitemagic-XYZ)

	logMsg("Making sure all source and destination files and folders are writable");

	// Check permissions for new version
	ensurePermissions($extDir, "writable");

	// Check permissions for old files/folders to be overwritten
	$objects = scandir($extDir);
	foreach ($objects as $object)
	{
		if ($object !== "." && $object !== "..")
		{
			if (file_exists($object) === true) // Checks both folders and files
				ensurePermissions($object, "writable"); // Make sure e.g. files/folders from new version is writable in old installation (current working directory)
		}
	}

	if ($upgrade === true) // Preserve config, files, data, active template(s), and extensions (3rd party extensions may have been installed)
	{
		logMsg("This is an upgrade - preserving translations, configuration, data, extensions, and subsites");

		$dsType = getDataSourceType();

		rename("config.xml.php", "config." . $ts . ".xml.php");
		rename("base", "base." . $ts);
		rename("files", "files." . $ts);
		rename("data", "data." . $ts);
		rename("templates", "templates." . $ts);
		rename("extensions", "extensions." . $ts);

		if (is_dir("sites") === true) // Does not exist on very old installations
			rename("sites", "sites." . $ts);
	}

	logMsg("Moving files from temporary folder '" . $extDir . "' to active Sitemagic installation path");

	$objects = scandir($extDir);
	foreach ($objects as $object)
	{
		if ($object !== "." && $object !== "..")
		{
			//if ($release === "dev" && (strpos($object, ".git") === 0 || strpos($object, ".vscode") === 0))
			if (strpos($object, ".") === 0) // Skip hidden files such as .git, .gitignore, .vscode, etc.
				continue;

			if (is_dir($extDir . "/" . $object) === true && is_dir($object) === true)
				removeDir($object); // Remove directories which cannot be overwritten when moved

			rename($extDir . "/" . $object, $object);
		}
	}

	logMsg("Removing temporary folder '" . $extDir . "'");

	removeDir($extDir);

	if ($upgrade === true)
	{
		logMsg("Restoring preserved configuration and data");

		unlink("config.xml.php");
		removeDir("files");
		removeDir("data");

		rename("config." . $ts . ".xml.php", "config.xml.php");
		rename("files." . $ts, "files");
		rename("data." . $ts, "data");

		logMsg("Restoring changes made to built-in templates");

		// Restore changes made to templates that ships with Sitemagic CMS
		foreach (scandir("templates") as $tpl)
		{
			if ($tpl === "." || $tpl === "..")
				continue;

			if (is_dir("templates." . $ts . "/" . $tpl) === true)
			{
				foreach (array("override.css", "override.js", "generated-bg.jpg") as $file)
				{
					if (is_file("templates." . $ts . "/" . $tpl . "/" . $file) === true)
					{
						copy("templates." . $ts . "/" . $tpl . "/" . $file, "templates/" . $tpl . "/" . $file);
					}
				}
			}
		}

		logMsg("Restoring custom made templates not part of the default package");

		// Restore templates not part of Sitemagic CMS
		foreach (scandir("templates." . $ts) as $tpl)
		{
			if ($tpl === "." || $tpl === "..")
				continue;

			if (is_dir("templates/" . $tpl) === false)
			{
				rename("templates." . $ts . "/" . $tpl, "templates/" . $tpl);
			}
		}

		logMsg("Restoring extensions not part of the default package");

		foreach (scandir("extensions." . $ts) as $ext)
		{
			if ($ext === "." || $ext === "..")
				continue;

			if (is_dir("extensions/" . $ext) === false)
			{
				rename("extensions." . $ts . "/" . $ext, "extensions/" . $ext);
			}
		}

		if (is_dir("sites." . $ts) === true)
		{
			logMsg("Restoring subsites");

			foreach (scandir("sites." . $ts) as $site)
			{
				if ($site === "." || $site === "..")
					continue;

				if (is_dir("sites." . $ts . "/" . $site) === true)
				{
					rename("sites." . $ts . "/" . $site, "sites/" . $site);

					copy("index.php", "sites/" . $site . "/index.php");

					if (is_file("sites/" . $site . "/files/.htaccess") === true)
						copy("sites/htaccess.fls", "sites/" . $site . "/files/.htaccess");
					if (is_file("sites/" . $site . "/templates/.htaccess") === true)
						copy("sites/htaccess.tpls", "sites/" . $site . "/templates/.htaccess");
				}
			}
		}

		logMsg("Restoring translations not part of the default package");

		if (is_dir("base." . $ts . "/Languages") === true) // Older versions did not have translations for base
		{
			foreach (scandir("base." . $ts . "/Languages") as $lang)
			{
				if ($lang === "." || $lang === "..")
					continue;

				if (is_file("base/Languages/" . $lang) === false)
				{
					rename("base." . $ts . "/Languages/" . $lang, "base/Languages/" . $lang);
				}
			}
		}

		foreach (scandir("extensions." . $ts) as $ext)
		{
			if ($ext === "." || $ext === "..")
				continue;

			if (is_dir("extensions." . $ts . "/" . $ext . "/Languages") === true && is_dir("extensions/" . $ext . "/Languages") === true)
			{
				foreach (scandir("extensions." . $ts . "/" . $ext . "/Languages") as $lang)
				{
					if ($lang === "." || $lang === "..")
						continue;

					if (is_file("extensions/" . $ext . "/Languages/" . $lang) === false)
					{
						rename("extensions." . $ts . "/" . $ext . "/Languages/" . $lang, "extensions/" . $ext . "/Languages/" . $lang);
					}
				}
			}
		}

		removeDir("base." . $ts);
		removeDir("templates." . $ts);
		removeDir("extensions." . $ts);

		if (is_dir("sites." . $ts) === true)
			removeDir("sites." . $ts);

		if ($dsType === "MySQL")
		{
			logMsg("Restoring MySQL database layer and unlinking XML based database layer");

			if (file_exists("base/SMDataSourceXml.classes.php") === true)
				unlink("base/SMDataSourceXml.classes.php"); // In case the file already exists due to multiple upgrades

			rename("base/SMDataSource.classes.php", "base/SMDataSourceXml.classes.php");
			rename("base/SMDataSourceSql.classes.php", "base/SMDataSource.classes.php");
		}
	}

	logMsg("Cleaning up - removing release file");

	unlink("sitemagic-" . $release . ".zip");

	logMsg("Upgrade complete - thank you!");

	exit;
}

?>

<html>
<head>
	<script type="text/javascript" src="https://sitemagic.org/external/installer/Fit.UI/Fit.UI.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://sitemagic.org/external/installer/Fit.UI/Fit.UI.min.css">

	<style type="text/css">
	body
	{
		font-family: verdana;
		font-size: 1em;
		color: #333333;
		padding: 3em;
	}

	#box
	{
		width: 600px;
		min-height: 320px;
		margin: 0 auto;
		box-shadow: 0px 0px 10px 0px #333333;
		padding: 4em;
		text-align: center;
	}

	#upgrade
	{
		display: none;
	}

	div.FitUiControlButton
	{
		margin: 0.5em;
	}

	input:focus
	{
		outline: none;
	}

	#progress.error
	{
		color: red;
	}

	#progress.success
	{
		color: green;
	}

	a
	{
		color: blue;
		text-decoration: none;
	}
	</style>
</head>
<body>

<div id="box">
	<b>This will install Sitemagic CMS <?php echo (getUpgradeMode() !== "stable" ? "(<i style='color: red'>" . getUpgradeMode() . "</i>)" : ""); ?> into the current directory.</b>

	<br><br><br>

	<div id="upgrade">
		Sitemagic CMS has already been installed.
		You can now upgrade your website to the latest version of Sitemagic CMS.
		Any changes to configuration, content, and design templates will be preserved.<br><br>

		<i style="color: red">Please create a backup before performing an upgrade !</i>

		<br><br>
	</div>

	<div id="buttons"></div>

	<br><br>

	<span id="progress"></span>
</div>

<script>
(function()
{
	function createPassDialog(cb)
	{
		var dia = new Fit.Controls.Dialog();
		dia.Modal(true);
		dia.Content("Enter website password to continue<br><br>");

		var txtPass = new Fit.Controls.Input("Password");
		txtPass.Type(Fit.Controls.Input.Type.Password);
		txtPass.Width(100, "%");
		txtPass.Render(dia.GetDomElement().firstElementChild);

		var cmdContinue = new Fit.Controls.Button();
		cmdContinue.Title("Continue");
		cmdContinue.OnClick(function(sender)
		{
			cb(txtPass.Value());

			txtPass.Dispose();
			cmdContinue.Dispose();
			dia.Dispose();
		});
		dia.AddButton(cmdContinue);

		dia.Open();
		txtPass.Focused(true)
	}

	var url = "<?php echo $_SERVER["PHP_SELF"] . ((isset($_SERVER["QUERY_STRING"]) === true && $_SERVER["QUERY_STRING"] !== "") ? "?" . $_SERVER["QUERY_STRING"] : ""); ?>";
	var upgrade = <?php echo (file_exists("config.xml.php") === true ? "true" : "false"); ?>;
	var progress = document.querySelector("#progress");

	if (upgrade === true)
	{
		document.querySelector("#upgrade").style.display = "block";

		var cmdBackup = new Fit.Controls.Button();
		cmdBackup.Title("Download backup");
		cmdBackup.OnClick(function()
		{
			createPassDialog(function(pass)
			{
				var r = new Fit.Http.Request(url);
				r.SetData("op=backup-init&p=" + pass);
				r.OnSuccess(function(sender)
				{
					var filename = r.GetResponseText();

					progress.className = "";
					progress.innerHTML = "Creating and downloading backup - please wait..";

					cmdBackup.Enabled(false);
					cmdDeploy.Enabled(false);

					var filename = location.pathname.substring(location.pathname.lastIndexOf("/") + 1);
					var form = Fit.Dom.CreateElement("<form type='submit' action='" + filename + "' method='POST' style='display: none'><input type='hidden' name='op' value='backup'><input type='password' name='p' value='" + pass + "'></form><iframe style='display: none' name='download'></iframe>");
					Fit.Dom.Add(document.body, form);
					form.firstElementChild.submit();

					var interval = setInterval(function()
					{
						var r2 = new Fit.Http.Request(url);
						r2.SetData("op=backup-monitor&p=" + pass);
						r2.OnSuccess(function(sender)
						{
							if (r2.GetResponseText() === "done")
							{
								progress.className = "";
								progress.innerHTML = "";

								cmdBackup.Enabled(true);
								cmdDeploy.Enabled(true);

								Fit.Dom.Remove(form);

								clearInterval(interval);
							}
						});
						r2.Start();

					}, 1000);
				});
				r.OnFailure(function(sender)
				{
					progress.className = "error";
					progress.innerHTML = r.GetResponseText();
				});
				r.Start();
			});
		});
		cmdBackup.Render(document.querySelector("#buttons"));
	}

	var cmdDeploy = new Fit.Controls.Button();
	cmdDeploy.Title((upgrade === false ? "Install" : "Upgrade") + " now");
	cmdDeploy.OnClick(function()
	{
		cmdDeploy.Enabled(false);

		var execute = function(pass)
		{
			progress.className = "";
			progress.innerHTML = "Downloading Sitemagic CMS - please wait..";

			var r = new Fit.Http.Request(url);
			r.SetData("op=start-install&p=" + pass);
			r.OnSuccess(function(sender)
			{
				progress.innerHTML = "Installing Sitemagic CMS - please wait..";

				var r2 = new Fit.Http.Request(url);
				r2.SetData("op=extract&p=" + pass);
				r2.OnSuccess(function(sender)
				{
					progress.className = "success";
					progress.innerHTML = "Done - Sitemagic CMS is ready - <a href='index.php'>go to website</a>";
				});
				r2.OnFailure(function(sender)
				{
					progress.className = "error";
					progress.innerHTML = r2.GetResponseText();
					cmdDeploy.Enabled(true);
				});
				r2.Start();
			});
			r.OnFailure(function(sender)
			{
				progress.className = "error";
				progress.innerHTML = r.GetResponseText();
				cmdDeploy.Enabled(true);
			});
			r.Start();
		}

		if (upgrade === false)
		{
			execute("");
		}
		else
		{
			createPassDialog(execute)
		}
	});
	cmdDeploy.Render(document.querySelector("#buttons"));
})();
</script>

</body>
</html>
