<?php
require_admin();

$pageTitle = $pageTitle ?? 'Admin';
$pageSubtitle = $pageSubtitle ?? 'Sistema interno RG Auto Sales';
$contentFile = $contentFile ?? null;
$alerts = $alerts ?? ($_SESSION['flash'] ?? []);
unset($_SESSION['flash']);

if (!$contentFile) {
    throw new RuntimeException('contentFile nao foi definido para o layout admin.');
}

$resolvedContentFile = $contentFile;
if (!str_contains($resolvedContentFile, ':') && !str_starts_with($resolvedContentFile, DIRECTORY_SEPARATOR)) {
    $resolvedContentFile = BASE_PATH . DIRECTORY_SEPARATOR . ltrim($resolvedContentFile, '/\\');
}

$realContentFile = realpath($resolvedContentFile);
$realBasePath = realpath(BASE_PATH);

if (!$realContentFile || !$realBasePath || !str_starts_with($realContentFile, $realBasePath)) {
    throw new RuntimeException('contentFile invalido para o layout admin.');
}

require __DIR__ . '/admin_header.php';
?>
<div class="rg-admin-shell">
    <?php require __DIR__ . '/admin_sidebar.php'; ?>

    <main class="rg-admin-main">
        <?php require __DIR__ . '/admin_topbar.php'; ?>

        <section class="rg-admin-content">
            <?php if (!empty($alerts)): ?>
                <div class="rg-admin-alerts">
                    <?php foreach ((array)$alerts as $alert): ?>
                        <?php
                        $type = is_array($alert) ? ($alert['type'] ?? 'info') : 'info';
                        $message = is_array($alert) ? ($alert['message'] ?? '') : (string)$alert;
                        ?>
                        <?php if ($message !== ''): ?>
                            <div class="rg-admin-alert rg-admin-alert--<?= h($type) ?>">
                                <?= h($message) ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php require $realContentFile; ?>
        </section>

<?php require __DIR__ . '/admin_footer.php'; ?>

