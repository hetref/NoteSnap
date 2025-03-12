<?php

/**
 * Encryption and decryption functions for NoteSnap
 */

// Global encryption key - in a production environment, this should be stored securely
define('ENCRYPTION_KEY', 'notesnap_secret_key');

/**
 * Encrypt data using OpenSSL
 * 
 * @param string $data Data to encrypt
 * @return string Encrypted data
 */
function encryptData($data)
{
    $method = 'AES-256-CBC';
    $key = substr(hash('sha256', ENCRYPTION_KEY, true), 0, 32);
    $iv = openssl_random_pseudo_bytes(16);

    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);

    // Return base64 encoded string of IV + encrypted data
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt data using OpenSSL
 * 
 * @param string $data Encrypted data to decrypt
 * @return string|false Decrypted data or false on failure
 */
function decryptData($data)
{
    $method = 'AES-256-CBC';
    $key = substr(hash('sha256', ENCRYPTION_KEY, true), 0, 32);

    // Decode the base64 encoded string
    $decoded = base64_decode($data);

    // Extract IV (first 16 bytes) and encrypted data
    $iv = substr($decoded, 0, 16);
    $encrypted = substr($decoded, 16);

    // Decrypt the data
    return openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
}
