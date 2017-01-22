<?php

// File named SMUtilities.classES.php, although only one class exists.
// It is very likely that more classes will be added later.

class SMStopWatch
{
	private $timeStart; // float
	private $timeStop;  // float

	public function __construct()
	{
		$this->Start();
	}

	public function Start()
	{
		$this->timeStart = microtime(true);
		$this->timeStop = null;
	}

	public function Stop()
	{
		$this->timeStop = microtime(true);
	}

	public function GetSeconds() // returns float
	{
		if ($this->timeStop === null)
			$this->Stop();

		if ($this->timeStart === null || $this->timeStop === null)
			return -1.00;

		return $this->timeStop - $this->timeStart;
	}
}

?>
