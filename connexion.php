<?php
session_start();
require 'config/db.php';

// Si déjà connecté → rediriger
if (isset($_SESSION['client_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mdp)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        $client = $stmt->fetch();

        if ($client && password_verify($mdp, $client['mot_de_passe'])) {
            // Connexion réussie
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['prenom']    = $client['prenom'];
            $_SESSION['nom']       = $client['nom'];
            $_SESSION['email']     = $client['email'];
            $_SESSION['role']      = $client['role'];

            // Redirection selon le rôle
            if ($client['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $erreur = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Floraison Éternelle</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=Lato:wght@300;400;700&display=swap');

        html, body { height:100%; }
        body {
            font-family:'Lato',sans-serif;
            background:#fdf8f5; color:#3a2a30;
            display:flex; flex-direction:column; min-height:100vh;
        }

        /* NAVBAR */
        .navbar {
            display:flex; align-items:center;
            justify-content:space-between;
            padding:14px 40px;
            background:white;
            border-bottom:1px solid #f0e0e8;
            position:sticky; top:0; z-index:100;
        }
        .logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .logo svg { width:44px; height:44px; }
        .logo-name {
            font-family:'Playfair Display',serif;
            font-size:18px; color:#3a2a30; line-height:1.2;
        }
        .logo-name small {
            display:block; font-family:'Lato',sans-serif;
            font-size:10px; color:#c8748a; letter-spacing:2px;
            text-transform:uppercase; font-weight:300;
        }
        .nav-links { display:flex; gap:28px; list-style:none; }
        .nav-links a {
            text-decoration:none; color:#8a6a74; font-size:13px;
            letter-spacing:1px; text-transform:uppercase;
            font-weight:400; transition:color .3s;
        }
        .nav-links a:hover { color:#c8748a; }
        .cart-link {
            position:relative; text-decoration:none;
            font-size:22px; color:#8a6a74;
        }
        .cart-count {
            position:absolute; top:-8px; right:-8px;
            background:#c8748a; color:white;
            border-radius:50%; width:18px; height:18px;
            font-size:10px; font-weight:700;
            display:flex; align-items:center; justify-content:center;
        }

        /* PAGE */
        .page-wrap {
            flex:1; display:flex;
            align-items:center; justify-content:center;
            padding:40px 20px;
        }

        /* CARTE DE CONNEXION */
        .login-card {
            background:white; border-radius:20px;
            border:1px solid #f0e0e8;
            box-shadow:0 8px 40px rgba(200,116,138,.12);
            padding:44px 48px;
            width:100%; max-width:440px;
        }
        .login-card h1 {
            font-family:'Playfair Display',serif;
            font-size:22px; color:#3a2a30;
            text-align:center; letter-spacing:1px;
            text-transform:uppercase;
            margin-bottom:8px;
        }
        .login-card .sous-titre {
            text-align:center; font-size:13px;
            color:#8a6a74; margin-bottom:28px;
            font-weight:300;
        }

        /* ERREUR */
        .msg-erreur {
            background:#fde8f0; color:#a05570;
            border:1px solid #f0c0d0;
            border-radius:8px; padding:10px 14px;
            font-size:13px; margin-bottom:18px;
            text-align:center;
        }

        /* CHAMPS */
        .field-wrap { position:relative; margin-bottom:16px; }
        .field-wrap input {
            width:100%; padding:13px 42px 13px 16px;
            border:1.5px solid #f0e0e8; border-radius:10px;
            font-size:14px; font-family:inherit; color:#3a2a30;
            outline:none; transition:border-color .2s, box-shadow .2s;
            background:white;
        }
        .field-wrap input:focus {
            border-color:#c8748a;
            box-shadow:0 0 0 3px rgba(200,116,138,.1);
        }
        .field-wrap input::placeholder { color:#c0a0a8; }
        .field-icon {
            position:absolute; right:14px; top:50%;
            transform:translateY(-50%);
            font-size:16px; cursor:pointer; user-select:none;
        }

        /* BOUTON */
        .btn-principal {
            width:100%; padding:14px;
            background:#c8748a; color:white;
            border:none; border-radius:10px;
            font-size:14px; font-weight:700;
            letter-spacing:1px; text-transform:uppercase;
            cursor:pointer; transition:background .3s;
            margin-top:8px;
        }
        .btn-principal:hover { background:#a05570; }

        /* LIENS BAS */
        .liens-bas {
            text-align:center; margin-top:22px;
            font-size:13px; color:#8a6a74;
            line-height:2;
        }
        .liens-bas a { color:#c8748a; font-weight:700; text-decoration:none; }
        .liens-bas a:hover { text-decoration:underline; }

        /* MESSAGE ADMIN (optionnel) */
        .info-admin {
            text-align:center;
            margin-top:15px;
            padding-top:12px;
            border-top:1px solid #f0e0e8;
            font-size:11px;
            color:#c8748a;
        }

        /* FOOTER */
        .site-footer {
            background:#3a2a30; color:rgba(255,255,255,.6);
            padding:10px 40px; display:flex;
            justify-content:space-between; align-items:center; font-size:12px;
        }
        .footer-brand { font-family:'Playfair Display',serif; font-size:16px; color:#fde8f0; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">
        <svg viewBox="0 0 44 44" fill="none">
            <circle cx="22" cy="22" r="7" fill="#c8748a" opacity=".25"/>
            <circle cx="22" cy="11" r="5" fill="#c8748a" opacity=".7"/>
            <circle cx="22" cy="33" r="5" fill="#c8748a" opacity=".7"/>
            <circle cx="11" cy="22" r="5" fill="#c8748a" opacity=".7"/>
            <circle cx="33" cy="22" r="5" fill="#c8748a" opacity=".7"/>
            <circle cx="22" cy="22" r="4" fill="#c8748a"/>
        </svg>
        <div class="logo-name">
            Floraison Éternelle
            <small>Fleurs Fraîches</small>
        </div>
    </a>
    <ul class="nav-links">
        <li><a href="index.php">Accueil</a></li>
        <li><a href="boutique.php">Boutique</a></li>
        <li><a href="occasion.php">Occasions</a></li>
    </ul>
    <a href="panier.php" class="cart-link">
        🛒
        <span class="cart-count"><?= isset($_SESSION['panier']) ? count($_SESSION['panier']) : 0 ?></span>
    </a>
</nav>

<div class="page-wrap">
    <div class="login-card">
        <h1>Connexion</h1>
        <p class="sous-titre">Connectez-vous à votre compte Floraison Éternelle</p>

        <?php if ($erreur): ?>
            <div class="msg-erreur">❌ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" action="connexion.php">
            <div class="field-wrap">
                <input type="email" name="email"
                       placeholder="Email *"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
                <span class="field-icon">✉️</span>
            </div>

            <div class="field-wrap">
                <input type="password" name="mot_de_passe"
                       id="mdp"
                       placeholder="Mot de passe *"
                       required autocomplete="current-password">
                <span class="field-icon" onclick="toggleMdp('mdp', this)">👁️</span>
            </div>

            <button type="submit" class="btn-principal">Se connecter</button>
        </form>

        <div class="liens-bas">
            Pas encore de compte ? <a href="inscription.php">Créer un compte</a><br>
            <a href="index.php">← Retour à la boutique</a>
        </div>

    </div>
</div>

<footer class="site-footer">
    <div class="footer-brand">Floraison Éternelle</div>
    <p>© 2025 Floraison Éternelle — Tous droits réservés</p>
    <p>contact@floraison-eternelle.tn</p>
</footer>

<script>
function toggleMdp(id, icon) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
    } else {
        input.type = 'password';
        icon.textContent = '👁️';
    }
}
</script>

</body>
</html>