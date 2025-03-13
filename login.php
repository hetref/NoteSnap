<?php
require_once 'includes/header.php';

// Redirect if already logged in
if ($session->isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $security->validateCSRFToken();

    $username = $security->sanitizeInput($_POST['username']);
    $password = $_POST['password'];

    try {
        $stmt = $db->prepare("
            SELECT id, password_hash 
            FROM users 
            WHERE username = ? AND is_active = 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ($security->verifyPassword($password, $user['password_hash'])) {
                // Create session
                $session->createSession($user['id']);

                // Log activity
                $security->logActivity($user['id'], 'login');

                // Redirect to dashboard
                header('Location: /dashboard.php');
                exit;
            }
        }

        // Invalid credentials
        $_SESSION['flash_message'] = 'Invalid username or password';
        $_SESSION['flash_type'] = 'error';
    } catch (Exception $e) {
        $_SESSION['flash_message'] = 'An error occurred. Please try again.';
        $_SESSION['flash_type'] = 'error';
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Welcome Back</h1>
            <p>Sign in to continue to NoteSnap</p>
        </div>

        <form method="POST" class="auth-form needs-validation">
            <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text"
                        id="username"
                        name="username"
                        class="form-input"
                        required
                        autofocus
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">
                    Password
                    <a href="/forgot-password.php" class="float-right">Forgot Password?</a>
                </label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        required>
                    <button type="button"
                        class="password-toggle"
                        aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1">
                    <span class="checkbox-custom"></span>
                    Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Sign In
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="auth-divider">
            <span>or continue with</span>
        </div>

        <div class="social-login">
            <button class="btn btn-outline btn-social">
                <i class="fab fa-google"></i>
                Google
            </button>
            <button class="btn btn-outline btn-social">
                <i class="fab fa-github"></i>
                GitHub
            </button>
        </div>

        <p class="auth-footer">
            Don't have an account?
            <a href="/register.php">Sign up</a>
        </p>
    </div>
</div>

<style>
    .auth-container {
        min-height: calc(100vh - var(--nav-height));
        display: flex;
        align-items: center;
        justify-content: center;
        padding: var(--spacing-xl) var(--spacing-md);
        background: linear-gradient(135deg, var(--color-primary-light) 0%, var(--color-primary) 100%);
    }

    .auth-card {
        background: var(--color-background);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--shadow-lg);
        padding: var(--spacing-xl);
        width: 100%;
        max-width: 400px;
        animation: slideIn var(--transition-normal);
    }

    .auth-header {
        text-align: center;
        margin-bottom: var(--spacing-xl);
    }

    .auth-header h1 {
        color: var(--color-primary);
        margin-bottom: var(--spacing-xs);
    }

    .auth-header p {
        color: var(--color-text-secondary);
    }

    .auth-form .input-group {
        position: relative;
    }

    .auth-form .input-icon {
        position: absolute;
        left: var(--spacing-md);
        top: 50%;
        transform: translateY(-50%);
        color: var(--color-text-secondary);
    }

    .auth-form .form-input {
        padding-left: calc(var(--spacing-xl) + var(--spacing-sm));
    }

    .password-toggle {
        position: absolute;
        right: var(--spacing-md);
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--color-text-secondary);
        cursor: pointer;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        cursor: pointer;
    }

    .checkbox-custom {
        width: 18px;
        height: 18px;
        border: 2px solid var(--color-border);
        border-radius: var(--border-radius-sm);
        position: relative;
    }

    .checkbox-label input:checked+.checkbox-custom::after {
        content: '\f00c';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: var(--color-primary);
        font-size: 12px;
    }

    .auth-divider {
        text-align: center;
        margin: var(--spacing-lg) 0;
        position: relative;
    }

    .auth-divider::before,
    .auth-divider::after {
        content: '';
        position: absolute;
        top: 50%;
        width: calc(50% - 50px);
        height: 1px;
        background: var(--color-border);
    }

    .auth-divider::before {
        left: 0;
    }

    .auth-divider::after {
        right: 0;
    }

    .auth-divider span {
        background: var(--color-background);
        padding: 0 var(--spacing-md);
        color: var(--color-text-secondary);
    }

    .social-login {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-lg);
    }

    .btn-social {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--spacing-sm);
    }

    .auth-footer {
        text-align: center;
        color: var(--color-text-secondary);
    }

    .auth-footer a {
        color: var(--color-primary);
        font-weight: 500;
    }

    @media (max-width: 480px) {
        .auth-card {
            padding: var(--spacing-lg);
        }

        .social-login {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Password visibility toggle
        const passwordToggles = document.querySelectorAll('.password-toggle');

        passwordToggles.forEach(toggle => {
            toggle.addEventListener('click', () => {
                const input = toggle.previousElementSibling;
                const icon = toggle.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>