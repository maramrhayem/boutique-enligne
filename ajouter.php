<?php
session_start();

$id       = $_POST['id'];
$nom      = $_POST['nom'];
$prix     = $_POST['prix'];
$quantite = (int)$_POST['quantite'];

if (isset($_SESSION['panier'][$id])) {
    $_SESSION['panier'][$id]['quantite'] += $quantite;
} else {
    $_SESSION['panier'][$id] = [
        'nom'      => $nom,
        'prix'     => $prix,
        'quantite' => $quantite
    ];
}

header('Location: index.php');
exit;
?>