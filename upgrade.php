<?php

// Make sure all errors/warnings are emitted so they can
// be catched using error handler registered further down.
error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", 1);

// Extend memory and time limit
ini_set("max_execution_time", 600);
ini_set("memory_limit", "1024M");

header("Cache-Control: max-age=0, no-cache, no-store, must-revalidate");

function fail($msg)
{
	header("HTTP/1.1 500 Internal Server Error");
	echo $msg;
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
				if (is_dir($dir . "/" . $object) === true)
				{
					ensurePermissions($dir . "/" . $object, $permission);
				}
				else
				{
					if ($permission === "writable" && is_writable($dir . "/" . $object) === false)
						fail("All files and folders must be writable - file '" . $dir . "/" . $object . "' is not");
					else if ($permission === "readable" && is_readable($dir . "/" . $object) === false)
						fail("All files and folders must be readable - file '" . $dir . "/" . $object . "' is not");
				}
			}
		}
	}
	else
	{
		fail("Error - '" . $dir . "' is not a directory");
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

function errorHandler($errNo, $errMsg, $errFile, $errLine)
{
	// Data is passed to error handler by PHP in UTF-8 encoding!
	$errMsg = utf8_decode($errMsg);
	$errFile = utf8_decode($errFile);

	fail("Error occurred on line " . $errLine . ".<br>Error message: " . $errMsg);
}
set_error_handler("errorHandler");

// Parameters

$op = (isset($_POST["op"]) ? $_POST["op"] : null);
$upgrade = (file_exists("config.xml.php") === true);

// Pre-flight

if ($op === null) // Check on initial page load
{
	// Fail immediately if ZIP extension is not available
	if (extension_loaded("zip") === false)
	{
		fail("The ZIP extension for PHP is required but not loaded - please contact your hosting provider");
	}
}

if ($op === "start-install" || $op === "backup-init") // Clean install and backup - make sure current folder is writable on initial operation
{
	if (is_writable(".") === false)
	{
		fail("Current folder is not writable - unable to proceed");
	}

	if ($op === "backup-init")
		ensurePermissions(".", "readable");
}

if ($op !== null && $upgrade === true) // Check on all operations when Sitemagic CMS is already installed
{
	// Always make sure user is authorized

	if (getConfig("Password") !== (isset($_POST["p"]) ? $_POST["p"] : null))
	{
		fail("Authentication failed - please provide valid password");
	}

	// Make sure server process has sufficient permissions to overwrite all files

	if ($op === "start-install") // Only check this the first time during upgrading
		ensurePermissions(".", "writable");
}

// Operations

session_start();

if ($op === "backup-init")
{
	$filename = "Backup-" . date("Y-m-d_H_i_s") . ".zip";
	$_SESSION["SMCMSWebInstallBackup"] = $filename;

	echo $filename;
	exit;
}
else if ($op === "backup-monitor")
{
	$filename = (isset($_POST["filename"]) ? $_POST["filename"] : null);
	$progress = (file_exists($filename) === false ? "done" : "downloading");

	echo $progress;
	exit;
}
else if ($op === "backup")
{
	backup($_SESSION["SMCMSWebInstallBackup"]);
	exit;
}
else if ($op === "start-install") // Download Sitemagic CMS to current directory
{
	if (file_exists("sitemagic-latest.zip") === true)
	{
		unlink("sitemagic-latest.zip");
	}

	$url = "http://sitemagic.org/files/downloads/sitemagic-latest.zip";
	$data = file_get_contents($url);
	file_put_contents("sitemagic-latest.zip", $data);

	exit;
}
else if ($op === "extract") // Extract Sitemagic CMS to current directory
{
	$zip = new ZipArchive();

	$ts = time();
	$dsType = null;

	if ($upgrade === true) // Preserve config, files, data, and active template(s)
	{
		rename("config.xml.php", "config." . $ts . ".xml.php");
		rename("files", "files." . $ts);
		rename("data", "data." . $ts);
		rename("templates", "templates." . $ts);

		$dsType = getDataSourceType();
	}

	$zip->open("sitemagic-latest.zip");
	$zip->extractTo(".");
	$zip->close();

	if ($upgrade === true)
	{
		unlink("config.xml.php");
		removeDir("files");
		removeDir("data");

		rename("config." . $ts . ".xml.php", "config.xml.php");
		rename("files." . $ts, "files");
		rename("data." . $ts, "data");

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

		removeDir("templates." . $ts);

		if ($dsType === "MySQL")
		{
			if (file_exists("base/SMDataSourceXml.classes.php") === true)
				unlink("base/SMDataSourceXml.classes.php"); // In case the file already exists due to multiple upgrades

			rename("base/SMDataSource.classes.php", "base/SMDataSourceXml.classes.php");
			rename("base/SMDataSourceSql.classes.php", "base/SMDataSource.classes.php");
		}
	}

	unlink("sitemagic-latest.zip");

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
	<b>This will install Sitemagic CMS into the current directory.</b>

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

	var url = "<?php echo $_SERVER["PHP_SELF"]; ?>";
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
