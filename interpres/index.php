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
    $fp = $_POST["fp"] ?? "";

    if (!empty($nomen)) {
        if ($actio == "intrare") {
            $resp = loqui_cum_daemonio("INTRARE|" . $nomen . "|" . $fp);
            $partes = explode("|", $resp);
            if ($partes[0] == "200") {
                $_SESSION["usor"] = $nomen;
                $_SESSION["fp"] = $fp;
                header("Location: fabulatio.php");
                exit();
            }
            else {
                $error = "Nomen vel Fingerprint mismatch! Accessus negatus.";
            }
        }
        elseif ($actio == "creare") {
            $resp = loqui_cum_daemonio("CREARE_USOREM|" . $nomen . "|" . $fp);
            $partes = explode("|", $resp);
            if ($partes[0] == "200") {
                $_SESSION["usor"] = $nomen;
                $_SESSION["fp"] = $fp;
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
            box-sizing: border-box; word-wrap: break-word; overflow-y: auto; max-height: 90vh;
        }

        h1 { font-size: 48px; text-shadow: 0 0 10px #00FF00; margin-bottom: 30px; margin-top: 0;}
        label { font-size: 24px; }
        
        input[type="text"], input[type="submit"] { 
            background-color: #000; color: #00FF00; border: 1px solid #00FF00; 
            font-family: 'VT323', "Courier New", Courier, monospace;
            font-size: 24px; padding: 10px; margin-top: 10px;
            box-shadow: inset 0 0 5px #00FF00; transition: all 0.2s;
            box-sizing: border-box;
            width: 100%;
        }
        
        input[type="submit"] {
            width: auto;
        }

        input[type="submit"]:hover, button:hover {
            background-color: #00FF00 !important; color: #000 !important; cursor: pointer;
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

        /* Mode Selector */
        .mode-selector { display: flex; border-bottom: 2px solid #00FF00; margin-bottom: 30px; }
        .mode-tab { flex: 1; padding: 10px; cursor: pointer; border: 1px solid transparent; transition: all 0.3s; font-size: 20px;}
        .mode-tab.active { background-color: #00FF00; color: #000; text-shadow: none; font-weight: bold;}
        .mode-tab:hover:not(.active) { background-color: #004400; }

        .hidden { display: none !important; }
        .error-msg { color: #ff3333; font-size: 18px; margin-bottom: 20px; text-shadow: 0 0 5px #ff0000; }

        .input-group { margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; margin-bottom: 5px; font-size: 20px; }
        .input-group input { width: 100%; box-sizing: border-box; }

        .form-footer { margin-top: 25px; font-size: 18px; display: flex; justify-content: space-between; align-items: center; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 0; }
        .checkbox-group input { cursor: pointer; width: 20px; height: 20px; margin: 0; }

        /* Secondary Modal */
        #forgot-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.9); z-index: 10000;
            display: none; justify-content: center; align-items: center;
        }
        .small-modal-content {
            border: 2px solid #00FF00; padding: 30px; background-color: #000;
            width: 400px; text-align: center; box-shadow: 0 0 20px #00FF00;
        }
    </style>
</head>
<body>
    <div id="necronomicon-modal">
        <div class="modal-content">
            <div class="glitch">* EX LIBRIS NECRONOMICON *</div>
            <div class="retro-text" id="typewriter"></div>
        </div>
    </div>

    <div class="container">
        <h1>Gateway - Necronomicon</h1>
        
        <div id="error-box" class="error-msg <?php echo $error ? '' : 'hidden'; ?>">
            <?php echo htmlspecialchars($error); ?>
        </div>

        <div class="mode-selector">
            <div id="tab-spiritus" class="mode-tab active" onclick="setMode('spiritus')">SPIRITUS (Guest)</div>
            <div id="tab-anima" class="mode-tab" onclick="setMode('anima')">ANIMA (Email)</div>
        </div>

        <!-- Mode: Spiritus (Legacy/Guest) -->
        <form id="form-spiritus" method="POST" action="index.php">
            <div class="input-group">
                <label>Nomen tuum (Nickname):</label>
                <input type="text" name="nomen" id="spiritus-name" placeholder="Vexillum..." autofocus>
            </div>
            <input type="hidden" name="fp" class="fp_input">
            <div style="margin-top: 20px;">
                <input type="submit" name="actio" value="intrare"> (Intrare)
                <input type="submit" name="actio" value="creare" style="margin-left: 10px;"> (Creare)
            </div>
        </form>

        <!-- Mode: Anima (Email/Password) -->
        <form id="form-anima" class="hidden">
            <div class="input-group" id="anima-nomen-group">
                <label>Nomen tuum (Username):</label>
                <input type="text" id="anima-nomen" placeholder="Viator...">
            </div>
            <div class="input-group">
                <label>Email:</label>
                <input type="text" id="anima-email" placeholder="email@spiritus.com">
            </div>
            <div class="input-group">
                <label>Password:</label>
                <input type="password" id="anima-pass" style="background-color: #000; color: #00FF00; border: 1px solid #00FF00; font-family: inherit; font-size: 24px; padding: 10px; width: 100%; box-sizing: border-box;">
            </div>

            <div class="form-footer">
                <label class="checkbox-group">
                    <input type="checkbox" id="anima-remember"> Memento Mei
                </label>
                <a href="javascript:void(0)" onclick="openForgotModal()">Oblivio (Forgot?)</a>
            </div>

            <div style="margin-top: 30px; display: flex; gap: 15px; justify-content: center;">
                <button type="button" onclick="handleAnimaAction('login_anima')" style="font-size: 24px; background: #000; color: #00FF00; border: 1px solid #00FF00; padding: 10px 20px; cursor: pointer; box-shadow: inset 0 0 5px #00FF00; transition: all 0.2s;">Intrare</button>
                <button type="button" onclick="handleAnimaAction('register_anima')" style="font-size: 24px; background: #000; color: #00FF00; border: 1px solid #00FF00; padding: 10px 20px; cursor: pointer; box-shadow: inset 0 0 5px #00FF00; transition: all 0.2s;">Creare</button>
            </div>
        </form>
    </div>

    <div id="forgot-modal">
        <div class="small-modal-content">
            <h2 style="margin-top: 0;">Recuperatio</h2>
            <p style="font-size: 20px; margin-bottom: 20px; text-transform: uppercase;">Haec functio in fabricatione est<br>(Feature in development)</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button type="button" onclick="closeForgotModal()" style="font-size: 20px; background: #000; color: #ff3333; border: 1px solid #ff3333; padding: 10px 20px; cursor: pointer;">Inducere (Close)</button>
            </div>
        </div>
    </div>

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

        function getFingerprint() {
            return btoa(navigator.userAgent + screen.width + "x" + screen.height + navigator.language + new Date().getTimezoneOffset());
        }

        function setMode(mode) {
            document.getElementById('tab-spiritus').classList.toggle('active', mode === 'spiritus');
            document.getElementById('tab-anima').classList.toggle('active', mode === 'anima');
            document.getElementById('form-spiritus').classList.toggle('hidden', mode === 'anima');
            document.getElementById('form-anima').classList.toggle('hidden', mode === 'spiritus');
            localStorage.setItem('login_mode', mode);
        }

        // Restore mode
        const savedMode = localStorage.getItem('login_mode') || 'spiritus';
        setMode(savedMode);

        // Sync FP inputs
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener("submit", function() {
                 let fpField = form.querySelector('.fp_input');
                 if (fpField) fpField.value = getFingerprint();
            });
        });

        // Special handling for the Spiritus form (legacy PHP submit)
        document.getElementById("form-spiritus").onsubmit = function() {
            document.querySelector('#form-spiritus .fp_input').value = getFingerprint();
        };

        function handleAnimaAction(action) {
            const email = document.getElementById('anima-email').value.trim();
            const pass = document.getElementById('anima-pass').value;
            const nomen = document.getElementById('anima-nomen').value.trim();
            const remember = document.getElementById('anima-remember').checked;
            const fp = getFingerprint();

            if (!email || !pass || (action === 'register_anima' && !nomen)) {
                showError("Vade Retro! Data desunt (Incomplete data).");
                return;
            }

            const formData = new URLSearchParams();
            formData.append('action', action);
            formData.append('email', email);
            formData.append('pass', pass);
            formData.append('nomen', nomen);
            formData.append('fp', fp);
            if (remember) formData.append('remember', '1');

            fetch('api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        location.href = 'fabulatio.php';
                    } else {
                        showError(data.message);
                    }
                })
                .catch(err => showError("Daemonium non respondet. Try again later."));
        }

        function showError(msg) {
            const box = document.getElementById('error-box');
            box.textContent = msg;
            box.classList.remove('hidden');
        }

        function openForgotModal() { document.getElementById('forgot-modal').style.display = 'flex'; }
        function closeForgotModal() { document.getElementById('forgot-modal').style.display = 'none'; }
    </script>
</body>
</html>
