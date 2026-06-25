<?php
session_start();
error_log("Session client_id: " . ($_SESSION['client_id'] ?? 'non défini'));
error_log("Session role: " . ($_SESSION['role'] ?? 'non défini'));
require '../config/db.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit;
}

$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits WHERE archive = FALSE");
$stats['produits_actifs'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM produits WHERE archive = TRUE");
$stats['produits_archives'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
$stats['clients'] = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Floraison Éternelle</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&family=Lato:wght@300;400;700&display=swap');

        body { font-family: 'Lato', sans-serif; background: #fdf8f5; }

        .admin-container { display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: 280px;
            background: #3a2a30;
            color: white;
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        .sidebar h2 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            line-height: 1.3;
        }
        .sidebar h2 small {
            display: block;
            font-size: 11px;
            color: #c8748a;
            font-family: 'Lato', sans-serif;
            margin-top: 5px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            transition: all 0.3s;
        }
        .sidebar nav a:hover, .sidebar nav a.active {
            background: #c8748a;
            color: white;
        }

        /* CONTENT */
        .content { flex: 1; margin-left: 280px; padding: 30px; }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #f0e0e8;
        }
        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            color: #3a2a30;
        }

        /* Bouton Se déconnecter — pill, même style que btn-back */
        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: white;
            color: #a83050;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            border: 1.5px solid #f0b8c8;
            transition: all 0.3s;
        }
        .btn-logout:hover {
            background: #a83050;
            color: white;
            border-color: #a83050;
            box-shadow: 0 3px 12px rgba(168,48,80,0.25);
            transform: translateY(-1px);
        }

        /* WELCOME */
        .welcome {
            background: white;
            padding: 25px 30px;
            border-radius: 16px;
            border-left: 4px solid #c8748a;
            margin-bottom: 30px;
        }
        .welcome h2 { color: #3a2a30; margin-bottom: 8px; font-family: 'Playfair Display', serif; }
        .welcome p  { color: #8a6a74; }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: white;
            padding: 28px 25px;
            border-radius: 16px;
            text-align: center;
            border: 1px solid #f0e0e8;
            transition: box-shadow 0.3s;
        }
        .stat-card:hover {
            box-shadow: 0 6px 20px rgba(200,116,138,0.12);
        }
        .stat-card h3 {
            font-size: 46px;
            color: #c8748a;
            margin-bottom: 10px;
            font-family: 'Playfair Display', serif;
        }
        .stat-card p {
            color: #8a6a74;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            font-weight: 700;
        }

        /* ACTIONS */
        .actions { display: flex; gap: 14px; flex-wrap: wrap; }

        /* Bouton principal — pill rose dégradé */
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: linear-gradient(135deg, #c8748a, #a85570);
            color: white;
            padding: 12px 26px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 15px rgba(200,116,138,0.35);
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #a85570, #8a3a55);
            box-shadow: 0 6px 20px rgba(200,116,138,0.5);
            transform: translateY(-1px);
        }

        /* Bouton secondaire — pill contour */
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: white;
            color: #c8748a;
            padding: 12px 26px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            border: 1.5px solid #e8c0cc;
            transition: all 0.3s;
        }
        .btn-secondary:hover {
            background: #fdf0f3;
            border-color: #c8748a;
            box-shadow: 0 4px 12px rgba(200,116,138,0.15);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="sidebar">
        <h2>Floraison Éternelle<br><small>Fleurs fraîches</small></h2>
        <nav>
            <a href="dashboard.php" class="active">📊 Tableau de bord</a>
            <a href="produits/index.php">🌸 Gestion des produits</a>
            <a href="produits/archives.php">📦 Produits archivés</a>
            <a href="commandes.php">📋 Commandes clients</a>
            <a href="../index.php">🛍️ Voir le site</a>
            <a href="../logout.php">🚪 Se déconnecter</a>
        </nav>
    </div>

    <div class="content">
        <div class="header">
            <h1>Tableau de bord</h1>
            <a href="../logout.php" class="btn-logout">🚪 Se déconnecter</a>
        </div>

        <div class="welcome">
            <h2>Bonjour, <?= htmlspecialchars($_SESSION['prenom']) ?> ! 👑</h2>
            <p>Bienvenue dans votre espace d'administration. Gérez vos produits, vos commandes et votre boutique.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= $stats['produits_actifs'] ?></h3>
                <p>🌸 Produits actifs</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['produits_archives'] ?></h3>
                <p>📦 Produits archivés</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['clients'] ?></h3>
                <p>👥 Clients inscrits</p>
            </div>
        </div>

        <div class="actions">
            <a href="produits/ajouter.php" class="btn-primary">✦ Ajouter un produit</a>
            <a href="produits/index.php"   class="btn-secondary">📋 Gérer les produits</a>
        </div>
    </div>
</div>
</body>
</html>