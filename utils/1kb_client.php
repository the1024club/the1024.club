<?php

// Load Sodium library
require 'vendor/autoload.php';

// Define the base URL for the API
$baseUrl = 'https://the1024.club';

// Display usage instructions
function showUsage() {
    echo "Usage:\n";
    echo "  php 1kb_client.php create <public_key_file>\n";
    echo "  php 1kb_client.php update <public_key_file> <private_key_file> <data_to_sign> <mime_type>\n";
    echo "  php 1kb_client.php retrieve <public_key_file>\n";
    exit(1);
}

// Function to perform safe base64 encoding (URL safe)
function safeBase64Encode($input) {
    return str_replace(['+', '/'], ['-', '_'], base64_encode($input));
}

// Function to perform safe base64 decoding (URL safe)
function safeBase64Decode($input) {
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $input));
}

// Function to perform cURL requests
function sendCurlRequest($url, $method, $postData = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('Error: ' . curl_error($ch) . "\n");
    }

    curl_close($ch);
    return $response;
}

// Check if sufficient arguments are provided
if ($argc < 3) {
    showUsage();
}

// Get the operation from the command line
$operation = $argv[1];
$publicKeyFile = $argv[2];

// Load public key from file
if (!file_exists($publicKeyFile)) {
    die("Public key file not found.\n");
}
$publicKey = trim(file_get_contents($publicKeyFile));
$decodedPublicKey = base64_decode($publicKey);
if ($decodedPublicKey === false || strlen($decodedPublicKey) !== 32) {
    die("Invalid public key format. It should be 32 bytes after base64 decoding.\n");
}
$base64PublicKey = safeBase64Encode($decodedPublicKey); // URL-safe encoding

// Create a new 1KB page
if ($operation === 'create') {
    // Send the create request with the public key included
    $url = "$baseUrl?action=create";
    $postData = [
        'pubkey' => $base64PublicKey,
        'public_key' => $publicKey // Send public key separately to store in the new column
    ];

    $response = sendCurlRequest($url, 'POST', $postData);
    echo "Server response: $response\n";
}

// Update an existing 1KB page
elseif ($operation === 'update') {
    if ($argc < 6) {
        showUsage();
    }

    $privateKeyFile = $argv[3];
    $dataToSign = $argv[4];
    $mimeType = $argv[5];

    // Load private key from file
    if (!file_exists($privateKeyFile)) {
        die("Private key file not found.\n");
    }

    $privateKey = trim(file_get_contents($privateKeyFile));
    $decodedPrivateKey = base64_decode($privateKey);

    if ($decodedPrivateKey === false || strlen($decodedPrivateKey) !== 64) {
        die("Invalid private key format. It should be 64 bytes after base64 decoding.\n");
    }

    // Sign the data using the private key
    $signature = sodium_crypto_sign_detached($dataToSign, $decodedPrivateKey);
    $base64Signature = safeBase64Encode($signature); // URL-safe encoding

    // Send the update request
    $url = "$baseUrl?action=update";
    $postData = [
        'pubkey' => $base64PublicKey,
        'signature' => $base64Signature,
        'data' => $dataToSign,
        'mime_type' => $mimeType
    ];

    $response = sendCurlRequest($url, 'PUT', $postData);
    echo "Server response: $response\n";
}

// Retrieve an existing 1KB page
elseif ($operation === 'retrieve') {
    // URL encode the Base64-encoded public key for the URL
    $encodedPubKey = rawurlencode($base64PublicKey);

    // Send the retrieve request
    $url = "$baseUrl?action=retrieve&pubkey=$encodedPubKey";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        die('Error: ' . curl_error($ch) . "\n");
    }

    curl_close($ch);
    echo "Server response: $response\n";
}

// Invalid operation
else {
    showUsage();
}
?>
