<?php
// Activation de l'affichage des erreurs pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrage de la session
session_start();

// Inclusion de la configuration de la base de données
// Assurez-vous que ce fichier existe et configure la connexion $pdo
require 'config/db.php';

// Récupération des 16 premiers produits
try {
    $stmt = $pdo->query("SELECT * FROM public.produits WHERE archive IS NOT TRUE ORDER BY id LIMIT 16");
    $produits = $stmt->fetchAll();
} catch (PDOException $e) {
    // En cas d'erreur de base de données, on initialise un tableau vide
    $produits = [];
    error_log("Erreur DB : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Floraison Éternelle — Fleurs Fraîches</title>
    <style>
        /* RESET & POLICES */
        * { margin:0; padding:0; box-sizing:border-box; }
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;1,400&family=Lato:wght@300;400;700&display=swap');

        body { font-family: 'Lato', sans-serif; background: #fdf8f5; color: #3a2a30; }

        /* --- BARRE DE NAVIGATION (NAVBAR) --- */
        .navbar {
            display: flex; align-items: center;
            justify-content: space-between;
            padding: 14px 40px;
            background: white;
            border-bottom: 1px solid #f0e0e8;
            position: sticky; top: 0; z-index: 100;
        }
        .logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
        .logo svg { width:44px; height:44px; }
        .logo-name {
            font-family: 'Playfair Display', serif;
            font-size: 18px; color: #3a2a30; line-height:1.2;
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
        .nav-links a:hover, .nav-links a.active { color:#c8748a; }
        .nav-links a.active {
            border-bottom: 2px solid #c8748a; padding-bottom:2px;
        }
        
        .nav-right { display:flex; align-items:center; gap:25px; }
        
        /* --- STYLE DU BOUTON MODIFIÉ SELON L'IMAGE --- */
        .nav-connect-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #5d3b79; 
            background: transparent;
            border: none;
            padding: 0;
            font-size: 16px;
            font-weight: 400;
            transition: opacity 0.3s;
            cursor: pointer;
        }
        
        .nav-connect-btn:hover {
            opacity: 0.8;
        }

        .nav-connect-btn .user-icon {
            font-size: 18px;
        }

        .logout-link {
            display:block;
            padding:12px 18px;
            text-decoration:none;
            color: #5d3b79;
            font-size:13px;
            transition: background 0.3s;
        }
        .logout-link:hover {
            background: #fdf8f5;
        }

        /* --- PANIER --- */
        .cart-link {
            position:relative; text-decoration:none;
            font-size:22px;
            color: #8a6a74;
        }
        .cart-count {
            position:absolute; top:-8px; right:-8px;
            background:#c8748a; color:white;
            border-radius:50%; width:18px; height:18px;
            font-size:10px; font-weight:700;
            display:flex; align-items:center; justify-content:center;
        }

        /* --- MENU DÉROULANT PROFIL --- */
        #dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 15px);
            right: 0;
            background: white;
            border: 1px solid #f0e0e8;
            border-radius: 12px;
            min-width: 200px;
            box-shadow: 0 8px 24px rgba(200,116,138,.15);
            z-index: 200;
            overflow: hidden;
        }
        .dropdown-item {
            display:block;
            padding:12px 18px;
            text-decoration:none;
            color:#3a2a30;
            font-size:13px;
            border-bottom:1px solid #f8f0f4;
            transition: background 0.3s;
        }
        .dropdown-item:hover { background: #fdf8f5; }

        /* --- HERO SECTION --- */
        .hero {
            position:relative; overflow:hidden;
            min-height: 250px;
            background: linear-gradient(120deg, #f5dde5 0%, #f5ede8 50%, #e8edf5 100%);
            display:flex; align-items:center;
        }
        .hero-bg-img {
            position:absolute; right:0; top:0;
            width:65%; height:100%;
            object-fit:cover; opacity:0.85;
        }
        .hero-overlay {
            position:absolute; left:0; top:0;
            width:55%; height:100%;
            background: linear-gradient(to right, #f5dde5 60%, transparent);
        }
        .hero-content {
            position:relative; z-index:2;
            padding: 25px 40px;
            max-width: 480px;
        }
        .hero-content h1 {
            font-family:'Playfair Display',serif;
            font-size:32px; color:#3a2a30;
            line-height:1.2; margin-bottom:10px;
        }
        .hero-content h1 em { font-style:italic; color:#5a3a4a; font-weight: 400; }
        .hero-content p {
            font-size:14px; color:#8a6a74;
            font-weight:300; margin-bottom:20px;
            letter-spacing:0.5px;
        }
        .btn-decouvrir {
            display:inline-block;
            background: rgba(90,58,74,0.1);
            color: #3a2a30;
            border: 1px solid #8a6a74;
            padding:10px 24px; border-radius:4px;
            text-decoration:none; font-size:12px;
            font-weight:700; letter-spacing:1px;
            text-transform:uppercase;
            transition: all .3s;
        }
        .btn-decouvrir:hover {
            background:#c8748a; color:white; border-color:#c8748a;
        }

        /* --- SECTION TITRES --- */
        .section-head {
            text-align:center; padding:40px 20px 25px;
        }
        .section-head h2 {
            font-family:'Playfair Display',serif;
            font-size:28px; color:#3a2a30;
            margin-bottom:10px;
        }
        .sep {
            width:60px; height:2px;
            background:#c8748a; margin:10px auto 0;
        }

        /* --- GRILLE PRODUITS --- */
        .products-wrap { padding: 10px 40px 50px; }
        .products-grid {
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(230px,1fr));
            gap:25px;
        }
        .card {
            background:white; border-radius:12px;
            overflow:hidden;
            border:1px solid #f0e0e8;
            transition: transform 0.3s, box-shadow 0.3s;
            display:flex; flex-direction:column;
        }
        .card:hover {
            transform:translateY(-5px);
            box-shadow:0 12px 30px rgba(200,116,138,.1);
        }
        .card-img {
            width:100%; height:220px;
            object-fit:cover; display:block; flex-shrink:0;
        }
        .card-img-placeholder {
            width:100%; height:220px;
            background: linear-gradient(135deg,#fde8f0,#f5e0e8);
            display:flex; align-items:center;
            justify-content:center; font-size:50px; flex-shrink:0; color: #c8748a;
        }
        .card-body { padding:18px; display:flex; flex-direction:column; flex:1; }
        .card-name {
            font-family:'Playfair Display',serif;
            font-size:16px; color:#3a2a30;
            margin-bottom:6px; line-height:1.4; font-weight: 500;
        }
        .card-desc {
            font-size:12px; color:#8a6a74;
            font-weight:300; margin-bottom:10px;
            line-height:1.5; flex:1;
        }
        .card-stars { color:#e8a020; font-size:12px; margin-bottom:10px; }
        .card-price {
            font-size:18px; font-weight:700;
            color:#c8748a; margin-bottom:15px;
        }
        
        /* Formulaires d'achat */
        .card form { display:flex; flex-direction:column; gap:10px; margin-top:auto; }
        .qty-row { display:flex; align-items:center; gap:10px; }
        .qty-row label { font-size:11px; color:#8a6a74; text-transform:uppercase; font-weight:700; letter-spacing: 1px; }
        .qty-row input {
            width:60px; padding:6px 10px;
            border:1px solid #f0e0e8; border-radius:6px;
            font-size:13px; text-align:center; outline:none;
            color: #3a2a30;
        }
        .qty-row input:focus { border-color:#c8748a; box-shadow: 0 0 0 2px rgba(200,116,138,0.1); }
        
        .btn-add {
            width:100%; padding:11px;
            background:#c8748a; color:white;
            border:none; border-radius:8px;
            font-size:12px; font-weight:700;
            letter-spacing:1px; text-transform:uppercase;
            cursor:pointer; transition: background 0.3s;
        }
        .btn-add:hover { background:#a05570; }
        .btn-login-req {
            background: #8a6a74;
        }
        .btn-login-req:hover {
            background: #6a4a54;
        }

        /* --- SECTION POURQUOI --- */
        .why-section {
            background:white; padding:30px 40px 40px;
            border-top:1px solid #f0e0e8;
        }
        .why-grid {
            display:grid; grid-template-columns:repeat(4,1fr);
            gap:20px; margin-top:15px;
        }
        .why-card { text-align:center; padding:10px; }
        .why-icon {
            width:50px; height:50px; border-radius:50%;
            background:#fde8f0;
            display:flex; align-items:center; justify-content:center;
            margin:0 auto 10px; font-size:24px; color: #c8748a;
        }
        .why-card h4 {
            font-family:'Playfair Display',serif;
            font-size:14px; color:#3a2a30; margin-bottom:6px; font-weight: 500;
        }
        .why-card p { font-size:11px; color:#8a6a74; font-weight:300; line-height:1.6; }

        /* --- FOOTER --- */
        .site-footer {
            background:#3a2a30; color:rgba(255,255,255,.5);
            padding:15px 40px;
            display:flex; justify-content:space-between;
            align-items:center; font-size:12px;
        }
        .footer-brand {
            font-family:'Playfair Display',serif;
            font-size:18px; color:#fde8f0;
        }
        .site-footer p { margin: 0 10px; }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">
        <svg viewBox="0 0 44 44" fill="none">
            <circle cx="22" cy="22" r="7"  fill="#c8748a" opacity=".25"/>
            <circle cx="22" cy="11" r="5"  fill="#c8748a" opacity=".7"/>
            <circle cx="22" cy="33" r="5"  fill="#c8748a" opacity=".7"/>
            <circle cx="11" cy="22" r="5"  fill="#c8748a" opacity=".7"/>
            <circle cx="33" cy="22" r="5"  fill="#c8748a" opacity=".7"/>
            <circle cx="22" cy="22" r="4"  fill="#c8748a"/>
        </svg>
        <div class="logo-name">
            Floraison Éternelle
            <small>Fleurs Fraîches</small>
        </div>
    </a>

    <ul class="nav-links">
        <li><a href="index.php" class="active">Accueil</a></li>
        <li><a href="boutique.php">Boutique</a></li>
        <li><a href="occasion.php">Occasions</a></li>
    </ul>

    <div class="nav-right">
        <?php if (isset($_SESSION['client_id'])): ?>
            <div style="position:relative;" id="profilWrap">
                <button class="nav-connect-btn" id="profileBtn">
                    <span class="user-icon">👤</span> 
                    <?= htmlspecialchars($_SESSION['prenom']) ?>
                </button>
                <div id="dropdown">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="dropdown-item">⚙️ Tableau de bord</a>
                    <?php endif; ?>
                    <a href="logout.php" class="dropdown-item logout-link">🚪 Se déconnecter</a>
                </div>
            </div>
        <?php else: ?>
            <a href="connexion.php" class="nav-connect-btn">
                <span class="user-icon">👤</span> 
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

<section class="hero">
   <img src="images/hero-bouquet.jpg" alt="Magnifique bouquet de fleurs fraîches" class="hero-bg-img"
     onerror="this.style.display='none'">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Fleurs Fraîches,<br><em>Livrées Chez Vous</em></h1>
        <p>Livraison express en 24h sur toute la Tunisie. Des compositions uniques pour chaque occasion.</p>
        <a href="#produits" class="btn-decouvrir">Découvrir nos bouquets</a>
    </div>
</section>

<section id="produits">
    <div class="section-head">
        <h2>Nouveautés &amp; Coups de Cœur</h2>
        <div class="sep"></div>
    </div>

    <div class="products-wrap">
        <div class="products-grid">
        <?php if (!empty($produits)): ?>
            <?php foreach ($produits as $p): ?>
                <div class="card">
                    <?php
                    $img_path = 'images/' . $p['image'];
                    if (!empty($p['image']) && file_exists($img_path)):
                    ?>
                        <img src="<?= htmlspecialchars($img_path) ?>"
                             alt="<?= htmlspecialchars($p['nom']) ?>"
                             class="card-img">
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
                                    <label for="qty-<?= $p['id'] ?>">Qté</label>
                                    <input type="number" id="qty-<?= $p['id'] ?>" name="quantite"
                                           value="1" min="1" max="<?= $p['stock'] ?>" required>
                                </div>

                                <button type="submit" class="btn-add">
                                    🛒 Ajouter au panier
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="connexion.php" method="GET">
                                <button type="submit" class="btn-add btn-login-req">
                                    🔒 Se connecter pour acheter
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center; grid-column: 1 / -1; color: #8a6a74; padding: 20px;">Aucun produit n'est disponible pour le moment.</p>
        <?php endif; ?>
        </div>
    </div>
</section>

<section class="why-section">
    <div class="section-head">
        <h2>Pourquoi Choisir Floraison Éternelle ?</h2>
        <div class="sep"></div>
    </div>
    <div class="why-grid">
        <div class="why-card">
            <div class="why-icon">🌿</div>
            <h4>Qualité Premium</h4>
            <p>Fleurs fraîches sélectionnées avec soin chaque matin chez nos producteurs.</p>
        </div>
        <div class="why-card">
            <div class="why-icon">🚚</div>
            <h4>Livraison Rapide</h4>
            <p>Livraison garantie en 24h partout en Tunisie pour une fraîcheur optimale.</p>
        </div>
        <div class="why-card">
            <div class="why-icon">💐</div>
            <h4>Artisan Fleuriste</h4>
            <p>Chaque bouquet est une création unique composée avec amour par nos experts.</p>
        </div>
        <div class="why-card">
            <div class="why-icon">♻️</div>
            <h4>Éco-responsable</h4>
            <p>Emballages 100% recyclables et valorisation des circuits courts locaux.</p>
        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="footer-brand">Floraison Éternelle</div>
    <p>© 2025 Floraison Éternelle — Tous droits réservés</p>
    <p>contact@floraison-eternelle.tn</p>
</footer>

<!-- JavaScript pour la gestion du menu déroulant -->
<script>
    // Attendre que le DOM soit complètement chargé
    document.addEventListener('DOMContentLoaded', function() {
        // Récupération des éléments du menu déroulant (uniquement si l'utilisateur est connecté)
        const profilWrap = document.getElementById('profilWrap');
        const dropdown = document.getElementById('dropdown');
        const profileBtn = document.getElementById('profileBtn');
        
        // Si les éléments existent (utilisateur connecté)
        if (profilWrap && dropdown && profileBtn) {
            
            // Ouvrir/Fermer le menu au clic sur le bouton profil
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation(); // Empêche la propagation du clic
                
                // Vérifier l'état actuel du dropdown
                if (dropdown.style.display === 'block') {
                    dropdown.style.display = 'none';
                } else {
                    dropdown.style.display = 'block';
                }
            });
            
            // Fermer le menu si on clique n'importe où ailleurs sur la page
            document.addEventListener('click', function(e) {
                // Vérifier si le clic est en dehors de #profilWrap
                if (!profilWrap.contains(e.target)) {
                    dropdown.style.display = 'none';
                }
            });
            
            // Optionnel : Fermer le menu quand on clique sur un lien à l'intérieur
            const dropdownLinks = dropdown.querySelectorAll('a');
            dropdownLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    // Le menu se ferme avant la navigation (optionnel)
                    // dropdown.style.display = 'none';
                    // La page va se recharger de toute façon à cause du lien
                });
            });
        }
    });
</script>

</body>
</html>