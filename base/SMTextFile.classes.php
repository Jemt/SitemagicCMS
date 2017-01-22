<?php

/// <container name="base/SMTextFileWriteMode">
/// 	Enum representing write mode
/// </container>
class SMTextFileWriteMode
{
	/// <member container="base/SMTextFileWriteMode" name="Create" access="public" static="true" type="string" default="Create">
	/// 	<description> Create file. Will fail if file already exists. It is recommended to use either Append or Overwrite. </description>
	/// </member>
	public static $Create = "Create";

	/// <member container="base/SMTextFileWriteMode" name="Overwrite" access="public" static="true" type="string" default="Overwrite">
	/// 	<description> Open for writing. File will be created if it does not exist, and overwritten if it already exists. </description>
	/// </member>
	public static $Overwrite = "Overwrite";

	/// <member container="base/SMTextFileWriteMode" name="Append" access="public" static="true" type="string" default="Append">
	/// 	<description> Append to existing file. File will be created if it does not exist. </description>
	/// </member>
	public static $Append = "Append";
}

/// <container name="base/SMTextFileWriter">
/// 	Class used to write text files.
///
/// 	$writer = new SMTextFileWriter(SMEnvironment::GetFilesDirectory() . "/MyFile.txt");
/// 	$writer->Write("Hello world");
/// 	$writer->Close();
/// </container>
class SMTextFileWriter
{
	private $fileHandler;
	private $bytesWritten;

	/// <function container="base/SMTextFileWriter" name="__construct" access="public">
	/// 	<description> Create instance of SMTextFileWriter </description>
	/// 	<param name="path" type="string"> File path </param>
	/// 	<param name="writeMode" type="SMTextFileWriteMode" default="SMTextFileWriteMode::$Append"> Optionally specify desired write mode </param>
	/// </function>
	public function __construct($path, $writeMode = "Append")
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);
		SMTypeCheck::CheckObject(__METHOD__, "writeMode", $writeMode, SMTypeCheckType::$String);

		if (property_exists("SMTextFileWriteMode", $writeMode) === false)
			throw new Exception("Invalid write mode - use SMTextFileWriteMode::WriteMode");

		$this->bytesWritten = 0;

		$fOpenAccessMode = "";

		if ($writeMode === SMTextFileWriteMode::$Create)
			$fOpenAccessMode = "x";
		else if ($writeMode === SMTextFileWriteMode::$Overwrite)
			$fOpenAccessMode = "w";
		else
			$fOpenAccessMode = "a";

		$pathUtf8 = utf8_encode($path);
		$this->fileHandler = fopen($pathUtf8, $fOpenAccessMode);

		if ($this->fileHandler === false)
			throw new Exception("Unable to open or create file '" . $path . "' for writing");
	}

	/// <function container="base/SMTextFileWriter" name="Write" access="public" returns="boolean">
	/// 	<description> Write data to text file - returns True on success, otherwise False </description>
	/// 	<param name="content" type="string"> Data to write </param>
	/// </function>
	public function Write($content)
	{
		SMTypeCheck::CheckObject(__METHOD__, "content", $content, SMTypeCheckType::$String);

		$bytes = fwrite($this->fileHandler, $content);
		$this->bytesWritten = (($bytes !== false) ? $bytes : 0);

		return ($bytes !== false);
	}

	/// <function container="base/SMTextFileWriter" name="GetBytesWritten" access="public" returns="integer">
	/// 	<description> Get number of bytes written during last call to Write(..) </description>
	/// </function>
	public function GetBytesWritten()
	{
		return $this->bytesWritten;
	}

	/// <function container="base/SMTextFileWriter" name="Close" access="public" returns="boolean">
	/// 	<description> Close writer - returns True on success, otherwise False </description>
	/// </function>
	public function Close()
	{
		return fclose($this->fileHandler);
	}
}

/// <container name="base/SMTextFileReader">
/// 	Class used to read text files.
///
/// 	$reader = new SMTextFileReader("MyFile.txt");
/// 	$content = $reader->ReadAll();
/// </container>
class SMTextFileReader
{
	private $path;

	/// <function container="base/SMTextFileReader" name="__construct" access="public">
	/// 	<description> Create instance of SMTextFileReader </description>
	/// 	<param name="path" type="string"> File path </param>
	/// </function>
	public function __construct($path)
	{
		SMTypeCheck::CheckObject(__METHOD__, "path", $path, SMTypeCheckType::$String);

		$pathUtf8 = utf8_encode($path);

		if (is_file($pathUtf8) === false)
			throw new Exception("File '" . $path . "' does not exist");

		if (is_readable($pathUtf8) === false)
			throw new Exception("File '" . $path . "' is not readable");

		$this->path = $path;
	}

	/// <function container="base/SMTextFileReader" name="ReadAll" access="public" returns="string">
	/// 	<description> Reads and returns entire content from text file </description>
	/// </function>
	public function ReadAll()
	{
		$pathUtf8 = utf8_encode($this->path);
		$content = file_get_contents($pathUtf8);

		if ($content === false)
			throw new Exception("An error occured while trying to read content from file '" . $this->path . "'");

		return $content;
	}
}

?>
