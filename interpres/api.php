<?php
session_start();
if (!isset($_SESSION["usor"])) {
    http_response_code(401);
    exit("Unauthorized");
}
$usor = $_SESSION["usor"];

function loqui_cum_daemonio($mandatum)
{
    $daemonium_host = getenv("DAEMONIUM_HOST") ?: "127.0.0.1";
    $fp = @fsockopen($daemonium_host, 8080, $errno, $errstr, 10);
    if (!$fp)
        return "500|Error|Daemonium non respondet";
    fwrite($fp, $mandatum . "\n");
    $responsum = fgets($fp, 8192);
    fclose($fp);
    return trim($responsum);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$cubiculum = $_GET['room'] ?? $_POST['room'] ?? 'default';

if ($action === 'list') {
    $resp = loqui_cum_daemonio("INDEX_FABULATIONUM|" . $usor);
    $partes = explode("|", $resp, 3);
    $rooms = [];
    if ($partes[0] == "200") {
        $rooms = explode(",", $partes[2]);
    }
    header('Content-Type: application/json');
    echo json_encode(["rooms" => $rooms]);
    exit();
}

if ($action === 'load') {
    $resp = loqui_cum_daemonio("LEGENDE_NUNTIOS|" . $usor . "|" . $cubiculum);
    $partes = explode("|", $resp, 3);
    $historia = ($partes[0] == "200") ? $partes[2] : "";
    header('Content-Type: text/plain');
    echo str_replace('\n', "\n", $historia);
    exit();
}

if ($action === 'delete') {
    loqui_cum_daemonio("DELE_FABULATIONEM|" . $usor . "|" . $cubiculum);
    header('Content-Type: application/json');
    echo json_encode(["status" => "ok"]);
    exit();
}

if ($action === 'send') {
    $nuntius = trim($_POST['nuntius'] ?? '');
    if (empty($nuntius))
        exit();

    // 1. Save user message
    loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|Tute: " . $nuntius);

    // 2. Query Knowledge Base (RAG)
    $rag_resp = loqui_cum_daemonio("INVESTIGARE|" . $nuntius);
    $partes_rag = explode("|", $rag_resp);
    $contextus = ($partes_rag[0] == "200") ? $partes_rag[2] : "";

    // 3. Prepare LLM Stream
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    while (ob_get_level() > 0)
        ob_end_flush();

    $apikey = getenv("OPENAI_API_KEY");
    if (!$apikey) {
        $msg = "Clavis API deest. Dicent mihi Oraculum non respondere.";
        echo "data: " . json_encode(["choices" => [["delta" => ["content" => $msg]]]]) . "\n\n";
        loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|Oraculum: " . $msg);
        exit();
    }

    $promptus = "Contextus: " . $contextus . "\nInterrogatio: " . $nuntius . "\nResponde Latine.";
    $model = getenv("OPENAI_API_MODEL") ?: "gpt-3.5-turbo";

    $data = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => "Tu es philosophus Romanus. Responde semper Latine."],
            ["role" => "user", "content" => $promptus]
        ],
        "max_tokens" => 300,
        "stream" => true
    ];

    $api_url = getenv("OPENAI_API_URL") ?: "https://api.openai.com/v1/chat/completions";
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apikey
    ]);

    $full_response = "";
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$full_response) {
        echo $chunk;
        flush();
        $lines = explode("\n", $chunk);
        foreach ($lines as $line) {
            if (strpos($line, 'data: ') === 0) {
                $jsonStr = substr($line, 6);
                if (trim($jsonStr) == '[DONE]')
                    continue;
                $json = json_decode($jsonStr, true);
                if (isset($json['choices'][0]['delta']['content'])) {
                    $full_response .= $json['choices'][0]['delta']['content'];
                }
            }
        }
        return strlen($chunk);
    });

    curl_exec($ch);
    if (curl_errno($ch)) {
        $err = "Error Oraculi: " . curl_error($ch);
        echo "data: " . json_encode(["choices" => [["delta" => ["content" => $err]]]]) . "\n\n";
        $full_response .= $err;
    }
    curl_close($ch);

    // Save bot response
    $clean_resp = str_replace(["\r", "\n"], " ", trim($full_response));
    if (empty($clean_resp))
        $clean_resp = "Oraculum mutum est.";
    loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|Oraculum: " . $clean_resp);
    exit();
}
