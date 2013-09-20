<?php

class Avangate_Manager_Admin extends Avangate_Manager {

	/**
	 * @see https://secure.avangate.com/cpanel/help.php?view=topic&topic=335#enableSubscription
	 */
	public function enableSubscription ($subscriptionReference) {
		$instance = Avangate_Client::getInstance();
		if($this->isValidClient($instance)) {
			$sessionID = $instance->getSessionID();
			$client = $instance->getClient();
			return $client->enableSubscription($sessionID, $subscriptionReference);
		}
		return false;
	}

	/**
	 * @see https://secure.avangate.com/cpanel/help.php?view=topic&topic=335#cancelSubscription
	 */
	public function cancelSubscription($subscriptionReference) {
		$instance = Avangate_Client::getInstance();
		if($this->isValidClient($instance)) {
			$sessionID = $instance->getSessionID();
			$client = $instance->getClient();
			return $client->cancelSubscription($sessionID, $subscriptionReference);
		}
		return false;
	}
	
}