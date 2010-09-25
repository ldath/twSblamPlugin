<?php

/**
 * sblamchallange actions.
 *
 * @package    antispam
 * @subpackage sblamchallange
 * @author     Arkadiusz Tułodziecki
 */
class SblamChallangeActions extends sfActions {
	public function executeIndex(sfWebRequest $request) {
		session_cache_limiter('nocache');

		$request->setRequestFormat('js');
		$this->getResponse()->addCacheControlHttpHeader('max_age=3600');
		$this->getResponse()->addCacheControlHttpHeader('private=True');

		$serveruid = SblamClient::sblamserveruid();

		$magic = dechex(mt_rand()) . ';' . dechex(time()) . ';' . $_SERVER['REMOTE_ADDR'];
		$magic = addslashes(md5($serveruid . $magic) . $magic);


		$this->getResponse()->setCookie('sblam_', md5($magic . $serveruid), time()+3600);

		$fieldname = 'sc'.abs(crc32($serveruid));

		$this->fieldname = $fieldname;
		$this->magic = $magic;
	}
}
