<?php

/// <container name="base/SMRandom">
/// 	Class used to generate GUIDs and random numbers or text.
///
/// 	$guid = SMRandom::CreateGuid();
/// 	$numb = SMRandom::CreateNumber(10, 90);
/// 	$text = SMRandom::CreateText();
/// </container>
class SMRandom
{
	/// <function container="base/SMRandom" name="CreateGuid" access="public" static="true" returns="string">
	/// 	<description> Create random GUID (Globally Unique IDentification) </description>
	/// </function>
	public static function CreateGuid()
	{
		return md5(uniqid(rand(), true));
	}

	/// <function container="base/SMRandom" name="CreateNumber" access="public" static="true" returns="integer">
	/// 	<description> Create random number </description>
	/// 	<param name="min" type="integer" default="0"> Optionally specify minimum value </param>
	/// 	<param name="max" type="integer" default="100"> Optionally specify maximum value </param>
	/// </function>
	public static function CreateNumber($min = 0, $max = 100)
	{
		SMTypeCheck::CheckObject(__METHOD__, "min", $min, SMTypeCheckType::$Integer);
		SMTypeCheck::CheckObject(__METHOD__, "max", $max, SMTypeCheckType::$Integer);

		return rand($min, $max);
	}

	/// <function container="base/SMRandom" name="CreateText" access="public" static="true" returns="string">
	/// 	<description> Create random sequence of letters and numbers </description>
	/// 	<param name="length" type="integer" default="8"> Optionally specify length of generated value </param>
	/// </function>
	public static function CreateText($length = 8)
	{
		SMTypeCheck::CheckObject(__METHOD__, "length", $length, SMTypeCheckType::$Integer);
		return substr(md5(uniqid("")), 0, $length);
	}
}

?>
