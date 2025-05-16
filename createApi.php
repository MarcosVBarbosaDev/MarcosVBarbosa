<?php

header('Content-Type: application/json');
session_start();

$baseTemp = sys_get_temp_dir() . '/api_temp_' . uniqid();
$zipFile = $baseTemp . '.zip';
$publicZipDir = __DIR__ . '/zip';
$publicZipPath = $publicZipDir . '/api_gerada_' . time() . '.zip'; // Caminho final do ZIP
$publicZipUrl = 'zip/' . basename($publicZipPath); // URL pública para download

$db_host = $_POST['DB_HOST'] ?? 'null';
$db_name = $_POST['DB_NAME'] ?? 'null';
$db_user = $_POST['DB_USER'] ?? 'null';
$db_pass = $_POST['DB_PASS'] ?? 'null';

$tabelasCriadas = [];
$tabelasIgnoradas = [];
$tables = [];
$primaryKeys = [];
$columnsSemPK = [];
$conn = null;

try {
    // Processa arquivo SQL enviado (upload)
    if (!empty($_FILES['sqlFile']) && $_FILES['sqlFile']['error'] === UPLOAD_ERR_OK) {
        $sqlContent = file_get_contents($_FILES['sqlFile']['tmp_name']);
        if (!$sqlContent) {
            throw new Exception('Erro ao ler o arquivo SQL.');
        }
        $dadosEstrutura = extrairDadosDoSQL($sqlContent);
        $tables = $dadosEstrutura['tables'];
        $primaryKeys = $dadosEstrutura['primaryKeys'];
        $columnsSemPK = $dadosEstrutura['columnsSemPK'];
    } else {
        // Conexão direta ao banco MySQL
        if (!$db_host || !$db_name || !$db_user) {
            throw new Exception('DB_HOST, DB_NAME e DB_USER são obrigatórios.');
        }
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $stmt = $conn->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($columns as $col) {
                if ($col['Key'] === 'PRI') {
                    $primaryKeys[$table] = $col['Field'];
                } else {
                    $columnsSemPK[$table][] = $col['Field'];
                }
            }
        }
    }

    if (empty($tables)) {
        throw new Exception('Nenhuma tabela encontrada na estrutura ou banco.');
    }

    // Criar pasta temporária para os arquivos
    if (!mkdir($baseTemp, 0755, true) && !is_dir($baseTemp)) {
        throw new Exception('Falha ao criar diretório temporário.');
    }

    // create arquivos base (token e conexão)
    $token = hash('sha256', date('YmdHis') . uniqid());
    file_put_contents("$baseTemp/function.php", createFunction());
    file_put_contents("$baseTemp/valida_token.php", createValidaToken($token));
    file_put_contents("$baseTemp/conexao.php", createConnection($db_host, $db_name, $db_user, $db_pass));

    // Para cada tabela, create pasta e arquivos da API
    foreach ($tables as $table) {
        $dir = "$baseTemp/$table";
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception("Falha ao criar diretório para tabela: $table");
        }

        file_put_contents("$dir/index.php", createIndex());

        $primaryKey = $primaryKeys[$table] ?? null;
        $columns = $columnsSemPK[$table] ?? [];

        if ($primaryKey !== null) {
            file_put_contents("$dir/get.php", createGet($table, $primaryKey));
            file_put_contents("$dir/post.php", createPost($table, $columns));
            file_put_contents("$dir/patch.php", createPatch($table, $primaryKey));
            file_put_contents("$dir/put.php", createPut($table, $columns, $primaryKey));
            file_put_contents("$dir/delete.php", createDelete($table, $primaryKey));
            $tabelasCriadas[] = $table;
        } else {
            // Tabela sem PK definida: só gera POST (criação)
            file_put_contents("$dir/post.php", createPost($table, $columns));
            $tabelasIgnoradas[] = $table;
        }
    }

    // Criar arquivo ZIP com a API
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
        throw new Exception('Não foi possível criar o arquivo ZIP.');
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseTemp),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($baseTemp) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();

    // Garante pasta pública para ZIP
    if (!is_dir($publicZipDir)) {
        mkdir($publicZipDir, 0755, true);
    }

    // Move ZIP para pasta pública
    rename($zipFile, $publicZipPath);

    // Limpar pasta temporária
    deleteDir($baseTemp);

    // Salvar na sessão e enviar resposta JSON
    $_SESSION['response'] = [
        'status' => true,
        'download_url' => $publicZipUrl,
        'tabelasCriadas' => $tabelasCriadas,
        'tabelasIgnoradas' => $tabelasIgnoradas,
    ];

    echo json_encode($_SESSION['response']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'result' => $e->getMessage(),
    ]);
    // Limpar pasta temporária se existir
    if (is_dir($baseTemp)) {
        deleteDir($baseTemp);
    }
    exit;
}

function extrairDadosDoSQL(string $sql): array
{
    $tables = [];
    $primaryKeys = [];
    $columnsSemPK = [];

    preg_match_all('/CREATE TABLE IF NOT EXISTS `([^`]+)` \((.*?)\)\s*ENGINE=/is', $sql, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $tableName = $match[1];
        $definitions = trim($match[2]);

        $tables[] = $tableName;
        $colunas = [];
        $primaryKey = null;

        $linhas = preg_split('/,\s*(?![^\(]*\))/', $definitions);

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if (preg_match('/^PRIMARY KEY\s*\(`([^`]+)`\)/i', $linha, $pkMatch)) {
                $primaryKey = $pkMatch[1];
                continue;
            }

            if (preg_match('/^`([^`]+)`\s+([a-zA-Z0-9\(\)]+)/', $linha, $colMatch)) {
                $coluna = $colMatch[1];
                $colunas[] = $coluna;
            }
        }

        $primaryKeys[$tableName] = $primaryKey;
        $columnsSemPK[$tableName] = array_filter($colunas, fn($col) => $col !== $primaryKey);
    }

    return [
        'tables' => $tables,
        'primaryKeys' => $primaryKeys,
        'columnsSemPK' => $columnsSemPK,
    ];
}


// Função para apagar pasta recursivamente
function deleteDir($dir)
{
    if (!file_exists($dir)) return;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    rmdir($dir);
}

function getPrimaryKey(PDO $conn, string $tabela, string $db_name): ?string
{
    $stmt = $conn->prepare("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = :db
          AND TABLE_NAME = :tabela
          AND COLUMN_KEY = 'PRI'
        LIMIT 1
    ");
    $stmt->execute([
        ':db' => $db_name,
        ':tabela' => $tabela
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['COLUMN_NAME'] : null;
}

function getColumnsWithoutPrimaryKey(PDO $conn, string $tabela, string $db_name): array
{
    $stmt = $conn->prepare("
        SELECT COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = :db
          AND TABLE_NAME = :tabela
          AND COLUMN_KEY != 'PRI'
    ");
    $stmt->execute([
        ':db' => $db_name,
        ':tabela' => $tabela
    ]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Função que gera o index.php para roteamento dos métodos
function createIndex(): string
{
    return <<<PHP
<?php
include('../valida_token.php');

// Verifica autorização definida no valida_token.php
if (!\$authorization) {
    http_response_code(401);
    echo json_encode(['status' => 'fail', 'result' => 'Token não autorizado']);
    exit;
}

if (\$method == 'GET') {
    require('get.php');
} elseif (\$method == 'POST') {
    require('post.php');
} elseif (\$method == 'PUT') {
    require('put.php');
} elseif (\$method == 'PATCH') {
    require('patch.php');
} elseif (\$method == 'DELETE') {
    require('delete.php');
} else {
    http_response_code(405);
    echo json_encode(['status' => 'fail', 'result' => 'Método não permitido']);
}
PHP;
}

function createFunction(): string
{
    return <<<PHP
<?php
function getResult(\$stmt)
{
    if (\$stmt->rowCount() < 1) {
        http_response_code(204);
        return null;
    } elseif (\$stmt->rowCount() == 1) {
        return \$stmt->fetch(PDO::FETCH_OBJ);
    } else {
        return \$stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
PHP;
}


function createConnection(string $db_host, string $db_name, string $db_user, string $db_pass): string
{
    return <<<PHP
<?php

\$timezone = getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo';
date_default_timezone_set(\$timezone);

define('host_db', '{$db_host}');
define('name_db', '{$db_name}');
define('user_db', '{$db_user}');
define('pass_db', '{$db_pass}');

try {
    \$conn = new PDO('mysql:host=' . host_db . ';dbname=' . name_db, user_db, pass_db);
    \$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$stmt = \$conn->prepare("SET lc_time_names = 'pt_br'");
    \$stmt->execute();
} catch (\\Throwable \$th) {
    \$result = array(
        "status" => "fail",
        "result" => \$th->getMessage()
    );
    echo json_encode(\$result);
    exit;
}
PHP;
}

function createValidaToken(string $tokenEsperado): string
{
    return <<<PHP
<?php

include_once("functions.php");

\$allowedOrigins = array(
    ''
);

if (isset(\$_SERVER['HTTP_ORIGIN']) && in_array(\$_SERVER['HTTP_ORIGIN'], \$allowedOrigins)) {
    \$http_origin = \$_SERVER['HTTP_ORIGIN'];
} else {
    \$http_origin = "";
}

// header("Access-Control-Allow-Origin: \$http_origin");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// AUTHORIZARION RESTRICT
\$authorization = false;

// REQUEST TOKEN
@\$token = \$_SERVER['HTTP_AUTHORIZATION'] ?? null;

// REQUEST METHOD
\$method = \$_SERVER['REQUEST_METHOD'];

// REQUEST BODY
\$json = json_decode(file_get_contents('php://input'), true) ?? null;

// VERIFICA SE O TOKEN FOI DECLARADO
if (!empty(\$token)) {
    if (\$token === '{$tokenEsperado}') {
        \$authorization = true;
        include_once("conn.php");
    } else {
        echo json_encode([
            'status' => 'fail',
            'result' => 'Token inválido!'
        ]);
        exit;
    }
} else {
    echo json_encode([
        'status' => 'fail',
        'result' => 'O envio do Token é obrigatório!'
    ]);
    exit;
}
PHP;
}



function createGet(string $tabela, ?string $primaryKey): string
{
    if ($primaryKey) {
        $sql = <<<PHP
<?php
try {
    if (isset(\$_GET['id']) && is_numeric(\$_GET['id'])) {
        \$id = trim(\$_GET['id']);
        \$sql = "SELECT * FROM {$tabela} WHERE {$primaryKey} = :id";
        \$stmt = \$conn->prepare(\$sql);
        \$stmt->bindParam(':id', \$id, PDO::PARAM_INT);
    } else {
        \$sql = "SELECT * FROM {$tabela}";
        \$stmt = \$conn->prepare(\$sql);
    }

    \$stmt->execute();
    \$result = \$stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'result' => \$result]);

} catch (Throwable \$th) {
    http_response_code(500);
    echo json_encode(['status' => 'fail', 'result' => \$th->getMessage()]);
} finally {
    \$conn = null;
}
PHP;
    } else {
        $sql = <<<PHP
<?php
try {
    \$sql = "SELECT * FROM {$tabela}";
    \$stmt = \$conn->prepare(\$sql);
    \$stmt->execute();
    \$result = \$stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'result' => \$result]);

} catch (Throwable \$th) {
    http_response_code(500);
    echo json_encode(['status' => 'fail', 'result' => \$th->getMessage()]);
} finally {
    \$conn = null;
}
PHP;
    }

    return $sql;
}



function createPost(string $tabela, array $colunas): string
{
    // Construir as partes da query INSERT e os binds dinamicamente

    $campos = implode(", ", $colunas);
    $placeholders = implode(", ", array_map(fn($c) => ":$c", $colunas));

    // create os binds de forma a aceitar NULL se valor for vazio
    $binds = "";
    foreach ($colunas as $col) {
        $binds .= "\$stmt->bindValue(':$col', trim(\$json['$col']) === '' ? null : trim(\$json['$col']));\n            ";
    }

    // create validação simples (checa se pelo menos um campo está presente, ou você pode exigir todos)
    // Aqui vou exigir todos os campos, você pode ajustar se quiser aceitar campos nulos
    $validations = implode(" && ", array_map(fn($c) => "isset(\$json['$c'])", $colunas));

    return <<<PHP
    <?php
        try {
            if ($validations) {

                \$sql = "INSERT INTO $tabela ($campos) VALUES ($placeholders)";
                \$stmt = \$conn->prepare(\$sql);

                $binds

                \$stmt->execute();

                http_response_code(201);
                \$result = [
                    'status' => 'success',
                    'result' => 'Registro inserido com sucesso!'
                ];
            } else {
                http_response_code(400);
                \$result = [
                    'status' => 'fail',
                    'result' => 'Campos obrigatórios ausentes!'
                ];
            }
        } catch (Throwable \$th) {
            http_response_code(500);
            \$result = [
                'status' => 'fail',
                'result' => \$th->getMessage()
            ];
        } finally {
            \$conn = null;
            echo json_encode(\$result);
        }
PHP;
}


function createPut(string $tabela, array $colunas, string $primaryKey): string
{

    // Monta os pares para o SET: campo = :campo
    $setParts = implode(', ', array_map(fn($c) => "$c = :$c", $colunas));

    // Gera a validação: PK obrigatória e os outros campos podem ser opcionais, mas para simplificar
    // aqui vamos exigir a PK e pelo menos um campo para atualizar
    $validations = "isset(\$json['$primaryKey']) && is_numeric(\$json['$primaryKey'])";

    // Gera os binds para os campos a atualizar
    $binds = "";
    foreach ($colunas as $col) {
        $binds .= "\$stmt->bindValue(':$col', isset(\$json['$col']) && trim(\$json['$col']) !== '' ? trim(\$json['$col']) : null);\n            ";
    }

    // Bind para a chave primária no WHERE
    $binds .= "\$stmt->bindValue(':$primaryKey', \$json['$primaryKey'], PDO::PARAM_INT);\n            ";

    return <<<PHP
    <?php
        try {
            if ($validations) {

                \$sql = "UPDATE $tabela SET $setParts WHERE $primaryKey = :$primaryKey";
                \$stmt = \$conn->prepare(\$sql);

                $binds

                \$stmt->execute();

                http_response_code(200);
                \$result = [
                    'status' => 'success',
                    'result' => 'Registro atualizado com sucesso!'
                ];
            } else {
                http_response_code(400);
                \$result = [
                    'status' => 'fail',
                    'result' => 'Chave primária ausente ou inválida!'
                ];
            }
        } catch (Throwable \$th) {
            http_response_code(500);
            \$result = [
                'status' => 'fail',
                'result' => \$th->getMessage()
            ];
        } finally {
            \$conn = null;
            echo json_encode(\$result);
        }
PHP;
}

function createPatch(string $tabela, string $primaryKey): string
{
    return <<<PHP
<?php

try {
     if (isset(\$json["id"]) && is_numeric(\$json["id"])) {
        \$sql = "UPDATE {$tabela} SET ";
        foreach (\$json as \$key => \$value) {
            if (\$key !== 'id') {
                \$sql .= "\$key = :\$key,";
            }
        }
        \$sql = rtrim(\$sql, ',') . " WHERE {$primaryKey} = :{$primaryKey}";

        \$stmt = \$conn->prepare(\$sql);
        
        foreach (\$json as \$key => \$value) {
            if (\$key !== '$primaryKey') {
                \$val = trim(\$value);
                if (\$val === '') {
                    \$val = null;
                }
                \$stmt->bindValue(":\$key", \$val);
            } else {
                \$stmt->bindValue(':$primaryKey', \$value, PDO::PARAM_INT);
            }
        }

        \$stmt->execute();

        http_response_code(200);
        \$result = [
            'status' => 'success',
            'result' => 'Registro atualizado com sucesso!'
        ];
    } else {
        http_response_code(400);
        \$result = [
            'status' => 'fail',
            'result' => 'ID não informado ou inválido!'
        ];
    }

} catch (Throwable \$th) {
    http_response_code(500);
    if (\$th->getCode() == 23000) {
        \$result = [
            'status' => 'fail',
            'result' => 'Registro já existente (violação de chave única).'
        ];
    } else {
        \$result = [
            'status' => 'fail',
            'result' => \$th->getMessage()
        ];
    }
} finally {
    \$conn = null;
    echo json_encode(\$result);
}
PHP;
}



function createDelete(string $tabela, string $primaryKey): string
{
    return <<<PHP
<?php

// VALIDA SE FOI LIBERADO O ACESSO
try {
    if (isset(\$json["id"]) && is_numeric(\$json["id"])) {
        \$sql = "DELETE FROM {$tabela} WHERE {$primaryKey} = :id";
        \$stmt = \$conn->prepare(\$sql);
        \$stmt->bindValue(':id', \$json["id"], PDO::PARAM_INT);
        \$stmt->execute();

        if (\$stmt->rowCount() > 0) {
            http_response_code(200);
            \$result = [
                'status' => 'success',
                'result' => 'Registro excluído com sucesso!'
            ];
        } else {
            http_response_code(404);
            \$result = [
                'status' => 'fail',
                'result' => 'Registro não encontrado!'
            ];
        }
    } else {
        http_response_code(400);
        \$result = [
            'status' => 'fail',
            'result' => 'ID não informado ou inválido!'
        ];
    }

} catch (Throwable \$th) {
    http_response_code(500);
    \$result = [
        'status' => 'fail',
        'result' => \$th->getMessage()
    ];
} finally {
    \$conn = null;
    echo json_encode(\$result);
}
PHP;
}
