<?php

/**
 * Note management module for NoteSnap
 */

require_once 'encrypt.php';

/**
 * Initialize a user's note file if it doesn't exist
 * 
 * @param string $uuid User's UUID
 * @return bool True if successful
 */
function initUserNotes($uuid)
{
    $filename = "notes_{$uuid}.csv";

    if (!file_exists($filename)) {
        $headers = ['id', 'title', 'content', 'tags', 'created_at', 'updated_at'];
        $file = fopen($filename, 'w');
        fputcsv($file, $headers);
        fclose($file);
    }

    return true;
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
    initUserNotes($uuid);

    $filename = "notes_{$uuid}.csv";
    $file = fopen($filename, 'r');

    // Get all existing notes to determine the next ID
    $notes = [];
    fgetcsv($file); // Skip header

    while (($note = fgetcsv($file)) !== false) {
        $notes[] = $note;
    }

    fclose($file);

    // Generate new note ID (simple increment)
    $id = count($notes) + 1;

    // Get current timestamp
    $now = date('Y-m-d H:i:s');

    // Encrypt note content
    $encryptedContent = encryptData($content);

    // Append the new note
    $file = fopen($filename, 'a');
    fputcsv($file, [$id, $title, $encryptedContent, $tags, $now, $now]);
    fclose($file);

    return true;
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
    initUserNotes($uuid);

    $filename = "notes_{$uuid}.csv";
    $file = fopen($filename, 'r');

    $notes = [];
    fgetcsv($file); // Skip header

    while (($note = fgetcsv($file)) !== false) {
        // Decrypt note content
        $note[2] = decryptData($note[2]);

        // Filter by tag if specified
        if (!empty($tag)) {
            $noteTags = explode(',', $note[3]);
            if (!in_array($tag, $noteTags)) {
                continue;
            }
        }

        $notes[] = [
            'id' => $note[0],
            'title' => $note[1],
            'content' => $note[2],
            'tags' => $note[3],
            'created_at' => $note[4],
            'updated_at' => $note[5]
        ];
    }

    fclose($file);
    return $notes;
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
    $notes = getNotes($uuid);

    foreach ($notes as $note) {
        if ($note['id'] == $id) {
            return $note;
        }
    }

    return false;
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
    initUserNotes($uuid);

    $filename = "notes_{$uuid}.csv";
    $tempFile = "notes_{$uuid}_temp.csv";

    $original = fopen($filename, 'r');
    $temp = fopen($tempFile, 'w');

    // Copy header
    fputcsv($temp, fgetcsv($original));

    $updated = false;

    while (($note = fgetcsv($original)) !== false) {
        if ($note[0] == $id) {
            // Only update fields that were provided
            if ($title !== null) {
                $note[1] = $title;
            }

            if ($content !== null) {
                $note[2] = encryptData($content);
            }

            if ($tags !== null) {
                $note[3] = $tags;
            }

            // Update the 'updated_at' timestamp
            $note[5] = date('Y-m-d H:i:s');

            $updated = true;
        }

        fputcsv($temp, $note);
    }

    fclose($original);
    fclose($temp);

    // Replace original file with updated file
    if ($updated) {
        unlink($filename);
        rename($tempFile, $filename);
        return true;
    } else {
        unlink($tempFile);
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
    initUserNotes($uuid);

    $filename = "notes_{$uuid}.csv";
    $tempFile = "notes_{$uuid}_temp.csv";

    $original = fopen($filename, 'r');
    $temp = fopen($tempFile, 'w');

    // Copy header
    fputcsv($temp, fgetcsv($original));

    $deleted = false;
    $notes = [];

    // Collect all notes except the one to be deleted
    while (($note = fgetcsv($original)) !== false) {
        if ($note[0] != $id) {
            $notes[] = $note;
        } else {
            $deleted = true;
        }
    }

    fclose($original);

    // If the note was deleted, rewrite all notes with new sequential IDs
    if ($deleted) {
        // Reorder note IDs
        for ($i = 0; $i < count($notes); $i++) {
            $notes[$i][0] = $i + 1; // Reassign IDs starting from 1
            fputcsv($temp, $notes[$i]);
        }

        fclose($temp);
        unlink($filename);
        rename($tempFile, $filename);
        return true;
    } else {
        fclose($temp);
        unlink($tempFile);
        return false;
    }
}

/**
 * Search notes by title or content
 * 
 * @param string $uuid User's UUID
 * @param string $query Search query
 * @return array Matching notes
 */
function searchNotes($uuid, $query)
{
    $allNotes = getNotes($uuid);
    $results = [];

    $query = strtolower($query);

    foreach ($allNotes as $note) {
        if (
            strpos(strtolower($note['title']), $query) !== false ||
            strpos(strtolower($note['content']), $query) !== false
        ) {
            $results[] = $note;
        }
    }

    return $results;
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
