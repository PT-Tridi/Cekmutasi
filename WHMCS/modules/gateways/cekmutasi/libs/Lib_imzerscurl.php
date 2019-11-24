<?php

if (!defined('WHMCS')) {
	exit('No direct script access allowed');
}

class Lib_imzerscurl
{
	public $instance = NULL;
	public $endpoint;
	public $error = FALSE,
			$error_msg = array();
	public $UA = "Cekmutasi WHMCS/1.0";

	function __construct()
	{
		# Set Endpoint
		$this->set_endpoint('');
		
		# Headers
		$this->set_headers();
		$this->add_headers('Content-type', 'application/x-www-form-urlencoded');
	}

	function set_endpoint($endpoint)
	{
		$this->endpoint = $endpoint;
		return $this;
	}

	//=======================================================================================================================
	function create_curl_request($action, $url, $UA, $headers = null, $params = array(), $timeout = 30)
	{
		$cookie_file = (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'cookies.txt');
		$url = (is_string($url) ? $url : '');

		if (strlen($url) > 0)
		{
			$url = str_replace( "&amp;", "&", urldecode(trim($url)) );
		}
		else
		{
			return FALSE;
		}

		$ch = curl_init();
		switch (strtolower($action))
		{
			case 'get':
				if ((is_array($params)) && (count($params) > 0)) {
					$Querystring = http_build_query($params);
					$url .= "?";
					$url .= $Querystring;
				}
				break;

			case 'post':
			default:
				$url .= "";
				break;
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, true);

		if( $headers != null )
		{
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		else
		{
			curl_setopt($ch, CURLOPT_HEADER, false);
		}

		curl_setopt($ch, CURLOPT_USERAGENT, $UA);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_COOKIE, $cookie_file);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

		$post_fields = NULL;
		switch (strtolower($action))
		{
			case 'get':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
				break;

			case 'put':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				break;

			case 'delete':
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
		}

		switch (strtolower($action))
		{
			case 'get':
				curl_setopt($ch, CURLOPT_POST, false);
				curl_setopt($ch, CURLOPT_POSTFIELDS, null);
				break;

			case 'put':
			case 'post':
			default:
				if ((is_array($params) && (count($params) > 0)) && (is_array($headers) && count($headers) > 0))
				{
					foreach ($headers as $heval)
					{
						$getContentType = explode(":", $heval);

						if (strtolower($getContentType[0]) !== 'content-type') {
							continue;
						}

						switch (strtolower(trim($getContentType[0])))
						{
							case 'content-type':
								if (isset($getContentType[1]))
								{
									if (is_string($getContentType[1]))
									{
										if (strpos(strtolower(trim($getContentType[1])), 'application/xml') !== FALSE)
										{
											$post_fields = $post_fields;
										}
										elseif (strpos(strtolower(trim($getContentType[1])), 'application/json') !== FALSE)
										{
											$post_fields = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
										}
										elseif (strpos(strtolower(trim($getContentType[1])), 'application/x-www-form-urlencoded') !== FALSE)
										{
											$post_fields = http_build_query($params);
										}
										elseif (strpos(strtolower(trim($getContentType[1])), 'multipart/form-data') !== FALSE)
										{
											$post_fields = http_build_query($params);
										}
										else
										{
											$post_fields = http_build_query($params);
										}
									}
								}
								break;

							default:
								$post_fields = http_build_query($params);
								break;
						}
					}
				}
				elseif((!empty($params)) || ($params != ''))
				{
					$post_fields = $params;
				}

				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
				break;
		}

		// Get Response
		$response = curl_exec($ch);
		$mixed_info = curl_getinfo($ch);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header_string = substr($response, 0, $header_size);
		$header_content = $this->get_headers_from_curl_response($header_string);
		$header_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (count($header_content) > 1)
		{
			$header_content = end($header_content);
		}

		$body = substr($response, $header_size);
		curl_close($ch);

		$return = array(
			'request'		=> array(
				'method'			=> $action,
				'host'				=> $url,
				'header'			=> $headers,
				'body'				=> $post_fields,
			),
			'response'		=> array(),
		);

		if (!empty($response) || $response != '')
		{
			$return['response']['code'] = (int) $header_code;
			$return['response']['header'] = array(
				'size' => $header_size, 
				'string' => $header_string,
				'content' => $header_content,
			);

			$return['response']['body'] = $body;
			return $return;
		}
		return false;
	}

	private static function get_headers_from_curl_response($headerContent)
	{
		$headers = array();
		$arrRequests = explode("\r\n\r\n", $headerContent);
		for ($index = 0; $index < (count($arrRequests) - 1); $index++)
		{
			foreach (explode("\r\n", $arrRequests[$index]) as $i => $line)
			{
				if ($i === 0)
				{
					$headers[$index]['http_code'] = $line;
				}
				else
				{
					list ($key, $value) = explode(': ', $line);
					$headers[$index][$key] = $value;
				}
			}
		}
		return $headers;
	}

	public function generate_curl_headers($headers = null)
	{
		if (!isset($headers))
		{
			$headers = $this->headers;
		}

		return $this->create_curl_headers($headers);
	}

	public function create_curl_headers($headers = array())
	{
		$curlheaders = array();
		foreach ($headers as $ke => $val) {
			$curlheaders[] = "{$ke}: {$val}";
		}
		return $curlheaders;
	}

	//------- utils
	function sanitize_file_name( $filename )
	{
		$filename_raw = $filename;
		$special_chars = array("?", "[", "]", "/", "\\", "=", "<", ">", ":", ";", ",", "'", "\"", "&", "$", "#", "*", "(", ")", "|", "~", "`", "!", "{", "}");
		foreach ($special_chars as $chr)
		{
			$filename = str_replace($chr, '', $filename);
		}
		$filename = preg_replace('/[\s-]+/', '-', $filename);
		$filename = trim($filename, '.-_');
		$filename;
	}

	function sanitize_url_parameter($params_input = array())
	{
		$sanitized = [];
		if (count($params_input) > 0)
		{
			foreach ($params_input as $key => $keval)
			{
				if (!is_array($keval) || (!is_object($keval)))
				{
					$keval = filter_var($keval, FILTER_SANITIZE_URL);
				}

				$sanitized[$key] = $keval;
			}
		}
		return $sanitized;
	}

	//------
	function set_headers($headers = array())
	{
		$this->headers = $headers;
		return $this;
	}

	function reset_headers()
	{
		$this->headers = null;
		return $this;
	}

	function add_headers($key, $val)
	{
		if (!isset($this->headers))
		{
			$this->headers = $this->get_this_headers();
		}

		$add_header = array($key => $val);
		$this->headers = array_merge($add_header, $this->headers);
	}

	function get_this_headers()
	{
		return $this->headers;
	}

	function create_permalink($url)
	{
		$url = strtolower($url);
		$url = preg_replace('/&.+?;/', '', $url);
		$url = preg_replace('/\s+/', '_', $url);
		$url = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '_', $url);
		$url = preg_replace('|%|', '_', $url);
		$url = preg_replace('/&#?[a-z0-9]+;/i', '', $url);
		$url = preg_replace('/[^%A-Za-z0-9 \_\-]/', '_', $url);
		$url = preg_replace('|_+|', '-', $url);
		$url = preg_replace('|-+|', '-', $url);
		$url = trim($url, '-');
		$url = (strlen($url) > 128) ? substr($url, 0, 128) : $url;
		return $url;
	}
	
	//--------------------------------------------------
	// Utils
	//--------------------------------------------------
	public static function parse_raw_http_request($content_type)
	{
		$input = file_get_contents('php://input');
		preg_match('/boundary=(.*)$/', $content_type, $bound_matches);
		$boundary = (isset($bound_matches[1]) ? $bound_matches[1] : '');
		$a_blocks = preg_split("/-+{$boundary}/", $input);
		array_pop($a_blocks);
		$a_data = array();
		// loop data blocks
		$i = 0;
		foreach ($a_blocks as $id => $block)
		{
			if (empty($block)) {
				continue;
			}

			if (strpos($block, 'application/octet-stream') !== FALSE)
			{
				preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
			}
			else
			{
				// match "name" and optional value in between newline sequences
				preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
			}

			if (isset($matches[1]) && isset($matches[2])) {
				$a_data[$matches[1]] = $matches[2];
			}

			$i += 1;
		}

		return $a_data;
	}

	public static function php_input_request()
	{
		###############################
		# Request Input
		###############################
		$RequestInputParams = array();
		$RequestInput = array();
		$incomingHeaders = self::apache_headers();

		if (isset($incomingHeaders['Content-Type']))
		{
			if ((!is_array($incomingHeaders['Content-Type'])) && (!is_object($incomingHeaders['Content-Type'])))
			{
				$incomingHeaders['Content-Type'] = strtolower($incomingHeaders['Content-Type']);
				if (strpos($incomingHeaders['Content-Type'], 'application/json') !== FALSE)
				{
					$RequestInput = file_get_contents("php://input");
					if (!$RequestInputJson = json_decode($RequestInput, true))
					{
						parse_str($RequestInput, $RequestInputParams);
					}
					else
					{
						$RequestInputParams = $RequestInputJson;
					}
				}
				elseif (strpos($incomingHeaders['Content-Type'], 'application/x-www-form-urlencoded') !== FALSE)
				{
					$RequestInput = file_get_contents("php://input");
					parse_str($RequestInput, $RequestInputParams);
				}
				elseif (strpos($incomingHeaders['Content-Type'], 'application/xml') !== FALSE)
				{
					$RequestInput = file_get_contents("php://input");
					$RequestInputParams = $RequestInput;
				}
				elseif (strpos($incomingHeaders['Content-Type'], 'multipart/form-data') !== FALSE)
				{
					$RequestInput = self::parse_raw_http_request($incomingHeaders['Content-Type']);
					$RequestInputParams = $RequestInput;
					if ($_SERVER['REQUEST_METHOD'] == 'POST')
					{
						if (isset($_POST) && (count($_POST) > 0))
						{
							foreach ($_POST as $k => $v) {
								$RequestInputParams = array_merge(array($k => $v), $RequestInputParams);
							}
						}

						if (isset($_FILES) && (count($_FILES) > 0))
						{
							foreach ($_FILES as $k => $v) {
								$RequestInputParams = array_merge(array($k => $v), $RequestInputParams);
							}
						}
					}
				}
				else
				{
					$RequestInput['ktl'] = 'latos';
					self::parse_raw_http_request($incomingHeaders['Content-Type'], $RequestInput);
					$RequestInputParams = $RequestInput;
					if ($_SERVER['REQUEST_METHOD'] == 'POST')
					{
						if (isset($_POST) && (count($_POST) > 0))
						{
							foreach ($_POST as $k => $v) {
								$RequestInputParams = array_merge(array($k => $v), $RequestInputParams);
							}
						}
						if (isset($_FILES) && (count($_FILES) > 0))
						{
							foreach ($_FILES as $k => $v) {
								$RequestInputParams = array_merge(array($k => $v), $RequestInputParams);
							}
						}
					}
				}
			}
		}
		else
		{
			$RequestInput = file_get_contents("php://input");
			parse_str($RequestInput, $RequestInputParams);
		}
		$params['input'] = $RequestInput;
		$params['header'] = $incomingHeaders;
		$params['body'] = $RequestInputParams;
		return $params;
	}

	public static function php_input_querystring()
	{
		$__GET = (isset($_GET) ? $_GET : array());
		$request_uri = ((isset($_SERVER['REQUEST_URI']) && (!empty($_SERVER['REQUEST_URI']))) ? $_SERVER['REQUEST_URI'] : '');
		$query_string = (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
		parse_str(parse_url(html_entity_decode($request_uri), PHP_URL_QUERY), $array);
		if (count($array) > 0)
		{
			foreach ($array as $key => $val)
			{
				$__GET[$key] = $val;
			}
		}
		return $__GET;
	}

	public static function _GET()
	{
		$__GET = (isset($_GET) ? $_GET : array());
		$request_uri = ((isset($_SERVER['REQUEST_URI']) && (!empty($_SERVER['REQUEST_URI']))) ? $_SERVER['REQUEST_URI'] : '');
		$_get_str = explode('?', $request_uri);
		if( !isset($_get_str[1]) ) return $__GET;
		$params = explode('&', $_get_str[1]);
		foreach ($params as $p) {
			$parts = explode('=', $p);
			$parts[0] = (is_string($parts[0]) ? strtolower($parts[0]) : $parts[0]);
			$__GET[$parts[0]] = isset($parts[1]) ? $parts[1] : '';
		}
		return $__GET;
	}

	public static function apache_headers()
	{
		if (function_exists('apache_request_headers'))
		{
			$headers = apache_request_headers();
			$out = array();
			foreach ($headers AS $key => $value) {
				$key = str_replace(" ", "-", ucwords(strtolower(str_replace("-", " ", $key))));
				$out[$key] = $value;
			}
			if	(isset($_SERVER['CONTENT_TYPE'])) {
				$out['Content-Type'] = $_SERVER['CONTENT_TYPE'];
			}
			if (isset($_ENV['CONTENT_TYPE'])) {
				$out['Content-Type'] = $_ENV['CONTENT_TYPE'];
			}
		}
		else
		{
			$out = array();

			if	(isset($_SERVER['CONTENT_TYPE'])) {
				$out['Content-Type'] = $_SERVER['CONTENT_TYPE'];
			}

			if (isset($_ENV['CONTENT_TYPE'])) {
				$out['Content-Type'] = $_ENV['CONTENT_TYPE'];
			}

			if (isset($_SERVER))
			{
				if (count($_SERVER) > 0)
				{
					foreach ($_SERVER as $key => $value)
					{
						if (substr($key, 0, 5) == "HTTP_")
						{
							$key = str_replace(" ", "-", ucwords(strtolower(str_replace("_", " ", substr($key, 5)))));
							$out[$key] = $value;
						}
					}
				}
			}
		}
		return $out;
	}
}