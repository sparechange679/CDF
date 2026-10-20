<?php
// Top bar shared by every page (public pages and the staff workspaces).
//
// Include it where the old gov-topbar div used to be:
// It loads bootstrap.php itself, so the session is always started and
// current_user() is available, even if the page forgot to load it first.
// Signed out: Login, Register and Staff sign-in links.
// Signed in:  the user's name, a workspace link for staff, and Logout.
require_once __DIR__ . '/bootstrap.php';

$topbarUser = current_user();

// Work out how many folders deep the page is, so links work from vdc/, mp/, etc.
$topbarRoot  = realpath(dirname(__DIR__));
$topbarHere  = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? '')) ?: $topbarRoot;
$topbarRel   = trim((string) substr($topbarHere, strlen($topbarRoot)), DIRECTORY_SEPARATOR);
$topbarDepth = $topbarRel === '' ? 0 : substr_count($topbarRel, DIRECTORY_SEPARATOR) + 1;
$topbarBase  = str_repeat('../', $topbarDepth);

$topbarSep = '<span class="mx-2">|</span>';
?>
<div class="gov-topbar">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-1">
        <div><i class="bi bi-building"></i> Official Government of Malawi Portal</div>
        <div>
            <?php if ($topbarUser): ?>
                <span class="d-none d-md-inline" style="opacity:.75">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($topbarUser['full_name'], ENT_QUOTES, 'UTF-8') ?>
                    (<?= htmlspecialchars($topbarUser['role_name'], ENT_QUOTES, 'UTF-8') ?>)
                </span>
                <span class="mx-2 d-none d-md-inline">|</span>
                <?php if ($topbarUser['role'] !== 'citizen'): ?>
                    <a href="<?= $topbarBase . htmlspecialchars($topbarUser['dashboard'], ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-speedometer2"></i> My workspace</a>
                    <?= $topbarSep ?>
                <?php endif; ?>
                <a href="<?= $topbarBase ?>logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
            <?php else: ?>
                <a href="<?= $topbarBase ?>login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                <?= $topbarSep ?>
                <a href="<?= $topbarBase ?>login.php?tab=register"><i class="bi bi-person-plus"></i> Register</a>
                <?= $topbarSep ?>
                <a href="<?= $topbarBase ?>auth.php"><i class="bi bi-people"></i> Staff sign-in</a>
            <?php endif; ?>
        </div>
    </div>
</div>