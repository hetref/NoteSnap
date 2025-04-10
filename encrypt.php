<?php

define('ENCRYPTION_KEY', 'notesnap_secret_key');

function encryptData($data)
{
    $method = 'AES-256-CBC';
    $key = substr(hash('sha256', ENCRYPTION_KEY, true), 0, 32);
    $iv = openssl_random_pseudo_bytes(16);

    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);

    return base64_encode($iv . $encrypted);
}

function decryptData($data)
{
    $method = 'AES-256-CBC';
    $key = substr(hash('sha256', ENCRYPTION_KEY, true), 0, 32);

    $decoded = base64_decode($data);

    $iv = substr($decoded, 0, 16);
    $encrypted = substr($decoded, 16);

    return openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
}
