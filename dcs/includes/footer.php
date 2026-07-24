</div> <!-- bs-content -->
    </main>
</div> <!-- bs-shell -->

<!-- Third-party libraries -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Custom scripts -->
<script src="../assets/js/dark-mode.js"></script>
<script src="../assets/js/toast.js"></script>

<!-- DataTables initialisation -->
<script>
$(document).ready(function() {
    $('.datatable').DataTable();
});
</script>

<!-- Toast notifications from session -->
<?php if (isset($_SESSION['toast'])): ?>
<script>
    showToast('<?= addslashes($_SESSION['toast']['message']) ?>', '<?= $_SESSION['toast']['type'] ?>');
</script>
<?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<!-- ⭐ FALLBACK: Dark mode toggle (in case dark-mode.js doesn't load) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // If the external script already set up the toggle, skip
    if (window.darkModeInitialized) return;

    const toggle = document.getElementById('darkToggle');
    if (toggle) {
        // Load preference
        if (localStorage.getItem('darkMode') === 'enabled') {
            document.body.classList.add('dark-mode');
            const icon = toggle.querySelector('i');
            if (icon) icon.className = 'bi bi-sun-fill';
        }
        toggle.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.className = isDark ? 'bi bi-sun-fill' : 'bi bi-moon';
            }
        });
        window.darkModeInitialized = true;
    }
});
</script>

</body>
</html>