<?php
// ========================================================
// Main UI Footer & Script includes
// File: includes/footer.php
// ========================================================
?>
    </div> <!-- /content-body -->

    <!-- FOOTER BAR -->
    <footer class="bg-white border-top py-3 px-4 text-center text-muted" style="font-size: 0.85rem;">
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <span>&copy; <?php echo date('Y'); ?> <strong>College English Department</strong>. AI-Powered Academic Scheduling.</span>
        <span>Version 1.0.0 &bull; Developed for Major Project</span>
      </div>
    </footer>
  </main>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Live Clock Script & Sidebar Toggle -->
<script>
  // Live Clock
  function updateClock() {
    const clockEl = document.getElementById('liveClock');
    if (clockEl) {
      const now = new Date();
      clockEl.textContent = now.toLocaleTimeString();
    }
  }
  setInterval(updateClock, 1000);
  updateClock();

  // Mobile Sidebar Toggle
  const toggleBtn = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('show');
    });
  }
</script>

</body>
</html>
