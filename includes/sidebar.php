<?php
// ============================================================
// includes/sidebar.php - Sidebar i dashboard-it sipas rolit
// Kërkon: $sidebarRole = 'patient' | 'doctor' | 'admin'
// ============================================================

$currentUrl  = $_SERVER['REQUEST_URI'];
$sidebarRole = $sidebarRole ?? getCurrentRole();
$user        = getCurrentUser();

$navItems = [];

if ($sidebarRole === ROLE_PATIENT) {
    $navItems = [
        ['url' => '/patient/dashboard.php',     'icon' => '&#9632;',  'label' => 'Paneli Im'],
        ['url' => '/patient/reserve.php',        'icon' => '&#43;',    'label' => 'Rezervo Takim'],
        ['url' => '/patient/appointments.php',   'icon' => '&#128197;','label' => 'Takimet e Mia'],
        ['url' => '/patient/prescriptions.php',  'icon' => '&#128138;','label' => 'Recetat'],
        ['url' => '/patient/profile.php',        'icon' => '&#128100;','label' => 'Profili Im'],
        ['url' => '/patient/change-password.php','icon' => '&#128274;','label' => 'Ndrysho Fjalëkalim'],
    ];
} elseif ($sidebarRole === ROLE_DOCTOR) {
    $navItems = [
        ['url' => '/doctor/dashboard.php',      'icon' => '&#9632;',  'label' => 'Paneli Im'],
        ['url' => '/doctor/schedule.php',        'icon' => '&#128197;','label' => 'Orari Im'],
        ['url' => '/doctor/patients.php',        'icon' => '&#128101;','label' => 'Pacientët e Mi'],
        ['url' => '/doctor/upload_rx.php',       'icon' => '&#128138;','label' => 'Ngarko Recetë'],
        ['url' => '/doctor/profile.php',         'icon' => '&#128100;','label' => 'Profili Im'],
        ['url' => '/doctor/change-password.php', 'icon' => '&#128274;','label' => 'Ndrysho Fjalëkalim'],
    ];
} elseif ($sidebarRole === ROLE_ADMIN) {
    $navItems = [
        ['url' => '/doctor/admin/dashboard.php',        'icon' => '&#9632;',  'label' => 'Paneli Admin'],
        ['url' => '/doctor/admin/doctors.php',          'icon' => '&#128101;','label' => 'Mjekët'],
        ['url' => '/doctor/admin/patients.php',         'icon' => '&#128101;','label' => 'Pacientët'],
        ['url' => '/doctor/admin/appointments.php',     'icon' => '&#128197;','label' => 'Takimet'],
        ['url' => '/doctor/admin/schedules.php',        'icon' => '&#128338;','label' => 'Oraret'],
        ['url' => '/doctor/admin/prices.php',           'icon' => '&#128178;','label' => 'Shërbimet &amp; Çmimet'],
        ['url' => '/doctor/admin/reports.php',          'icon' => '&#128202;','label' => 'Raportet'],
        ['url' => '/doctor/admin/contact-queries.php',  'icon' => '&#9993;',  'label' => 'Mesazhet Kontakt'],
        ['url' => '/doctor/admin/session-logs.php',     'icon' => '&#128221;','label' => 'Loget e Sesioneve'],
    ];
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo-icon">&#43;</div>
        <div class="sidebar-brand">
            <strong>Vitanova</strong>
            <small>Clinic</small>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-avatar">
            <?php if (!empty($user['photo_path'])): ?>
                <img src="<?= BASE_URL . '/' . e($user['photo_path']) ?>" alt="Foto">
            <?php else: ?>
                <?= e(getInitials($user['name'])) ?>
            <?php endif; ?>
        </div>
        <div class="sidebar-user-info">
            <strong><?= e($user['name']) ?></strong>
            <small><?= e($sidebarRole) ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($navItems as $item): ?>
        <a href="<?= BASE_URL . $item['url'] ?>"
           class="sidebar-link <?= str_contains($currentUrl, $item['url']) ? 'active' : '' ?>">
            <span class="link-icon"><?= $item['icon'] ?></span>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <form action="<?= BASE_URL ?>/public/logout.php" method="POST">
            <?= csrfInput() ?>
            <button type="submit" class="sidebar-link sidebar-link-logout w-100" style="border:none;cursor:pointer;background:none;">
                <span class="link-icon">&#128682;</span> Dil
            </button>
        </form>
    </div>
</aside>

<!-- Overlay për mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
