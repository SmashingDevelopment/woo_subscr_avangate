<?php

class Avangate_Client
{

	const WSDL = 'https://api.avangate.com/subscription/2.0/soap/?wsdl';
	const SOAP_URL = 'https://api.avangate.com/subscription/2.0/soap/';

	private static $instance = null;
	private $client;
	private $sessionID = '';
	private $error = false;
	private $errorMessage = '';

	private function __construct()
	{
		try
		{
			$client = new SoapClient(self::WSDL);
			$avangate_settings = $this->getAvangateSettings();

			date_default_timezone_set('UTC');
			$merchantCode	 = $avangate_settings[Gateway_Avangate::MERCHANT_CODE];
			$key			 = $avangate_settings[Gateway_Avangate::SECRET_KEY];

			$now			 = date('Y-m-d H:i:s');
			$string			 = strlen($merchantCode) . $merchantCode . strlen($now) . $now;
			$hash	 	     = hash_hmac('md5', $string, $key);
			
			$sessionID  	 = $client->login($merchantCode, $now, $hash);

			$this->setSessionID($sessionID);


			$this->setClient($client);
		} catch (SoapFault $e)
		{
			$this->setError();
			$this->setErrorMessage($e->getMessage());
		}
	}

	/**
	 *
	 * @return \self
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance))
		{
			self::$instance = new self();
		}
		return new self();
		return self::$instance;
	}

	/**
	 * mock
	 */
	private function __clone()
	{

	}

	/**
	 * mock
	 */
	private function __wakeup()
	{
		
	}

	/**
	 *
	 * @return SoapClient
	 */
	public function getClient()
	{
		return $this->client;
	}

	public function setClient($client)
	{
		$this->client = $client;
	}

	public function getSessionID()
	{
		return $this->sessionID;
	}

	public function setSessionID($sessionID)
	{
		$this->sessionID = $sessionID;
	}

	public function getError()
	{
		return $this->error;
	}

	public function setError()
	{
		$this->error = true;
	}

	public function getErrorMessage()
	{
		return $this->errorMessage;
	}

	public function setErrorMessage($errorMessage)
	{
		$this->errorMessage = $errorMessage;
	}

	private function getAvangateSettings()
	{
		getAvangateSettings();
	}

}
