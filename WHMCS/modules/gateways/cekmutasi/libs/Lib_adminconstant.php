<?php

class Lib_adminconstant
{
	public static $notify = false;
	public static $CekmutasiConfigs;

	public function __construct($configs = array())
	{
		$this->set_protocol(self::getThisUrlProtocol());
		$this->protocol = (isset($this->protocol) ? $this->protocol : $this->get_protocol());
		$this->set_hostname(self::getThisUrlHost());
		$this->hostname = (isset($this->hostname) ? $this->hostname : $this->get_hostname());
		$this->set_url('notify', "{$this->protocol}{$this->hostname}modules/gateways/callback/cekmutasi.php?page=notify");
		self::$CekmutasiConfigs = $configs;
	}

	public static function getConstantConfigs($vars)
	{
		$configs = array();
		if (!isset(self::$CekmutasiConfigs)) {
			return false;
		}
		switch (strtolower($vars)) {
			case 'banks':
				$configs = (isset(self::$CekmutasiConfigs['banks']) ? self::$CekmutasiConfigs['banks'] : NULL);
			break;
		}
		return $configs;
	}

	public static function getThisUrlProtocol()
	{
		if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			if ( $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) {
				$_SERVER['HTTPS']       = 'on';
				$_SERVER['SERVER_PORT'] = 443;
			}
		}
		$protocol = 'http://';
		if (isset($_SERVER['HTTPS'])) {
			$protocol = (($_SERVER['HTTPS'] == 'on') ? 'https://' : 'http');
		} else {
			$protocol = (isset($_SERVER["SERVER_PROTOCOL"]) ? $_SERVER["SERVER_PROTOCOL"] : 'http');
			$protocol = ((strtolower(substr($protocol, 0, 5)) =='https') ? 'https://': 'http://');
		}
		return $protocol;
	}

	public static function getThisUrlHost()
	{
		$currentPath = (isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
		$pathInfo = pathinfo(dirname($currentPath)); 
		$hostName = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
		$return = $hostName;
		$return .= ((substr($hostName, -1) == '/') ? '' : '/');
		return $return;
	}

	public static function _GET()
	{
		$__GET = array();
		$request_uri = ((isset($_SERVER['REQUEST_URI']) && (!empty($_SERVER['REQUEST_URI']))) ? $_SERVER['REQUEST_URI'] : '/');
		$_get_str = explode('?', $request_uri);
		if( !isset($_get_str[1]) ) return $__GET;
		$params = explode('&', $_get_str[1]);
		foreach ($params as $p) {
			$parts = explode('=', $p);
			$__GET[$parts[0]] = isset($parts[1]) ? $parts[1] : '';
		}
		return $__GET;
	}

	public function set_hostname($hostname)
	{
		$this->hostname = $hostname;
		return $this;
	}

	public function get_hostname()
	{
		return $this->hostname;
	}

	public function set_protocol($protocol)
	{
		$this->protocol = $protocol;
		return $this;
	}

	public function get_protocol()
	{
		return $this->protocol;
	}

	public static function set_url($type, $url)
	{
		switch (strtolower($type))
		{
			case 'notify':
			default:
				self::$notify = $url;
				break;
		}
	}
}

