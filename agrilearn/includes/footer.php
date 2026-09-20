<div class="app-footer-note">
        &copy; <?= date('Y') ?> <?= SITE_NAME ?> &mdash; <?= CENTER_NAME ?>
      </div>
    </div><!-- /.app-content -->
  </div><!-- /.app-main -->
</div><!-- /.app-shell -->

<?php if (current_user()): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/script.js"></script>
<script>
  const alBurgerBtn = document.getElementById('alBurgerBtn');
  const appSidebar  = document.getElementById('appSidebar');
  const alOverlay   = document.getElementById('alOverlay');

  function alOpenSidebar()  {
    appSidebar.classList.add('open');
    alOverlay.classList.add('open');
    alBurgerBtn.classList.add('open');
    document.body.classList.add('al-noscroll');
  }
  function alCloseSidebar() {
    appSidebar.classList.remove('open');
    alOverlay.classList.remove('open');
    alBurgerBtn.classList.remove('open');
    document.body.classList.remove('al-noscroll');
  }

  if (alBurgerBtn) {
    alBurgerBtn.addEventListener('click', () => {
      appSidebar.classList.contains('open') ? alCloseSidebar() : alOpenSidebar();
    });
  }
  if (alOverlay) alOverlay.addEventListener('click', alCloseSidebar);

  // Close the sidebar automatically when a nav link is tapped on mobile
  document.querySelectorAll('.app-sidebar-nav a').forEach(link => {
    link.addEventListener('click', alCloseSidebar);
  });

  const notifBellBtn = document.getElementById('notifBellBtn');
  const notifDropdown = document.getElementById('notifDropdown');
  if (notifBellBtn) {
    notifBellBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      notifDropdown.classList.toggle('open');
    });
    document.addEventListener('click', (e) => {
      if (!notifDropdown.contains(e.target) && e.target !== notifBellBtn) {
        notifDropdown.classList.remove('open');
      }
    });
  }

  const notifMarkAllBtn = document.getElementById('notifMarkAllBtn');
  if (notifMarkAllBtn) {
    notifMarkAllBtn.addEventListener('click', () => {
      fetch('<?= BASE_URL ?>/ajax/mark_notifications_read.php', { method: 'POST' })
        .then(() => {
          document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
          const badge = document.getElementById('notifBadge');
          if (badge) badge.remove();
          notifMarkAllBtn.remove();
        });
    });
  }
</script>
<?php else: ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
</body>
</html>