<?php
session_start();
require_once __DIR__ . '/backend/vendor/autoload.php';

use CabalOnline\Auth;
use CabalOnline\Database;

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: recover.html?error=invalid_token');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        header('Location: reset.php?token=' . urlencode($token) . '&error=missing_password');
        exit;
    }

    if ($new_password !== $confirm_password) {
        header('Location: reset.php?token=' . urlencode($token) . '&error=password_mismatch');
        exit;
    }

    if (strlen($new_password) < 6 || strlen($new_password) > 16) {
        header('Location: reset.php?token=' . urlencode($token) . '&error=password_length');
        exit;
    }

    try {
        $db = Database::getInstance()->getConnection();
        $auth = new Auth($db);

        // Find valid token
        $stmt = $db->prepare(
            "SELECT user_id FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW()"
        );
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            header('Location: recover.html?error=expired_token');
            exit;
        }

        // Reset password
        $result = $auth->resetPassword($reset['user_id'], $new_password);
        if ($result['success']) {
            // Mark token as used
            $stmt = $db->prepare(
                "UPDATE password_resets SET used = 1 WHERE token = ?"
            );
            $stmt->execute([$token]);

            header('Location: login.html?success=password_reset');
            exit;
        } else {
            header('Location: reset.php?token=' . urlencode($token) . '&error=' . urlencode($result['message']));
            exit;
        }
    } catch (Exception $e) {
        error_log("Reset error: " . $e->getMessage());
        header('Location: recover.html?error=system_error');
        exit;
    }
} else {
    // Show reset form
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha — Cabal Réquiem</title>
    <meta name="author" content="William Corrêa">
    <meta name="copyright" content="© 2026 Cabal Réquiem">
    <meta name="robots" content="noindex, nofollow">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="Themes/Dark/style.css">
</head>
<body>
    <main class="auth-page">
        <div class="auth-card">
            <div class="auth-card__header">
                <i class="fas fa-key"></i>
                <h1>Redefinir Senha</h1>
            </div>
            <div class="auth-card__body">
                <form action="" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label class="form-label">Nova Senha</label>
                        <input type="password" class="form-input" name="new_password" required minlength="6" maxlength="16">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirmar Senha</label>
                        <input type="password" class="form-input" name="confirm_password" required minlength="6" maxlength="16">
                    </div>
                    <button type="submit" class="form-btn"><i class="fas fa-key"></i> Redefinir Senha</button>
                </form>
            </div>
            <div class="auth-card__footer">
                <p>Lembrou da senha?</p>
                <a href="login.html" class="auth-card__link"><i class="fas fa-sign-in-alt"></i> Entrar</a>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <img src="Imagens/Logo.png" alt="Cabal Réquiem" style="width:80px;">
            <p class="footer__copy">© 2026 Cabal Réquiem — Todos os direitos reservados.</p>
            <p class="footer__dev">Desenvolvido por Wonka</p>
            <nav class="footer__nav">
                <a href="login.html">Entrar</a>
                <a href="register.html">Criar conta</a>
                <a href="ranking.html">Rankings</a>
            </nav>
        </div>
    </footer>

    <script src="Themes/Dark/script.js" defer></script>
</body>
</html>
<?php
}