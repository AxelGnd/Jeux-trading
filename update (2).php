<?php
session_start();

// Connexion à la base
$pdo = new PDO("mysql:host=localhost;dbname=trading;charset=utf8", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// 1. Récupération de la date actuelle
$stmt = $pdo->query("SELECT global_date FROM date LIMIT 1");
$currentDate = $stmt->fetchColumn();

// 2. Historique des prix des actions
$actions = $pdo->query("SELECT id_actions, valeur FROM actions")->fetchAll(PDO::FETCH_ASSOC);
foreach ($actions as $action) {
    $pdo->prepare("
        INSERT INTO historique_actions (id_action, valeur, date_enregistrement) 
        VALUES (:id, :valeur, :date)
    ")->execute([
        'id' => $action['id_actions'],
        'valeur' => $action['valeur'],
        'date' => $currentDate
    ]);
}

// 3. Historique du portefeuille (solde + valeur en actions)
$sql = "
    SELECT u.id AS user_id, 
           p.compte + p.valeur_en_actions AS total
    FROM utilisateur u
    JOIN portefeuille p ON u.id = p.id
";
$users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $pdo->prepare("
        INSERT INTO historique_portefeuille (id_utilisateur, valeur_totale, date_enregistrement)
        VALUES (:id_utilisateur, :valeur_totale, :date)
    ")->execute([
        'id_utilisateur' => $user['user_id'],
        'valeur_totale' => $user['total'],
        'date' => $currentDate
    ]);
}

$actions = $pdo->query("SELECT id_actions, valeur, variation_mois_prec FROM actions")->fetchAll(PDO::FETCH_ASSOC);

foreach ($actions as $action) {
    // Valeur actuelle de l'action
    $valeur_actuelle = $action['valeur'];

    // Variation du mois précédent (en pourcentage)
    $variation_mois_prec = $action['variation_mois_prec'];

    // Calcul de la variation aléatoire pour le mois actuel
    $variation_aleatoire = mt_rand(-300, 300) / 100; // entre -3% et +3%

    // Calcul de la variation totale en ajoutant la variation du mois précédent
    $variation_totale = $variation_mois_prec + $variation_aleatoire;

    // On s'assure que la variation totale reste entre -10% et +10%
    $variation_totale = max(-10, min(10, $variation_totale));

    // Calcul de la nouvelle valeur
    $nouvelle_valeur = round($valeur_actuelle * (1 + $variation_totale / 100), 2);

    // La valeur ne doit pas descendre en dessous de 1€
    $nouvelle_valeur = max(1, $nouvelle_valeur);

    // Mise à jour de la nouvelle valeur de l'action dans la base de données
    $pdo->prepare("UPDATE actions SET valeur = :valeur, variation_mois_prec = :variation WHERE id_actions = :id")
        ->execute([
            'valeur' => $nouvelle_valeur,
            'variation' => $variation_totale,
            'id' => $action['id_actions']
        ]);
}

// Recalcul de la valeur en actions pour chaque utilisateur
$sql = "
    SELECT u.id AS user_id, SUM(au.nombre * a.valeur) AS total_actions
    FROM utilisateur u
    JOIN action_utilisateur au ON u.id = au.id_utilisateur
    JOIN actions a ON au.id_actions = a.id_actions
    GROUP BY u.id
";
$portefeuilles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($portefeuilles as $p) {
    $pdo->prepare("UPDATE portefeuille SET valeur_en_actions = :valeur WHERE id = :id")
        ->execute([
            'valeur' => $p['total_actions'] ?? 0,
            'id' => $p['user_id']
        ]);
}


// 5. Distribution de dividendes si c'est le bon mois
$mois_actuel = date('n', strtotime($currentDate));

$sql = "
    SELECT u.id AS id_utilisateur, a.id_actions, au.nombre, d.pourcentage, d.date_distribution, a.valeur
    FROM dividende d
    JOIN actions a ON a.id_dividende = d.id
    JOIN action_utilisateur au ON au.id_actions = a.id_actions
    JOIN utilisateur u ON u.id = au.id_utilisateur
";
$dividendes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($dividendes as $d) {
    $mois_dividende = date('n', strtotime($d['date_distribution']));
    if ($mois_dividende == $mois_actuel) {
        $gain = ($d['pourcentage'] / 100) * $d['valeur'] * $d['nombre'];
        $pdo->prepare("UPDATE portefeuille SET compte = compte + :gain WHERE id = :id_utilisateur")
            ->execute([
                'gain' => $gain,
                'id_utilisateur' => $d['id_utilisateur']
            ]);
    }
}

// 6. Avancer d’un mois
$pdo->query("UPDATE date SET global_date = DATE_ADD(global_date, INTERVAL 1 MONTH)");

echo "Mise à jour effectuée avec succès.";
?>
