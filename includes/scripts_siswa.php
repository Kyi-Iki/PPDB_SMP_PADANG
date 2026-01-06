<?php
/**
 * File: includes/scripts_siswa.php
 * Deskripsi: JavaScript libraries untuk halaman siswa
 */
?>
<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Form Validation & UI Helpers -->
<script src="/ppdb-smp/assets/js/form-validation.js?v=<?php echo time(); ?>"></script>

<!-- Custom Siswa Scripts -->
<script>
// Auto hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.classList.add('fade');
            setTimeout(function() {
                alert.remove();
            }, 150);
        }, 5000);
    });
});
</script>

