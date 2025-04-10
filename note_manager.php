<?php

require_once 'encrypt.php';
require_once 'database.php';

function initUserNotes($uuid)
{
    try {
        $db = Database::getInstance();
        $db->ensureTablesExist();
        return true;
    } catch (Exception $e) {
        error_log("Failed to initialize user notes: " . $e->getMessage());
        return false;
    }
}

function createNote($uuid, $title, $content, $tags = '')
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $encryptedContent = encryptData($content);

        $sql = "INSERT INTO " . TABLE_NOTES . " (user_uuid, title, content, tags) VALUES (:uuid, :title, :content, :tags)";
        $stmt = $conn->prepare($sql);

        return $stmt->execute([
            ':uuid' => $uuid,
            ':title' => $title,
            ':content' => $encryptedContent,
            ':tags' => $tags
        ]);
    } catch (Exception $e) {
        error_log("Failed to create note: " . $e->getMessage());
        return false;
    }
}

function getNotes($uuid, $tag = '')
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $sql = "SELECT * FROM " . TABLE_NOTES . " WHERE user_uuid = :uuid";
        $params = [':uuid' => $uuid];

        if (!empty($tag)) {
            $sql .= " AND FIND_IN_SET(:tag, tags) > 0";
            $params[':tag'] = $tag;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $notes = [];
        while ($note = $stmt->fetch()) {
            $notes[] = [
                'id' => $note['id'],
                'title' => $note['title'],
                'content' => decryptData($note['content']),
                'tags' => $note['tags'],
                'created_at' => $note['created_at'],
                'updated_at' => $note['updated_at']
            ];
        }

        return $notes;
    } catch (Exception $e) {
        error_log("Failed to get notes: " . $e->getMessage());
        return [];
    }
}

function getNoteById($uuid, $id)
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $sql = "SELECT * FROM " . TABLE_NOTES . " WHERE user_uuid = :uuid AND id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':uuid' => $uuid, ':id' => $id]);

        if ($note = $stmt->fetch()) {
            return [
                'id' => $note['id'],
                'title' => $note['title'],
                'content' => decryptData($note['content']),
                'tags' => $note['tags'],
                'created_at' => $note['created_at'],
                'updated_at' => $note['updated_at']
            ];
        }

        return false;
    } catch (Exception $e) {
        error_log("Failed to get note by ID: " . $e->getMessage());
        return false;
    }
}

function editNote($uuid, $id, $title = null, $content = null, $tags = null)
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $updates = [];
        $params = [':uuid' => $uuid, ':id' => $id];

        if ($title !== null) {
            $updates[] = "title = :title";
            $params[':title'] = $title;
        }

        if ($content !== null) {
            $updates[] = "content = :content";
            $params[':content'] = encryptData($content);
        }

        if ($tags !== null) {
            $updates[] = "tags = :tags";
            $params[':tags'] = $tags;
        }

        if (empty($updates)) {
            return true;
        }

        $sql = "UPDATE " . TABLE_NOTES . " SET " . implode(", ", $updates) .
            " WHERE user_uuid = :uuid AND id = :id";

        $stmt = $conn->prepare($sql);
        return $stmt->execute($params);
    } catch (Exception $e) {
        error_log("Failed to edit note: " . $e->getMessage());
        return false;
    }
}

function deleteNote($uuid, $id)
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $sql = "DELETE FROM " . TABLE_NOTES . " WHERE user_uuid = :uuid AND id = :id";
        $stmt = $conn->prepare($sql);
        return $stmt->execute([':uuid' => $uuid, ':id' => $id]);
    } catch (Exception $e) {
        error_log("Failed to delete note: " . $e->getMessage());
        return false;
    }
}

function searchNotes($uuid, $query)
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $sql = "SELECT * FROM " . TABLE_NOTES .
            " WHERE user_uuid = :uuid AND (title LIKE :query1 OR content LIKE :query2)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':uuid' => $uuid,
            ':query1' => '%' . $query . '%',
            ':query2' => '%' . $query . '%'
        ]);

        $notes = [];
        while ($note = $stmt->fetch()) {
            $notes[] = [
                'id' => $note['id'],
                'title' => $note['title'],
                'content' => decryptData($note['content']),
                'tags' => $note['tags'],
                'created_at' => $note['created_at'],
                'updated_at' => $note['updated_at']
            ];
        }

        return $notes;
    } catch (Exception $e) {
        error_log("Failed to search notes: " . $e->getMessage());
        return [];
    }
}

function exportNotes($uuid, $exportFilename = null)
{
    $notes = getNotes($uuid);

    if (empty($notes)) {
        return false;
    }

    if ($exportFilename === null) {
        $timestamp = date('Y-m-d_H-i-s');
        $exportFilename = "notes_export_{$timestamp}.csv";
    }

    $exportFile = fopen($exportFilename, 'w');
    if ($exportFile === false) {
        return false;
    }

    fputcsv($exportFile, ['id', 'title', 'content', 'tags', 'created_at', 'updated_at']);

    foreach ($notes as $note) {
        fputcsv($exportFile, [
            $note['id'],
            $note['title'],
            $note['content'],
            $note['tags'],
            $note['created_at'],
            $note['updated_at']
        ]);
    }

    fclose($exportFile);
    return true;
}
