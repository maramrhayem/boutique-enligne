<?php
session_start();

$id       = $_POST['id'];
$quantite = (int)$_POST['quantite'];

if ($quantite > 0) {
    $_SESSION['panier'][$id]['quantite'] = $quantite;
}

header('Location: panier.php');
exit;
?>