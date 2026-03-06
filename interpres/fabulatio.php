<?php
session_start();

if (!isset($_SESSION["usor"])) {
    header("Location: index.php");
    exit();
}

$usor = $_SESSION["usor"];

function loqui_cum_daemonio($mandatum)
{
    $daemonium_host = getenv("DAEMONIUM_HOST") ?: "127.0.0.1";
    $fp = fsockopen($daemonium_host, 8080, $errno, $errstr, 10);
    if (!$fp) {
        return "500|Error|Daemonium non respondet";
    }
    fwrite($fp, $mandatum . "\n");
    $responsum = fgets($fp, 4096);
    fclose($fp);
    return trim($responsum);
}

function invocare_oraculum($contextus, $interrogatio)
{
    $apikey = getenv("OPENAI_API_KEY");
    if (!$apikey) {
        return "Clavis API deest. Dicent mihi Oraculum non respondere.";
    }

    $promptus = "Contextus: " . $contextus . "\nInterrogatio: " . $interrogatio . "\nResponde Latine.";

    $model = getenv("OPENAI_API_MODEL") ?: "gpt-3.5-turbo";
    $data = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => "Tu es philosophus Romanus. Responde semper Latine."],
            ["role" => "user", "content" => $promptus]
        ],
        "max_tokens" => 300
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

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return "Error Oraculi: " . curl_error($ch);
    }
    curl_close($ch);

    $json = json_decode($result, true);
    if (isset($json["choices"][0]["message"]["content"])) {
        return $json["choices"][0]["message"]["content"];
    }
    else {
        return "Oraculum mutum est. (Codex: " . htmlspecialchars($result) . ")";
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
        @import url('https://fonts.googleapis.com/css2?family=VT323&display=swap');

        body { 
            background-color: #050505; color: #00FF00; 
            font-family: 'VT323', "Courier New", Courier, monospace; 
            margin: 0; padding: 20px;
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh;
        }

        body::after {
            content: " "; display: block; position: fixed; top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            z-index: 99; background-size: 100% 2px, 3px 100%; pointer-events: none;
        }

        .container {
            width: 90%; max-width: 1000px;
            border: 2px solid #00FF00; padding: 30px; 
            box-shadow: 0 0 20px #00FF00, inset 0 0 10px #00FF00;
            background-color: #000; position: relative; z-index: 10;
        }

        h1 { font-size: 36px; text-shadow: 0 0 5px #00FF00; margin-top: 0;}
        
        input[type="text"], input[type="submit"] { 
            background-color: #000; color: #00FF00; border: 1px solid #00FF00; padding: 10px; 
            font-family: 'VT323', "Courier New", Courier, monospace; font-size: 24px;
        }
        input[type="submit"]:hover { background-color: #00FF00; color: #000; cursor: pointer; }
        
        #chat { 
            width: 100%; height: 50vh; border: 1px solid #00FF00; overflow-y: auto; 
            padding: 15px; white-space: pre-wrap; margin-bottom: 20px;
            box-sizing: border-box; font-size: 22px;
            box-shadow: inset 0 0 10px #00FF00;
        }
        
        .blink { animation: blink-animation 1s steps(5, start) infinite; -webkit-animation: blink-animation 1s steps(5, start) infinite; }
        @keyframes blink-animation { to { visibility: hidden; } }
        @-webkit-keyframes blink-animation { to { visibility: hidden; } }

        /* Welcome Modal Styles */
        #welcome-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: #000; z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            opacity: 1; transition: opacity 0.5s ease;
        }
        .welcome-content {
            text-align: center; color: #00FF00;
        }
        .welcome-text {
            font-size: 36px; font-weight: bold; overflow: hidden; white-space: pre-wrap; margin: 0 auto;
            border-right: .15em solid #00FF00; animation: blink-caret .75s step-end infinite;
        }
    </style>
    <meta http-equiv="refresh" content="10"> <!-- Auto refresh every 10 seconds -->
</head>
<body>
    <div id="welcome-modal">
        <div class="welcome-content">
            <div class="welcome-text" id="welcome-typewriter"></div>
        </div>
    </div>

    <div class="container">
        <h1>Forum: <?php echo htmlspecialchars($usor); ?> <span class="blink">_</span></h1>
        <div id="chat"><?php
if ($historia) {
    echo htmlspecialchars(str_replace('\n', "\n", $historia));
}
else {
    echo "Nihil scriptum est...";
}
?></div>

        <form method="POST" action="fabulatio.php" style="display: flex; gap: 10px; margin-bottom: 10px;">
            <input type="text" name="nuntius" style="flex-grow: 1;" autofocus autocomplete="off" placeholder="Dicent...">
            <input type="submit" value="Mittere (Send)">
        </form>
        
        <form method="POST" action="fabulatio.php" style="text-align: right;">
            <input type="submit" name="exire" value="Exire (Logout)">
        </form>
    </div>

    <script>
        const chatEl = document.getElementById("chat");
        chatEl.scrollTop = chatEl.scrollHeight; // Auto-scroll to bottom

        // Welcome Animation Logic
        const welcomeText = "CONEXIO STABILITA...\nSALVE, <?php echo htmlspecialchars($usor); ?>.\nORACULUM TE EXSPECTAT.";
        const typeEl = document.getElementById("welcome-typewriter");
        const modalEl = document.getElementById("welcome-modal");
        let i = 0;

        function typeWriterWelcome() {
            if (i < welcomeText.length) {
                typeEl.innerHTML += welcomeText.charAt(i) === '\n' ? '<br/>' : welcomeText.charAt(i);
                i++;
                setTimeout(typeWriterWelcome, 40);
            } else {
                setTimeout(closeWelcomeModal, 800);
            }
        }

        function closeWelcomeModal() {
            modalEl.style.opacity = '0';
            setTimeout(() => { modalEl.style.display = 'none'; }, 500);
        }

        window.onload = () => {
            const sessionKey = 'welcomeShown_<?php echo htmlspecialchars($usor); ?>';
            if (!sessionStorage.getItem(sessionKey)) {
                typeWriterWelcome();
                sessionStorage.setItem(sessionKey, 'true');
            } else {
                modalEl.style.display = 'none';
            }
        };

        modalEl.addEventListener('click', closeWelcomeModal);
    </script>
</body>
</html>
