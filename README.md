# Multi-User Note Manager - NoteSnap

A terminal-based PHP application for managing encrypted notes with user authentication, using CSV files as a database.

## Features

- **User Authentication**

  - Secure user registration and login
  - Password hashing for security
  - Password recovery via security questions
  - Update security question and answer while logged in
  - Each user has a unique UUID
  - Delete account and all associated data

- **Note Management**

  - Create, edit, view, and delete notes
  - Notes are encrypted for privacy
  - Tag notes for organization
  - Search notes by title or content
  - Filter notes by tags
  - Export notes to a non-encrypted CSV file for sharing
  - Automatic note ID management after deletion

- **Security**
  - All passwords are hashed using PHP's password_hash()
  - Note contents are encrypted using OpenSSL encryption
  - Input validation and sanitization

## Technical Implementation

- **Storage Structure**

  - `database.csv`: Stores user credentials and security information
  - `notes_{UUID}.csv`: Each user has their own notes file

- **Database Design**

  - Uses MySQL with foreign key constraints for data integrity
  - User table (`users`) is the parent table with UUID as primary key
  - Notes table (`notes`) references user table with `ON DELETE CASCADE`
  - Ensures automatic deletion of all user data when account is deleted

- **Data Deletion**

  - Account deletion is handled at database level
  - Foreign key constraints ensure referential integrity
  - Cascading deletes remove all associated notes automatically
  - Atomic operation: entire transaction succeeds or fails together
  - No orphaned data remains after account deletion

- **File Structure**
  - `main.php`: The main application entry point
  - `auth.php`: User authentication functions
  - `note_manager.php`: Note management functions
  - `encrypt.php`: Encryption utilities

## File Structure and Documentation

This project consists of the following files:

### `main.php`

The entry point for the application and contains:

- Main application loop and menu system
- User interface functions (`displayHeader()`, `displayMainMenu()`)
- Handler functions for all user actions (like `handleLogin()`, `handleCreateNote()`, etc.)
- Global session variables to keep track of logged-in state
- Input sanitization and validation

### `auth.php`

Handles all authentication-related functionality:

- User registration (`registerUser()`)
- User login (`loginUser()`)
- Password recovery (`resetPassword()`)
- Security question management (`updateSecurityQuestion()`)
- UUID generation for user identification
- Database initialization and management for user accounts

### `note_manager.php`

Manages all note-related operations:

- Creating notes (`createNote()`)
- Reading notes (`getNotes()`, `getNoteById()`)
- Updating notes (`editNote()`)
- Deleting notes (`deleteNote()`)
- Searching notes (`searchNotes()`)
- Filtering notes by tags
- Exporting notes (`exportNotes()`)
- Note file initialization and management

### `encrypt.php`

Provides encryption and decryption utilities:

- Encryption key definition
- Data encryption (`encryptData()`)
- Data decryption (`decryptData()`)
- Uses OpenSSL for secure encryption/decryption

### CSV Files Generated During Runtime:

- `database.csv`: Contains user authentication details
  - Columns: uuid, username, hashed_password, security_question, security_answer
- `notes_{UUID}.csv`: For each user, created upon first note
  - Columns: id, title, content, tags, created_at, updated_at
- `notes_export_{timestamp}.csv`: Created when a user exports their notes
  - Contains the same structure as the notes file but with decrypted content

## Requirements

- PHP 7.0 or higher
- Command-line access

## Installation

1. Clone or download the repository
2. Ensure you have PHP installed on your system
   - For Windows users, you can download PHP from [php.net](https://www.php.net/downloads.php)
   - For Linux users, you can install PHP using your package manager (e.g., `sudo apt install php` for Ubuntu)
   - For macOS users, you can use Homebrew (`brew install php`)
3. Navigate to the project directory
4. Run the application using PHP:

```bash
php main.php
```

## Usage

### Registration

1. Select "Register" from the main menu
2. Enter a username, password, and security question/answer
3. Your user account will be created

### Login

1. Select "Login" from the main menu
2. Enter your username and password
3. Upon successful login, you'll have access to note management features

### Password Recovery

1. Select "Recover Password" from the main menu
2. Enter your username
3. Answer your security question correctly
4. Set a new password

### Update Security Question

1. Login to your account
2. Select "Update Security Question" from the main menu
3. Enter your new security question and answer
4. Confirm the security answer

### Delete Account

1. Login to your account
2. Select "Delete Account" from the main menu
3. Read the warning message carefully - this action is irreversible
4. Type 'DELETE' (in uppercase) to confirm account deletion
   - Any other input will cancel the operation
5. Upon confirmation:
   - Your user account will be permanently deleted
   - All your notes will be automatically deleted
   - All your security information will be removed
   - You will be automatically logged out
6. The deletion is atomic - either everything is deleted or nothing is
7. After deletion, you will need to create a new account to use the application again

Note: The account deletion process uses database-level cascading deletes to ensure that all associated data (notes, security information, etc.) is completely removed from the system. This is handled automatically by the MySQL foreign key constraints with `ON DELETE CASCADE`.

### Note Management

Once logged in, you can:

- View all notes or filter by tags
- Create new notes with titles, content, and optional tags
- Edit existing notes
- Delete notes (IDs will be automatically reorganized)
- Search notes by title or content

### Exporting Notes

1. Login to your account
2. Select "Export Notes" from the main menu
3. Enter a filename or accept the default name
4. A CSV file will be created with your decrypted notes
5. This file can be shared with others or used for backup

## Extending the Application

If you want to add new features to the application:

1. For authentication-related features:

   - Modify `auth.php` to add new functions
   - Update the database structure in `initUserDatabase()` if needed

2. For note-related features:

   - Add new functions to `note_manager.php`
   - Update the note file structure in `initUserNotes()` if needed

3. For UI/menu changes:

   - Update `displayMainMenu()` in `main.php` to add new menu options
   - Create new handler functions in `main.php` for the new features
   - Update the main application loop to handle the new menu options

4. For security enhancements:
   - Update encryption methods in `encrypt.php`

## Security Considerations

- The encryption key is defined in the encrypt.php file. In a production environment, this should be stored securely.
- All user inputs are sanitized to prevent CSV injection attacks.
- Security answers are encrypted for additional protection.
- Notes are stored with encrypted content in the CSV files, but exported with decrypted content.

## Troubleshooting

- If you encounter "php not found" errors, ensure PHP is installed and in your system's PATH
- On Windows, you might need to add PHP to your PATH environment variable
- Make sure the application has write permissions in the directory where it's running
- If the application cannot create or modify files, check your directory permissions

## License

This project is open-source and free to use.
