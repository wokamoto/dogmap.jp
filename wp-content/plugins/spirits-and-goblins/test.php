<?php
require dirname(__FILE__).'/includes/twilio-php/Services/Twilio.php';
require dirname(__FILE__).'/includes/php-otp-1.1.1/class.otp.php';

$otp = new otp();
$seed = 'hogehoge'; //$otp->generateSeed();
$seequence_count = 100;
$pass = $otp->generateOtp('wokamotohogehoge', $seed, $seequence_count, 'sha1');
//print_r($otp->initializeOtp($pass['hex_otp'], 'alpha1', $seequence_count, 'sha1'));
$pass_hex = $pass['hex_otp'];
$pass_dec = $otp->convertOtp($pass_hex, 'hex', 'dec');
$result = hexdec($pass_hex) % pow(10,6);
var_dump($result);

exit;

$version = "2010-04-01";

//$sid = 'AC442c1c184d88b3579b37235394e31f0e';
//$token = '733b83f3a148c84cad38b702be250334';
//$twilo_number = '+17542272846';

$sid = 'ACd08d53c86345838377a28cb5f0ccd081';
$token = '39d56cf7d2c816c82cfe4dbd910171af';
$twilo_number = '+15005550006';

$client = new Services_Twilio($sid, $token, $version);
try {
	$message = $client->account->sms_messages->create(
		$twilo_number,
		'+819046060654',
		'Hello World!'
		);
	echo "Success: {$message->sid} - {$message->body}\n";
} catch (Exception $e) {
	echo 'Error: ' . $e->getMessage() . "\n";
}
