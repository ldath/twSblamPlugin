<?php
/**
 * SblamClientRouting library.
 *
 * @package    antispam
 * @subpackage library
 * @author     Arkadiusz Tułodziecki
 * @version    SVN: $Id: SblamClientRouting.class.php 3070 2010-03-13 22:30:57Z ldath $
 */
class SblamClientRouting {
	/**
	 * Listens to the routing.load_configuration event.
	 *
	 * @param sfEvent An sfEvent instance
	 */
	static public function listenToRoutingLoadConfigurationEvent(sfEvent $event) {
		$r = $event->getSubject();

		// preprend our routes
		$r->prependRoute('tw_sblam_challange', new sfRoute ('/tw_sblam_challange.js', array('module' => 'SblamChallange', 'action' => 'index')));
	}
}
