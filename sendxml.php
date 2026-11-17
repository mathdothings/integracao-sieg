<?php

const BASEPATH = __DIR__;

require_once BASEPATH . '/vendor/autoload.php';

function createSiegJwt(string $clientId, string $secretKey)
{
    $url = 'https://api.sieg.com/api/v1/create-jwt';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Client-Id: ' . $clientId,
        'X-Secret-Key: ' . $secretKey,
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    unset($ch);

    if ($curlError) {
        throw new Exception('Erro no cURL: ' . $curlError);
    }

    if ($httpCode !== 200) {
        throw new Exception('Falha na requisição, código de status HTTP: ' . $httpCode . ', resposta: ' . $response);
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Falha ao analisar a resposta JSON: ' . $response);
    }

    file_put_contents(BASEPATH . '/token.js', $data);

    return $data;
}

function sendSiegXml(string $jwt, string $oauthToken, string $xml, int $timeout = 30): array
{
    $url = 'https://api.sieg.com/api/v1/send-xml';

    $payload = json_encode(['Xml' => base64_encode($xml)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        throw new RuntimeException('Falha ao gerar o JSON do payload: ' . json_last_error_msg());
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $jwt,
            'X-API-Key: ' . $oauthToken,
        ],
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    unset($ch);

    if ($curlError) {
        throw new RuntimeException('Erro no cURL (send-xml): ' . $curlError);
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['raw' => $response];
    }

    return [
        'data' => $data,
        'code' => $httpCode
    ];
}

function loadSentFiles(string $file): array
{
    if (!is_file($file) || !is_readable($file)) {
        throw new RuntimeException("Arquivo não encontrado ou sem permissão de leitura: $file");
    }

    $sent = [];
    $handle = fopen($file, 'rb');
    if ($handle === false) {
        throw new RuntimeException("Não foi possível abrir o arquivo: $file");
    }

    try {
        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $name = basename($line);
            $sent[$name] = true;
        }
    } finally {
        fclose($handle);
    }

    return $sent;
}

$jwt = 'your_jwt_token';
$oAuthToken = 'your_api_key_or_o_auth_token';

// echo createSiegJwt('id', 'secret') . PHP_EOL;
// die;

$dir = BASEPATH . '/output/xml/Yets SP XML Outubro 2026/';

$totalFiles = 0;
$totalBytes = 0;
$min = PHP_INT_MAX;
$max = 0;

foreach (new DirectoryIterator($dir) as $f) {
    if ($f->isDot() || !$f->isFile()) continue;
    if (strtolower($f->getExtension()) !== 'xml') continue;

    $size = $f->getSize();
    $totalFiles++;
    $totalBytes += $size;
    if ($size < $min) $min = $size;
    if ($size > $max) $max = $size;
}

if ($totalFiles > 0) {
    printf(
        "Files: %d\nTotal: %.2f MB\nAvg: %.2f KB\nMin: %.2f KB\nMax: %.2f MB\n",
        $totalFiles,
        $totalBytes / 1048576,
        ($totalBytes / $totalFiles) / 1024,
        $min / 1024,
        $max / 1048576
    );
}

die;

$total = 0;
foreach (new DirectoryIterator($dir) as $f) {
    if ($f->isDot() || !$f->isFile()) continue;
    if (strtolower($f->getExtension()) !== 'xml') continue;
    $total++;
}

$sentFile = BASEPATH . '/output/processados/LISTA.txt';
$sent = loadSentFiles($sentFile);

$i = 0;

foreach (new DirectoryIterator($dir) as $file) {
    if ($file->isDot() || !$file->isFile()) {
        continue;
    }
    if (strtolower($file->getExtension()) !== 'xml') {
        continue;
    }

    $file->getSize();
    $path = $file->getPathname();
    $filename = $file->getFilename();

    if (isset($sent[$filename])) {
        $i++;
        // echo "[$i/$total] IGNORADO: $filename" . PHP_EOL;
        continue;
    }

    $xml = file_get_contents($path);
    if ($xml === false) {
        error_log("Failed to read: $path");
        continue;
    }

    $success['code'] = 0;

    $success = sendSiegXml($jwt, $oAuthToken, $xml);

    if ($success['code'] !== 200) {
        var_dump($success);
        die('Erro no XML ' . $filename . PHP_EOL);
    }

    file_put_contents(BASEPATH . '/output/processados/LISTA.txt', $filename . PHP_EOL, FILE_APPEND);

    $i++;

    echo sprintf(
        '[%s] Enviado NFe %s (%s de %s)' . PHP_EOL,
        new Datetime('now', new DateTimeZone('America/Sao_Paulo'))->format('Y-m-d H:i:s'),
        $filename,
        $i,
        $total
    );

    sleep(1);
}
