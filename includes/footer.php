    </main>
    
    <!-- Footer -->
    <footer class="bg-dark text-white mt-5">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="text-uppercase mb-4">Saint Joseph Institute of Technology</h5>
                    <p>Student Portal - Access your educational resources, grades, and more in one place.</p>
                    <div class="social-icons mt-4">
                        <a href="#" class="me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="col-md-4 col-lg-2 mb-4 mb-md-0">
                    <h6 class="text-uppercase fw-bold mb-4">Quick Links</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="dashboard.php">Dashboard</a></li>
                        <li class="mb-2"><a href="myprofile.php">My Profile</a></li>
                        <li class="mb-2"><a href="schedule.php">Class Schedule</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 col-lg-2 mb-4 mb-md-0">
                    <h6 class="text-uppercase fw-bold mb-4">Support</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="#">Help Center</a></li>
                        <li class="mb-2"><a href="#">FAQs</a></li>
                        <li class="mb-2"><a href="#">Contact Us</a></li>
                        <li class="mb-2"><a href="#">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 col-lg-2">
                    <h6 class="text-uppercase fw-bold mb-4">About</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="#">About SJIT</a></li>
                        <li class="mb-2"><a href="#">Academic Programs</a></li>
                        <li class="mb-2"><a href="#">Admissions</a></li>
                        <li class="mb-2"><a href="#">News & Events</a></li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4" style="border-color: rgba(255, 255, 255, 0.2);">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Saint Joseph Institute of Technology. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-white me-3">Terms of Service</a>
                    <a href="#" class="text-white">Privacy Policy</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Auto-hide alerts after 5 seconds
        window.setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Add smooth scrolling to all links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Add active class to current nav link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = location.pathname.split('/').pop() || 'index.php';
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (linkHref && linkHref.includes(currentPage) && linkHref !== '#') {
                    link.classList.add('active');
                    link.setAttribute('aria-current', 'page');
                }
            });
        });
    </script>
</body>
</html>
