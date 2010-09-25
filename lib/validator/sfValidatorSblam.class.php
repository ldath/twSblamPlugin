<?php

/**
 * sfValidatorSblam checks if form is not a spam using SBLAM service
 *
 * @package    antispam
 * @subpackage validator
 * @author     Arkadiusz Tułodziecki
 */
class sfValidatorSblam extends sfValidatorSchema {
	const HAM = -2;
	const PROBABLY_HAM = -1;
	const SERVER_ERROR = 0;
	const PROBABLY_SPAM = 1;
	const SPAM = 2;

	/**
	 * Constructor.
	 *
	 * @param array  $fieldnames  An array of fields to check
	 * @param string $apikey      Generated in http://sblam.com/key.html SBLAM apikey
	 * @param mixed  $not_valid   Level (int) or levels (array) when Validator throw not Valid error
	 * @param array  $hosts       Optionaly array of alternative SBLAM server hosts
	 * @param array  $options     An array of options
	 * @param array  $messages    An array of error messages
	 *
	 * @see sfValidatorBase
	 */
	public function __construct($fieldnames = NULL, $apikey = NULL, $not_valid = self::SPAM, $hosts = NULL, $options = array(), $messages = array()) {
		$this->addOption('fieldnames', $fieldnames);
		$this->addOption('apikey', $apikey);
		$this->addOption('not_valid', $not_valid);
		$this->addOption('hosts', $hosts);

		parent::__construct(null, $options, $messages);
	}

	/**
	 * @see sfValidatorBase
	 */
	protected function doClean($values) {
		if (null === $values) {
			$values = array();
		}

		if (!is_array($values)) {
			throw new InvalidArgumentException('You must pass an array parameter to the clean() method');
		}

		$sblamclient = new SblamClient($this->getOption('hosts'));
		$test = $sblamclient->testPost($this->getOption('fieldnames'), $this->getOption('apikey'));

		$valid = true;
		$not_valid = $this->getOption('not_valid');
		if (is_array($not_valid) and in_array($test, $not_valid)) {
			$valid = false;
		}
		if (is_int($not_valid) and $test >= $not_valid) {
			$valid = false;
		}

		if (!$valid) {
			throw new sfValidatorError($this, 'invalid', array(
				'sblam_id'  => $sblamclient->getLastId(),
				'error'     => $sblamclient->getLastError(),
				'raporturl' => $sblamclient->getReportUrl(),
			));
		}

		return $values;
	}
}