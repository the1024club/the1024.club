<?php
require 'vendor/autoload.php';

// Generate a keypair
$keypair = sodium_crypto_sign_keypair();

// Extract the public and private keys
$publicKey = sodium_crypto_sign_publickey($keypair);
$privateKey = sodium_crypto_sign_secretkey($keypair);

// Encode keys to base64 for easy use
echo "Public Key: " . base64_encode($publicKey) . PHP_EOL;
echo "Private Key: " . base64_encode($privateKey) . PHP_EOL;
?>