<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require 'config/db.php';

// ── Lecture des produits + couleurs depuis la table produit_couleurs ──
$stmt = $pdo->query("
    SELECT p.*,
           COALESCE(STRING_AGG(DISTINCT pc.couleur, ' '), '') AS couleurs,
           COALESCE(STRING_AGG(DISTINCT pt.type, ' '), '') AS types
    FROM public.produits p
    LEFT JOIN public.produit_couleurs pc ON pc.produit_id = p.id
    LEFT JOIN public.produit_types pt ON pt.produit_id = p.id
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
    <title>Boutique — Floraison Éternelle</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=Lato:wght@300;400;700&display=swap');

        body { font-family:'Lato',sans-serif; background:#fdf8f5; color:#3a2a30; }

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
        .logo-name { font-family:'Playfair Display',serif; font-size:18px; color:#3a2a30; line-height:1.2; }
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
        .nav-links a.active { color:#c8748a; border-bottom:2px solid #c8748a; padding-bottom:2px; }
        .nav-right { display:flex; align-items:center; gap:18px; }
        .nav-right span { font-size:14px; color:#8a6a74; cursor:pointer; }
        .cart-link { position:relative; text-decoration:none; font-size:22px; color:#8a6a74; }
        .cart-count {
            position:absolute; top:-8px; right:-8px;
            background:#c8748a; color:white;
            border-radius:50%; width:18px; height:18px;
            font-size:10px; font-weight:700;
            display:flex; align-items:center; justify-content:center;
        }

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

        .page-title {
            text-align:center;
            padding:30px 20px 20px;
            font-family:'Playfair Display',serif;
            font-size:32px; color:#3a2a30;
        }
        .page-title::after {
            content:''; display:block;
            width:55px; height:2px;
            background:#c8748a; margin:10px auto 0;
        }

        .boutique-layout {
            display:flex; gap:24px;
            padding:20px 32px 50px;
            align-items:flex-start;
        }

        .filtres {
            width:240px; flex-shrink:0;
            background:white; border-radius:14px;
            border:1px solid #f0e0e8;
            padding:22px; position:sticky; top:80px;
        }
        .filtres h3 {
            font-family:'Playfair Display',serif;
            font-size:18px; color:#3a2a30; margin-bottom:20px;
        }

        .filtre-section { margin-bottom:22px; }
        .filtre-section > label {
            font-size:13px; font-weight:700;
            color:#3a2a30; letter-spacing:0.5px;
            display:block; margin-bottom:12px;
        }
        .prix-range { width:100%; accent-color:#c8748a; cursor:pointer; }
        .prix-inputs {
            display:flex; gap:8px;
            margin-top:10px; align-items:center;
        }
        .prix-inputs input {
            width:85px; padding:6px 8px;
            border:1px solid #f0e0e8;
            border-radius:8px; font-size:13px;
            text-align:center; color:#3a2a30; outline:none;
        }
        .prix-inputs input:focus { border-color:#c8748a; }
        .prix-inputs span { color:#8a6a74; font-size:12px; }

        .couleurs-grid { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
        .couleur-btn {
            width:30px; height:30px;
            border-radius:50%; border:2px solid transparent;
            cursor:pointer; transition:transform .2s, border-color .2s;
            position:relative; display:flex;
            align-items:center; justify-content:center;
        }
        .couleur-btn:hover { transform:scale(1.15); }
        .couleur-btn.selected { border-color:#3a2a30; transform:scale(1.15); }
        .couleur-btn.selected::after {
            content:'✓';
            font-size:12px; font-weight:700; color:#3a2a30;
        }
        .couleur-btn[data-couleur="blanc"].selected::after,
        .couleur-btn[data-couleur="all"].selected::after { color:#aaa; }

        .type-list { margin-top:8px; }
        .type-list label {
            display:flex; align-items:center;
            gap:8px; font-size:13px;
            color:#8a6a74; cursor:pointer;
            padding:5px 0; font-weight:400;
            letter-spacing:0; transition:color .2s;
        }
        .type-list label:hover { color:#c8748a; }
        .type-list input[type="checkbox"] {
            accent-color:#c8748a;
            width:15px; height:15px; cursor:pointer;
        }

        .btn-reset {
            width:100%; padding:9px;
            background:transparent;
            border:1px solid #c8748a;
            color:#c8748a; border-radius:8px;
            font-size:12px; font-weight:700;
            letter-spacing:.5px; text-transform:uppercase;
            cursor:pointer; margin-top:16px; transition:all .3s;
        }
        .btn-reset:hover { background:#c8748a; color:white; }

        .products-zone { flex:1; }
        .products-count { font-size:13px; color:#8a6a74; margin-bottom:16px; font-weight:300; }

        .products-grid {
            display:grid;
            grid-template-columns:repeat(auto-fill, minmax(210px,1fr));
            gap:18px;
        }
        .card {
            background:white; border-radius:12px;
            overflow:hidden; border:1px solid #f0e0e8;
            transition:transform .3s, box-shadow .3s;
            display:flex; flex-direction:column;
        }
        .card:hover {
            transform:translateY(-5px);
            box-shadow:0 12px 30px rgba(200,116,138,.15);
        }
        .card.hidden { display:none !important; }

        .card-img { width:100%; height:190px; object-fit:cover; display:block; flex-shrink:0; }
        .card-img-placeholder {
            width:100%; height:190px;
            background:linear-gradient(135deg,#fde8f0,#f5e0e8);
            display:flex; align-items:center;
            justify-content:center; font-size:50px; flex-shrink:0;
        }
        .card-body { padding:14px; display:flex; flex-direction:column; flex:1; }
        .card-name {
            font-family:'Playfair Display',serif;
            font-size:14px; color:#3a2a30;
            margin-bottom:4px; line-height:1.4;
        }
        .card-desc { font-size:11px; color:#8a6a74; font-weight:300; margin-bottom:7px; line-height:1.5; flex:1; }
        .card-stars { color:#e8a020; font-size:12px; margin-bottom:7px; }
        .card-price { font-size:16px; font-weight:700; color:#c8748a; margin-bottom:10px; }
        .card form { display:flex; flex-direction:column; gap:7px; margin-top:auto; }
        .qty-row { display:flex; align-items:center; gap:8px; }
        .qty-row label { font-size:11px; color:#8a6a74; text-transform:uppercase; font-weight:700; }
        .qty-row input {
            width:55px; padding:5px 8px;
            border:1px solid #f0e0e8; border-radius:6px;
            font-size:13px; text-align:center; outline:none;
        }
        .qty-row input:focus { border-color:#c8748a; }
        .btn-add {
            width:100%; padding:9px;
            background:#c8748a; color:white;
            border:none; border-radius:8px;
            font-size:12px; font-weight:700;
            letter-spacing:.5px; text-transform:uppercase;
            cursor:pointer; transition:background .3s;
        }
        .btn-add:hover { background:#a05570; }

        .no-result {
            text-align:center; padding:60px 20px;
            color:#8a6a74; font-size:15px;
            display:none; grid-column:1/-1;
            font-family:'Playfair Display',serif;
        }

        .site-footer {
            background:#3a2a30; color:rgba(255,255,255,.6);
            padding:10px 40px;
            display:flex; justify-content:space-between;
            align-items:center; font-size:12px;
        }
        .footer-brand { font-family:'Playfair Display',serif; font-size:16px; color:#fde8f0; }
    </style>
</head>
<body>

<!-- NAVBAR -->
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
        <li><a href="boutique.php" class="active">Boutique</a></li>
        <li><a href="occasion.php">Occasions</a></li>
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
            <span class="cart-count">
                <?= isset($_SESSION['panier']) ? count($_SESSION['panier']) : 0 ?>
            </span>
        </a>
    </div>
</nav>

<!-- TITRE -->
<div class="page-title">Notre Boutique</div>

<!-- LAYOUT -->
<div class="boutique-layout">

    <!-- FILTRES -->
    <aside class="filtres">
        <h3>Filtrer vos Bouquets</h3>

        <!-- Prix -->
        <div class="filtre-section">
            <label>Prix (DT)</label>
            <input type="range" class="prix-range" id="prixRange" min="0" max="100" value="100">
            <div class="prix-inputs">
                <input type="number" id="prixMin" value="0" min="0" max="100">
                <span>→</span>
                <input type="number" id="prixMax" value="100" min="0" max="100">
            </div>
        </div>

        <!-- Couleurs -->
        <div class="filtre-section">
            <label>Couleurs</label>
            <div class="couleurs-grid">
                <div class="couleur-btn selected"
                     style="background:#f8f8f8; border:2px solid #ddd;"
                     data-couleur="all" title="Toutes"></div>

                <div class="couleur-btn"
                     style="background:#ffffff; border:2px solid #e0e0e0;"
                     data-couleur="blanc" title="Blanc"></div>

                <div class="couleur-btn"
                     style="background:#ffc0cb;"
                     data-couleur="rose" title="Rose"></div>

                <div class="couleur-btn"
                     style="background:#e60000;"
                     data-couleur="rouge" title="Rouge"></div>

                <div class="couleur-btn"
                     style="background:#b3d4ff;"
                     data-couleur="bleu" title="Bleu"></div>

                <div class="couleur-btn"
                     style="background:#d4b3ff;"
                     data-couleur="violet" title="Violet"></div>

                <div class="couleur-btn"
                     style="background:#fff4b3;"
                     data-couleur="jaune" title="Jaune"></div>
            </div>
        </div>

        <!-- Type -->
        <div class="filtre-section">
            <label>Bouquet Type</label>
            <div class="type-list">
               <label><input type="checkbox" value="roses"> Roses</label>
                <label><input type="checkbox" value="tournesols"> Tournesols</label>
                <label><input type="checkbox" value="sauvages"> Fleurs Sauvages</label>
                <label><input type="checkbox" value="tulipes"> Tulipes</label>
                <label><input type="checkbox" value="box"> Box</label>
            </div>
        </div>

        <button class="btn-reset" onclick="resetFiltres()">
            Réinitialiser les filtres
        </button>
    </aside>

    <!-- PRODUITS -->
    <div class="products-zone">
        <div class="products-count" id="prodCount">
            <?= count($produits) ?> bouquets disponibles
        </div>

        <div class="products-grid" id="produitsGrid">

        <?php foreach ($produits as $p):
            $couleurs_str = trim($p['couleurs'] ?? '');
            $types_str    = trim($p['types'] ?? '');
        ?>
            <div class="card"
                 data-prix="<?= $p['prix'] ?>"
                 data-couleurs="<?= htmlspecialchars($couleurs_str) ?>"
                 data-types="<?= htmlspecialchars($types_str) ?>">
                <?php
                $img = 'images/' . $p['image'];
                if (!empty($p['image']) && file_exists($img)):
                ?>
                    <img src="<?= $img ?>" alt="<?= htmlspecialchars($p['nom']) ?>" class="card-img">
                <?php else: ?>
                    <div class="card-img-placeholder">🌸</div>
                <?php endif; ?>

                <div class="card-body">
                    <div class="card-name"><?= htmlspecialchars($p['nom']) ?></div>
                    <div class="card-desc"><?= htmlspecialchars($p['description']) ?></div>
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

            <div class="no-result" id="noResult">
                🌸 Aucun bouquet ne correspond à vos filtres.
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
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

// Script pour les filtres
const range   = document.getElementById('prixRange');
const prixMin = document.getElementById('prixMin');
const prixMax = document.getElementById('prixMax');
let couleurActive = 'all';

function appliquerFiltres() {
    const min = parseFloat(prixMin.value) || 0;
    const max = parseFloat(prixMax.value) || 9999;

    const typesCochés = Array.from(
        document.querySelectorAll('.type-list input:checked')
    ).map(cb => cb.value);

    const cards = document.querySelectorAll('.card[data-prix]');
    let visible = 0;

    cards.forEach(card => {
        const prix = parseFloat(card.dataset.prix);
        const types = card.dataset.types.split(' ').filter(Boolean);
        const couleurs = card.dataset.couleurs.split(' ').filter(Boolean);
        const okCouleur = couleurActive === 'all' || couleurs.includes(couleurActive);
        const okPrix = prix >= min && prix <= max;
        const okType = typesCochés.length === 0 || typesCochés.some(t => types.includes(t));

        if (okPrix && okCouleur && okType) {
            card.classList.remove('hidden');
            visible++;
        } else {
            card.classList.add('hidden');
        }
    });

    document.getElementById('prodCount').textContent = visible + ' bouquets disponibles';
    document.getElementById('noResult').style.display = visible === 0 ? 'block' : 'none';
}

range.addEventListener('input', () => {
    prixMax.value = range.value;
    appliquerFiltres();
});
prixMin.addEventListener('input', appliquerFiltres);
prixMax.addEventListener('input', () => {
    range.value = prixMax.value;
    appliquerFiltres();
});

document.querySelectorAll('.couleur-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.couleur-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        couleurActive = btn.dataset.couleur;
        appliquerFiltres();
    });
});

document.querySelectorAll('.type-list input').forEach(cb => {
    cb.addEventListener('change', appliquerFiltres);
});

function resetFiltres() {
    prixMin.value = 0;
    prixMax.value = 100;
    range.value   = 100;
    couleurActive = 'all';
    document.querySelectorAll('.type-list input').forEach(cb => cb.checked = false);
    document.querySelectorAll('.couleur-btn').forEach(b => b.classList.remove('selected'));
    document.querySelector('[data-couleur="all"]').classList.add('selected');
    appliquerFiltres();
}
</script>

</body>
</html>