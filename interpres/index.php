<?php
session_start();

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

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nomen = trim($_POST["nomen"]);
    $actio = $_POST["actio"];

    if (!empty($nomen)) {
        if ($actio == "intrare") {
            $resp = loqui_cum_daemonio("INTRARE|" . $nomen);
            $partes = explode("|", $resp);
            if ($partes[0] == "200") {
                $_SESSION["usor"] = $nomen;
                header("Location: fabulatio.php");
                exit();
            }
            else {
                $error = "Nomen non inventum. Creare novum?";
            }
        }
        elseif ($actio == "creare") {
            $resp = loqui_cum_daemonio("CREARE_USOREM|" . $nomen);
            $partes = explode("|", $resp);
            if ($partes[0] == "200") {
                $_SESSION["usor"] = $nomen;
                header("Location: fabulatio.php");
                exit();
            }
            else {
                $error = "Nomen iam exstat.";
            }
        }
    }
    else {
        $error = "Nomen vacuum est.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Porta Introitus</title>
    <style>
        body { background-color: black; color: #00FF00; font-family: "Courier New", Courier, monospace; margin: 0; padding: 20px; }
        input[type="text"], input[type="submit"] { background-color: black; color: #00FF00; border: 1px solid #00FF00; }
        a { color: #00FF00; }
        
        /* Modal Styles */
        #necronomicon-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: #000; z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            opacity: 1; transition: opacity 0.5s ease;
        }
        .modal-content {
            border: 4px double #00FF00; padding: 20px 40px; text-align: center;
            background-color: #050505; max-width: 600px;
            box-shadow: 0 0 15px #00FF00 inset;
        }
        .retro-text {
            font-size: 24px; font-weight: bold; text-transform: uppercase;
            overflow: hidden; white-space: pre-wrap; margin: 0 auto;
            border-right: .15em solid #00FF00; 
            animation: blink-caret .75s step-end infinite;
        }
        @keyframes blink-caret { from, to { border-color: transparent } 50% { border-color: #00FF00; } }
        /* Glitch effect on header */
        .glitch { font-size: 30px; font-weight: bold; text-shadow: 2px 2px #0f0; margin-bottom: 20px;}
    </style>
</head>
<body>
    <div id="necronomicon-modal">
        <div class="modal-content">
            <div class="glitch">* EX LIBRIS NECRONOMICON *</div>
            <div class="retro-text" id="typewriter"></div>
        </div>
    </div>

    <h1>Salve Viator!</h1>
    <?php if ($error)
    echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST" action="index.php">
        <label>Nomen tuum:</label><br>
        <input type="text" name="nomen" autofocus><br><br>
        <input type="submit" name="actio" value="intrare"> (Intrare)<br><br>
        <input type="submit" name="actio" value="creare"> (Creare)
    </form>

    <script>
        // Retro Typewriter Logic
        const textToType = "EVOCATIO SPIRITUUM...\nAPERITUR PORTA...\n\nSISTE VIATOR ET INGREDIRE.";
        const typewriterEl = document.getElementById("typewriter");
        const modalEl = document.getElementById("necronomicon-modal");
        let i = 0;
        const typingSpeed = 30; // ms per char (fast for ~1-2 sec total)

        function typeWriter() {
            if (i < textToType.length) {
                typewriterEl.innerHTML += textToType.charAt(i) === '\n' ? '<br/>' : textToType.charAt(i);
                i++;
                setTimeout(typeWriter, typingSpeed);
            } else {
                // Wait briefly after typing, then fade out (Total ~3s max)
                setTimeout(closeModal, 1000);
            }
        }

        function closeModal() {
            modalEl.style.opacity = '0';
            setTimeout(() => { modalEl.style.display = 'none'; }, 500); // Wait for transition
        }

        // Start animation immediately
        window.onload = () => {
            // Check if user came from inside we could skip it using sessionstorage, but for now show it
            if (!sessionStorage.getItem('introShown')) {
                typeWriter();
                sessionStorage.setItem('introShown', 'true');
            } else {
                modalEl.style.display = 'none'; // Skip if already seen this session
            }
        };

        // click to skip
        modalEl.addEventListener('click', closeModal);
    </script>
</body>
</html>
