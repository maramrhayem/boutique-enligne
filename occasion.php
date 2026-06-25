<?php
session_start();
require 'config/db.php';

$stmt = $pdo->query("
    SELECT p.*,
           COALESCE(STRING_AGG(po.occasion, ' '), '') AS occasions
    FROM public.produits p
    LEFT JOIN public.produit_occasions po ON po.produit_id = p.id
    WHERE p.archive IS NOT TRUE
    GROUP BY p.id
    ORDER BY p.id
");
$produits = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Occasions — Floraison Éternelle</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=Lato:wght@300;400;700&display=swap');
        body { font-family:'Lato',sans-serif; background:#fdf8f5; color:#3a2a30; }

        .navbar { display:flex; align-items:center; justify-content:space-between; padding:14px 40px; background:white; border-bottom:1px solid #f0e0e8; position:sticky; top:0; z-index:100; }
        .logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .logo svg { width:44px; height:44px; }
        .logo-name { font-family:'Playfair Display',serif; font-size:18px; color:#3a2a30; line-height:1.2; }
        .logo-name small { display:block; font-family:'Lato',sans-serif; font-size:10px; color:#c8748a; letter-spacing:2px; text-transform:uppercase; font-weight:300; }
        .nav-links { display:flex; gap:28px; list-style:none; }
        .nav-links a { text-decoration:none; color:#8a6a74; font-size:13px; letter-spacing:1px; text-transform:uppercase; font-weight:400; transition:color .3s; }
        .nav-links a:hover { color:#c8748a; }
        .nav-links a.active { color:#c8748a; border-bottom:2px solid #c8748a; padding-bottom:2px; }
        .nav-right { display:flex; align-items:center; gap:18px; }
        .cart-link { position:relative; text-decoration:none; font-size:22px; color:#8a6a74; }
        .cart-count { position:absolute; top:-8px; right:-8px; background:#c8748a; color:white; border-radius:50%; width:18px; height:18px; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; }

        /* --- BOUTON CONNEXION POUR ACHETER --- */
        .btn-login-req {
            width:100%; padding:9px;
            background: #8a6a74;
            color:white;
            border:none;
            border-radius:8px;
            font-size:12px;
            font-weight:700;
            letter-spacing:.5px;
            text-transform:uppercase;
            cursor:pointer;
            transition:background .3s;
            text-align:center;
            display:inline-block;
            text-decoration:none;
        }
        .btn-login-req:hover {
            background: #6a4a54;
        }

        /* ── HERO : fallback en premier, image par-dessus ── */
        .hero-occasions {
            position:relative; height:280px; overflow:hidden;
            display:flex; align-items:center; justify-content:center;
        }
        .hero-occasions-fallback {
            position:absolute; top:0; left:0; width:100%; height:100%;
            background:linear-gradient(135deg,#c8748a 0%,#8a6a74 100%);
            z-index:0;
        }
        .hero-occasions-bg {
            position:absolute; top:0; left:0;
            width:100%; height:100%;
            object-fit:cover;
            filter:brightness(0.65) saturate(0.9);
            z-index:1;
        }
        .hero-overlay-dark {
            position:absolute; top:0; left:0; width:100%; height:100%;
            background:rgba(40,20,25,0.3);
            z-index:2;
        }
        .hero-occasions-content {
            position:relative; z-index:3;
            text-align:center; color:white; padding:20px;
        }
        .hero-occasions-content h1 {
            font-family:'Playfair Display',serif; font-size:32px;
            font-weight:500; letter-spacing:2px; text-transform:uppercase;
            margin-bottom:10px; text-shadow:0 2px 10px rgba(0,0,0,0.5);
        }
        .hero-occasions-content p {
            font-size:14px; font-weight:300;
            letter-spacing:1px; opacity:0.92;
            text-shadow:0 1px 6px rgba(0,0,0,0.4);
        }

        .categories-wrap { padding:28px 40px 20px; }
        .categories-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
        .cat-btn { background:white; border:1px solid #f0e0e8; border-radius:12px; padding:20px 16px; text-align:center; cursor:pointer; transition:all .3s; }
        .cat-btn:hover, .cat-btn.active { border-color:#c8748a; background:#fde8f0; transform:translateY(-2px); box-shadow:0 6px 20px rgba(200,116,138,.15); }
        .cat-btn svg { width:32px; height:32px; stroke:#c8748a; fill:none; stroke-width:1.5; display:block; margin:0 auto 10px; }
        .cat-btn span { font-size:13px; font-weight:700; color:#3a2a30; letter-spacing:0.5px; text-transform:uppercase; }

        .section-head { text-align:center; padding:30px 20px 12px; }
        .section-head h2 { font-family:'Playfair Display',serif; font-size:26px; color:#3a2a30; margin-bottom:8px; }
        .sep { width:55px; height:2px; background:#c8748a; margin:8px auto 0; }
        .nb-resultats { text-align:center; font-size:13px; color:#8a6a74; margin-bottom:10px; }

        .products-wrap { padding:10px 40px 50px; }
        .products-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:20px; }

        .card { background:white; border-radius:12px; overflow:hidden; border:1px solid #f0e0e8; transition:transform .3s, box-shadow .3s; animation:fadeIn .4s ease; display:flex; flex-direction:column; }
        .card:hover { transform:translateY(-5px); box-shadow:0 12px 30px rgba(200,116,138,.15); }
        @keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

        .card-img { width:100%; height:190px; object-fit:cover; display:block; flex-shrink:0; }
        .card-img-placeholder { width:100%; height:190px; background:linear-gradient(135deg,#fde8f0,#f5e0e8); display:flex; align-items:center; justify-content:center; font-size:50px; flex-shrink:0; }

        .card-body { padding:14px; display:flex; flex-direction:column; flex:1; }
        .card-name { font-family:'Playfair Display',serif; font-size:14px; color:#3a2a30; margin-bottom:4px; line-height:1.4; }
        .card-stars { color:#e8a020; font-size:12px; margin-bottom:6px; }
        .card-price { font-size:16px; font-weight:700; color:#c8748a; margin-bottom:10px; }
        .card form { display:flex; flex-direction:column; gap:7px; margin-top:auto; }
        .qty-row { display:flex; align-items:center; gap:8px; }
        .qty-row label { font-size:11px; color:#8a6a74; text-transform:uppercase; font-weight:700; }
        .qty-row input { width:55px; padding:5px 8px; border:1px solid #f0e0e8; border-radius:6px; font-size:13px; text-align:center; outline:none; }
        .btn-add { width:100%; padding:9px; background:#c8748a; color:white; border:none; border-radius:8px; font-size:12px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; cursor:pointer; transition:background .3s; }
        .btn-add:hover { background:#a05570; }

        .msg-vide { display:none; text-align:center; padding:60px 20px; grid-column:1/-1; }
        .msg-vide .vide-icon { font-size:52px; margin-bottom:14px; }
        .msg-vide p { color:#8a6a74; font-size:15px; }

        .site-footer { background:#3a2a30; color:rgba(255,255,255,.6); padding:12px 40px; display:flex; justify-content:space-between; align-items:center; font-size:12px; }
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
        <li><a href="occasion.php" class="active">Occasions</a></li>
    </ul>
    <div class="nav-right">
        <?php if (isset($_SESSION['client_id'])): ?>
            <div style="position:relative;" id="profilWrap">
                <button class="nav-connect-btn" id="profileBtn" style="display:flex;align-items:center;gap:8px;background:transparent;border:none;color:#5d3b79;cursor:pointer;">
                    <span>👤</span> 
                    <?= htmlspecialchars($_SESSION['prenom']) ?>
                </button>
                <div id="dropdown" style="display:none;position:absolute;top:100%;right:0;background:white;border:1px solid #f0e0e8;border-radius:12px;min-width:180px;z-index:200;box-shadow:0 8px 24px rgba(0,0,0,.1);">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" style="display:block;padding:10px 16px;text-decoration:none;color:#3a2a30;border-bottom:1px solid #f0e0e8;">⚙️ Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" style="display:block;padding:10px 16px;text-decoration:none;color:#3a2a30;">🚪 Se déconnecter</a>
                </div>
            </div>
        <?php else: ?>
            <a href="connexion.php" style="display:flex;align-items:center;gap:8px;text-decoration:none;color:#5d3b79;">
                <span>👤</span> 
                Se connecter
            </a>
        <?php endif; ?>
        <a href="panier.php" class="cart-link">
            🛒
            <span class="cart-count"><?= isset($_SESSION['panier']) ? count($_SESSION['panier']) : 0 ?></span>
        </a>
    </div>
</nav>

<!-- HERO -->
<div class="hero-occasions">
    <div class="hero-occasions-fallback"></div>
    <img src="images/hero-occasions.jpg" alt="Décoration florale" class="hero-occasions-bg">
    <div class="hero-overlay-dark"></div>
    <div class="hero-occasions-content">
        <h1>Célébrez Chaque Instant avec Élégance</h1>
        <p>Nos collections pour toutes vos occasions</p>
    </div>
</div>

<div class="categories-wrap">
    <div class="categories-grid">
        <div class="cat-btn active" onclick="filtrer('mariage', this)">
            <svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="3"/><circle cx="15" cy="7" r="3"/><path d="M12 10c-4 0-7 2-7 5v2h14v-2c0-3-3-5-7-5z"/></svg>
            <span>Mariages</span>
        </div>
        <div class="cat-btn" onclick="filtrer('anniversaire', this)">
            <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/><line x1="12" y1="3" x2="12" y2="5"/></svg>
            <span>Anniversaires</span>
        </div>
        <div class="cat-btn" onclick="filtrer('naissance', this)">
            <svg viewBox="0 0 24 24"><path d="M12 2C8 2 5 5 5 9c0 5 7 13 7 13s7-8 7-13c0-4-3-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
            <span>Naissances</span>
        </div>
        <div class="cat-btn" onclick="filtrer('felicitations', this)">
            <svg viewBox="0 0 24 24"><rect x="3" y="8" width="18" height="13" rx="2"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
            <span>Félicitations</span>
        </div>
    </div>
</div>

<div class="section-head">
    <h2 id="titreSec">Collections Mariages</h2>
    <div class="sep"></div>
</div>
<p class="nb-resultats" id="nbRes"></p>

<div class="products-wrap">
    <div class="products-grid" id="produitsGrid">

        <?php foreach ($produits as $p):
            $occasions_str = trim($p['occasions'] ?? '');
            $img = 'images/' . $p['image'];
        ?>
        <div class="card" data-occasions="<?= htmlspecialchars($occasions_str) ?>">
            <?php if (!empty($p['image']) && file_exists($img)): ?>
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($p['nom']) ?>" class="card-img">
            <?php else: ?>
                <div class="card-img-placeholder">🌸</div>
            <?php endif; ?>
            <div class="card-body">
                <div class="card-name"><?= htmlspecialchars($p['nom']) ?></div>
                <div class="card-stars">★★★★★</div>
                <div class="card-price"><?= number_format($p['prix'], 2) ?> DT</div>

                <?php if (isset($_SESSION['client_id'])): ?>
                    <form action="ajouter.php" method="POST">
                        <input type="hidden" name="id"   value="<?= $p['id'] ?>">
                        <input type="hidden" name="nom"  value="<?= htmlspecialchars($p['nom']) ?>">
                        <input type="hidden" name="prix" value="<?= $p['prix'] ?>">
                        <div class="qty-row">
                            <label>Qté</label>
                            <input type="number" name="quantite" value="1" min="1" max="<?= $p['stock'] ?>">
                        </div>
                        <button type="submit" class="btn-add">🛒 Ajouter au panier</button>
                    </form>
                <?php else: ?>
                    <a href="connexion.php" class="btn-login-req">
                        🔒 Se connecter pour acheter
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="msg-vide" id="msgVide">
            <div class="vide-icon">🌸</div>
            <p>Aucun bouquet disponible dans cette catégorie pour le moment.</p>
        </div>
    </div>
</div>

<footer class="site-footer">
    <div class="footer-brand">Floraison Éternelle</div>
    <p>© 2025 Floraison Éternelle — Tous droits réservés</p>
    <p>floraison-eternelle@gmail.com</p>
</footer>

<script>
// Script pour le menu déroulant profil
document.addEventListener('DOMContentLoaded', function() {
    const profilWrap = document.getElementById('profilWrap');
    const dropdown = document.getElementById('dropdown');
    const profileBtn = document.getElementById('profileBtn');
    
    if (profilWrap && dropdown && profileBtn) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });
        
        document.addEventListener('click', function(e) {
            if (!profilWrap.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }
});

// Script pour les filtres occasions
const titres = {
    mariage:       'Collections Mariages',
    anniversaire:  'Collections Anniversaires',
    naissance:     'Collections Naissances',
    felicitations: 'Collections Félicitations'
};

function filtrer(cat, btn) {
    document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('titreSec').textContent = titres[cat];

    const cartes = document.querySelectorAll('.card[data-occasions]');
    let visible = 0;

    cartes.forEach(card => {
        const liste = card.dataset.occasions.split(' ').filter(Boolean);
        if (liste.includes(cat)) {
            card.style.display = '';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    document.getElementById('msgVide').style.display = visible === 0 ? 'block' : 'none';
    document.getElementById('nbRes').textContent = visible + ' bouquets disponibles';
}

document.addEventListener('DOMContentLoaded', () => {
    filtrer('mariage', document.querySelector('.cat-btn.active'));
});
</script>
</body>
</html>