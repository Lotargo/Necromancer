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
        @import url('https://fonts.googleapis.com/css2?family=VT323&display=swap');
        
        body { 
            background-color: #050505; color: #00FF00; 
            font-family: 'VT323', "Courier New", Courier, monospace; 
            margin: 0; padding: 0;
            display: flex; justify-content: center; align-items: center;
            height: 100vh; overflow: hidden;
        }
        
        /* CRT Scanline Effect */
        body::after {
            content: " "; display: block; position: absolute; top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            z-index: 2; background-size: 100% 2px, 3px 100%; pointer-events: none;
        }
        
        .container {
            border: 2px solid #00FF00; padding: 40px; box-shadow: 0 0 20px #00FF00, inset 0 0 20px #00FF00;
            background-color: #000; z-index: 1; text-align: center;
            width: 80%; max-width: 600px; position: relative;
        }

        h1 { font-size: 48px; text-shadow: 0 0 10px #00FF00; margin-bottom: 30px;}
        label { font-size: 24px; }
        
        input[type="text"], input[type="submit"] { 
            background-color: #000; color: #00FF00; border: 1px solid #00FF00; 
            font-family: 'VT323', "Courier New", Courier, monospace;
            font-size: 24px; padding: 10px; margin-top: 10px;
            box-shadow: inset 0 0 5px #00FF00; transition: all 0.2s;
        }
        
        input[type="submit"]:hover {
            background-color: #00FF00; color: #000; cursor: pointer;
        }

        a { color: #00FF00; }
        
        /* Modal Styles */
        #necronomicon-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: #000; z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            opacity: 1; transition: opacity 0.5s ease;
        }
        .modal-content {
            border: 4px double #00FF00; padding: 40px; text-align: center;
            background-color: #050505; max-width: 800px;
            box-shadow: 0 0 25px #00FF00 inset;
        }
        .retro-text {
            font-size: 32px; font-weight: bold; text-transform: uppercase;
            overflow: hidden; white-space: pre-wrap; margin: 0 auto;
            border-right: .15em solid #00FF00; 
            animation: blink-caret .75s step-end infinite;
            text-align: left;
        }
        @keyframes blink-caret { from, to { border-color: transparent } 50% { border-color: #00FF00; } }
        /* Glitch effect on header */
        .glitch { font-size: 42px; font-weight: bold; text-shadow: 2px 2px #0f0, -2px -2px #f00; margin-bottom: 30px; letter-spacing: 5px;}
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
            typeWriter();
        };

        // click to skip
        modalEl.addEventListener('click', closeModal);
    </script>
</body>
</html>
