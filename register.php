<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pdo = new PDO("mysql:host=localhost;dbname=trading;charset=utf8", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pseudo = $_POST['pseudo'] ?? '';
    $email = $_POST['email'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $question = $_POST['security_question'] ?? '';
    $errors = [];

    if (!$pseudo || !$email || !$dob || !$password || !$confirmPassword) {
        $errors[] = "Tous les champs obligatoires doivent être remplis.";
    }

    $age = date_diff(date_create($dob), date_create('today'))->y;
    if ($age < 18) {
        $errors[] = "Vous devez avoir au moins 18 ans.";
    }

    $hasUpper = preg_match('@[A-Z]@', $password);
    $hasLower = preg_match('@[a-z]@', $password);
    $hasSpecial = preg_match('@[\W_]@', $password);
    $hasLength = strlen($password) >= 8;

    if (!($hasUpper && $hasLower && $hasSpecial && $hasLength)) {
        $errors[] = "Le mot de passe ne respecte pas les critères.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        // Vérifie si le pseudo existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE Pseudo = :pseudo");
        $stmt->execute([':pseudo' => $pseudo]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ce pseudo est déjà utilisé.";
        }

        // Vérifie si l'email existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE mail = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Cette adresse email est déjà utilisée.";
        }
        if (!$question) {
            $errors[] = "La question personnelle est obligatoire.";
        }
        if (empty($errors)) {
            // Hash le mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insère le nouvel utilisateur
            $stmt = $pdo->prepare("INSERT INTO utilisateur (Pseudo, mail, birth, password, question) VALUES (:pseudo, :email, :dob, :password, :question)");
            $stmt->execute([
                ':pseudo' => $pseudo,
                ':email' => $email,
                ':dob' => $dob,
                ':password' => $hashedPassword,
                ':question' => $question
            ]);
            $stmt = $pdo->prepare("INSERT INTO portefeuille ( compte, valeur_en_actions)
                       VALUES ( :valeur_compte, :valeur_actions)");
            $stmt->execute([
                ':valeur_compte' => 10000,
                ':valeur_actions' => 0 // au départ
            ]);

            header("Location: confirmation_reussie.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription | StockSim</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background: black;
            overflow-y: auto; /* modifié */
            overflow-x: hidden; /* pour éviter scroll horizontal */
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
            animation: fadeIn 1s ease;
        }

        .card h2 {
            text-align: center;
            font-size: 1.6em;
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

        .card input:focus {
            outline: 2px solid #00ffe1;
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
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        input[type="submit"]:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px #00ffe1;
        }

        .error-message {
            background: rgba(255, 80, 80, 0.2);
            color: #ff4e4e;
            padding: 6px;
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 0.8em;
        }
        .password-criteria {
            background: rgba(0, 0, 0, 0.6);
            padding: 8px 12px;
            margin-top: 5px;
            border: 1px solid #00ffe1;
            font-size: 12px;
            border-radius: 6px;
            max-width: 100%;
        }

        .password-criteria p {
            margin: 4px 0;
            color: #ff7b7b;
            text-align: left;
            padding-left: 15px;
            position: relative;
        }

        .password-criteria p::before {
            position: absolute;
            left: 0;
            color: inherit;
        }

        .password-criteria p.valid {
            color: #00ffe1;
            text-decoration: none;
        }

        p {
            font-size: 12px;
            text-align: center;
            margin-top: 15px;
            color: #cfcfcf;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="background"></div>
<div class="container">
    <form class="card" method="POST" onsubmit="return validateClient();">
        <h2>Inscription</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <label for="pseudo">Pseudo *</label>
        <input type="text" name="pseudo" id="pseudo" required value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>">

        <label for="email">Adresse email *</label>
        <input type="email" name="email" id="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

        <label for="dob">Date de naissance *</label>
        <input type="date" name="dob" id="dob" required value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>">

        <label for="password">Mot de passe *</label>
        <input type="password" name="password" id="password" required onfocus="showCriteria()" oninput="checkPasswordStrength()" onblur="hideCriteria()">
        <div id="criteria" class="password-criteria" style="display:none;">
            <p id="length">• Au moins 8 caractères</p>
            <p id="uppercase">• Une majuscule</p>
            <p id="lowercase">• Une minuscule</p>
            <p id="special">• Un caractère spécial</p>
        </div>

        <label for="confirmPassword">Confirmer le mot de passe *</label>
        <input type="password" name="confirmPassword" id="confirmPassword" required oninput="checkMatch()">

        <label for="security_question">Question personnelle *</label>
        <input type="text" name="security_question" id="security_question" required placeholder="Le lycée dans lequel vous avez étudié">


        <input type="submit" value="S'inscrire">
        <p>* Champs obligatoires</p>
    </form>
</div>

<script>
    function showCriteria() {
        document.getElementById("criteria").style.display = "block";
    }
    function hideCriteria() {
        document.getElementById("criteria").style.display = "none";
    }

    function checkPasswordStrength() {
        const password = document.getElementById("password").value;
        const criteria = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            special: /[\W_]/.test(password)
        };

        for (const key in criteria) {
            const el = document.getElementById(key);
            if (criteria[key]) {
                el.classList.add("valid");
            } else {
                el.classList.remove("valid");
            }
        }
    }

    function checkMatch() {
        const pass = document.getElementById("password").value;
        const confirm = document.getElementById("confirmPassword").value;
    }

    function validateClient() {
        return true;
    }
</script>
</body>
</html>
