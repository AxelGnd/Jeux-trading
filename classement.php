<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=trading;charset=utf8", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Vérifier si un pseudo a été recherché
$searchResult = [];
$actionsUtilisateur = [];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchPseudo = $_GET['search'];

    // Requête pour récupérer les informations du joueur recherché
    $searchQuery = $pdo->prepare("
        SELECT u.pseudo, (p.compte + p.valeur_en_actions) AS capital_total
        FROM utilisateur u
        JOIN portefeuille p ON u.id = p.id
        WHERE u.pseudo LIKE :searchPseudo
    ");
    $searchQuery->execute([':searchPseudo' => "%$searchPseudo%"]);
    $searchResult = $searchQuery->fetchAll(PDO::FETCH_ASSOC);

    // Si un joueur est trouvé, récupérer ses actions et quantités
    if (!empty($searchResult)) {
        $userId = $searchResult[0]['pseudo']; // Récupérer le pseudo du premier (et seul) résultat trouvé

        // Récupérer les actions et quantités détenues par le joueur
        $actionsQuery = $pdo->prepare("
            SELECT a.nom_actions, COALESCE(au.nombre, 0) AS quantite
            FROM actions a
            LEFT JOIN action_utilisateur au ON a.id_actions = au.id_actions
            LEFT JOIN utilisateur u ON au.id_utilisateur = u.id
            WHERE u.pseudo = :pseudo AND au.nombre > 0
        ");
        $actionsQuery->execute([':pseudo' => $searchPseudo]);
        $actionsUtilisateur = $actionsQuery->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Si aucune recherche n'a été faite, afficher tous les joueurs triés par capital
if (empty($searchResult)) {
    $query = $pdo->prepare("
        SELECT u.pseudo, (p.compte + p.valeur_en_actions) AS capital_total
        FROM utilisateur u
        JOIN portefeuille p ON u.id = p.id
        ORDER BY capital_total DESC
    ");
    $query->execute();
    $classement = $query->fetchAll(PDO::FETCH_ASSOC);
} else {
    $classement = $searchResult; // Afficher uniquement les résultats de la recherche
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Classement | StockSim</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        /* Style simple pour afficher le classement */
        body {
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at center, #0f2027, #203a43, #2c5364);
            color: white;
            padding: 40px;
        }

        h1 {
            font-size: 2.2em;
            margin-bottom: 30px;
            background: linear-gradient(45deg, #00ffe1, #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .search-bar {
            margin-bottom: 30px;
        }

        .search-bar input {
            padding: 10px;
            font-size: 1em;
            width: 80%;
            border-radius: 10px;
            border: none;
        }

        .search-bar button {
            padding: 10px 20px;
            background-color: #00ffe1;
            color: white;
            border-radius: 10px;
            border: none;
            cursor: pointer;
        }

        .classement-list {
            list-style-type: none;
            padding: 0;
        }

        .classement-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
        }

        .classement-item span {
            font-size: 1.2em;
        }

        .actions-list {
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 10px;
        }

        .actions-list h3 {
            margin-bottom: 10px;
            font-size: 1.4em;
        }

        .action-item {
            margin-bottom: 5px;
        }

        /* Style du bouton de retour */
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: transparent;
            border: none;
            color: #00ffe1;
            font-size: 2em;
            cursor: pointer;
            transition: color 0.3s;
        }

        .back-button:hover {
            color: #ffffff;
        }

    </style>
</head>
<body>
<!-- Bouton de retour -->
<a href="dashboard.php">
    <button class="back-button">&#8592;</button>
</a>

<h1>Classement des joueurs</h1>

<!-- Barre de recherche -->
<div class="search-bar">
    <form action="classement.php" method="GET">
        <input type="text" name="search" placeholder="Rechercher un joueur..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
        <button type="submit">Rechercher</button>
    </form>
</div>

<!-- Afficher les résultats de la recherche ou le classement -->
<ul class="classement-list">
    <?php if (empty($classement)): ?>
        <li>Aucun joueur trouvé.</li>
    <?php else: ?>
        <?php foreach ($classement as $rank): ?>
            <li class="classement-item">
                <span><?= htmlspecialchars($rank['pseudo']) ?></span>
                <span><?= number_format($rank['capital_total'], 2, ',', ' ') ?> €</span>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
</ul>

<!-- Si un joueur a été recherché, afficher ses actions -->
<?php if (!empty($actionsUtilisateur)): ?>
    <div class="actions-list">
        <h3>Actions détenues par <?= htmlspecialchars($searchPseudo) ?> :</h3>
        <?php foreach ($actionsUtilisateur as $action): ?>
            <div class="action-item">
                <span><?= htmlspecialchars($action['nom_actions']) ?> - Quantité : <?= $action['quantite'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</body>
</html>
