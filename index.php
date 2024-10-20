<?php

// Load dependencies
require 'vendor/autoload.php';
use ParagonIE\Sodium\CryptoSign;

// Enable or disable debugging/logging
$debug = isset($_GET['debug']) ? (bool)$_GET['debug'] : true;
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Database setup function
function initializeDatabase($db) {
    $query = "
    CREATE TABLE IF NOT EXISTS storage (
        pubkey_hash CHAR(64) PRIMARY KEY,
        data TEXT NOT NULL,
        mime_type VARCHAR(50) NOT NULL,
        public_key BLOB NOT NULL
    )";
    $db->exec($query);

    // Check if the 'public_key' column already exists; if not, add it.
    $columns = $db->query("PRAGMA table_info(storage)")->fetchAll(PDO::FETCH_ASSOC);
    $hasPublicKeyColumn = false;

    foreach ($columns as $column) {
        if ($column['name'] === 'public_key') {
            $hasPublicKeyColumn = true;
            break;
        }
    }

    if (!$hasPublicKeyColumn) {
        $db->exec("ALTER TABLE storage ADD COLUMN public_key BLOB NOT NULL");
    }
}

// Initialize SQLite and auto-create the database and table if they don't exist
$db = new PDO('sqlite:/home/retrodig/1024clubdb/storage.db');
initializeDatabase($db);

// Helper function to respond with JSON
function respond($status, $message, $data = []) {
    http_response_code($status);
    echo json_encode(['message' => $message, 'data' => $data]);
    exit;
}

// Enhance Base64 decoding to handle both raw and URL-safe inputs
function safeBase64Decode($input) {
    // First, attempt a direct Base64 decode
    $decoded = base64_decode($input, true);
    if ($decoded !== false && strlen($decoded) === 32) {
        return $decoded;
    }

    // If the direct decode fails, attempt a URL-safe decode
    $urlSafeInput = str_replace(['-', '_'], ['+', '/'], $input);
    return base64_decode($urlSafeInput, true);
}

// Handle Base64 encoding safely
function safeBase64Encode($input) {
    return str_replace(['+', '/'], ['-', '_'], base64_encode($input));
}

// Check if 'action' is set in the query parameters before proceeding
if (isset($_GET['action'])) {
    // Create or get storage space based on pubkey hash
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'create') {
        $pubkey = $_POST['pubkey'] ?? null;

        if (!$pubkey) {
            respond(400, "Missing public key");
        }

        // Decode the public key, try both raw and URL-safe Base64 decodings
        $decodedPubkey = safeBase64Decode($pubkey);
        if ($decodedPubkey === false || strlen($decodedPubkey) !== 32) {
            respond(400, "Invalid public key");
        }

        $pubkey_hash = bin2hex(sodium_crypto_generichash($decodedPubkey));

        $stmt = $db->prepare("SELECT * FROM storage WHERE pubkey_hash = :pubkey_hash");
        $stmt->execute([':pubkey_hash' => $pubkey_hash]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            respond(200, "Space already exists", ['pubkey_hash' => $pubkey_hash]);
        }

        $stmt = $db->prepare("INSERT INTO storage (pubkey_hash, data, mime_type, public_key) VALUES (:pubkey_hash, '', 'text/plain', :public_key)");
        $stmt->execute([':pubkey_hash' => $pubkey_hash, ':public_key' => $decodedPubkey]);

        respond(201, "Space created", ['pubkey_hash' => $pubkey_hash]);
    }

// Retrieve stored data using the public key or public key hash
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'retrieve') {
    $pubkey = $_GET['pubkey'] ?? null;
    $pubkey_hash = $_GET['pubkey_hash'] ?? null;

    if ($pubkey) {
        // Replace spaces with '+' to account for unencoded URLs
        $pubkey = str_replace(' ', '+', $pubkey);

        // Attempt to decode public key (try both standard and URL-safe Base64)
        $decodedPubkey = safeBase64Decode($pubkey);

        if ($decodedPubkey === false || strlen($decodedPubkey) !== 32) {
            respond(400, "Failed to decode public key: " . htmlspecialchars($pubkey));
        }

        // Hash the decoded public key
        $pubkey_hash = bin2hex(sodium_crypto_generichash($decodedPubkey));

    } elseif ($pubkey_hash) {
        // Validate the provided public key hash (must be 64 characters long, hex format)
        if (!ctype_xdigit($pubkey_hash) || strlen($pubkey_hash) !== 64) {
            respond(400, "Invalid public key hash: " . htmlspecialchars($pubkey_hash));
        }

    } else {
        respond(400, "Missing public key or public key hash");
    }

    // Retrieve data by public key hash
    $stmt = $db->prepare("SELECT * FROM storage WHERE pubkey_hash = :pubkey_hash");
    $stmt->execute([':pubkey_hash' => $pubkey_hash]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        respond(404, "No data found for this public key");
    }

    // Set the correct MIME type and output the data without encoding
    header('Content-Type: ' . $result['mime_type']);
    echo $result['data'];
    exit;
}

    // Update stored data with a signed transaction
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $_GET['action'] === 'update') {
        parse_str(file_get_contents("php://input"), $_PUT);

        $pubkey = $_PUT['pubkey'] ?? null;
        $signature = $_PUT['signature'] ?? null;
        $data = $_PUT['data'] ?? null;
        $mime_type = $_PUT['mime_type'] ?? 'text/plain';

        if (!$pubkey || !$signature || !$data) {
            respond(400, "Invalid input: missing public key, signature, or data");
        }

        // Decode base64-encoded public key and signature using safe base64 decoding
        $decodedPubkey = safeBase64Decode($pubkey);
        $decodedSignature = safeBase64Decode($signature);

        if ($decodedPubkey === false || strlen($decodedPubkey) !== 32) {
            respond(400, "Invalid public key");
        }

        if ($decodedSignature === false) {
            respond(400, "Invalid signature: not valid base64");
        }

        $pubkey_hash = bin2hex(sodium_crypto_generichash($decodedPubkey));

        if (!sodium_crypto_sign_verify_detached($decodedSignature, $data, $decodedPubkey)) {
            respond(400, "Invalid signature");
        }

        $stmt = $db->prepare("SELECT * FROM storage WHERE pubkey_hash = :pubkey_hash");
        $stmt->execute([':pubkey_hash' => $pubkey_hash]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            respond(404, "Space not found");
        }

        if (strlen($data) > 1024) {
            respond(400, "Data exceeds 1k size limit");
        }

        $stmt = $db->prepare("UPDATE storage SET data = :data, mime_type = :mime_type WHERE pubkey_hash = :pubkey_hash");
        $stmt->execute([':data' => $data, ':mime_type' => $mime_type, ':pubkey_hash' => $pubkey_hash]);

        respond(200, "Storage updated");
    }

    // Handle deletion (not in spec, but helpful)
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && $_GET['action'] === 'delete') {
        $pubkey = $_POST['pubkey'] ?? null;
        $signature = $_POST['signature'] ?? null;

        if (!$pubkey || !$signature) {
            respond(400, "Invalid input: missing public key or signature");
        }

        // Decode base64-encoded public key and signature using safe base64 decoding
        $decodedPubkey = safeBase64Decode($pubkey);
        $decodedSignature = safeBase64Decode($signature);

        if ($decodedPubkey === false || strlen($decodedPubkey) !== 32) {
            respond(400, "Invalid public key");
        }

        if ($decodedSignature === false) {
            respond(400, "Invalid signature: not valid base64");
        }

        $pubkey_hash = bin2hex(sodium_crypto_generichash($decodedPubkey));

        if (!CryptoSign::verify_detached($decodedSignature, $pubkey_hash, $decodedPubkey)) {
            respond(400, "Invalid signature");
        }

        $stmt = $db->prepare("DELETE FROM storage WHERE pubkey_hash = :pubkey_hash");
        $stmt->execute([':pubkey_hash' => $pubkey_hash]);

        respond(200, "Storage deleted");
    }
} else {
    // If no action is specified, show the contents of main.php
    if (file_exists('main.php')) {
        include 'main.php';
    } else {
        respond(400, "No action specified and main.php not found");
    }
}

?>