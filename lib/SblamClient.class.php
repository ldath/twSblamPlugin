<?php
/**
 * SblamClient library.
 *
 * @package    antispam
 * @subpackage library
 * @author     Arkadiusz Tułodziecki
 * @author     PorneL
 * @version    SVN: $Id: SblamClient.class.php 3073 2010-03-14 12:57:18Z ldath $
 */
class SblamClient {

	const RESPONSE_RAW = 0;
	const RESPONSE_ANALYZED = 1;

	const REQUEST_IN = 0;
	const REQUEST_DATA = 1;
	const REQUEST_RAW = 2;

	protected $hosts = array('api.sblam.com','api2.sblam.com','spamapi.geekhood.net');
	protected $apikey = 'default';
	protected $last_id = NULL;
	protected $last_host = NULL;
	protected $last_error = NULL;

	private $compress = false;
	private $request_in;
	private $request_data;
	private $request_raw;

	private $server_responce;
	private $parsed_responce = 0;

	/**
	 * Constructor class
	 *
	 * @param array $hosts  Optionaly array of alternative SBLAM server hosts
	 */
	public function __construct($hosts = NULL) {
		if (!is_null($hosts) and is_array($hosts)) {
			$this->hosts = $hosts;
		}
		if (count($this->hosts) > 1) {
			// randomize SBLAM server use
			shuffle($this->hosts);
		}
	}

	/**
	 * Blog/Forum/Comment POST testing method
	 *
	 * @param array   $fieldnames  Optional Array with field names.
	 *                              Will be read as
	 *                                  1 - description,
	 *                                  2 - author,
	 *                                  3 - author e-mail,
	 *                                  4 - author www.
	 *                              If record not exists in form then NULL
	 *                              in place on not existing field name in form.
	 * @param string  $apikey      Generated in http://sblam.com/key.html SBLAM apikey
	 *
	 * @return int                 Returns -2 or -1 or 0 or 1 or 2 Where
	 *                                 -2 - For sure not SPAM
	 *                                 -1 - Probably not SPAM
	 *                                  0 - Server Error
	 *                                  1 - Probably SPAM
	 *                                  2 - For sure SPAM
	 */
	public function testPost($fieldnames = NULL, $apikey = NULL) {
		if (!count($_POST)) {
			$this->last_error = 'Sblam: This test  need $_POST data to analyze\n';
			return 0;
		}
		if (!function_exists('fsockopen')) {
			$this->last_error = 'Sblam: sockets (fsockopen) extension to use SBLAM\n';
			return 0;
		}

		if (!is_null($apikey)) {
			$this->apikey = $apikey;
		}
		$this->createInputArray($fieldnames);
		$data = '';
		foreach ($this->request_in as $key => $val) {
			$data .= strtr($key,"\0"," ")."\0".strtr($val,"\0"," ")."\0";
		}
		$this->request_data = $data;
		$req_len = strlen($data);
		if ($req_len > 300000) {
			$this->last_error = 'Sblam: Message to big: '.$req_len.' > 300000';
			return 0;
		}

		if ($req_len > 5000 && function_exists('gzcompress')) {
			$this->compress = true;
			$data = gzcompress($data, 1);
		}

		return $this->makeTest($data);
	}

	/**
	 * Special method for testing marked posts
	 *
	 * @param string $data
	 * @param string $salt
	 * @param string $apikey
	 * @return int
	 */
	public function testPreparedRawPost($data, $salt, $apikey = NULL) {
		if (!is_null($apikey)) {
			$this->apikey = $apikey;
		}
		$res = array();
		if (empty($data) || !preg_match('!^application/x-sblam\s*;\s*sig\s*=\s*([a-z0-9]{32})([a-z0-9]{32})(\s*;\s*compress\s*=\s*gzip)?\s*$!i', $data, $res)) {
			$this->last_error = 'Sblam: Wrong RAW data for query\n';
			return 0;
		}
		$this->request_in['salt'] = $salt;

		$this->compress = !empty($res[3]);

		return $this->makeTest($data);
	}

	/**
	 * Sending prepared data and taking response
	 *
	 * @param string $data
	 * @return int
	 */
	protected function makeTest($data) {
		do {
			$host = array_pop($this->hosts);
			$this->createRequest($host, $data);
			$this->createResponse($host);
			$responce = $this->getResponse();
		} while (!is_null($host) && $responce != 0);
		return $responce;
	}

	/**
	 * Method to automaticly send reports about possible wrong filter result
	 *
	 * @param int     $post_id  Post ID registered by SBLAM
	 * @param string  $host     Host of server where POST was registered
	 */
	public function sendReport($post_id, $host) {
		; // TODO: Here API 2 raport request
	}

	/**
	 * Returns last created SBLAM ID
	 *
	 * @return int
	 */
	public function getLastId() {
		return $this->last_id;
	}

	/**
	 * Returns URL to place where bug filtr report can be created
	 *
	 * @return string
	 */
	public function getReportUrl() {
		return 'http://sblam.com/report/'.$this->last_id;
	}

	/**
	 * Returns last error/errors or NULL if all checks go OK.
	 *
	 * Client trying to connect more servers than success check can be achived even with errors
	 *
	 * @return mixed
	 */
	public function getLastError() {
		return $this->last_error;
	}

	/**
	 * Getting request
	 *
	 * This method in most situations is for internal use but can be
	 * very helpful for debug use
	 *
	 * @param const $rqtype  By default self::REQUEST_RAW
	 * @return mixed
	 */
	public function getRequest($rqtype = self::REQUEST_RAW) {
		if ($rqtype == self::REQUEST_RAW) {
			return $this->request_raw;
		}
		if ($rqtype == self::REQUEST_DATA) {
			return $this->request_data;
		}
		if ($rqtype == self::REQUEST_IN) {
			return $this->request_in;
		}
		return NULL;
	}

	/**
	 * Getting response
	 *
	 * This method in most situations is for internal use but can be
	 * very helpful for debug use
	 *
	 * @param const $rstype  By default self::RESPONSE_ANALYZED
	 * @return mixed
	 */
	public function getResponse($rstype = self::RESPONSE_ANALYZED) {
		if ($rstype == self::RESPONSE_ANALYZED) {
			return $this->parsed_responce;
		}
		if ($rstype == self::RESPONSE_RAW) {
			return $this->server_responce;
		}
		return NULL;
	}

	/**
	 * Helper function for SBLAM - for internal use and JS challenge
	 *
	 * @return string
	 */
	static public function sblamserveruid() {
		return md5(PHP_VERSION . __FILE__ . $_SERVER['HTTP_HOST']);
	}

	/**
	 * Creating Request for SBLAM server
	 *
	 * @param string $host  Hostname of sblam server
	 * @param string $data  Prepared data for tests
	 */
	protected function createRequest($host, $data) {
		$this->request_raw =
			"POST / HTTP/1.1\r\n" .
			"Host:$host\r\n" .
			"Connection:close\r\n" .
			"Content-Type:application/x-sblam;sig=".md5("^&$@$2\n".$this->apikey."@@").md5($this->apikey . $data).($this->compress?";compress=gzip":'')."\r\n" .
			"Content-Length:" . strlen($data) . "\r\n".
			"\r\n".$data;
		;
	}

	/**
	 * Catching and Analizing Response from SBLAM server
	 *
	 * @param string $host  Hostname of sblam server
	 */
	protected function createResponse($host) {
		$errn = $errs = $out = $res = null;
		$fs = @fsockopen($host, 80, $errn, $errs, 5);
		if ($fs !== false && function_exists('stream_set_timeout')) {
			stream_set_timeout($fs, 15);
		}
		if ($fs !== false && fwrite($fs, $this->request_raw)) {
			$this->server_responce = '';
			while(!feof($fs)) {
				$this->server_responce .= fread($fs, 1024);
				if (preg_match('!\r\n\r\n.*\n!', $this->server_responce)) {
					break;
				}
			}
			fclose($fs);

			if (preg_match('!HTTP/1\..\s+(\d+\s+[^\r\n]+)\r?\n((?:[^\r\n]+\r?\n)+)\r?\n(.+)!s', $this->server_responce, $out)) {
				if (intval($out[1]) == 200) {
					if (preg_match('!^(-?\d+):([a-z0-9-]{0,42}):([a-z0-9]{32})!',$out[3],$res)) {
						if (md5($this->apikey . $res[1] . $this->request_in['salt']) === $res[3]) {
							$this->last_id = $res[2];
							$this->parsed_responce = $res[1];
							$this->last_host = $host;
						} else {
							$this->last_error .="Sblam: Server $host result have not valid signature\n";
						}
					} else {
						$this->last_error .="Sblam: Server $host Error. Response have not valid format: ".htmlspecialchars($out[3])."\n";
					}
				} else {
					$this->last_error .="Sblam: Server $host responsce: ".htmlspecialchars(substr($out[1], 0, 80))."\n";
				}
			} else {
				$this->last_error .="Sblam: Not valid response from $host server\n";
			}
		} else {
			$this->last_error .= "Sblam: Communication problem with server $host - $errn:$errs\n";
		}
	}

	/**
	 * Preparing special request input array
	 *
	 * @param mixed $fieldnames  NULL or array of form names in order
	 */
	private function createInputArray($fieldnames) {
		$in = array(
			'uid'        => SblamClient::sblamserveruid(),
			'uri'        => empty($_SERVER['REQUEST_URI'])?$_SERVER['SCRIPT_NAME'].'?'.$_SERVER['QUERY_STRING']:$_SERVER['REQUEST_URI'],
			'host'       => empty($_SERVER['HTTP_HOST'])?$_SERVER['SERVER_NAME']:$_SERVER['HTTP_HOST'],

			'ip'         => $_SERVER['REMOTE_ADDR'],
			'time'       => time(),

			'cookies'    => count($_COOKIE)?1:0,
			'session'    => isset($_COOKIE[session_name()])?1:0,
			'sblamcookie'=> isset($_COOKIE['sblam_'])?$_COOKIE['sblam_']:'',

			'salt'       =>'x'.mt_rand().time(),
		);


		if (is_array($fieldnames)) {
			$i = 0;
			foreach($fieldnames as $key => $val) {
				if (is_array($val)) {
					foreach($val as $k => $v) {
						$in['field_'.$i] = $key.'_'.$v;
						$i++;
					}
				} else {
					$in['field_'.$i] = $val;
					$i++;
				}
			}
		}

		foreach($_POST as $key => $val) {
			if (is_array($val)) {
				foreach($val as $k => $v) {
					$in['POST_'.$key.'_'.$k] = stripslashes(is_array($v)?implode("\n",$v):$v);
				}
			} else {
				$in['POST_'.$key] = stripslashes(is_array($val)?implode("\n",$val):$val);
			}
		}

		if (function_exists("getallheaders")) {
			foreach(getallheaders() as $header => $val) {
				$in['HTTP_'.strtr(strtoupper($header),"-","_")] = $val;
			}
		} else {
			foreach($_SERVER as $key => $val) {
				if (substr($key,0,5) !== 'HTTP_') {
					continue;
				}
				$in[$key] = stripslashes($val);
			}
		}
		unset($in['HTTP_COOKIE']);
		unset($in['HTTP_AUTHORIZATION']);
		$this->request_in = $in;
	}
}
