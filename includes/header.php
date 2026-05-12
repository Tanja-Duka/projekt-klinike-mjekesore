<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="description" content="<?= APP_NAME ?> - Shërbime mjekësore profesionale për të gjithë familjen.">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= APP_NAME ?></title>

    <!-- CSS kryesor -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/navbar.css">

    <!-- CSS specifik sipas faqes -->
    <?php if (isset($cssFile)): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= e($cssFile) ?>">
    <?php endif; ?>

    <!-- CSS shtesë (mund të specifikohet si array) -->
    <?php if (!empty($extraCss) && is_array($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= e($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
