<?php
// Top bar for the public pages.
// The page must load includes/bootstrap.php on its very first line (before any
// HTML) so the session is started, then include this file where the old
// gov-topbar div used to be (include __DIR__ . '/includes/topbar.php').
$topbarUser = function_exists('current_user') ? current_user() : null;
?>
<div class="gov-topbar">
    <div class="container d-flex justify-content-between align-items-center">
        <div><i class="bi bi-building"></i> Official Government of Malawi Portal</div>
        <div>
            <?php if ($topbarUser): ?>
                <span class="d-none d-md-inline" style="opacity:.75">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($topbarUser['full_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span class="mx-2 d-none d-md-inline">|</span>
                <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            <?php else: ?>
                <a href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                <span class="mx-2">|</span>
                <a href="login.php?tab=register"><i class="bi bi-person-plus"></i> Register</a>
            <?php endif; ?>
        </div>
    </div>
</div>
