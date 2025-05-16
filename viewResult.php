<?php
session_start();
if ($_SESSION['response']) {
    $response =  $_SESSION['response'];
} else {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Resultado da Geração</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            flex: 1;
            padding: 30px;
            display: flex;
            flex-direction: column;
        }

        h1 {
            color: #28a745;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            font-weight: bold;
            font-size: 1.2em;
            margin-bottom: 20px;
        }

        .boxes {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            flex: 1;
            min-width: 45%;
            max-height: 75vh;
            overflow-y: auto;
        }

        .box h2 {
            margin-top: 0;
        }

        ul {
            padding-left: 20px;
        }

        li {
            margin-bottom: 5px;
        }

        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 30px;
            align-self: flex-start;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        @media (max-width: 768px) {
            .boxes {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
            <h1>Resultado da Geração da API</h1>
            <a href="<?= htmlspecialchars($response['download_url']) ?>" class="btn" download>📦 Baixar API ZIP</a>
        </div>
        <div class="boxes">
            <div class="box">
                <h2>Tabelas Criadas (<?= count($response['tabelasCriadas']) ?>)</h2>
                <ul>
                    <?php foreach ($response['tabelasCriadas'] as $tabela): ?>
                        <li><?= htmlspecialchars($tabela) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="box">
                <h2>Tabelas Ignoradas (<?= count($response['tabelasIgnoradas']) ?>)</h2>
                <ul>
                    <?php foreach ($response['tabelasIgnoradas'] as $tabela): ?>
                        <li><?= htmlspecialchars($tabela) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>


    </div>

</body>

</html>