<?php
$pdo = new PDO("mysql:host=localhost;dbname=trading;charset=utf8", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$idAction = $_GET['id_action'] ?? null;

if (!$idAction) {
    echo json_encode(['error' => 'Aucun id_action spécifié.']);
    exit();
}

// Calculer la date il y a 12 mois
$date12mois = date('Y-m-d', strtotime('-12 months'));

// Récupérer les données des 12 derniers mois pour l'action spécifiée
$stmt = $pdo->prepare("
    SELECT date, valeur
    FROM historique_actions
    WHERE id_actions = ? AND date >= ?
    ORDER BY date DESC
");
$stmt->execute([$idAction, $date12mois]);
$donnees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Débogage - Vérifier si les données ont été récupérées
if (empty($donnees)) {
    echo json_encode(['error' => 'Aucune donnée trouvée pour cette action.']);
    exit();
}

// Retourner les données en JSON
header('Content-Type: application/json');
echo json_encode($donnees);
?>
