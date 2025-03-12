<?php

/**
 * Authentication module for NoteSnap
 */

require_once 'encrypt.php';

/**
 * Check if the user database exists, and create it if it doesn't
 * 
 * @return bool True if database exists or was created successfully
 */
function initUserDatabase()
{
    if (!file_exists('database.csv')) {
        $headers = ['uuid', 'username', 'hashed_password', 'security_question', 'security_answer'];
        $file = fopen('database.csv', 'w');
        fputcsv($file, $headers);
        fclose($file);
        return true;
    }
    return true;
}

/**
 * Generate a unique UUID v4
 * 
 * @return string UUID
 */
function generateUUID()
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Check if a username already exists
 * 
 * @param string $username Username to check
 * @return bool True if username exists
 */
function usernameExists($username)
{
    // If the database doesn't exist, initialize it
    if (!file_exists('database.csv')) {
        initUserDatabase();
        return false; // No users exist yet
    }

    $file = fopen('database.csv', 'r');
    if ($file === false) {
        // If we can't open the file, create it and return false
        initUserDatabase();
        return false;
    }

    fgetcsv($file); // Skip header

    while (($user = fgetcsv($file)) !== false) {
        if ($user[1] == $username) {
            fclose($file);
            return true;
        }
    }

    fclose($file);
    return false;
}

/**
 * Register a new user
 * 
 * @param string $username Username
 * @param string $password Password
 * @param string $securityQuestion Security question
 * @param string $securityAnswer Security answer
 * @return array|bool User data if successful, false otherwise
 */
function registerUser($username, $password, $securityQuestion, $securityAnswer)
{
    initUserDatabase();

    // Check if the username is already taken
    if (usernameExists($username)) {
        return false;
    }

    // Generate UUID and hash the password
    $uuid = generateUUID();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    // Encrypt security answer
    $securityAnswer = encryptData(strtolower($securityAnswer));

    // Create new user record
    $userData = [
        'uuid' => $uuid,
        'username' => $username,
        'hashed_password' => $hashedPassword,
        'security_question' => $securityQuestion,
        'security_answer' => $securityAnswer
    ];

    // Append the user to the database
    $file = fopen('database.csv', 'a');
    fputcsv($file, [
        $userData['uuid'],
        $userData['username'],
        $userData['hashed_password'],
        $userData['security_question'],
        $userData['security_answer']
    ]);
    fclose($file);

    return $userData;
}

/**
 * Authenticate a user
 * 
 * @param string $username Username
 * @param string $password Password
 * @return array|bool User data if credentials are valid, false otherwise
 */
function loginUser($username, $password)
{
    if (!file_exists('database.csv')) {
        initUserDatabase();
        return false;
    }

    $file = fopen('database.csv', 'r');
    if ($file === false) {
        // If we can't open the file, create it and return false
        initUserDatabase();
        return false;
    }

    fgetcsv($file); // Skip header

    while (($user = fgetcsv($file)) !== false) {
        if ($user[1] == $username && password_verify($password, $user[2])) {
            fclose($file);
            return [
                'uuid' => $user[0],
                'username' => $user[1],
                'security_question' => $user[3]
            ];
        }
    }

    fclose($file);
    return false;
}

/**
 * Get user data by username
 * 
 * @param string $username Username
 * @return array|bool User data if found, false otherwise
 */
function getUserByUsername($username)
{
    if (!file_exists('database.csv')) {
        initUserDatabase();
        return false;
    }

    $file = fopen('database.csv', 'r');
    if ($file === false) {
        // If we can't open the file, create it and return false
        initUserDatabase();
        return false;
    }

    fgetcsv($file); // Skip header

    while (($user = fgetcsv($file)) !== false) {
        if ($user[1] == $username) {
            fclose($file);
            return [
                'uuid' => $user[0],
                'username' => $user[1],
                'security_question' => $user[3],
                'security_answer' => $user[4]
            ];
        }
    }

    fclose($file);
    return false;
}

/**
 * Reset password by verifying security answer
 * 
 * @param string $username Username
 * @param string $securityAnswer Answer to security question
 * @param string $newPassword New password
 * @return bool True if password was reset successfully
 */
function resetPassword($username, $securityAnswer, $newPassword)
{
    $userData = getUserByUsername($username);

    if (!$userData) {
        return false;
    }

    // Verify security answer
    $storedAnswer = $userData['security_answer'];
    $providedAnswer = strtolower($securityAnswer);

    if (decryptData($storedAnswer) !== $providedAnswer) {
        return false;
    }

    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $updated = false;

    if (!file_exists('database.csv')) {
        return false;
    }

    $tempFile = 'database_temp.csv';
    $original = fopen('database.csv', 'r');
    if ($original === false) {
        return false;
    }

    $temp = fopen($tempFile, 'w');
    if ($temp === false) {
        fclose($original);
        return false;
    }

    // Copy header
    fputcsv($temp, fgetcsv($original));

    // Update user's password
    while (($user = fgetcsv($original)) !== false) {
        if ($user[1] == $username) {
            $user[2] = $hashedPassword;
            $updated = true;
        }
        fputcsv($temp, $user);
    }

    fclose($original);
    fclose($temp);

    // Replace original file with updated file
    if ($updated) {
        unlink('database.csv');
        rename($tempFile, 'database.csv');
        return true;
    } else {
        unlink($tempFile);
        return false;
    }
}

/**
 * Update security question and answer for a user
 * 
 * @param string $username Username
 * @param string $newSecurityQuestion New security question
 * @param string $newSecurityAnswer New security answer
 * @return bool True if update was successful
 */
function updateSecurityQuestion($username, $newSecurityQuestion, $newSecurityAnswer)
{
    if (!file_exists('database.csv')) {
        return false;
    }

    $tempFile = 'database_temp.csv';
    $original = fopen('database.csv', 'r');
    if ($original === false) {
        return false;
    }

    $temp = fopen($tempFile, 'w');
    if ($temp === false) {
        fclose($original);
        return false;
    }

    // Encrypt the new security answer
    $encryptedAnswer = encryptData(strtolower($newSecurityAnswer));

    // Copy header
    fputcsv($temp, fgetcsv($original));

    $updated = false;

    // Update user's security question and answer
    while (($user = fgetcsv($original)) !== false) {
        if ($user[1] == $username) {
            $user[3] = $newSecurityQuestion;
            $user[4] = $encryptedAnswer;
            $updated = true;
        }
        fputcsv($temp, $user);
    }

    fclose($original);
    fclose($temp);

    // Replace original file with updated file
    if ($updated) {
        unlink('database.csv');
        rename($tempFile, 'database.csv');
        return true;
    } else {
        unlink($tempFile);
        return false;
    }
}
