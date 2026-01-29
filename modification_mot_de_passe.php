<?php
$pdo = new PDO("mysql:host=localhost;dbname=trading;charset=utf8", "root", "", [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$email = $_POST['email'] ?? '';
$response = $_POST['security_question'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$step = 1;
$question = '';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['check_email'])) {
        $stmt = $pdo->prepare("SELECT question FROM utilisateur WHERE mail = :email");
        $stmt->execute([':email' => $email]);
        $question = $stmt->fetchColumn();

        if ($question) {
            $step = 2;
        } else {
            $errors[] = "Adresse email inconnue.";
        }
    } elseif (isset($_POST['reset_password'])) {
        // Vérifier la réponse à la question
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE mail = :email AND question = :response");
        $stmt->execute([
            ':email' => $email,
            ':response' => $response
        ]);
        $user = $stmt->fetch();

        if ($user) {
            // Vérifier le mot de passe
            $hasUpper = preg_match('@[A-Z]@', $newPassword);
            $hasLower = preg_match('@[a-z]@', $newPassword);
            $hasSpecial = preg_match('@[\W_]@', $newPassword);
            $hasLength = strlen($newPassword) >= 8;

            if (!($hasUpper && $hasLower && $hasSpecial && $hasLength)) {
                $errors[] = "Le mot de passe ne respecte pas les critères.";
            }

            if ($newPassword !== $confirmPassword) {
                $errors[] = "Les mots de passe ne correspondent pas.";
            }

            if (empty($errors)) {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE utilisateur SET password = :pwd WHERE mail = :email");
                $stmt->execute([':pwd' => $hashed, ':email' => $email]);
                header("Location: connexion.php?reset=success");
                exit;
            } else {
                $step = 2; // rester à l'étape 2 si erreurs
                $question = $pdo->query("SELECT question FROM utilisateur WHERE mail = " . $pdo->quote($email))->fetchColumn();
            }
        } else {
            $errors[] = "La réponse à la question personnelle est incorrecte.";
            $step = 2;
            $question = $pdo->query("SELECT question FROM utilisateur WHERE mail = " . $pdo->quote($email))->fetchColumn();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialisation du mot de passe | StockSim</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background: black;
            overflow: hidden;
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            position: relative;
            z-index: 1;
        }
        .card {
            backdrop-filter: blur(16px);
            background-color: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 18px;
            padding: 40px;
            box-shadow: 0 0 12px rgba(0, 255, 225, 0.4);
            color: white;
            width: 360px;
        }
        .card h2 {
            text-align: center;
            margin-bottom: 25px;
        }
        .card label {
            display: block;
            margin-top: 10px;
            font-weight: 500;
        }
        .card input {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            background-color: rgba(255, 255, 255, 0.9);
        }
        input[type="submit"] {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(270deg, #005e5a, #00e0d5, #005e5a);
            background-size: 400% 400%;
            color: white;
            font-weight: bold;
            font-size: 1em;
            cursor: pointer;
            animation: gradientMove 6s ease infinite;
            box-shadow: 0 0 12px rgba(0, 224, 213, 0.3);
        }
        .error-message {
            background: rgba(255, 80, 80, 0.2);
            color: #ff4e4e;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9em;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .password-criteria {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.85em;
            margin-top: 10px;
            color: white;
        }
        .password-criteria ul {
            margin: 0;
            padding-left: 18px;
        }
        .password-criteria li {
            list-style-type: disc;
        }
    </style>
</head>
<body>
<div class="background"></div>
<div class="container">
    <form class="card" method="POST">
        <h2>Réinitialisation</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <label for="email">Votre adresse email</label>
            <input type="email" name="email" id="email" required value="<?= htmlspecialchars($email) ?>">
            <input type="submit" name="check_email" value="Continuer">
        <?php elseif ($step === 2): ?>
            <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
            <label for="response">Question personnelle :</label>
            <input type="text" name="security_question" id="security_question" required placeholder="Le lycée dans lequel vous avez étudié">

            <label for="newPassword">Nouveau mot de passe</label>
            <input type="password" name="newPassword" id="newPassword" required>
            <div id="passwordCriteria" class="password-criteria" style="display: none;">
                <p>Le mot de passe doit contenir :</p>
                <ul>
                    <li>Au moins 8 caractères</li>
                    <li>Une lettre majuscule</li>
                    <li>Une lettre minuscule</li>
                    <li>Un caractère spécial</li>
                </ul>
            </div>


            <label for="confirmPassword">Confirmer le mot de passe</label>
            <input type="password" name="confirmPassword" id="confirmPassword" required>

            <input type="submit" name="reset_password" value="Réinitialiser">
        <?php endif; ?>
    </form>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const passwordInput = document.getElementById("newPassword");
        const criteriaBox = document.getElementById("passwordCriteria");

        passwordInput.addEventListener("focus", () => {
            criteriaBox.style.display = "block";
        });

        passwordInput.addEventListener("blur", () => {
            criteriaBox.style.display = "none";
        });
    });
</script>
</body>
</html>
