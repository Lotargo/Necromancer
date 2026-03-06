<?php
session_start();

if (!isset($_SESSION["usor"])) {
    header("Location: index.php");
    exit();
}

$usor = $_SESSION["usor"];

function loqui_cum_daemonio($mandatum) {
    $fp = fsockopen("127.0.0.1", 8080, $errno, $errstr, 10);
    if (!$fp) {
        return "500|Error|Daemonium non respondet";
    }
    fwrite($fp, $mandatum . "\n");
    $responsum = fgets($fp, 4096);
    fclose($fp);
    return trim($responsum);
}

function invocare_oraculum($contextus, $interrogatio) {
    $apikey = getenv("OPENAI_API_KEY");
    if (!$apikey) {
        return "Clavis API deest. Dicent mihi Oraculum non respondere.";
    }

    $promptus = "Contextus: " . $contextus . "\nInterrogatio: " . $interrogatio . "\nResponde Latine.";

    $data = [
        "model" => "gpt-3.5-turbo",
        "messages" => [
            ["role" => "system", "content" => "Tu es philosophus Romanus. Responde semper Latine."],
            ["role" => "user", "content" => $promptus]
        ],
        "max_tokens" => 150
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apikey
    ]);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return "Error Oraculi: " . curl_error($ch);
    }
    curl_close($ch);

    $json = json_decode($result, true);
    if (isset($json["choices"][0]["message"]["content"])) {
        return $json["choices"][0]["message"]["content"];
    } else {
        return "Oraculum mutum est.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["exire"])) {
        session_destroy();
        header("Location: index.php");
        exit();
    }

    if (!empty(trim($_POST["nuntius"]))) {
        $nuntius = trim($_POST["nuntius"]);

        // Add User Message
        loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|Tute: " . $nuntius);

        // RAG: Query Knowledge Base
        $rag_resp = loqui_cum_daemonio("INVESTIGARE|" . $nuntius);
        $partes_rag = explode("|", $rag_resp);
        $contextus = "";
        if ($partes_rag[0] == "200") {
            $contextus = $partes_rag[2];
        }

        // LLM Call
        $responsum_oraculi = invocare_oraculum($contextus, $nuntius);

        // Add LLM Message
        $responsum_oraculi = str_replace(array("\r", "\n"), " ", $responsum_oraculi);
        loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|Oraculum: " . $responsum_oraculi);

        header("Location: fabulatio.php");
        exit();
    }
}

$resp_historia = loqui_cum_daemonio("LEGENDE_NUNTIOS|" . $usor);
$partes_hist = explode("|", $resp_historia, 3);
$historia = "";
if ($partes_hist[0] == "200") {
    $historia = $partes_hist[2];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fabulatio</title>
    <style>
        body { background-color: black; color: #00FF00; font-family: "Courier New", Courier, monospace; }
        input[type="text"], input[type="submit"] { background-color: black; color: #00FF00; border: 1px solid #00FF00; padding: 5px; }
        #chat { width: 80%; height: 400px; border: 1px solid #00FF00; overflow-y: scroll; padding: 10px; white-space: pre-wrap; margin-bottom: 10px; }
        .blink { animation: blink-animation 1s steps(5, start) infinite; -webkit-animation: blink-animation 1s steps(5, start) infinite; }
        @keyframes blink-animation { to { visibility: hidden; } }
        @-webkit-keyframes blink-animation { to { visibility: hidden; } }
    </style>
    <meta http-equiv="refresh" content="10"> <!-- Auto refresh every 10 seconds -->
</head>
<body>
    <h1>Forum: <?php echo htmlspecialchars($usor); ?> <span class="blink">_</span></h1>
    <div id="chat"><?php
if ($historia) {
    echo htmlspecialchars(str_replace('\n', "\n", $historia));
} else {
    echo "Nihil scriptum est...";
}
?></div>

    <form method="POST" action="fabulatio.php">
        <label>Dicent:</label><br>
        <input type="text" name="nuntius" size="80" autofocus autocomplete="off">
        <input type="submit" value="Mittere (Send)">
    </form>
    <br>
    <form method="POST" action="fabulatio.php">
        <input type="submit" name="exire" value="Exire (Logout)">
    </form>
</body>
</html>
