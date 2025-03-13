<?php
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Session.php';

$security = Security::getInstance();
$session = Session::getInstance();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>NoteSnap - Secure Note Taking</title>

    <!-- Security Meta Tags -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta name="referrer" content="strict-origin-when-cross-origin">

    <!-- SEO Meta Tags -->
    <meta name="description" content="NoteSnap - A secure, encrypted note-taking application">
    <meta name="keywords" content="notes, secure notes, encrypted notes, privacy">
    <meta name="author" content="Your Name">

    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:title" content="NoteSnap - Secure Note Taking">
    <meta property="og:description" content="Your secure space for taking and organizing notes">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://your-domain.com">
    <meta property="og:image" content="https://your-domain.com/images/og-image.jpg">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/themes.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Navigation -->
    <nav class="main-nav">
        <div class="nav-container">
            <a href="/" class="logo">
                <img src="/assets/images/logo.svg" alt="NoteSnap Logo">
                <span>NoteSnap</span>
            </a>

            <button class="mobile-menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <div class="nav-links">
                <?php if ($session->isLoggedIn()): ?>
                    <a href="/dashboard.php">Dashboard</a>
                    <a href="/notes/new.php">New Note</a>
                    <a href="/search.php">Search</a>
                    <div class="user-menu">
                        <button class="user-menu-toggle">
                            <img src="/assets/images/avatar.png" alt="User avatar" class="avatar">
                            <span>Account</span>
                        </button>
                        <div class="dropdown-menu">
                            <a href="/profile.php">Profile</a>
                            <a href="/settings.php">Settings</a>
                            <hr>
                            <a href="/logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/#features">Features</a>
                    <a href="/#pricing">Pricing</a>
                    <a href="/login.php" class="btn btn-outline">Login</a>
                    <a href="/register.php" class="btn btn-primary">Sign Up</a>
                <?php endif; ?>

                <button class="theme-toggle" aria-label="Toggle theme">
                    <i class="fas fa-sun"></i>
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main id="main-content">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="flash-message <?php echo $_SESSION['flash_type'] ?? 'info'; ?>">
                <?php
                echo $_SESSION['flash_message'];
                unset($_SESSION['flash_message']);
                unset($_SESSION['flash_type']);
                ?>
            </div>
        <?php endif; ?>