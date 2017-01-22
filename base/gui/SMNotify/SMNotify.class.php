<?php

/// <container name="gui/SMNotify">
/// 	SMNotify is a helper class useful for creating notifications
/// 	with a common look and feel. Notice that this class should
/// 	not be instantiated - members are static.
///
/// 	$output = SMNotify::Render(&quot;Please fill out all fields&quot;);
/// </container>
class SMNotify
{
	/// <function container="gui/SMNotify" name="Render" access="public" static="true" returns="string">
	/// 	<description> Renders and returns HTML code necessary to display a notification </description>
	/// 	<param name="msg" type="string"> Message to display </param>
	/// </function>
	public static function Render($msg)
	{
		SMTypeCheck::CheckObject(__METHOD__, "msg", $msg, SMTypeCheckType::$String);
		return "<div class=\"SMNotify\">" . $msg . "</div>";
	}
}

?>
