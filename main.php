<?php

require_once 'auth.php';
require_once 'note_manager.php';

$loggedIn = false;
$currentUser = null;

function clearScreen()
{
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        system('cls');
    } else {
        system('clear');
    }
}

function displayHeader()
{
    clearScreen();
    echo "=============================================================\n";
    echo "                      NOTESNAP\n";
    echo "=============================================================\n";
}

function displayMainMenu()
{
    global $loggedIn, $currentUser;

    displayHeader();

    if ($loggedIn) {
        echo "Logged in as: {$currentUser['username']}\n";
        echo "=============================================================\n";
        echo "1. View All Notes\n";
        echo "2. Create New Note\n";
        echo "3. Edit Note\n";
        echo "4. Delete Note\n";
        echo "5. Search Notes\n";
        echo "6. Filter Notes by Tag\n";
        echo "7. Export Notes\n";
        echo "8. Update Security Question\n";
        echo "9. Delete Account\n";
        echo "10. Logout\n";
        echo "11. Exit\n";
    } else {
        echo "1. Login\n";
        echo "2. Register\n";
        echo "3. Recover Password\n";
        echo "4. Exit\n";
    }

    echo "=============================================================\n";
    echo "Enter your choice: ";
}

function sanitizeInput($input)
{
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

function handleRegistration()
{
    displayHeader();
    echo "USER REGISTRATION\n";
    echo "=============================================================\n";

    $username = '';
    $password = '';
    $confirmPassword = '';
    $securityQuestion = '';
    $securityAnswer = '';

    while (empty($username)) {
        echo "Enter username: ";
        $username = sanitizeInput(trim(fgets(STDIN)));

        if (empty($username)) {
            echo "Username cannot be empty!\n";
            continue;
        }

        if (usernameExists($username)) {
            echo "Username already exists. Please choose another one.\n";
            $username = '';
        }
    }

    while (empty($password) || $password != $confirmPassword) {
        echo "Enter password (minimum 6 characters): ";
        $password = sanitizeInput(trim(fgets(STDIN)));

        if (strlen($password) < 6) {
            echo "Password must be at least 6 characters long!\n";
            $password = '';
            continue;
        }

        echo "Confirm password: ";
        $confirmPassword = sanitizeInput(trim(fgets(STDIN)));

        if ($password != $confirmPassword) {
            echo "Passwords do not match! Please try again.\n";
            $password = '';
            $confirmPassword = '';
        }
    }

    while (empty($securityQuestion)) {
        echo "Enter security question: ";
        $securityQuestion = sanitizeInput(trim(fgets(STDIN)));

        if (empty($securityQuestion)) {
            echo "Security question cannot be empty!\n";
        }
    }

    while (empty($securityAnswer)) {
        echo "Enter security answer: ";
        $securityAnswer = sanitizeInput(trim(fgets(STDIN)));

        if (empty($securityAnswer)) {
            echo "Security answer cannot be empty!\n";
        }
    }

    $result = registerUser($username, $password, $securityQuestion, $securityAnswer);

    if ($result) {
        echo "Registration successful! You can now login.\n";
    } else {
        echo "Registration failed! Please try again.\n";
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handleLogin()
{
    global $loggedIn, $currentUser;

    displayHeader();
    echo "USER LOGIN\n";
    echo "=============================================================\n";

    echo "Enter username: ";
    $username = sanitizeInput(trim(fgets(STDIN)));

    echo "Enter password: ";
    $password = sanitizeInput(trim(fgets(STDIN)));

    $result = loginUser($username, $password);

    if ($result) {
        $loggedIn = true;
        $currentUser = $result;
        echo "Login successful!\n";
    } else {
        echo "Invalid username or password. Please try again.\n";
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handlePasswordRecovery()
{
    displayHeader();
    echo "PASSWORD RECOVERY\n";
    echo "=============================================================\n";

    echo "Enter username: ";
    $username = sanitizeInput(trim(fgets(STDIN)));

    $userData = getUserByUsername($username);

    if (!$userData) {
        echo "Username not found!\n";
        echo "Press Enter to continue...";
        fgets(STDIN);
        return;
    }

    echo "Security Question: {$userData['security_question']}\n";
    echo "Enter your answer: ";
    $answer = sanitizeInput(trim(fgets(STDIN)));

    $newPassword = '';
    $confirmPassword = '';

    while (empty($newPassword) || $newPassword != $confirmPassword) {
        echo "Enter new password (minimum 6 characters): ";
        $newPassword = sanitizeInput(trim(fgets(STDIN)));

        if (strlen($newPassword) < 6) {
            echo "Password must be at least 6 characters long!\n";
            $newPassword = '';
            continue;
        }

        echo "Confirm new password: ";
        $confirmPassword = sanitizeInput(trim(fgets(STDIN)));

        if ($newPassword != $confirmPassword) {
            echo "Passwords do not match! Please try again.\n";
            $newPassword = '';
            $confirmPassword = '';
        }
    }

    $result = resetPassword($username, $answer, $newPassword);

    if ($result) {
        echo "Password reset successful! You can now login with your new password.\n";
    } else {
        echo "Password reset failed! Incorrect security answer.\n";
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handleViewAllNotes()
{
    global $currentUser;

    displayHeader();
    echo "VIEW ALL NOTES\n";
    echo "=============================================================\n";

    $notes = getNotes($currentUser['uuid']);

    if (empty($notes)) {
        echo "You don't have any notes yet.\n";
    } else {
        echo "ID | Title | Tags | Created At | Updated At\n";
        echo "-------------------------------------------------------------\n";

        foreach ($notes as $note) {
            echo "{$note['id']} | {$note['title']} | {$note['tags']} | {$note['created_at']} | {$note['updated_at']}\n";
        }

        echo "-------------------------------------------------------------\n";
        echo "Enter note ID to view content (or 0 to go back): ";
        $noteId = (int)trim(fgets(STDIN));

        if ($noteId > 0) {
            $selectedNote = getNoteById($currentUser['uuid'], $noteId);

            if ($selectedNote) {
                echo "\nTitle: {$selectedNote['title']}\n";
                echo "Tags: {$selectedNote['tags']}\n";
                echo "Created: {$selectedNote['created_at']}\n";
                echo "Updated: {$selectedNote['updated_at']}\n";
                echo "Content:\n{$selectedNote['content']}\n";
            } else {
                echo "Note not found!\n";
            }
        }
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handleCreateNote()
{
    global $currentUser;

    displayHeader();
    echo "CREATE NEW NOTE\n";
    echo "=============================================================\n";

    $title = '';
    while (empty($title)) {
        echo "Enter note title: ";
        $title = sanitizeInput(trim(fgets(STDIN)));

        if (empty($title)) {
            echo "Title cannot be empty!\n";
        }
    }

    echo "Enter note content (type '---END---' on a new line to finish):\n";
    $content = '';
    $line = '';

    while (($line = fgets(STDIN)) !== false) {
        $line = trim($line);
        if ($line === '---END---') {
            break;
        }
        $content .= $line . "\n";
    }

    echo "Enter tags (comma-separated, or leave empty): ";
    $tags = sanitizeInput(trim(fgets(STDIN)));

    $result = createNote($currentUser['uuid'], $title, $content, $tags);

    if ($result) {
        echo "Note created successfully!\n";
    } else {
        echo "Failed to create note!\n";
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handleEditNote()
{
    global $currentUser;

    displayHeader();
    echo "EDIT NOTE\n";
    echo "=============================================================\n";

    $notes = getNotes($currentUser['uuid']);

    if (empty($notes)) {
        echo "You don't have any notes to edit.\n";
    } else {
        echo "ID | Title | Tags | Created At | Updated At\n";
        echo "-------------------------------------------------------------\n";

        foreach ($notes as $note) {
            echo "{$note['id']} | {$note['title']} | {$note['tags']} | {$note['created_at']} | {$note['updated_at']}\n";
        }

        echo "-------------------------------------------------------------\n";
        echo "Enter note ID to edit (or 0 to go back): ";
        $noteId = (int)trim(fgets(STDIN));

        if ($noteId > 0) {
            $selectedNote = getNoteById($currentUser['uuid'], $noteId);

            if ($selectedNote) {
                echo "Current title: {$selectedNote['title']}\n";
                echo "Enter new title (or leave empty to keep current): ";
                $newTitle = trim(fgets(STDIN));
                $newTitle = empty($newTitle) ? null : sanitizeInput($newTitle);

                echo "Current content:\n{$selectedNote['content']}\n";
                echo "Enter new content (type '---END---' on a new line to finish, or leave empty to keep current):\n";

                $newContent = '';
                $line = '';

                while (($line = fgets(STDIN)) !== false) {
                    $line = trim($line);
                    if ($line === '---END---') {
                        break;
                    }
                    if ($line === '') {
                        $newContent = null;
                        break;
                    }
                    $newContent .= $line . "\n";
                }

                echo "Current tags: {$selectedNote['tags']}\n";
                echo "Enter new tags (or leave empty to keep current): ";
                $newTags = trim(fgets(STDIN));
                $newTags = empty($newTags) ? null : sanitizeInput($newTags);

                $result = editNote($currentUser['uuid'], $noteId, $newTitle, $newContent, $newTags);

                if ($result) {
                    echo "Note updated successfully!\n";
                } else {
                    echo "Failed to update note!\n";
                }
            } else {
                echo "Note not found!\n";
            }
        }
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handleDeleteNote()
{
    global $currentUser;

    displayHeader();
    echo "DELETE NOTE\n";
    echo "=============================================================\n";

    $notes = getNotes($currentUser['uuid']);

    if (empty($notes)) {
        echo "You don't have any notes to delete.\n";
    } else {
        echo "ID | Title | Tags | Created At | Updated At\n";
        echo "-------------------------------------------------------------\n";

        foreach ($notes as $note) {
            echo "{$note['id']} | {$note['title']} | {$note['tags']} | {$note['created_at']} | {$note['updated_at']}\n";
        }

        echo "-------------------------------------------------------------\n";
        echo "Enter note ID to delete (or 0 to go back): ";
        $noteId = (int)trim(fgets(STDIN));

        if ($noteId > 0) {
            echo "Are you sure you want to delete this note? (y/n): ";
            $confirm = trim(fgets(STDIN));

            if (strtolower($confirm) === 'y') {
                $result = deleteNote($currentUser['uuid'], $noteId);

                if ($result) {
                    echo "Note deleted successfully!\n";
                } else {
                    echo "Failed to delete note! Note ID not found.\n";
                }
            }
        }
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handleSearchNotes()
{
    global $currentUser;

    displayHeader();
    echo "SEARCH NOTES\n";
    echo "=============================================================\n";

    echo "Enter search query: ";
    $query = sanitizeInput(trim(fgets(STDIN)));

    if (!empty($query)) {
        $results = searchNotes($currentUser['uuid'], $query);

        if (empty($results)) {
            echo "No notes found matching '{$query}'.\n";
        } else {
            echo "ID | Title | Tags | Created At | Updated At\n";
            echo "-------------------------------------------------------------\n";

            foreach ($results as $note) {
                echo "{$note['id']} | {$note['title']} | {$note['tags']} | {$note['created_at']} | {$note['updated_at']}\n";
            }

            echo "-------------------------------------------------------------\n";
            echo "Enter note ID to view content (or 0 to go back): ";
            $noteId = (int)trim(fgets(STDIN));

            if ($noteId > 0) {
                $selectedNote = getNoteById($currentUser['uuid'], $noteId);

                if ($selectedNote) {
                    echo "\nTitle: {$selectedNote['title']}\n";
                    echo "Tags: {$selectedNote['tags']}\n";
                    echo "Created: {$selectedNote['created_at']}\n";
                    echo "Updated: {$selectedNote['updated_at']}\n";
                    echo "Content:\n{$selectedNote['content']}\n";
                } else {
                    echo "Note not found!\n";
                }
            }
        }
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handleFilterByTag()
{
    global $currentUser;

    displayHeader();
    echo "FILTER NOTES BY TAG\n";
    echo "=============================================================\n";

    echo "Enter tag to filter by: ";
    $tag = sanitizeInput(trim(fgets(STDIN)));

    if (!empty($tag)) {
        $filteredNotes = getNotes($currentUser['uuid'], $tag);

        if (empty($filteredNotes)) {
            echo "No notes found with tag '{$tag}'.\n";
        } else {
            echo "ID | Title | Tags | Created At | Updated At\n";
            echo "-------------------------------------------------------------\n";

            foreach ($filteredNotes as $note) {
                echo "{$note['id']} | {$note['title']} | {$note['tags']} | {$note['created_at']} | {$note['updated_at']}\n";
            }

            echo "-------------------------------------------------------------\n";
            echo "Enter note ID to view content (or 0 to go back): ";
            $noteId = (int)trim(fgets(STDIN));

            if ($noteId > 0) {
                $selectedNote = getNoteById($currentUser['uuid'], $noteId);

                if ($selectedNote) {
                    echo "\nTitle: {$selectedNote['title']}\n";
                    echo "Tags: {$selectedNote['tags']}\n";
                    echo "Created: {$selectedNote['created_at']}\n";
                    echo "Updated: {$selectedNote['updated_at']}\n";
                    echo "Content:\n{$selectedNote['content']}\n";
                } else {
                    echo "Note not found!\n";
                }
            }
        }
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handleUpdateSecurityQuestion()
{
    global $currentUser;

    displayHeader();
    echo "UPDATE SECURITY QUESTION\n";
    echo "=============================================================\n";

    $newSecurityQuestion = '';
    $newSecurityAnswer = '';
    $confirmSecurityAnswer = '';

    while (empty($newSecurityQuestion)) {
        echo "Enter new security question: ";
        $newSecurityQuestion = sanitizeInput(trim(fgets(STDIN)));

        if (empty($newSecurityQuestion)) {
            echo "Security question cannot be empty!\n";
        }
    }

    while (empty($newSecurityAnswer) || $newSecurityAnswer != $confirmSecurityAnswer) {
        echo "Enter new security answer: ";
        $newSecurityAnswer = sanitizeInput(trim(fgets(STDIN)));

        if (empty($newSecurityAnswer)) {
            echo "Security answer cannot be empty!\n";
            continue;
        }

        echo "Confirm security answer: ";
        $confirmSecurityAnswer = sanitizeInput(trim(fgets(STDIN)));

        if ($newSecurityAnswer != $confirmSecurityAnswer) {
            echo "Security answers do not match! Please try again.\n";
            $newSecurityAnswer = '';
            $confirmSecurityAnswer = '';
        }
    }

    $result = updateSecurityQuestion($currentUser['username'], $newSecurityQuestion, $newSecurityAnswer);

    if ($result) {
        echo "Security question and answer updated successfully!\n";
    } else {
        echo "Failed to update security question and answer!\n";
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handleExportNotes()
{
    global $currentUser;

    displayHeader();
    echo "EXPORT NOTES\n";
    echo "=============================================================\n";

    $timestamp = date('Y-m-d_H-i-s');
    $defaultFilename = "notes_export_{$timestamp}.csv";

    echo "Enter filename for export (or leave blank for '{$defaultFilename}'): ";
    $filename = trim(fgets(STDIN));

    if (empty($filename)) {
        $filename = $defaultFilename;
    }

    $result = exportNotes($currentUser['uuid'], $filename);

    if ($result) {
        echo "Notes successfully exported to '{$filename}'!\n";
    } else {
        echo "Failed to export notes. You might not have any notes to export.\n";
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function handleDeleteAccount()
{
    global $loggedIn, $currentUser;

    displayHeader();
    echo "DELETE ACCOUNT\n";
    echo "=============================================================\n";

    echo "WARNING: This action will permanently delete your account and all your notes!\n";
    echo "Are you sure you want to delete your account? (type 'DELETE' to confirm): ";
    $confirmation = trim(fgets(STDIN));

    if ($confirmation === 'DELETE') {
        $result = deleteUser($currentUser['username']);

        if ($result) {
            echo "Your account has been successfully deleted.\n";
            $loggedIn = false;
            $currentUser = null;
        } else {
            echo "Failed to delete account. Please try again later.\n";
        }
    } else {
        echo "Account deletion cancelled.\n";
    }

    echo "Press Enter to continue...";
    fgets(STDIN);
}

function runApplication()
{
    global $loggedIn, $currentUser;

    $running = true;

    while ($running) {
        displayMainMenu();
        $choice = trim(fgets(STDIN));

        if ($loggedIn) {
            switch ($choice) {
                case '1':
                    handleViewAllNotes();
                    break;
                case '2':
                    handleCreateNote();
                    break;
                case '3':
                    handleEditNote();
                    break;
                case '4':
                    handleDeleteNote();
                    break;
                case '5':
                    handleSearchNotes();
                    break;
                case '6':
                    handleFilterByTag();
                    break;
                case '7':
                    handleExportNotes();
                    break;
                case '8':
                    handleUpdateSecurityQuestion();
                    break;
                case '9':
                    handleDeleteAccount();
                    break;
                case '10':
                    $loggedIn = false;
                    $currentUser = null;
                    break;
                case '11':
                    $running = false;
                    break;
                default:
                    echo "Invalid choice! Press Enter to continue...";
                    fgets(STDIN);
            }
        } else {
            switch ($choice) {
                case '1':
                    handleLogin();
                    break;
                case '2':
                    handleRegistration();
                    break;
                case '3':
                    handlePasswordRecovery();
                    break;
                case '4':
                    $running = false;
                    break;
                default:
                    echo "Invalid choice! Press Enter to continue...";
                    fgets(STDIN);
            }
        }
    }

    displayHeader();
    echo "Thank you for using NoteSnap!\n";
    echo "=============================================================\n";
}

runApplication();
