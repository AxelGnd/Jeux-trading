<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Réussie | StockSim</title>
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
            padding: 50px;
            text-align: center;
            color: white;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            width: 400px;
            animation: fadeIn 1.5s ease;
        }

        .card img {
            width: 80px;
            margin-bottom: 20px;
        }

        .card h1 {
            font-size: 1.8em;
            background: linear-gradient(45deg, #00ffe1, #ffffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 15px;
        }

        .card p {
            font-size: 1em;
            color: #e0e0e0;
            margin-bottom: 30px;
        }

        .card a {
            display: inline-block;
            background: linear-gradient(270deg, #005e5a, #00e0d5, #005e5a);
            background-size: 400% 400%;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            box-shadow: 0 0 12px rgba(0, 224, 213, 0.3);
            animation: gradientMove 6s ease infinite;
            transition: all 0.3s ease;
        }

        .card a:hover {
            transform: scale(1.05);
            box-shadow: 0 0 15px #00ffe1;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
    </style>
</head>
<body>

<div class="background"></div>

<div class="container">
    <div class="card">
        <img src="https://cdn-icons-png.flaticon.com/512/190/190411.png" alt="Succès">
        <h1>Inscription réussie !</h1>
        <p>Votre compte a été créé avec succès.<br>Vous pouvez maintenant vous connecter.</p>
        <a href="connexion.php">Se connecter</a>
    </div>
</div>

</body>
</html>
