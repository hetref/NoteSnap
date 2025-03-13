    </main>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3>NoteSnap</h3>
                    <p>Your secure space for taking and organizing notes. Built with privacy and security in mind.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Follow us on Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Follow us on GitHub"><i class="fab fa-github"></i></a>
                        <a href="#" aria-label="Follow us on LinkedIn"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>

                <div class="footer-section">
                    <h4>Product</h4>
                    <ul>
                        <li><a href="/#features">Features</a></li>
                        <li><a href="/#pricing">Pricing</a></li>
                        <li><a href="/security.php">Security</a></li>
                        <li><a href="/enterprise.php">Enterprise</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Resources</h4>
                    <ul>
                        <li><a href="/docs">Documentation</a></li>
                        <li><a href="/api">API</a></li>
                        <li><a href="/guides">Guides</a></li>
                        <li><a href="/blog">Blog</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="/about">About</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/privacy">Privacy Policy</a></li>
                        <li><a href="/terms">Terms of Service</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h4>Stay Updated</h4>
                    <form class="newsletter-form" action="/subscribe" method="POST">
                        <?php $csrf_token = $security->generateCSRFToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Enter your email" required>
                            <button type="submit" class="btn btn-primary">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> NoteSnap. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="/privacy">Privacy</a>
                    <a href="/terms">Terms</a>
                    <a href="/cookies">Cookies</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="/assets/js/main.js" defer></script>
    <script src="/assets/js/theme.js" defer></script>
    <?php if ($session->isLoggedIn()): ?>
        <script src="/assets/js/app.js" defer></script>
    <?php endif; ?>

    <!-- Toast Notifications Container -->
    <div id="toast-container" aria-live="polite"></div>
    </body>

    </html>