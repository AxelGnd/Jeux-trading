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

// Récupération des soldes
$stmt = $pdo->prepare("SELECT compte, valeur_en_actions FROM portefeuille WHERE id = :id");
$stmt->execute([':id' => $userId]);
$solde = $stmt->fetch(PDO::FETCH_ASSOC);
$valeurCompte = $solde['compte'];
$valeurActions = $solde['valeur_en_actions'];

// Récupération des actions disponibles + quantités détenues
$query = $pdo->prepare("
    SELECT a.*, 
           COALESCE(au.nombre, 0) AS quantite
    FROM actions a
    LEFT JOIN action_utilisateur au ON a.id_actions = au.id_actions AND au.id_utilisateur = :user_id
");
$query->execute(['user_id' => $userId]);
$actions = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de bord | StockSim</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at center, #0f2027, #203a43, #2c5364);
            color: white;
            min-height: 100vh;
        }

        .container {
            padding: 40px;
            max-width: 1200px;
            margin: auto;
        }

        h1 {
            font-size: 2.2em;
            background: linear-gradient(45deg, #00ffe1, #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 30px;
        }

        .solde {
            display: flex;
            gap: 30px;
            margin-bottom: 40px;
        }

        .solde div {
            flex: 1;
            background-color: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 0 12px rgba(0,255,225,0.2);
            text-align: center;
        }

        .solde h2 {
            margin-bottom: 10px;
            font-size: 1.4em;
            color: #00ffe1;
        }

        .action-cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
        }

        .action-card {
            position: relative;
            height: 320px;
            background: linear-gradient(135deg, #101820, #1c2b3a);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .action-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,255,225,0.3);
        }

        .action-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            max-width: 120px;
            max-height: 120px;
            opacity: 0.8;
            transition: opacity 0.3s ease;
            z-index: 1;
        }

        .overlay {
            position: absolute;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.85);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
            padding: 20px;
            border-radius: 20px;
            text-align: center;
        }

        .action-card:hover .overlay {
            opacity: 1;
        }

        .overlay h3 {
            font-size: 1.2em;
            margin-bottom: 10px;
            color: #00ffe1;
        }

        .overlay p {
            font-size: 0.95em;
            margin: 5px 0;
            color: #ffffff;
        }

        .card-link {
            display: block;
            width: 100%;
            height: 100%;
            text-decoration: none;
            color: inherit;
        }
    </style>

</head>
<body>

<div class="container">
    <h1>Bienvenue, <?= htmlspecialchars($_SESSION['Pseudo']) ?> !</h1>
    <!-- Ajouter un bouton "Classement" -->
    <div class="classement-btn">
        <a href="classement.php">
            <button style="padding: 10px 20px; font-size: 1.1em; border: none; background-color: #00ffe1; color: white; border-radius: 10px; cursor: pointer;">
                Classement
            </button>
        </a>
    </div>

    <div class="solde">
        <div>
            <h2>Solde disponible</h2>
            <p><?= number_format($valeurCompte, 2, ',', ' ') ?> €</p>
        </div>
        <div>
            <h2>Capital en actions</h2>
            <p><?= number_format($valeurActions, 2, ',', ' ') ?> €</p>
        </div>
    </div>

    <h2>📊 Vos actions :</h2>

    <div class="action-cards-container">
        <?php foreach ($actions as $action): ?>
            <a class="card-link" href="action_details.php?id=<?= $action['id_actions'] ?>">
                <div class="action-card">
                    <img src="<?= htmlspecialchars($action['logo']) ?>" alt="<?= htmlspecialchars($action['nom_actions']) ?>" class="action-logo">
                    <div class="overlay">
                        <h3><?= htmlspecialchars($action['nom_actions']) ?></h3>
                        <p>Valeur actuelle : <?= number_format($action['valeur'], 2, ',', ' ') ?> €</p>
                        <p>Quantité détenue : <?= $action['quantite'] ?></p>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<script src="launch_update.js"></script>
</body>
</html>
