<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=trading;charset=utf8", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$userId = $_SESSION['user_id'];
$actionId = $_GET['id'] ?? null;
if (!$actionId) {
    header("Location: dashboard.php");
    exit();
}

// TRAITEMENT ACHAT / VENTE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantite = intval($_POST['quantite']);
    $quantite = max(1, $quantite);
    $isAchat = isset($_POST['acheter']);
    $isVente = isset($_POST['vendre']);

    $stmt = $pdo->prepare("SELECT valeur FROM actions WHERE id_actions = ?");
    $stmt->execute([$actionId]);
    $valeurAction = $stmt->fetchColumn();

    if (!$valeurAction) {
        $erreur = "Action introuvable.";
    } else {
        $montant = $quantite * $valeurAction;
        $stmt = $pdo->prepare("SELECT compte, valeur_en_actions FROM portefeuille WHERE id = ?");
        $stmt->execute([$userId]);
        $portefeuille = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($isAchat) {
            if ($portefeuille['compte'] >= $montant) {
                $pdo->prepare("UPDATE portefeuille SET compte = compte - ?, valeur_en_actions = valeur_en_actions + ? WHERE id = ?")
                    ->execute([$montant, $montant, $userId]);

                $stmt = $pdo->prepare("SELECT nombre FROM action_utilisateur WHERE id_utilisateur = ? AND id_actions = ?");
                $stmt->execute([$userId, $actionId]);
                $existant = $stmt->fetchColumn();

                if ($existant !== false) {
                    $pdo->prepare("UPDATE action_utilisateur SET nombre = nombre + ? WHERE id_utilisateur = ? AND id_actions = ?")
                        ->execute([$quantite, $userId, $actionId]);
                } else {
                    $pdo->prepare("INSERT INTO action_utilisateur (id_utilisateur, id_actions, nombre) VALUES (?, ?, ?)")
                        ->execute([$userId, $actionId, $quantite]);
                }

                $success = "Achat effectué avec succès.";
            } else {
                $erreur = "Fonds insuffisants pour cet achat.";
            }
        }

        if ($isVente) {
            $stmt = $pdo->prepare("SELECT nombre FROM action_utilisateur WHERE id_utilisateur = ? AND id_actions = ?");
            $stmt->execute([$userId, $actionId]);
            $possede = $stmt->fetchColumn();

            if ($possede >= $quantite) {
                $pdo->prepare("UPDATE portefeuille SET compte = compte + ?, valeur_en_actions = valeur_en_actions - ? WHERE id = ?")
                    ->execute([$montant, $montant, $userId]);

                $pdo->prepare("UPDATE action_utilisateur SET nombre = nombre - ? WHERE id_utilisateur = ? AND id_actions = ?")
                    ->execute([$quantite, $userId, $actionId]);

                $pdo->prepare("DELETE FROM action_utilisateur WHERE id_utilisateur = ? AND id_actions = ? AND nombre <= 0")
                    ->execute([$userId, $actionId]);

                $success = "Vente effectuée avec succès.";
            } else {
                $erreur = "Vous ne possédez pas suffisamment d’actions.";
            }
        }
    }
}

// Récupération des données de l'action
$stmt = $pdo->prepare("SELECT * FROM actions WHERE id_actions = ?");
$stmt->execute([$actionId]);
$action = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT compte FROM portefeuille WHERE id = ?");
$stmt->execute([$userId]);
$solde = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail de l'action</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background-color: black;
            color: white;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            font-size: 24px;
            color: #00ffe1;
        }

        h1 {
            margin-top: 60px;
            font-size: 32px;
        }

        .content {
            display: flex;
            gap: 40px;
            margin-top: 30px;
        }

        .graph {
            flex: 2;
            height: 300px;
            background-color: #111; /* plus neutre, sombre */
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,255,225,0.1);
        }


        .form {
            flex: 1;
            padding: 20px;
            background-color: rgba(255,255,255,0.05);
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,255,225,0.15);
        }

        .form h3 {
            margin-bottom: 10px;
        }

        .form p {
            font-size: 14px;
            margin-bottom: 20px;
        }

        .form input[type="number"] {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: none;
            margin-bottom: 15px;
        }

        .form button {
            display: inline-block;
            background: linear-gradient(270deg, #005e5a, #00e0d5, #005e5a);
            background-size: 400% 400%;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            border: none;
            cursor: pointer;
            animation: gradientMove 6s ease infinite;
            transition: all 0.3s ease;
            margin-right: 10px;
        }

        .form button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px #00ffe1;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .erreur {
            color: #ff5050;
            margin-bottom: 10px;
        }

        .success {
            color: #00ffa2;
            margin-bottom: 10px;
        }

        .solde {
            margin-top: 10px;
            font-size: 14px;
            color: #00ffe1;
        }
    </style>
</head>
<body>

<a href="dashboard.php" class="back-button">← Retour</a>

<h1><?= htmlspecialchars($action['nom_actions']) ?> - <?= number_format($action['valeur'], 2, ',', ' ') ?> €</h1>

<div class="content">
    <div class="graph">
        <canvas id="graphiqueAction" width="100%" height="100%"></canvas>
    </div>

    <div class="form">
        <h3>Opérations</h3>

        <?php if (isset($erreur)): ?>
            <div class="erreur"><?= $erreur ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <form method="post">
            <label for="quantite">Quantité :</label>
            <input type="number" name="quantite" id="quantite" min="1" required>

            <button type="submit" name="acheter">Acheter</button>
            <button type="submit" name="vendre">Vendre</button>
        </form>

        <div class="solde">💰 Solde actuel : <?= number_format($solde, 2, ',', ' ') ?> €</div>
    </div>
</div>
<script src="launch_update.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const idAction = <?= (int)$_GET['id'] ?>;

    async function loadChart() {
        try {
            const response = await fetch(`get_historique_action.php?id_action=${idAction}`);
            const data = await response.json();

            if (data.length === 0) {
                console.log("Aucune donnée pour afficher le graphique.");
                return;
            }

            const labels = data.map(item => item.date);  // Extraire les dates
            const valeurs = data.map(item => parseFloat(item.valeur));  // Extraire les valeurs des actions

            const ctx = document.getElementById('graphiqueAction').getContext('2d');

            // Création du graphique avec Chart.js
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,  // Utiliser les dates comme labels
                    datasets: [{
                        label: 'Évolution du cours (€)',
                        data: valeurs,  // Utiliser les valeurs des actions
                        fill: false,  // Ne pas remplir sous la ligne
                        borderColor: 'rgba(0, 255, 225, 0.8)',  // Couleur de la ligne
                        tension: 0.2,  // Courbure de la ligne
                        pointRadius: 3,  // Taille des points
                        pointBackgroundColor: 'white'  // Couleur des points
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { labels: { color: '#fff' } }  // Couleur de la légende
                    },
                    scales: {
                        x: {
                            ticks: { color: '#ccc' },  // Couleur des ticks sur l'axe des X
                            title: { display: true, text: 'Date', color: '#ccc' }  // Titre de l'axe X
                        },
                        y: {
                            ticks: { color: '#ccc' },  // Couleur des ticks sur l'axe des Y
                            title: { display: true, text: 'Prix (€)', color: '#ccc' }  // Titre de l'axe Y
                        }
                    }
                }
            });
        } catch (error) {
            console.error("Erreur lors du chargement des données :", error);
        }
    }

    // Appeler la fonction pour charger le graphique
    loadChart();

</script>

</body>
</html>
