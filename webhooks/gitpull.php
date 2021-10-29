<?php
$debug = false;
$secret = 'secret';
$gitCommand = 'git pull hub master';

if (!$debug) {
	$headers = getallheaders();
	$hubSignature = $headers['X-Hub-Signature'] ?? null;
	// Check for X-Hub-Signature header
	if (!$hubSignature) {
		die('HTTP header "X-Hub-Signature" is missing');
	}
	// Split signature into algorithm and hash
	list($algo, $hash) = explode('=', $hubSignature, 2);
	// Check for correct algorithm
	if (!in_array($algo, hash_algos(), TRUE)) {
		die('Hash algorithm "' . $algo . '" is not supported');
	}
	// Get payload
	$payload = file_get_contents('php://input');
	// Calculate hash based on payload and the secret
	$payloadHash = hash_hmac($algo, $payload, $secret);
	// Check if hashes are equivalent
	if (!hash_equals($hash, $payloadHash)) {
		die('Access denied');
	}
	//get payload it exists
	$body = file_get_contents('php://input');
	//decode json
	$payload = json_decode($body, true);
}

require_once(__DIR__ . '/../vendor/autoload.php');

use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

$key = PublicKeyLoader::load(file_get_contents(__DIR__ . '/id_rsa_github'));

$ssh = new SSH2('sshsite.domain');
if (!$ssh->login('login', $key)) {
	die('Login Failed');
}

echo $ssh->exec($gitCommand);