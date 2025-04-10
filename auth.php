<?php

require_once 'encrypt.php';
require_once 'database.php';

function initUserDatabase()
{
    try {
        $db = Database::getInstance();
        $db->ensureTablesExist();
        return true;
    } catch (Exception $e) {
        error_log("Failed to initialize user database: " . $e->getMessage());
        return false;
    }
}

function generateUUID()
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function usernameExists($username)
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $sql = "SELECT COUNT(*) as count FROM " . TABLE_USERS . " WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':username' => $username]);

        $result = $stmt->fetch();
        return $result['count'] > 0;
    } catch (Exception $e) {
        error_log("Failed to check username: " . $e->getMessage());
        return false;
    }
}

function registerUser($username, $password, $securityQuestion, $securityAnswer)
{
    try {
        initUserDatabase();

        if (usernameExists($username)) {
            return false;
        }

        $db = Database::getInstance();
        $conn = $db->getConnection();

        $uuid = generateUUID();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $encryptedAnswer = encryptData(strtolower($securityAnswer));

        $sql = "INSERT INTO " . TABLE_USERS . " (uuid, username, hashed_password, security_question, security_answer) 
                VALUES (:uuid, :username, :password, :question, :answer)";

        $stmt = $conn->prepare($sql);
        $success = $stmt->execute([
            ':uuid' => $uuid,
            ':username' => $username,
            ':password' => $hashedPassword,
            ':question' => $securityQuestion,
            ':answer' => $encryptedAnswer
        ]);

        if ($success) {
            return [
                'uuid' => $uuid,
                'username' => $username,
                'security_question' => $securityQuestion
            ];
        }
        return false;
    } catch (Exception $e) {
        error_log("Failed to register user: " . $e->getMessage());
        return false;
    }
}

function loginUser($username, $password)
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $sql = "SELECT * FROM " . TABLE_USERS . " WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':username' => $username]);

        if ($user = $stmt->fetch()) {
            if (password_verify($password, $user['hashed_password'])) {
                return [
                    'uuid' => $user['uuid'],
                    'username' => $user['username'],
                    'security_question' => $user['security_question']
                ];
            }
        }
        return false;
    } catch (Exception $e) {
        error_log("Failed to login user: " . $e->getMessage());
        return false;
    }
}

function getUserByUsername($username)
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $sql = "SELECT * FROM " . TABLE_USERS . " WHERE username = :username";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':username' => $username]);

        if ($user = $stmt->fetch()) {
            return [
                'uuid' => $user['uuid'],
                'username' => $user['username'],
                'security_question' => $user['security_question'],
                'security_answer' => $user['security_answer']
            ];
        }
        return false;
    } catch (Exception $e) {
        error_log("Failed to get user: " . $e->getMessage());
        return false;
    }
}

function resetPassword($username, $securityAnswer, $newPassword)
{
    try {
        $userData = getUserByUsername($username);
        if (!$userData) {
            return false;
        }

        $storedAnswer = $userData['security_answer'];
        $providedAnswer = strtolower($securityAnswer);

        if (decryptData($storedAnswer) !== $providedAnswer) {
            return false;
        }

        $db = Database::getInstance();
        $conn = $db->getConnection();

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE " . TABLE_USERS . " SET hashed_password = :password WHERE username = :username";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            ':password' => $hashedPassword,
            ':username' => $username
        ]);
    } catch (Exception $e) {
        error_log("Failed to reset password: " . $e->getMessage());
        return false;
    }
}

function updateSecurityQuestion($username, $newSecurityQuestion, $newSecurityAnswer)
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $encryptedAnswer = encryptData(strtolower($newSecurityAnswer));

        $sql = "UPDATE " . TABLE_USERS . " 
                SET security_question = :question, security_answer = :answer 
                WHERE username = :username";

        $stmt = $conn->prepare($sql);
        return $stmt->execute([
            ':question' => $newSecurityQuestion,
            ':answer' => $encryptedAnswer,
            ':username' => $username
        ]);
    } catch (Exception $e) {
        error_log("Failed to update security question: " . $e->getMessage());
        return false;
    }
}

function deleteUser($username)
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $sql = "DELETE FROM " . TABLE_USERS . " WHERE username = :username";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':username' => $username]);
    } catch (Exception $e) {
        error_log("Failed to delete user: " . $e->getMessage());
        return false;
    }
}
