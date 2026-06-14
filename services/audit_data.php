<?php

require_once "../path.php";

require_once ROOT_PATH . "services/UrlChecker.php";
require_once ROOT_PATH . "services/AccountScanner.php";

$email = $_POST['email'] ?? '';
$username = $_POST['username'] ?? '';
if (!$email || !$username) {
    exit;
}

$accountsResult = scanAccounts($email, $username);
?>
<div class="profiles-grid">
<?php foreach ($accountsResult as $account): ?>
    <div class="profile-item">
        <strong><?= htmlspecialchars($account["platform"]) ?></strong>
        <?php if ($account["exists"]): ?>
            <p>✓ Znaleziono</p>
            <a
                href="<?= htmlspecialchars($account["url"]) ?>"
                target="_blank"
            >
                Otwórz profil
            </a>
        <?php else: ?>
            <p>✗ Nie znaleziono</p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>
