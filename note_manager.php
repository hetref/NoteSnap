<?php

/**
 * Note management module for NoteSnap
 */

require_once 'encrypt.php';
require_once 'database.php';

/**
 * Initialize database tables for user notes if they don't exist
 * 
 * @return bool True if successful
 */
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

/**
 * Create a new note for a user
 * 
 * @param string $uuid User's UUID
 * @param string $title Note title
 * @param string $content Note content
 * @param string $tags Comma-separated tags
 * @return bool True if successful
 */
function createNote($uuid, $title, $content, $tags = '')
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Encrypt note content
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

/**
 * Get all notes for a user
 * 
 * @param string $uuid User's UUID
 * @param string $tag Optional tag to filter by
 * @return array Array of notes
 */
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

/**
 * Get a specific note by ID
 * 
 * @param string $uuid User's UUID
 * @param int $id Note ID
 * @return array|bool Note data if found, false otherwise
 */
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

/**
 * Edit an existing note
 * 
 * @param string $uuid User's UUID
 * @param int $id Note ID
 * @param string $title New title (optional)
 * @param string $content New content (optional)
 * @param string $tags New tags (optional)
 * @return bool True if successful
 */
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

/**
 * Delete a note
 * 
 * @param string $uuid User's UUID
 * @param int $id Note ID
 * @return bool True if successful
 */
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

/**
 * Search notes by content or title
 * 
 * @param string $uuid User's UUID
 * @param string $query Search query
 * @return array Array of matching notes
 */
function searchNotes($uuid, $query)
{
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $sql = "SELECT * FROM " . TABLE_NOTES .
            " WHERE user_uuid = :uuid AND (title LIKE :query OR content LIKE :query)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':uuid' => $uuid,
            ':query' => '%' . $query . '%'
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

/**
 * Export user notes to a CSV file with decrypted content
 * 
 * @param string $uuid User's UUID
 * @param string $exportFilename Filename for the exported CSV
 * @return bool True if successful
 */
function exportNotes($uuid, $exportFilename = null)
{
    // Get all user notes with decrypted content
    $notes = getNotes($uuid);

    if (empty($notes)) {
        return false;
    }

    // Generate export filename if not provided
    if ($exportFilename === null) {
        $timestamp = date('Y-m-d_H-i-s');
        $exportFilename = "notes_export_{$timestamp}.csv";
    }

    // Create export file
    $exportFile = fopen($exportFilename, 'w');
    if ($exportFile === false) {
        return false;
    }

    // Write header
    fputcsv($exportFile, ['id', 'title', 'content', 'tags', 'created_at', 'updated_at']);

    // Write notes with decrypted content
    foreach ($notes as $note) {
        fputcsv($exportFile, [
            $note['id'],
            $note['title'],
            $note['content'], // Content is already decrypted by getNotes
            $note['tags'],
            $note['created_at'],
            $note['updated_at']
        ]);
    }

    fclose($exportFile);
    return true;
}
