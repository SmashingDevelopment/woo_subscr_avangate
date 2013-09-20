<?php

class Avangate_Manager_User extends Avangate_Manager {

	/**
	 * @see https://secure.avangate.com/cpanel/help.php?view=topic&topic=335#getCustomerSubscriptions
	 */
	public function getCustomerSubscriptions() {
		$instance = Avangate_Client::getInstance();
		if($this->isValidClient($instance)) {
			$sessionID = $instance->getSessionID();
			$client = $instance->getClient();
			return $client->getCustomerSubscriptions($sessionID, '', '');
		}
		return false;
	}
}
