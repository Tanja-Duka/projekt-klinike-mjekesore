<?php
// Gjej URL-në aktuale për të shënuar linkun aktiv
$currentUrl = $_SERVER['REQUEST_URI'];
$user       = isLoggedIn() ? getCurrentUser() : null;
$role       = $user['role'] ?? null;
?>
<nav class="navbar">
    <div class="container navbar-inner">

        <!-- Logo -->
        <a href="<?= BASE_URL ?>/public/home.php" class="navbar-logo">
            <div class="navbar-logo-icon">&#43;</div>
            <div class="navbar-logo-text">
                <strong>Vitanova</strong>
                <span>Clinic</span>
            </div>
        </a>

        <!-- Navigimi qendror -->
        <ul class="navbar-menu" id="navMenu">
            <li><a href="<?= BASE_URL ?>/public/home.php"
                   class="<?= str_contains($currentUrl, 'home') ? 'active' : '' ?>">Home</a></li>
            <li><a href="<?= BASE_URL ?>/public/services.php"
                   class="<?= str_contains($currentUrl, 'services') ? 'active' : '' ?>">Shërbimet</a></li>
            <li><a href="<?= BASE_URL ?>/public/about.php"
                   class="<?= str_contains($currentUrl, 'about') ? 'active' : '' ?>">Rreth Nesh</a></li>
            <li><a href="<?= BASE_URL ?>/public/doctors.php"
                   class="<?= str_contains($currentUrl, 'doctors') ? 'active' : '' ?>">Mjekët</a></li>
            <li><a href="<?= BASE_URL ?>/public/contact.php"
                   class="<?= str_contains($currentUrl, 'contact') ? 'active' : '' ?>">Kontakt</a></li>

            <?php if ($role === ROLE_PATIENT): ?>
                <li><a href="<?= BASE_URL ?>/patient/dashboard.php"
                       class="<?= str_contains($currentUrl, '/patient/') ? 'active' : '' ?>">Paneli Im</a></li>
            <?php elseif ($role === ROLE_DOCTOR): ?>
                <li><a href="<?= BASE_URL ?>/doctor/dashboard.php"
                       class="<?= str_contains($currentUrl, '/doctor/') ? 'active' : '' ?>">Paneli Im</a></li>
            <?php elseif ($role === ROLE_ADMIN): ?>
                <li><a href="<?= BASE_URL ?>/doctor/admin/dashboard.php"
                       class="<?= str_contains($currentUrl, '/admin/') ? 'active' : '' ?>">Admin</a></li>
            <?php endif; ?>
        </ul>

        <!-- Search bar (vetëm vizitorë dhe pacientë) -->
        <?php if (!$user || $role === ROLE_PATIENT): ?>
        <div class="navbar-search">
            <input type="text" id="navSearch" placeholder="Kërko mjekë, shërbime..." autocomplete="off">
            <button class="navbar-search-btn" aria-label="Kërko">&#128269;</button>
            <div class="search-results" id="searchResults"></div>
        </div>
        <?php endif; ?>

        <!-- Aksionet djathtas -->
        <div class="navbar-actions">
            <?php if ($user): ?>
                <div class="navbar-user">
                    <div class="navbar-user-avatar">
                        <?php if (!empty($user['photo_path'])): ?>
                            <img src="<?= BASE_URL . '/' . e($user['photo_path']) ?>" alt="Foto">
                        <?php else: ?>
                            <?= e(getInitials($user['name'])) ?>
                        <?php endif; ?>
                    </div>
                    <span class="navbar-user-name"><?= e(explode(' ', $user['name'])[0]) ?></span>
                </div>
                <form action="<?= BASE_URL ?>/public/logout.php" method="POST" style="margin:0;">
                    <?= csrfInput() ?>
                    <button type="submit" class="btn btn-outline btn-sm">Dil</button>
                </form>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/public/login.php" class="btn btn-outline btn-sm">Hyr</a>
                <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta btn-sm">Rezervo Takim</a>
            <?php endif; ?>
        </div>

        <!-- Hamburger (mobile) -->
        <button class="navbar-hamburger" id="navHamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>

    </div>
</nav>

