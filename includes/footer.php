<?php ?>
    </main>
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/app.js"></script>
    
    <!-- Footer -->
    <footer class="footer bg-dark text-white py-3 mt-auto">
        <div class="container text-center">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> <a href="https://www.muni.ac.ug" class="text-white text-decoration-none" target="_blank">Muni University</a>. 
                All Rights Reserved. | QR Verification System v2.0
            </p>
        </div>
    </footer>
</body>
</html>
<?php
if (isset($pdo)) {
    $pdo = null;
}
?>