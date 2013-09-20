<?php

abstract class Avangate_Manager {
	
	 protected function getClientProvider() {
		 $instance = Avangate_Client::getInstance();
		 if(! $instance->getError() && $instance->getSessionID()) {
			 return $instance;
		 }

		 return false;
	 }

	 protected function isValidClient($instance) {
		 return !$instance->getError() &&  $instance->getSessionID();
	 }
}
