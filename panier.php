<?php
session_start();
$total = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Panier — Floraison Éternelle</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Footer toujours en bas ── */
        html { height: 100%; }
        body { min-height: 100vh; display: flex; flex-direction: column; }
        .panier-container { flex: 1; }

        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(58,42,48,0.55);
            backdrop-filter: blur(3px);
            z-index: 999; align-items: center; justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white; border-radius: 16px;
            padding: 36px 40px; width: 100%; max-width: 580px;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 20px 60px rgba(58,42,48,.25);
            animation: slideUp .3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(30px); opacity:0; }
            to   { transform: translateY(0);    opacity:1; }
        }
        .modal h2 {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 22px; color: #3a2a30;
            margin-bottom: 16px;
            border-bottom: 2px solid #f0e0e8; padding-bottom: 12px;
        }
        .modal-section { margin-bottom: 20px; }
        .modal-section h3 {
            font-size: 13px; text-transform: uppercase;
            letter-spacing: 1.5px; color: #c8748a;
            font-weight: 700; margin-bottom: 12px;
        }
        .form-row {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 12px; margin-bottom: 12px;
        }
        .form-row.full { grid-template-columns: 1fr; }
        .field-wrap { position: relative; display: flex; flex-direction: column; }
        .form-input {
            padding: 11px 36px 11px 14px;
            border: 1.5px solid #f0e0e8; border-radius: 8px;
            font-size: 14px; font-family: inherit; color: #3a2a30;
            outline: none; transition: border-color .2s, box-shadow .2s; width: 100%;
        }
        .form-input:focus { border-color: #c8748a; box-shadow: 0 0 0 3px rgba(200,116,138,.1); }
        .form-input::placeholder { color: #c0a0a8; }
        .form-input.valid   { border-color: #4caf50; background: #f9fff9; }
        .form-input.invalid { border-color: #e53935; background: #fff5f5; }
        .field-icon {
            position: absolute; right: 11px; top: 13px;
            font-size: 13px; pointer-events: none;
        }
        .err-msg {
            font-size: 11px; color: #e53935;
            margin-top: 4px; margin-left: 2px;
            display: none; font-weight: 600; line-height: 1.4;
        }
        .err-msg.show { display: block; }

        .progress-bar { height: 5px; background: #f0e0e8; border-radius: 4px; margin-bottom: 6px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg,#c8748a,#e8a0b8); border-radius: 4px; transition: width .4s ease; width: 0%; }
        .progress-label { font-size: 11px; color: #8a6a74; text-align: right; margin-bottom: 16px; }

        .payment-options { display: flex; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
        .pay-option {
            flex: 1; min-width: 120px;
            border: 2px solid #f0e0e8; border-radius: 10px;
            padding: 10px 14px; cursor: pointer; text-align: center;
            font-size: 13px; color: #8a6a74; transition: all .2s; user-select: none;
        }
        .pay-option.selected { border-color: #c8748a; background: #fdf0f4; color: #c8748a; font-weight: 700; }
        .pay-option input[type="radio"] { display: none; }
        .card-fields { display: none; }
        .card-fields.visible { display: block; }
        .virement-info {
            background: #f8f0f4; border-radius: 10px;
            padding: 14px 16px; font-size: 13px;
            color: #5a3a4a; line-height: 1.8;
        }
        .total-ligne {
            background: #fdf0f4; border-radius: 10px;
            padding: 14px 18px; display: flex;
            justify-content: space-between; align-items: center;
            font-size: 15px; color: #3a2a30; margin-bottom: 20px;
        }
        .total-ligne strong { font-size: 20px; color: #c8748a; }
        .modal-btns { display: flex; gap: 12px; }
        .btn-annuler {
            flex: 1; padding: 13px; border: 2px solid #f0e0e8;
            border-radius: 10px; background: white; color: #8a6a74;
            font-size: 14px; font-weight: 700; cursor: pointer; transition: all .2s;
        }
        .btn-annuler:hover { border-color: #c8748a; color: #c8748a; }
        .btn-confirmer {
            flex: 2; padding: 13px; border: none; border-radius: 10px;
            background: #c8748a; color: white; font-size: 14px;
            font-weight: 700; cursor: pointer; transition: background .2s; letter-spacing: .5px;
        }
        .btn-confirmer:hover { background: #a05570; }
    </style>
</head>
<body>

<header>
    <div class="nav-container">
        <a href="index.php" class="logo">
            <svg class="logo-icon" viewBox="0 0 40 40" fill="none">
                <circle cx="20" cy="20" r="6" fill="#c8748a" opacity="0.3"/>
                <circle cx="20" cy="10" r="5" fill="#c8748a" opacity="0.7"/>
                <circle cx="20" cy="30" r="5" fill="#c8748a" opacity="0.7"/>
                <circle cx="10" cy="20" r="5" fill="#c8748a" opacity="0.7"/>
                <circle cx="30" cy="20" r="5" fill="#c8748a" opacity="0.7"/>
                <circle cx="20" cy="20" r="4" fill="#c8748a"/>
            </svg>
            <!-- Modification 2 : Couleurs du logo -->
            <div class="logo-texte" style="color:#3a2a30;">Floraison Éternelle<span style="color:#c8748a;">Fleurs Fraîches</span></div>
        </a>
        <nav><ul>
            <li><a href="index.php">Accueil</a></li>
            <li><a href="boutique.php">Boutique</a></li>
            <!-- Modification 1 : Ajout du lien Occasions -->
            <li><a href="occasion.php">Occasions</a></li>
        </ul></nav>
        <div class="nav-actions">
            <a href="panier.php" class="btn-panier">
                🛒 Mon Panier
                <span class="badge"><?= isset($_SESSION['panier']) ? count($_SESSION['panier']) : 0 ?></span>
            </a>
        </div>
    </div>
</header>

<div class="panier-container">
    <h1>🛒 Mon Panier</h1>

    <?php if (empty($_SESSION['panier'])): ?>
        <div class="panier-vide">
            <p>Votre panier est vide 🌸</p><br>
            <a href="index.php">← Retour à la boutique</a>
        </div>
    <?php else: ?>

    <table>
        <thead>
            <tr>
                <th>Produit</th><th>Prix unitaire</th>
                <th>Quantité</th><th>Sous-total</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($_SESSION['panier'] as $id => $item):
            $sous_total = $item['prix'] * $item['quantite'];
            $total += $sous_total;
        ?>
            <tr>
                <td><strong><?= htmlspecialchars($item['nom']) ?></strong></td>
                <td><?= number_format($item['prix'], 2) ?> DT</td>
                <td>
                    <form action="modifier.php" method="POST" style="display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="number" name="quantite" value="<?= $item['quantite'] ?>" min="1"
                               style="width:60px;padding:5px;border:1px solid #f0e0e8;border-radius:6px;text-align:center;">
                        <button type="submit" class="btn-modifier">OK</button>
                    </form>
                </td>
                <td><strong><?= number_format($sous_total, 2) ?> DT</strong></td>
                <td><a href="supprimer.php?id=<?= $id ?>" class="btn-supprimer">Supprimer</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="panier-total">
        <div><a href="index.php" class="btn-continuer">← Continuer mes achats</a></div>
        <div style="text-align:right;">
            <div style="font-size:14px;color:#8a6a74;margin-bottom:6px;">Total de la commande</div>
            <div class="total-montant"><?= number_format($total, 2) ?> DT</div>
        </div>
        <button onclick="ouvrirModal()" class="btn-valider">Valider la commande →</button>
    </div>

    <?php endif; ?>
</div>

<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <h2>🌸 Livraison &amp; Paiement</h2>

        <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
        <div class="progress-label" id="progressLabel">0 / 8 champs valides</div>

        <form id="commandeForm" action="commande.php" method="POST" novalidate>

            <div class="modal-section">
                <h3>📦 Adresse de Livraison</h3>
                <div class="form-row full">
                    <div class="field-wrap">
                        <input type="text" name="adresse" id="adresse" class="form-input" placeholder="Rue et numéro * (ex: 12 rue des Roses)">
                        <span class="field-icon" id="icon-adresse"></span>
                        <span class="err-msg" id="err-adresse">Adresse obligatoire (min 5 caractères)</span>
                    </div>
                </div>
                <div class="form-row">
                    <div class="field-wrap">
                        <input type="text" name="code_postal" id="code_postal" class="form-input" placeholder="Code Postal *" maxlength="10">
                        <span class="field-icon" id="icon-code_postal"></span>
                        <span class="err-msg" id="err-code_postal">4 à 6 chiffres requis</span>
                    </div>
                    <div class="field-wrap">
                        <input type="text" name="ville" id="ville" class="form-input" placeholder="Ville *">
                        <span class="field-icon" id="icon-ville"></span>
                        <span class="err-msg" id="err-ville">Ville obligatoire (min 2 caractères)</span>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="field-wrap">
                        <input type="tel" name="telephone" id="telephone" class="form-input" placeholder="Téléphone * (ex: 0612345678)">
                        <span class="field-icon" id="icon-telephone"></span>
                        <span class="err-msg" id="err-telephone">8 à 15 chiffres requis</span>
                    </div>
                </div>
            </div>

            <div class="modal-section">
                <h3>💳 Moyen de Paiement</h3>
                <div class="payment-options">
                    <label class="pay-option selected" onclick="selectPay(this,'carte')">
                        <input type="radio" name="paiement" value="carte" checked>💳 Carte Bancaire
                    </label>
                    <label class="pay-option" onclick="selectPay(this,'paypal')">
                        <input type="radio" name="paiement" value="paypal">🅿️ PayPal
                    </label>
                    <label class="pay-option" onclick="selectPay(this,'virement')">
                        <input type="radio" name="paiement" value="virement">🏦 Virement
                    </label>
                </div>

                <div class="card-fields visible" id="carteFields">
                    <div class="form-row full">
                        <div class="field-wrap">
                            <input type="text" name="numero_carte" id="numeroCarte" class="form-input"
                                   placeholder="Numéro de Carte * (1234 5678 9012 3456)" maxlength="19">
                            <span class="field-icon" id="icon-numeroCarte"></span>
                            <span class="err-msg" id="err-numeroCarte">16 chiffres requis</span>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="field-wrap">
                            <input type="text" name="expiry" id="expiryDate" class="form-input" placeholder="MM/AA *" maxlength="5">
                            <span class="field-icon" id="icon-expiryDate"></span>
                            <span class="err-msg" id="err-expiryDate">Date invalide ou expirée</span>
                        </div>
                        <div class="field-wrap">
                            <input type="text" name="cvv" id="cvv" class="form-input" placeholder="CVV * (3 chiffres)" maxlength="3">
                            <span class="field-icon" id="icon-cvv"></span>
                            <span class="err-msg" id="err-cvv">3 chiffres requis</span>
                        </div>
                    </div>
                    <div class="form-row full">
                        <div class="field-wrap">
                            <input type="text" name="nom_carte" id="nomCarte" class="form-input" placeholder="Nom sur la Carte *">
                            <span class="field-icon" id="icon-nomCarte"></span>
                            <span class="err-msg" id="err-nomCarte">Nom obligatoire sur la carte</span>
                        </div>
                    </div>
                </div>

                <div class="card-fields" id="paypalFields">
                    <div class="form-row full">
                        <div class="field-wrap">
                            <input type="email" name="paypal_email" id="paypalEmail" class="form-input" placeholder="Email PayPal *">
                            <span class="field-icon" id="icon-paypalEmail"></span>
                            <span class="err-msg" id="err-paypalEmail">Email PayPal invalide</span>
                        </div>
                    </div>
                </div>

                <div class="card-fields" id="virementFields">
                    <div class="virement-info">
                        🏦 <strong>Coordonnées bancaires :</strong><br>
                        IBAN : FR76 3000 4028 3798 7654 3210 943<br>
                        Référence : votre numéro de commande
                    </div>
                </div>
            </div>

            <div class="total-ligne">
                <span>Total de la commande :</span>
                <strong><?= number_format($total, 2) ?> DT</strong>
            </div>

            <div class="modal-btns">
                <button type="button" class="btn-annuler" onclick="fermerModal()">Annuler</button>
                <button type="submit" class="btn-confirmer">Confirmer la Commande →</button>
            </div>
        </form>
    </div>
</div>

<footer>
    <div class="footer-logo">Floraison Éternelle</div>
    <p>© 2025 Floraison Éternelle</p>
</footer>

<script>
const REGLES = {
    adresse:     { re: /^.{5,100}$/,                      msg: "Min 5 caractères" },
    code_postal: { re: /^\d{4,6}$/,                      msg: "4 à 6 chiffres requis" },
    ville:       { re: /^[a-zA-ZÀ-ÿ\s\-]{2,50}$/,       msg: "Min 2 caractères" },
    telephone:   { re: /^[\+\d\s\-\(\)]{8,15}$/,         msg: "8 à 15 chiffres requis" },
    nomCarte:    { re: /^[a-zA-ZÀ-ÿ\s\-]{2,60}$/,       msg: "Nom obligatoire" },
    paypalEmail: { re: /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/,  msg: "Email PayPal invalide" },
};

const valides = {};

function setEtat(id, etat, errTxt) {
    const inp = document.getElementById(id);
    const ico = document.getElementById('icon-' + id);
    const err = document.getElementById('err-' + id);
    if (!inp) return;
    inp.classList.remove('valid','invalid');
    if (ico) ico.textContent = '';
    if (err) err.classList.remove('show');
    
    if (etat === 'ok') {
        inp.classList.add('valid');
        if (ico) ico.textContent = '✅';
        valides[id] = true;
    } else if (etat === 'err') {
        inp.classList.add('invalid');
        if (ico) ico.textContent = '❌';
        if (err) { err.textContent = errTxt || ''; err.classList.add('show'); }
        valides[id] = false;
    } else {
        valides[id] = false;
    }
    progression();
}

function testerChamp(id, val) {
    if (!REGLES[id]) return true;
    if (val.trim() === '') { setEtat(id, 'vide'); return false; }
    const ok = REGLES[id].re.test(val.trim());
    setEtat(id, ok ? 'ok' : 'err', REGLES[id].msg);
    return ok;
}

function testerCarte(val) {
    const ch = val.replace(/\s/g,'');
    if (ch === '') { setEtat('numeroCarte','vide'); return false; }
    const ok = ch.length === 16 && /^\d+$/.test(ch);
    setEtat('numeroCarte', ok ? 'ok' : 'err', '16 chiffres requis');
    return ok;
}

function testerExpiry(val) {
    if (val === '') { setEtat('expiryDate','vide'); return false; }
    const m = val.match(/^(\d{2})\/(\d{2})$/);
    let ok = false;
    if (m) {
        const mo = parseInt(m[1]), an = parseInt('20'+m[2]);
        const exp = new Date(an, mo-1, 1), now = new Date();
        ok = mo >= 1 && mo <= 12 && exp >= new Date(now.getFullYear(), now.getMonth(), 1);
    }
    setEtat('expiryDate', ok ? 'ok' : 'err', 'Date invalide');
    return ok;
}

function testerCVV(val) {
    if (val === '') { setEtat('cvv','vide'); return false; }
    const ok = /^\d{3}$/.test(val);
    setEtat('cvv', ok ? 'ok' : 'err', '3 chiffres requis');
    return ok;
}

function progression() {
    const pay = document.querySelector('input[name="paiement"]:checked')?.value || 'carte';
    let champs = ['adresse','code_postal','ville','telephone'];
    if (pay === 'carte')   champs.push('numeroCarte','expiryDate','cvv','nomCarte');
    if (pay === 'paypal')  champs.push('paypalEmail');
    
    const nb = champs.filter(c => valides[c] === true).length;
    const pct = Math.round((nb / champs.length) * 100);
    document.getElementById('progressFill').style.width = pct + '%';
    document.getElementById('progressLabel').textContent = nb + ' / ' + champs.length + ' champs valides';
}

['adresse','code_postal','ville','telephone','nomCarte','paypalEmail'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', () => testerChamp(id, el.value));
});

document.getElementById('numeroCarte').addEventListener('input', function() {
    let v = this.value.replace(/\D/g,'').substring(0,16);
    this.value = v.replace(/(.{4})/g,'$1 ').trim();
    testerCarte(this.value);
});

document.getElementById('expiryDate').addEventListener('input', function() {
    let v = this.value.replace(/\D/g,'').substring(0,4);
    if (v.length >= 3) v = v.substring(0,2)+'/'+v.substring(2);
    this.value = v;
    testerExpiry(this.value);
});

document.getElementById('cvv').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g,'').substring(0,3);
    testerCVV(this.value);
});

function ouvrirModal() {
    document.getElementById('modalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    progression();
}
function fermerModal() {
    document.getElementById('modalOverlay').classList.remove('active');
    document.body.style.overflow = '';
}

function selectPay(label, type) {
    document.querySelectorAll('.pay-option').forEach(l => l.classList.remove('selected'));
    label.classList.add('selected');
    label.querySelector('input').checked = true;
    ['carteFields','paypalFields','virementFields'].forEach(id => document.getElementById(id).classList.remove('visible'));
    document.getElementById(type === 'carte' ? 'carteFields' : type === 'paypal' ? 'paypalFields' : 'virementFields').classList.add('visible');
    
    ['numeroCarte','expiryDate','cvv','nomCarte','paypalEmail'].forEach(k => { valides[k] = false; });
    progression();
}

document.getElementById('commandeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const pay = document.querySelector('input[name="paiement"]:checked').value;
    let ok = true;

    ['adresse','code_postal','ville','telephone'].forEach(id => {
        if (!testerChamp(id, document.getElementById(id).value)) ok = false;
    });
    
    if (pay === 'carte') {
        if (!testerCarte(document.getElementById('numeroCarte').value)) ok = false;
        if (!testerExpiry(document.getElementById('expiryDate').value)) ok = false;
        if (!testerCVV(document.getElementById('cvv').value)) ok = false;
        if (!testerChamp('nomCarte', document.getElementById('nomCarte').value)) ok = false;
    }
    if (pay === 'paypal') {
        if (!testerChamp('paypalEmail', document.getElementById('paypalEmail').value)) ok = false;
    }

    if (!ok) {
        const premier = document.querySelector('.form-input.invalid');
        if (premier) premier.scrollIntoView({ behavior:'smooth', block:'center' });
        return;
    }
    this.submit();
});
</script>
</body>
</html>