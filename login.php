<?php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erreur_connexion = '';
$success_message = '';
$email = '';

if (isset($_GET['inscription']) && $_GET['inscription'] === 'success') {
    $success_message = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['password'] ?? '';

    if ($email === '' || $mot_de_passe === '') {
        $erreur_connexion = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur_connexion = 'Veuillez saisir une adresse e-mail valide.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, prenom, email, mot_de_passe
                 FROM utilisateurs
                 WHERE email = ?
                 LIMIT 1'
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $user &&
                password_verify($mot_de_passe, $user['mot_de_passe'])
            ) {
                // Renouvelle l'identifiant de session après connexion.
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = $user['prenom'];

                // N'autorise que les redirections locales prévues.
                $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                unset($_SESSION['redirect_after_login']);

                $pages_autorisees = [
                    'index.php',
                    'evenements.php',
                    'profil.php'
                ];

                if (!in_array($redirect, $pages_autorisees, true)) {
                    $redirect = 'index.php';
                }

                header('Location: ' . $redirect);
                exit;
            }

            $erreur_connexion = 'E-mail ou mot de passe incorrect.';
        } catch (PDOException $e) {
            // L'erreur technique reste dans les journaux du serveur.
            error_log($e->getMessage());
            $erreur_connexion =
                'Une erreur est survenue. Veuillez réessayer plus tard.';
        }
    }
}

include 'includes/header.php';
?>

<style>
    .login-section {
        min-height: 75vh;
        padding: 70px 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            radial-gradient(circle at top left, #eee7ff 0, transparent 40%),
            linear-gradient(135deg, #f8f7fc, #f0ebfa);
    }

    .login-card {
        width: 100%;
        max-width: 460px;
        padding: 40px;
        background: #fff;
        border: 1px solid rgba(109, 40, 217, 0.08);
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(48, 25, 90, 0.10);
    }

    .login-icon {
        width: 68px;
        height: 68px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        border-radius: 20px;
        background: #f0e8ff;
        color: #6d28d9;
        font-size: 30px;
    }

    .login-title {
        color: #251641;
        font-weight: 800;
        letter-spacing: -0.5px;
    }

    .login-subtitle {
        color: #777187;
        font-size: 0.95rem;
    }

    .login-card .form-label {
        color: #33284b;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .login-card .form-control {
        min-height: 50px;
        padding: 12px 15px;
        border: 1px solid #e5e0ee;
        border-radius: 12px;
        background-color: #fcfbfe;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .login-card .form-control:focus {
        border-color: #8b5cf6;
        background-color: #fff;
        box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.12);
    }

    .password-wrapper {
        position: relative;
    }

    .password-wrapper .form-control {
        padding-right: 75px;
    }

    .toggle-password {
        position: absolute;
        top: 50%;
        right: 12px;
        transform: translateY(-50%);
        padding: 5px;
        border: none;
        background: transparent;
        color: #6d28d9;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-login {
        min-height: 50px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #7c3aed, #5b21b6);
        color: #fff;
        font-weight: 700;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn-login:hover {
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(109, 40, 217, 0.25);
    }

    .login-link {
        color: #6d28d9;
        font-weight: 700;
        text-decoration: none;
    }

    .login-link:hover {
        color: #4c1d95;
        text-decoration: underline;
    }

    .login-alert {
        border: none;
        border-radius: 12px;
        font-size: 0.92rem;
    }

    @media (max-width: 576px) {
        .login-section {
            padding: 40px 14px;
        }

        .login-card {
            padding: 28px 22px;
            border-radius: 20px;
        }
    }
</style>

<section class="login-section">
    <div class="login-card">

        <div class="text-center mb-4">
            <div class="login-icon" aria-hidden="true">
                <i class="bi bi-person-lock"></i>
            </div>

            <h1 class="login-title h2 mb-2">Bon retour !</h1>

            <p class="login-subtitle mb-0">
                Connecte-toi pour retrouver tes événements préférés.
            </p>
        </div>

        <?php if ($success_message !== ''): ?>
            <div class="alert alert-success login-alert" role="status">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?= htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($erreur_connexion !== ''): ?>
            <div class="alert alert-danger login-alert" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                <?= htmlspecialchars($erreur_connexion, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="on">
            <div class="mb-4">
                <label for="email" class="form-label">Adresse e-mail</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="exemple@email.com"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="email"
                    maxlength="254"
                    required
                    autofocus
                >
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Mot de passe</label>

                <div class="password-wrapper">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Saisis ton mot de passe"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword"
                        aria-controls="password"
                        aria-pressed="false"
                    >
                        Afficher
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-login w-100 mb-4">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Se connecter
            </button>

            <div class="text-center">
                <p class="text-secondary mb-2">
                    Tu n'as pas encore de compte ?
                </p>

                <a href="register.php" class="login-link">
                    Créer un compte
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </form>
    </div>
</section>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';

        passwordInput.type = isPassword ? 'text' : 'password';
        togglePassword.textContent = isPassword ? 'Masquer' : 'Afficher';
        togglePassword.setAttribute('aria-pressed', String(isPassword));
    });
</script>

<?php include 'includes/footer.php'; ?>
