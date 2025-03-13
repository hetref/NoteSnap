<?php
require_once 'includes/header.php';

// Redirect if already logged in
if ($session->isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $security->validateCSRFToken();

    $username = $security->sanitizeInput($_POST['username']);
    $email = $security->sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $securityQuestion = $security->sanitizeInput($_POST['security_question']);
    $securityAnswer = $security->sanitizeInput($_POST['security_answer']);

    $errors = [];

    // Validate username
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = 'Username must be between 3 and 50 characters';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        $errors['username'] = 'Username can only contain letters, numbers, underscores, and hyphens';
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    // Validate password
    if (!$security->validatePassword($password)) {
        $errors['password'] = 'Password must be at least 8 characters and contain uppercase, lowercase, number, and special character';
    }

    // Validate password confirmation
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // Validate security question and answer
    if (empty($securityQuestion)) {
        $errors['security_question'] = 'Security question is required';
    }
    if (empty($securityAnswer)) {
        $errors['security_answer'] = 'Security answer is required';
    }

    if (empty($errors)) {
        try {
            // Check if username or email already exists
            $stmt = $db->prepare("
                SELECT username, email 
                FROM users 
                WHERE username = ? OR email = ?
            ");

            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($existing = $result->fetch_assoc()) {
                if ($existing['username'] === $username) {
                    $errors['username'] = 'Username already taken';
                }
                if ($existing['email'] === $email) {
                    $errors['email'] = 'Email already registered';
                }
            } else {
                // Create new user
                $uuid = $security->generateUUID();
                $hashedPassword = $security->hashPassword($password);
                $hashedAnswer = $security->hashPassword($securityAnswer);

                $stmt = $db->prepare("
                    INSERT INTO users (uuid, username, email, password_hash, security_question, security_answer_hash)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $stmt->bind_param("ssssss", $uuid, $username, $email, $hashedPassword, $securityQuestion, $hashedAnswer);

                if ($stmt->execute()) {
                    // Log activity
                    $userId = $stmt->insert_id;
                    $security->logActivity($userId, 'register');

                    // Create session
                    $session->createSession($userId);

                    // Redirect to dashboard
                    header('Location: /dashboard.php');
                    exit;
                } else {
                    throw new Exception('Failed to create user');
                }
            }
        } catch (Exception $e) {
            $_SESSION['flash_message'] = 'An error occurred. Please try again.';
            $_SESSION['flash_type'] = 'error';
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h1>Create Account</h1>
            <p>Join NoteSnap and start taking secure notes</p>
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
                        class="form-input <?php echo isset($errors['username']) ? 'error' : ''; ?>"
                        required
                        autofocus
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <?php if (isset($errors['username'])): ?>
                    <div class="error-message"><?php echo $errors['username']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email"
                        id="email"
                        name="email"
                        class="form-input <?php echo isset($errors['email']) ? 'error' : ''; ?>"
                        required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <?php if (isset($errors['email'])): ?>
                    <div class="error-message"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password"
                        id="password"
                        name="password"
                        class="form-input <?php echo isset($errors['password']) ? 'error' : ''; ?>"
                        required
                        data-validate-password="true">
                    <button type="button"
                        class="password-toggle"
                        aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['password'])): ?>
                    <div class="error-message"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
                <div class="password-strength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-input <?php echo isset($errors['confirm_password']) ? 'error' : ''; ?>"
                        required>
                    <button type="button"
                        class="password-toggle"
                        aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?>
                    <div class="error-message"><?php echo $errors['confirm_password']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="security_question" class="form-label">Security Question</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-question-circle"></i></span>
                    <select id="security_question"
                        name="security_question"
                        class="form-input <?php echo isset($errors['security_question']) ? 'error' : ''; ?>"
                        required>
                        <option value="">Select a security question</option>
                        <option value="What was your first pet's name?">What was your first pet's name?</option>
                        <option value="What city were you born in?">What city were you born in?</option>
                        <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                        <option value="What was the name of your first school?">What was the name of your first school?</option>
                        <option value="What is your favorite book?">What is your favorite book?</option>
                    </select>
                </div>
                <?php if (isset($errors['security_question'])): ?>
                    <div class="error-message"><?php echo $errors['security_question']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="security_answer" class="form-label">Security Answer</label>
                <div class="input-group">
                    <span class="input-icon"><i class="fas fa-key"></i></span>
                    <input type="text"
                        id="security_answer"
                        name="security_answer"
                        class="form-input <?php echo isset($errors['security_answer']) ? 'error' : ''; ?>"
                        required>
                </div>
                <?php if (isset($errors['security_answer'])): ?>
                    <div class="error-message"><?php echo $errors['security_answer']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" required>
                    <span class="checkbox-custom"></span>
                    I agree to the <a href="/terms" target="_blank">Terms of Service</a> and
                    <a href="/privacy" target="_blank">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Create Account
                <i class="fas fa-user-plus"></i>
            </button>
        </form>

        <div class="auth-divider">
            <span>or sign up with</span>
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
            Already have an account?
            <a href="/login.php">Sign in</a>
        </p>
    </div>
</div>

<style>
    /* Password Strength Indicator */
    .password-strength {
        height: 4px;
        margin-top: var(--spacing-xs);
        border-radius: var(--border-radius-sm);
        background: var(--color-border);
        overflow: hidden;
    }

    .password-strength::before {
        content: '';
        display: block;
        height: 100%;
        width: 0;
        transition: width var(--transition-normal), background-color var(--transition-normal);
    }

    .password-strength[data-strength="weak"]::before {
        width: 33.33%;
        background-color: var(--color-error);
    }

    .password-strength[data-strength="medium"]::before {
        width: 66.66%;
        background-color: var(--color-warning);
    }

    .password-strength[data-strength="strong"]::before {
        width: 100%;
        background-color: var(--color-success);
    }

    /* Form Select Styling */
    select.form-input {
        appearance: none;
        padding-right: calc(var(--spacing-xl) + var(--spacing-sm));
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right var(--spacing-md) center;
        background-size: var(--spacing-md);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthIndicator = document.querySelector('.password-strength');

        passwordInput.addEventListener('input', () => {
            const password = passwordInput.value;
            let strength = 'weak';

            if (password.length >= 8) {
                if (/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/.test(password)) {
                    strength = 'strong';
                } else if (/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(password)) {
                    strength = 'medium';
                }
            }

            strengthIndicator.setAttribute('data-strength', strength);
        });

        // Password confirmation checker
        const confirmInput = document.getElementById('confirm_password');

        confirmInput.addEventListener('input', () => {
            if (confirmInput.value === passwordInput.value) {
                confirmInput.setCustomValidity('');
            } else {
                confirmInput.setCustomValidity('Passwords do not match');
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>