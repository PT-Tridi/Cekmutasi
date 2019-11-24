<?php

class Lib_datezone
{
	public $DateObject = NULL;

	function __construct()
	{
		$this->create_dateobject('Asia/Jakarta', 'Y-m-d H:i:s', date('Y-m-d H:i:s'));
	}

	function create_dateobject($timezone, $format, $date)
	{
		$microtime = microtime(true);
		$micro = sprintf("%06d", (($microtime - floor($microtime)) * 1000000));
		$datetime = date('Y-m-d H:i:s', strtotime($date));
		$this->DateObject = new DateTime("{$datetime}.{$micro}");
		$this->DateObject->setTimezone(new DateTimeZone($timezone));
		$this->DateObject->createFromFormat($format, $date);
		return $this;
	}

	function add_date_by($by_type, $by_value, $Datezone = null)
	{
		if (!isset($Datezone)) {
			$Datezone = $this->DateObject;
		}

		$by_type = (is_string($by_type) ? strtoupper($by_type) : 'DAY');
		$by_value = (is_numeric($by_value) ? (int)$by_value : 0);
		switch ($by_type)
		{
			case 'SECOND':
				$Datezone->add(new DateInterval("PT{$by_value}S"));
				break;

			case 'MINUTE':
				$Datezone->add(new DateInterval("PT{$by_value}M"));
				break;

			case 'HOUR':
				$Datezone->add(new DateInterval("PT{$by_value}H"));
				break;

			case 'DAY':
			default:
				$Datezone->add(new DateInterval("P{$by_value}D"));
				break;

			case 'WEEK':
				$by_value = ($by_value * 7);
				$Datezone->add(new DateInterval("P{$by_value}D"));
				break;

			case 'MONTH':
				$Datezone->add(new DateInterval("P{$by_value}M"));
				break;

			case 'YEAR':
				$Datezone->add(new DateInterval("P{$by_value}Y0M0DT0H0M0S"));
				break;
		}
		return $Datezone;
	}

	function reduce_date_by($by_type, $by_value, $Datezone = null)
	{
		if (!isset($Datezone)) {
			$Datezone = $this->DateObject;
		}

		$by_type = (is_string($by_type) ? strtoupper($by_type) : 'DAY');
		$by_value = (is_numeric($by_value) ? (int)$by_value : 0);
		switch ($by_type)
		{
			case 'SECOND':
				$Datezone->sub(new DateInterval("PT{$by_value}S"));
				break;

			case 'MINUTE':
				$Datezone->sub(new DateInterval("PT{$by_value}M"));
				break;

			case 'HOUR':
				$Datezone->sub(new DateInterval("PT{$by_value}H"));
				break;

			case 'DAY':
			default:
				$Datezone->sub(new DateInterval("P{$by_value}D"));
				break;

			case 'WEEK':
				$by_value = ($by_value * 7);
				$Datezone->sub(new DateInterval("P{$by_value}D"));
				break;

			case 'MONTH':
				$Datezone->sub(new DateInterval("P{$by_value}M"));
				break;

			case 'YEAR':
				$Datezone->sub(new DateInterval("P{$by_value}Y0M0DT0H0M0S"));
				break;
		}
		return $Datezone;
	}
}