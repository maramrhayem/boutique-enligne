<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['client_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../connexion.php');
    exit;
}

// ── Changer le statut ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commande_id'], $_POST['statut'])) {
    $statuts_valides = ['en attente', 'confirmée', 'annulée', 'livrée'];
    $nouveau_statut  = $_POST['statut'];
    $commande_id     = (int)$_POST['commande_id'];

    if (in_array($nouveau_statut, $statuts_valides) && $commande_id > 0) {
        $upd = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
        $upd->execute([$nouveau_statut, $commande_id]);
    }
    header('Location: commandes.php?msg=ok');
    exit;
}

// ── Filtre ────────────────────────────────────────────────────
$filtre = $_GET['filtre'] ?? 'toutes';
$where  = match($filtre) {
    'en attente' => "WHERE c.statut = 'en attente'",
    'confirmée'  => "WHERE c.statut = 'confirmée'",
    'livrée'     => "WHERE c.statut = 'livrée'",
    'annulée'    => "WHERE c.statut = 'annulée'",
    default      => ''
};

// ── Récupérer commandes via la vue corrigée ───────────────────
// Le total est maintenant CALCULÉ depuis details_commande
// Les infos perso viennent de la table livraisons
$commandes = $pdo->query("
    SELECT
        c.id,
        c.date_commande,
        c.statut,
        c.client_id,

        -- Total calculé dynamiquement (plus d'attribut redondant)
        COALESCE(SUM(dc.quantite * dc.prix_unitaire), 0) AS total,
        COUNT(dc.id) AS nb_articles,

        -- Infos de livraison (table séparée, couvre clients ET invités)
        COALESCE(cl.prenom,    l.prenom,    '') AS prenom,
        COALESCE(cl.nom,       l.nom,       '') AS nom,
        COALESCE(cl.email,     l.email,     '') AS email,
        COALESCE(cl.telephone, l.telephone, '') AS telephone,
        COALESCE(l.adresse,    '')              AS adresse,
        COALESCE(l.code_postal,'')              AS code_postal,
        COALESCE(l.ville,      '')              AS ville,
        COALESCE(l.paiement,   'carte')         AS paiement

    FROM commandes c
    LEFT JOIN clients          cl ON cl.id = c.client_id
    LEFT JOIN livraisons        l  ON l.commande_id = c.id
    LEFT JOIN details_commande dc  ON dc.commande_id = c.id
    $where
    GROUP BY
        c.id, c.date_commande, c.statut, c.client_id,
        cl.prenom, cl.nom, cl.email, cl.telephone,
        l.prenom, l.nom, l.email, l.telephone,
        l.adresse, l.code_postal, l.ville, l.paiement
    ORDER BY c.date_commande DESC
")->fetchAll();

// ── Stats (total calculé depuis details_commande) ─────────────
$stats = $pdo->query("
    SELECT
        COUNT(*) FILTER (WHERE c.statut = 'en attente') AS en_attente,
        COUNT(*) FILTER (WHERE c.statut = 'confirmée')  AS confirmees,
        COUNT(*) FILTER (WHERE c.statut = 'livrée')     AS livrees,
        COUNT(*) FILTER (WHERE c.statut = 'annulée')    AS annulees,
        COALESCE(
            SUM(dc.quantite * dc.prix_unitaire)
            FILTER (WHERE c.statut != 'annulée'),
            0
        ) AS ca
    FROM commandes c
    LEFT JOIN details_commande dc ON dc.commande_id = c.id
")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Commandes — Admin Floraison Éternelle</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lato:wght@300;400;700&display=swap');
        body { font-family:'Lato',sans-serif; background:#fdf8f5; }

        .admin-container { display:flex; min-height:100vh; }

        .sidebar {
            width: 280px; background: #3a2a30; color: white;
            padding: 30px 20px; position: fixed; height: 100vh; overflow-y: auto;
        }
        .sidebar h2 {
            font-family:'Playfair Display',serif; font-size:22px;
            margin-bottom:30px; padding-bottom:15px;
            border-bottom:1px solid rgba(255,255,255,0.2); line-height:1.3;
        }
        .sidebar h2 small {
            display:block; font-size:11px; color:#c8748a;
            font-family:'Lato',sans-serif; margin-top:5px;
            letter-spacing:1px; text-transform:uppercase;
        }
        .sidebar nav a {
            display:flex; align-items:center; gap:12px; padding:12px 15px;
            color:rgba(255,255,255,0.7); text-decoration:none;
            border-radius:10px; margin-bottom:8px; transition:all 0.3s;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background:#c8748a; color:white; }
        .sidebar-sep { border:none; border-top:1px solid rgba(255,255,255,0.1); margin:16px 0; }

        .content { flex:1; margin-left:280px; padding:30px; }

        .top-bar {
            background:white; padding:20px 30px; border-radius:16px;
            margin-bottom:24px; display:flex; justify-content:space-between;
            align-items:center; border:1px solid #f0e0e8;
        }
        .top-bar h1 { font-family:'Playfair Display',serif; font-size:26px; color:#3a2a30; }

        .btn-back {
            display:inline-flex; align-items:center; gap:6px;
            background:white; color:#8a6a74; padding:10px 20px;
            border-radius:30px; text-decoration:none; font-weight:700;
            font-size:13px; border:1.5px solid #e0c8d0; transition:all 0.3s;
        }
        .btn-back:hover { border-color:#c8748a; color:#c8748a; }

        .stats-row {
            display:grid; grid-template-columns:repeat(5,1fr);
            gap:14px; margin-bottom:20px;
        }
        .stat-box {
            background:white; border-radius:12px; padding:16px;
            text-align:center; border:1px solid #f0e0e8;
        }
        .stat-num { font-size:26px; font-weight:700; margin-bottom:4px; }
        .stat-lbl { font-size:11px; color:#8a6a74; text-transform:uppercase; letter-spacing:.5px; }
        .c-attente { color:#c8820a; }
        .c-ok      { color:#2d7a2d; }
        .c-livree  { color:#1565c0; }
        .c-annulee { color:#a83050; }
        .c-ca      { color:#c8748a; font-size:18px; }

        .msg-ok {
            background:#e8f5e9; color:#2e7d32; padding:12px 16px;
            border-radius:8px; margin-bottom:18px;
            border-left:3px solid #43a047; font-weight:700;
        }

        .filtres { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
        .f-btn {
            padding:7px 16px; border-radius:20px; text-decoration:none;
            font-size:13px; font-weight:700; border:1.5px solid #f0e0e8;
            color:#8a6a74; background:white; transition:all .2s;
        }
        .f-btn:hover { border-color:#c8748a; color:#c8748a; }
        .f-btn.act-all     { background:#3a2a30; color:white; border-color:#3a2a30; }
        .f-btn.act-attente { background:#c8820a; color:white; border-color:#c8820a; }
        .f-btn.act-ok      { background:#2d7a2d; color:white; border-color:#2d7a2d; }
        .f-btn.act-livree  { background:#1565c0; color:white; border-color:#1565c0; }
        .f-btn.act-annulee { background:#a83050; color:white; border-color:#a83050; }

        .table-wrap { background:white; border-radius:16px; border:1px solid #f0e0e8; overflow:hidden; }
        table { width:100%; border-collapse:collapse; }
        th {
            background:#fef4f7; color:#8a6a74; font-size:11px;
            text-transform:uppercase; letter-spacing:.5px;
            padding:13px 14px; text-align:left;
            border-bottom:1px solid #f0e0e8; font-weight:700;
        }
        td {
            padding:12px 14px; border-bottom:1px solid #f8f0f4;
            font-size:13px; color:#3a2a30; vertical-align:middle;
        }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:#fdf5f7; }

        .badge {
            display:inline-block; padding:4px 10px; border-radius:20px;
            font-size:11px; font-weight:700; white-space:nowrap;
        }
        .b-attente   { background:#fff8ed; color:#b8720a; border:1px solid #f0d49a; }
        .b-confirmee { background:#edf7ed; color:#2d7a2d; border:1px solid #b5deb5; }
        .b-livree    { background:#e3f2fd; color:#1565c0; border:1px solid #90caf9; }
        .b-annulee   { background:#fdf0f3; color:#a83050; border:1px solid #f0b8c8; }

        .bc { display:inline-block; padding:2px 7px; border-radius:10px; font-size:10px; font-weight:700; margin-top:3px; }
        .bc-compte { background:#edf7ed; color:#2d7a2d; }
        .bc-invite { background:#fff8ed; color:#b8720a; }

        .sel-statut {
            padding:6px 10px; border-radius:20px; border:1.5px solid #f0e0e8;
            font-size:12px; background:white; cursor:pointer;
            font-family:inherit; color:#3a2a30; transition:border .2s;
        }
        .sel-statut:focus { outline:none; border-color:#c8748a; }

        .btn-detail {
            display:inline-flex; align-items:center; gap:5px;
            padding:6px 14px; border-radius:20px;
            border:1.5px solid #e8d0da; background:#fdf0f3;
            color:#a83050; font-size:12px; font-weight:600;
            font-family:inherit; cursor:pointer; transition:all 0.25s ease;
        }
        .btn-detail:hover {
            background:#c8748a; color:white; border-color:#c8748a;
            box-shadow:0 3px 10px rgba(200,116,138,0.3); transform:translateY(-1px);
        }

        .detail-row { display:none; background:#fdf5f7; }
        .detail-row.open { display:table-row; }
        .detail-box { padding:16px 20px; display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .det-sec h4 { font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#c8748a; margin-bottom:8px; }
        .det-sec p  { font-size:13px; color:#3a2a30; margin-bottom:3px; }
        .prod-ligne { display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f0e0e8; font-size:13px; }
        .prod-ligne:last-child { border-bottom:none; font-weight:700; color:#c8748a; }
        .vide { text-align:center; padding:60px; color:#8a6a74; }

        /* Badge correction DB */
        .db-badge {
            display:inline-block; background:#edf7ed; color:#2d7a2d;
            border:1px solid #b5deb5; padding:3px 9px; border-radius:10px;
            font-size:10px; font-weight:700; margin-left:8px; vertical-align:middle;
        }
    </style>
</head>
<body>
<div class="admin-container">

    <div class="sidebar">
        <h2>Floraison Éternelle<br><small>Fleurs fraîches</small></h2>
        <nav>
            <a href="dashboard.php">📊 Tableau de bord</a>
            <a href="produits/index.php">🌸 Gestion des produits</a>
            <a href="produits/archives.php">📦 Produits archivés</a>
            <a href="commandes.php" class="active">📋 Commandes clients</a>
            <hr class="sidebar-sep">
            <a href="../index.php">🛍️ Voir le site</a>
            <a href="../logout.php">🚪 Se déconnecter</a>
        </nav>
    </div>

    <div class="content">

        <div class="top-bar">
            <h1>📋 Gestion des commandes</h1>
            <a href="dashboard.php" class="btn-back">← Retour</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="msg-ok">✅ Statut de la commande mis à jour !</div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-num c-attente"><?= $stats['en_attente'] ?></div>
                <div class="stat-lbl">⏳ En attente</div>
            </div>
            <div class="stat-box">
                <div class="stat-num c-ok"><?= $stats['confirmees'] ?></div>
                <div class="stat-lbl">✅ Confirmées</div>
            </div>
            <div class="stat-box">
                <div class="stat-num c-livree"><?= $stats['livrees'] ?></div>
                <div class="stat-lbl">🚚 Livrées</div>
            </div>
            <div class="stat-box">
                <div class="stat-num c-annulee"><?= $stats['annulees'] ?></div>
                <div class="stat-lbl">❌ Annulées</div>
            </div>
            <div class="stat-box">
                <!-- total calculé depuis details_commande, pas stocké en base -->
                <div class="stat-num c-ca"><?= number_format($stats['ca'], 2) ?> DT</div>
                <div class="stat-lbl">💰 CA Total</div>
            </div>
        </div>

        <!-- FILTRES -->
        <div class="filtres">
            <a href="?filtre=toutes"     class="f-btn <?= $filtre==='toutes'     ? 'act-all'     : '' ?>">Toutes</a>
            <a href="?filtre=en+attente" class="f-btn <?= $filtre==='en attente' ? 'act-attente' : '' ?>">⏳ En attente (<?= $stats['en_attente'] ?>)</a>
            <a href="?filtre=confirmée"  class="f-btn <?= $filtre==='confirmée'  ? 'act-ok'      : '' ?>">✅ Confirmées (<?= $stats['confirmees'] ?>)</a>
            <a href="?filtre=livrée"     class="f-btn <?= $filtre==='livrée'     ? 'act-livree'  : '' ?>">🚚 Livrées (<?= $stats['livrees'] ?>)</a>
            <a href="?filtre=annulée"    class="f-btn <?= $filtre==='annulée'    ? 'act-annulee' : '' ?>">❌ Annulées (<?= $stats['annulees'] ?>)</a>
        </div>

        <!-- TABLE -->
        <div class="table-wrap">
        <?php if (empty($commandes)): ?>
            <div class="vide">📭 Aucune commande dans cette catégorie.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Ville</th>
                    <th>Paiement</th>
                    <th>Articles</th>
                    <th>Total <span class="db-badge">calculé</span></th>
                    <th>Statut</th>
                    <th>Modifier</th>
                    <th>Détail</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($commandes as $cmd):
                $s = $cmd['statut'] ?? 'en attente';
                $badge = match($s) {
                    'confirmée' => 'b-confirmee',
                    'livrée'    => 'b-livree',
                    'annulée'   => 'b-annulee',
                    default     => 'b-attente'
                };
                $est_connecte = !empty($cmd['client_id']);

                // Détails produits de la commande
                $det = $pdo->prepare("
                    SELECT dc.quantite, dc.prix_unitaire, p.nom
                    FROM details_commande dc
                    JOIN produits p ON p.id = dc.produit_id
                    WHERE dc.commande_id = ?
                ");
                $det->execute([$cmd['id']]);
                $produits_cmd = $det->fetchAll();

                // Total recalculé depuis les détails (cohérence affichage)
                $total_calcule = array_sum(array_map(
                    fn($p) => $p['quantite'] * $p['prix_unitaire'],
                    $produits_cmd
                ));
            ?>
            <tr>
                <td><strong style="color:#c8748a;">#<?= $cmd['id'] ?></strong></td>
                <td><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></td>
                <td>
                    <?php if (!empty($cmd['prenom']) || !empty($cmd['nom'])): ?>
                        <strong><?= htmlspecialchars(trim($cmd['prenom'].' '.$cmd['nom'])) ?></strong><br>
                        <small style="color:#8a6a74;"><?= htmlspecialchars($cmd['email']) ?></small><br>
                        <span class="bc <?= $est_connecte ? 'bc-compte' : 'bc-invite' ?>">
                            <?= $est_connecte ? '👤 Compte' : '🛒 Invité' ?>
                        </span>
                    <?php else: ?>
                        <span style="color:#8a6a74; font-style:italic;">Non renseigné</span>
                        <span class="bc bc-invite">🛒 Invité</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($cmd['telephone'])): ?>
                        📞 <?= htmlspecialchars($cmd['telephone']) ?>
                    <?php else: ?>
                        <span style="color:#c0a0a8;">—</span>
                    <?php endif; ?>
                </td>
                <td><?= !empty($cmd['ville']) ? htmlspecialchars($cmd['ville']) : '<span style="color:#c0a0a8;">—</span>' ?></td>
                <td>
                    <?php
                    $ico = ['carte'=>'💳','paypal'=>'🅿️','virement'=>'🏦'];
                    echo ($ico[$cmd['paiement']] ?? '💳').' '.ucfirst($cmd['paiement'] ?? '—');
                    ?>
                </td>
                <td style="text-align:center;"><?= $cmd['nb_articles'] ?></td>
                <td>
                    <!-- Total CALCULÉ depuis details_commande, non stocké -->
                    <strong style="color:#c8748a;"><?= number_format($total_calcule, 2) ?> DT</strong>
                </td>
                <td><span class="badge <?= $badge ?>"><?= ucfirst($s) ?></span></td>
                <td>
                    <form method="POST" action="commandes.php">
                        <input type="hidden" name="commande_id" value="<?= $cmd['id'] ?>">
                        <select name="statut" class="sel-statut" onchange="this.form.submit()">
                            <option value="en attente" <?= $s==='en attente' ? 'selected' : '' ?>>⏳ En attente</option>
                            <option value="confirmée"  <?= $s==='confirmée'  ? 'selected' : '' ?>>✅ Confirmée</option>
                            <option value="livrée"     <?= $s==='livrée'     ? 'selected' : '' ?>>🚚 Livrée</option>
                            <option value="annulée"    <?= $s==='annulée'    ? 'selected' : '' ?>>❌ Annulée</option>
                        </select>
                    </form>
                </td>
                <td>
                    <button class="btn-detail" onclick="toggleDetail(<?= $cmd['id'] ?>)">👁 Voir</button>
                </td>
            </tr>

            <tr class="detail-row" id="det-<?= $cmd['id'] ?>">
                <td colspan="11">
                    <div class="detail-box">
                        <div class="det-sec">
                            <h4>📦 Adresse de livraison</h4>
                            <!-- Données issues de la table livraisons -->
                            <p><strong><?= htmlspecialchars(trim($cmd['prenom'].' '.$cmd['nom'])) ?: 'Non renseigné' ?></strong></p>
                            <p><?= htmlspecialchars($cmd['adresse']) ?: '—' ?></p>
                            <p><?= htmlspecialchars(trim($cmd['code_postal'].' '.$cmd['ville'])) ?: '—' ?></p>
                            <p>📞 <?= htmlspecialchars($cmd['telephone']) ?: '—' ?></p>
                            <p>✉️ <?= htmlspecialchars($cmd['email']) ?: '—' ?></p>
                        </div>
                        <div class="det-sec">
                            <h4>🌸 Produits commandés</h4>
                            <?php foreach ($produits_cmd as $pl): ?>
                            <div class="prod-ligne">
                                <span><?= htmlspecialchars($pl['nom']) ?> × <?= $pl['quantite'] ?></span>
                                <span><?= number_format($pl['prix_unitaire'] * $pl['quantite'], 2) ?> DT</span>
                            </div>
                            <?php endforeach; ?>
                            <div class="prod-ligne">
                                <span>TOTAL (calculé)</span>
                                <span><?= number_format($total_calcule, 2) ?> DT</span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        </div>

    </div>
</div>

<script>
function toggleDetail(id) {
    const row = document.getElementById('det-' + id);
    row.classList.toggle('open');
}
</script>
</body>
</html>