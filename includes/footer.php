<footer class="footer mt-5 py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h5 class="fw-bold mb-3" style="color: #ffffff !important;">
                        <i class="fas fa-graduation-cap me-2" style="color: #ffffff !important;"></i>
                        PPDB SMP 2025
                    </h5>
                    <p class="text-light opacity-75 mb-2">
                        Sistem Informasi Penerimaan Peserta Didik Baru<br>
                        Tingkat SMP Tahun Ajaran <?php echo defined('TAHUN_AJARAN') ? TAHUN_AJARAN : '2025/2026'; ?>
                    </p>
                </div>
                
                <div class="col-md-3 mb-3 mb-md-0">
                    <h6 class="fw-bold mb-3" style="color: #ffffff !important;">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/siswa/') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>index.php" class="text-light opacity-75 text-decoration-none">
                                <i class="fas fa-chevron-right me-2"></i>Beranda
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/siswa/') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>panduan.php" class="text-light opacity-75 text-decoration-none">
                                <i class="fas fa-chevron-right me-2"></i>Panduan
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?php echo strpos($_SERVER['REQUEST_URI'], '/siswa/') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? '../' : ''; ?>panduan.php#faqAccordion" class="text-light opacity-75 text-decoration-none">
                                <i class="fas fa-chevron-right me-2"></i>FAQ
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-3">
                    <h6 class="fw-bold mb-3" style="color: #ffffff !important;">Kontak</h6>
                    <ul class="list-unstyled text-light opacity-75">
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i> (0751) 123456
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i> info@ppdb-smp.sch.id
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i> Padang, Sumatera Barat
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4 bg-white opacity-25">
            
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-light opacity-75">
                        &copy; <?php echo date('Y'); ?> PPDB SMP. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small class="text-light opacity-75">
                        <i class="fas fa-code me-1"></i>
                        Developed for Informatika Project
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (Optional) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Leaflet JS for Interactive Map -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Turf.js for Point-in-Polygon -->
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Auto-hide alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.classList.contains('alert-dismissible')) {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 5000);
            });
        });
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Form validation feedback
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>