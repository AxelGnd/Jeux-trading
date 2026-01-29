    <?php
    session_start();
    
    $host = "localhost";
    $dbname = "trading";
    $user = "root";
    $pass = "";
    $errors = [];
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $mail     = trim($_POST['mail'] ?? '');
            $password = $_POST['password'] ?? '';
    
            if (!$mail || !$password) {
                $errors[] = "Veuillez remplir tous les champs.";
            } else {
                $stmt = $pdo->prepare(
                    "SELECT id, Pseudo, password 
                     FROM utilisateur 
                     WHERE mail = :m OR Pseudo = :m"
                );
                $stmt->execute([':m' => $mail]);
                $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
                if ($userRow) {
                    if (password_verify($password, $userRow['password'])) {
                        $_SESSION['user_id'] = $userRow['id'];
                        $_SESSION['Pseudo']  = $userRow['Pseudo'];
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        $errors[] = "Mot de passe incorrect.";
                    }
                } else {
                    $errors[] = "E-mail ou pseudo introuvable.";
                }
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur de connexion à la base de données : " . $e->getMessage();
    }
    ?>

    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Connexion | StockSim</title>
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
                gap: 60px;
                height: 100vh;
                position: relative;
                z-index: 1;
                animation: fadeIn 1.2s ease-out;
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .card, .welcome-box {
                backdrop-filter: blur(16px);
                background-color: rgba(0, 0, 0, 0.6);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 18px;
                padding: 40px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
                color: white;
                width: 360px;
                transition: all 0.3s ease;
            }

            .welcome-box {
                text-align: left;
            }

            .welcome-box h1 {
                font-size: 1.8em;
                margin-bottom: 15px;
                background: linear-gradient(45deg, #ffffff, #00ffe1);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .welcome-box p {
                font-size: 1em;
                line-height: 1.6;
                color: #e0e0e0;
            }

            .card h2 {
                text-align: center;
                font-size: 1.6em;
                margin-bottom: 25px;
            }

            .card input {
                width: 100%;
                padding: 12px;
                margin: 10px 0;
                border: none;
                border-radius: 8px;
                font-size: 1em;
                background-color: rgba(255, 255, 255, 0.9);
            }

            .card input:focus {
                outline: 2px solid #00ffe1;
            }

            .card button {
                width: 100%;
                padding: 12px;
                margin-top: 15px;
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

            .card button:hover {
                transform: scale(1.05);
                box-shadow: 0 0 15px #00ffe1;
            }

            .card .switch-link {
                display: block;
                margin-top: 12px;
                font-size: 0.9em;
                text-decoration: none;
                color: #cfcfcf;
                text-align: center;
            }

            .card .switch-link:hover {
                color: #ffffff;
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
                0% {
                    background-position: 0% 50%;
                }
                50% {
                    background-position: 100% 50%;
                }
                100% {
                    background-position: 0% 50%;
                }
            }
            .success-message {
                background: rgba(0, 255, 0, 0.15);
                color: #00ff90;
                padding: 12px;
                margin-bottom: 20px;
                border-radius: 8px;
                font-size: 0.95em;
                text-align: center;
            }
        </style>
    </head>
    <body>
    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <div class="success-message">
            Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.
        </div>
    <?php endif; ?>

    <div class="background"></div>

    <div class="container">
        <div class="welcome-box">
            <h1>Bienvenue dans StockSim !</h1>
            <p>
                Plongez dans l’univers palpitant des marchés financiers avec notre simulateur de trading.
                <br><br>
                Vous commencez avec un portefeuille virtuel de <strong>10 000 €</strong>. Achetez, vendez et anticipez les variations du marché pour faire croître votre capital.
                <br><br>
                Mais attention : si votre solde descend sous <strong>1 000 €</strong>, c’est la faillite !
                Serez-vous un investisseur avisé ou un trader trop audacieux ? À vous de jouer !
            </p>
        </div>

        <div class="card">
            <h2>CONNEXION</h2>

            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="text" name="mail" placeholder="e-mail ou pseudo" value="<?= htmlspecialchars($mail ?? '') ?>" required>
                <input type="password" name="password" placeholder="Mot de passe" required>
                <button type="submit">Se connecter</button>
            </form>
            <a class="switch-link" href="modification_mot_de_passe.php">Mot de passe oublié ?</a>
            <a class="switch-link" href="register.php">Créer un compte</a>
        </div>
    </div>

    </body>
    </html>
