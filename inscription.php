<?php
session_start();
require 'config/db.php';

// Si déjà connecté → rediriger
if (isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit;
}

$erreur  = '';
$succes  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom    = trim($_POST['prenom']    ?? '');
    $nom       = trim($_POST['nom']       ?? '');
    $email     = trim($_POST['email']     ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $mdp       = $_POST['mot_de_passe']         ?? '';
    $mdp_conf  = $_POST['mot_de_passe_confirm'] ?? '';

    // Validations
    if (empty($prenom) || empty($nom) || empty($email) || empty($mdp)) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';
    } elseif (strlen($mdp) < 6) {
        $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($mdp !== $mdp_conf) {
        $erreur = 'Les mots de passe ne correspondent pas.';
    } else {
        // Vérifier si email déjà utilisé
        $chk = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $erreur = 'Cette adresse email est déjà utilisée.';
        } else {
            // Insérer le nouveau client
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare("
                INSERT INTO clients (prenom, nom, email, telephone, mot_de_passe, role)
                VALUES (?, ?, ?, ?, ?, 'client')
            ");
            $ins->execute([$prenom, $nom, $email, $telephone, $hash]);
            $new_id = $pdo->lastInsertId();

            // Connecter automatiquement
            $_SESSION['client_id'] = $new_id;
            $_SESSION['prenom']    = $prenom;
            $_SESSION['nom']       = $nom;
            $_SESSION['email']     = $email;
            $_SESSION['role']      = 'client';

            // Rediriger vers l'accueil avec message de bienvenue
            header('Location: index.php?bienvenue=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription — Floraison Éternelle</title>
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

        /* NAV */
        .navbar { display:flex; align-items:center; justify-content:space-between; padding:14px 40px; background:white; border-bottom:1px solid #f0e0e8; }
        .logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .logo svg { width:44px; height:44px; }
        .logo-name { font-family:'Playfair Display',serif; font-size:18px; color:#3a2a30; line-height:1.2; }
        .logo-name small { display:block; font-size:10px; color:#c8748a; letter-spacing:2px; text-transform:uppercase; font-weight:300; }
        .nav-links { display:flex; gap:28px; list-style:none; }
        .nav-links a { text-decoration:none; color:#8a6a74; font-size:13px; letter-spacing:1px; text-transform:uppercase; font-weight:400; transition:color .3s; }
        .nav-links a:hover { color:#c8748a; }
        .cart-link { position:relative; text-decoration:none; font-size:22px; }
        .cart-count { position:absolute; top:-8px; right:-8px; background:#c8748a; color:white; border-radius:50%; width:18px; height:18px; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; }

        /* PAGE */
        .page-wrap {
            flex:1; display:flex;
            align-items:center; justify-content:center;
            padding:40px 20px;
        }

        /* CARTE */
        .register-card {
            background:white; border-radius:20px;
            border:1px solid #f0e0e8;
            box-shadow:0 8px 40px rgba(200,116,138,.12);
            padding:44px 48px;
            width:100%; max-width:520px;
        }
        .register-card h1 {
            font-family:'Playfair Display',serif;
            font-size:22px; color:#3a2a30;
            text-align:center; letter-spacing:1px;
            text-transform:uppercase;
            margin-bottom:8px;
        }
        .register-card .sous-titre {
            text-align:center; font-size:13px;
            color:#8a6a74; margin-bottom:28px;
            font-weight:300;
        }
        .sep-rose {
            width:50px; height:2px;
            background:#c8748a; margin:0 auto 28px;
            border-radius:2px;
        }

        /* ERREUR */
        .msg-erreur {
            background:#fde8f0; color:#a05570;
            border:1px solid #f0c0d0;
            border-radius:8px; padding:10px 14px;
            font-size:13px; margin-bottom:18px;
            text-align:center;
        }

        /* SECTION TITRE */
        .section-label {
            font-size:10px; font-weight:700;
            color:#c8748a; text-transform:uppercase;
            letter-spacing:2px; margin-bottom:12px;
            margin-top:20px; display:flex;
            align-items:center; gap:8px;
        }
        .section-label::after {
            content:''; flex:1; height:1px; background:#f0e0e8;
        }

        /* LIGNE 2 COLONNES */
        .row-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

        /* CHAMPS */
        .field-wrap { position:relative; margin-bottom:14px; }
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
        .field-wrap input.optionnel { background:#fdf8f5; }
        .field-icon {
            position:absolute; right:14px; top:50%;
            transform:translateY(-50%);
            font-size:16px; cursor:pointer; user-select:none;
        }

        /* INDICATEUR FORCE MOT DE PASSE */
        .pwd-strength {
            margin-top:6px; height:4px;
            background:#f0e0e8; border-radius:2px; overflow:hidden;
        }
        .pwd-strength-bar {
            height:100%; width:0; border-radius:2px;
            transition:width .3s, background .3s;
        }
        .pwd-hint {
            font-size:11px; color:#b0909a; margin-top:4px;
        }

        /* BOUTON */
        .btn-principal {
            width:100%; padding:14px;
            background:#c8748a; color:white;
            border:none; border-radius:10px;
            font-size:14px; font-weight:700;
            letter-spacing:1px; text-transform:uppercase;
            cursor:pointer; transition:background .3s;
            margin-top:12px;
        }
        .btn-principal:hover { background:#a05570; }

        /* SÉPARATEUR */
        .ou-sep {
            display:flex; align-items:center; gap:12px;
            margin:18px 0;
        }
        .ou-sep::before, .ou-sep::after { content:''; flex:1; height:1px; background:#f0e0e8; }
        .ou-sep span { font-size:12px; color:#b0909a; }

        /* CONTINUER SANS COMPTE */
        .btn-invite {
            width:100%; padding:12px;
            background:transparent; color:#8a6a74;
            border:1.5px dashed #d0b0b8;
            border-radius:10px; font-size:13px;
            font-weight:400; cursor:pointer;
            font-family:inherit; transition:all .2s;
            text-align:center;
        }
        .btn-invite:hover {
            border-color:#c8748a; color:#c8748a;
            background:#fdf0f4;
        }

        /* LIENS BAS */
        .liens-bas {
            text-align:center; margin-top:20px;
            font-size:13px; color:#8a6a74;
            line-height:2;
        }
        .liens-bas a { color:#c8748a; font-weight:700; text-decoration:none; }
        .liens-bas a:hover { text-decoration:underline; }

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
        <div class="logo-name">Floraison Éternelle<small>Fleurs Fraîches</small></div>
    </a>
    <ul class="nav-links">
        <li><a href="index.php">Accueil</a></li>
        <li><a href="boutique.php">Boutique</a></li>
        <li><a href="occasion.php">Occasions</a></li>
    </ul>
    <a href="panier.php" class="cart-link">🛒
        <span class="cart-count"><?= isset($_SESSION['panier']) ? count($_SESSION['panier']) : 0 ?></span>
    </a>
</nav>

<div class="page-wrap">
    <div class="register-card">
        <h1>Inscription Nouveau Client</h1>
        <p class="sous-titre">Créez votre compte pour commander facilement 🌸</p>
        <div class="sep-rose"></div>

        <?php if ($erreur): ?>
            <div class="msg-erreur">❌ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" action="inscription.php" id="formInscription">

            <!-- VOS INFORMATIONS -->
            <div class="section-label">👤 Vos informations</div>
            <div class="row-2">
                <div class="field-wrap">
                    <input type="text" name="prenom"
                           placeholder="Prénom *"
                           value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                           required autocomplete="given-name">
                </div>
                <div class="field-wrap">
                    <input type="text" name="nom"
                           placeholder="Nom *"
                           value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                           required autocomplete="family-name">
                </div>
            </div>

            <div class="field-wrap">
                <input type="email" name="email"
                       placeholder="Email * (ex: nom@mail.com)"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required autocomplete="email">
                <span class="field-icon">✉️</span>
            </div>

            <div class="field-wrap">
                <input type="tel" name="telephone"
                       placeholder="Numéro de téléphone (optionnel)"
                       class="optionnel"
                       value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>"
                       autocomplete="tel">
                <span class="field-icon">📱</span>
            </div>

            <!-- MOT DE PASSE -->
            <div class="section-label">🔒 Sécurité</div>

            <div class="field-wrap">
                <input type="password" name="mot_de_passe"
                       id="mdp1"
                       placeholder="Mot de Passe * (min. 6 caractères)"
                       required autocomplete="new-password"
                       oninput="evaluerForce(this.value)">
                <span class="field-icon" onclick="toggleMdp('mdp1', this)">🌸</span>
            </div>
            <div class="pwd-strength"><div class="pwd-strength-bar" id="pwdBar"></div></div>
            <p class="pwd-hint" id="pwdHint"></p>

            <div class="field-wrap" style="margin-top:10px;">
                <input type="password" name="mot_de_passe_confirm"
                       id="mdp2"
                       placeholder="Confirmer Mot de Passe *"
                       required autocomplete="new-password">
                <span class="field-icon" onclick="toggleMdp('mdp2', this)">🌸</span>
            </div>

            <button type="submit" class="btn-principal">
                Créer mon compte →
            </button>
        </form>

        <!-- SÉPARATEUR -->
        <div class="ou-sep"><span>ou</span></div>



        <div class="liens-bas">
            Déjà un compte ? <a href="connexion.php">Connectez-vous</a><br>
            <a href="index.php">← Retour à la boutique</a>
        </div>
    </div>
</div>

<footer class="site-footer">
    <div class="footer-brand">Floraison Éternelle</div>
    <p>© 2025 Floraison Éternelle — Tous droits réservés</p>
    <p>floraison-eternelle@gmail.com</p>
</footer>

<script>
function toggleMdp(id, icon) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        icon.textContent = '🙈';
    } else {
        input.type = 'password';
        icon.textContent = '🌸';
    }
}

function evaluerForce(mdp) {
    const bar  = document.getElementById('pwdBar');
    const hint = document.getElementById('pwdHint');
    let score  = 0;

    if (mdp.length >= 6)  score++;
    if (mdp.length >= 10) score++;
    if (/[A-Z]/.test(mdp)) score++;
    if (/[0-9]/.test(mdp)) score++;
    if (/[^A-Za-z0-9]/.test(mdp)) score++;

    const niveaux = [
        { pct:'0%',   color:'#f0e0e8', label:'' },
        { pct:'20%',  color:'#e53935', label:'Très faible' },
        { pct:'40%',  color:'#ff7043', label:'Faible' },
        { pct:'60%',  color:'#ffa726', label:'Moyen' },
        { pct:'80%',  color:'#66bb6a', label:'Bon' },
        { pct:'100%', color:'#43a047', label:'Excellent 🌸' },
    ];

    const n = niveaux[score];
    bar.style.width    = n.pct;
    bar.style.background = n.color;
    hint.textContent   = n.label;
    hint.style.color   = n.color;
}
</script>

</body>
</html>