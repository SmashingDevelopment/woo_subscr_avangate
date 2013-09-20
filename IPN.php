<?php
require '../../../wp-load.php';

$testPost = array(
	'IPN_PID' => 1,
	'IPN_PNAME' => 'test',
	'IPN_DATE' =>date(),
	'DATE'=> date(),
	'SECRET_KEY'=>'123',
	'AVANGATE_CUSTOMER_REFERENCE'=>'321321',
	'FIRSTNAME'=>'Andrey',
	'LASTNAME'=> 'Sivachok',
	'CUSTOMEREMAIL'=>'test@gmail.com',
);


$ipn = new Avangate_ResponseProcess($testPost);
$ipn->process();
