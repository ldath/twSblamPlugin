<?php

if (sfConfig::get('app_tw_sblam_plugin_routes_register', true) && in_array('SblamChallange', sfConfig::get('sf_enabled_modules', array()))) {
	$this->dispatcher->connect('routing.load_configuration', array('SblamClientRouting', 'listenToRoutingLoadConfigurationEvent'));
}
