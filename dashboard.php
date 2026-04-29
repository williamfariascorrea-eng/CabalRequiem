<?php
session_start();
require_once __DIR__ . '/backend/vendor/autoload.php';

use CabalOnline\Auth;
use CabalOnline\Database;

// Check if user is logged in
if (empty($_SESSION['user_id']) || empty($_SESSION['token'])) {
    header('Location: login.html?error=please_login');
    exit;
}

try {
    // Get database connection
    $db = Database::getInstance()->getConnection();
    $auth = new Auth($db);

    // Validate token from session
    $user_data = $auth->validateToken($_SESSION['token']);
    if (!$user_data) {
        // Token invalid, clear session and redirect
        session_unset();
        session_destroy();
        header('Location: login.html?error=session_expired');
        exit;
    }

    // Get dashboard data
    $dashboard = $auth->getDashboardData($_SESSION['user_id']);
    if (!$dashboard['success']) {
        throw new Exception($dashboard['message']);
    }

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    session_unset();
    session_destroy();
    header('Location: login.html?error=dashboard_error');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel — Cabal Réquiem</title>
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
    <main class="dashboard-page">
        <header class="dashboard-header">
            <div class="header-left">
                <i class="fas fa-chart-line"></i>
                <h1>Painel de Controle</h1>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($dashboard['user']['username']); ?> 
                        (<?php echo htmlspecialchars($dashboard['user']['role']); ?>)
                    </span>
                </div>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- User Profile -->
            <section class="dashboard-widget">
                <h2><i class="fas fa-id-card"></i> Informações do Usuário</h2>
                <div class="widget-body">
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($dashboard['user']['full_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($dashboard['user']['email']); ?></p>
                    <p><strong>Data de Registro:</strong> <?php echo htmlspecialchars($dashboard['user']['created_at']); ?></p>
                    <p><strong>Último Login:</strong> <?php echo htmlspecialchars($dashboard['user']['last_login']); ?></p>
                </div>
            </section>

            <!-- Game Profile -->
            <section class="dashboard-widget">
                <h2><i class="fas fa-gamepad"></i> Perfil de Jogo</h2>
                <div class="widget-body">
                    <p><strong>Nome do Personagem:</strong> 
                        <?php echo $dashboard['profile']['character_name'] ? 
                            htmlspecialchars($dashboard['profile']['character_name']) : 
                            '<em>Não definido</em>'; ?>
                    </p>
                    <p><strong>Classe:</strong> 
                        <?php echo $dashboard['profile']['class'] ? 
                            htmlspecialchars($dashboard['profile']['class']) : 
                            '<em>Não definido</em>'; ?>
                    </p>
                    <p><strong>Nível:</strong> <?php echo htmlspecialchars($dashboard['profile']['level']); ?></p>
                    <p><strong>Experiência:</strong> <?php echo number_format($dashboard['profile']['experience']); ?></p>
                    <p><strong>Ouro:</strong> <?php echo number_format($dashboard['profile']['gold']); ?></p>
                    <p><strong>Último Jogo:</strong> 
                        <?php echo $dashboard['profile']['last_played'] ? 
                            htmlspecialchars($dashboard['profile']['last_played']) : 
                            '<em>Nunca</em>'; ?>
                    </p>
                </div>
            </section>

            <!-- Rankings -->
            <section class="dashboard-widget">
                <h2><i class="fas fa-trophy"></i> Rankings (Top 10 por Nível)</h2>
                <div class="widget-body">
                    <?php if (empty($dashboard['rankings'])): ?>
                        <p class="no-data">Nenhum dado de ranking disponível.</p>
                    <?php else: ?>
                        <table class="rankings-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Usuário</th>
                                    <th>Nível</th>
                                    <th>Classe</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard['rankings'] as $index => $rank): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($rank['username']); ?></td>
                                        <td><?php echo htmlspecialchars($rank['level']); ?></td>
                                        <td><?php echo htmlspecialchars($rank['class']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>
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