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
function sani($val) {
    if (is_array($val)) {
        return array_map('sani', $val);
    }
    return str_replace(['|', "\r", "\n"], '', $val);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nomen = sani(trim($_POST["nomen"]));
    $actio = sani($_POST["actio"]);
    $fp = sani($_POST["fp"] ?? "");

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
        
        #bgCanvas {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: 0; pointer-events: none;
        }

        body { 
            background-color: #010501; color: #1aff66; 
            font-family: 'VT323', "Courier New", Courier, monospace; 
            margin: 0; padding: 0;
            display: flex; justify-content: center; align-items: center;
            height: 100vh; overflow: hidden;
        }
        
        /* CRT Scanline and Vignette Effect */
        body::after {
            content: " "; display: block; position: absolute; top: 0; left: 0; bottom: 0; right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.18) 50%), 
                        radial-gradient(circle, rgba(0,0,0,0) 60%, rgba(0,0,0,0.7) 100%);
            z-index: 99; background-size: 100% 3px, 100% 100%; pointer-events: none;
            opacity: 0.9;
        }
        
        .container {
            border: 2px solid #1aff66; padding: 40px; box-shadow: 0 0 20px #1aff66, inset 0 0 20px #1aff66;
            background-color: rgba(2, 8, 2, 0.85); z-index: 1; text-align: center;
            width: 80%; max-width: 600px; position: relative;
            box-sizing: border-box; word-wrap: break-word; overflow-y: auto; max-height: 90vh;
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            border-radius: 8px;
        }

        h1 { font-size: 48px; text-shadow: 0 0 10px #1aff66; margin-bottom: 30px; margin-top: 0;}
        label { font-size: 24px; }
        
        input[type="text"], input[type="password"], input[type="submit"], button { 
            background-color: rgba(0, 0, 0, 0.6); color: #1aff66; border: 1px solid #1aff66; 
            font-family: 'VT323', "Courier New", Courier, monospace;
            font-size: 24px; padding: 10px; margin-top: 10px;
            box-shadow: inset 0 0 5px rgba(26, 255, 102, 0.3); transition: all 0.2s ease-in-out;
            box-sizing: border-box;
            width: 100%;
            border-radius: 4px;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            box-shadow: 0 0 12px #1aff66, inset 0 0 8px #1aff66;
        }
        
        input[type="submit"], button {
            width: auto;
        }

        input[type="submit"]:hover, button:hover {
            background-color: #1aff66 !important; color: #010501 !important; cursor: pointer;
            box-shadow: 0 0 15px #1aff66;
        }

        a { color: #1aff66; }
        
        /* Modal Styles */
        #necronomicon-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: #010501; z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            opacity: 1; transition: opacity 0.5s ease;
        }
        @keyframes modal-twitch {
            0% { transform: translate(0); box-shadow: 0 0 25px #1aff66 inset; }
            2% { transform: translate(-2px, 1px); box-shadow: -2px 0 10px #f00, 2px 0 10px #00f, 0 0 25px #1aff66 inset; }
            4% { transform: translate(2px, -1px); box-shadow: 2px 0 10px #f00, -2px 0 10px #00f, 0 0 25px #1aff66 inset; }
            6% { transform: translate(0); box-shadow: 0 0 25px #1aff66 inset; }
            100% { transform: translate(0); box-shadow: 0 0 25px #1aff66 inset; }
        }

        .modal-content {
            border: 4px double #1aff66; padding: 40px; text-align: center;
            background-color: #020802; max-width: 800px;
            box-shadow: 0 0 25px #1aff66 inset;
            animation: modal-twitch 3s infinite linear;
        }
        .retro-text {
            font-size: 32px; font-weight: bold; text-transform: uppercase;
            overflow: hidden; white-space: pre-wrap; margin: 0 auto;
            border-right: .15em solid #1aff66; 
            animation: blink-caret .75s step-end infinite;
            text-align: left;
        }
        @keyframes blink-caret { from, to { border-color: transparent } 50% { border-color: #1aff66; } }
        /* Glitch effect on header */
        .glitch { font-size: 42px; font-weight: bold; text-shadow: 2px 2px #0f0, -2px -2px #f00; margin-bottom: 30px; letter-spacing: 5px;}

        /* Mode Selector */
        .mode-selector { display: flex; border-bottom: 2px solid #1aff66; margin-bottom: 30px; }
        .mode-tab { flex: 1; padding: 10px; cursor: pointer; border: 1px solid transparent; transition: all 0.3s; font-size: 20px;}
        .mode-tab.active { background-color: #1aff66; color: #010501; text-shadow: none; font-weight: bold;}
        .mode-tab:hover:not(.active) { background-color: #063b15; }

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
        @keyframes small-modal-twitch {
            0% { transform: translate(0); box-shadow: 0 0 20px #1aff66; }
            1% { transform: translate(-3px, 2px); box-shadow: -3px 0 15px #f00, 3px 0 15px #00f, 0 0 20px #1aff66; }
            2% { transform: translate(3px, -2px); box-shadow: 3px 0 15px #f00, -3px 0 15px #00f, 0 0 20px #1aff66; }
            3% { transform: translate(0); box-shadow: 0 0 20px #1aff66; }
            100% { transform: translate(0); box-shadow: 0 0 20px #1aff66; }
        }
        .small-modal-content {
            border: 2px solid #1aff66; padding: 30px; background-color: #010501;
            width: 400px; text-align: center; box-shadow: 0 0 20px #1aff66;
            animation: small-modal-twitch 4s infinite linear;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <canvas id="bgCanvas"></canvas>
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
            
            const errorBox = document.getElementById('error-box');
            if (errorBox) {
                errorBox.textContent = '';
                errorBox.classList.add('hidden');
            }
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

        // Background Canvas Occult Circles
        const bgCanvas = document.getElementById('bgCanvas');
        const bgCtx = bgCanvas.getContext('2d');
        bgCanvas.width = window.innerWidth;
        bgCanvas.height = window.innerHeight;

        window.addEventListener('resize', () => {
            bgCanvas.width = window.innerWidth;
            bgCanvas.height = window.innerHeight;
        });

        let bgOccultAngle = 0;
        function drawBgOccult() {
            requestAnimationFrame(drawBgOccult);
            const w = bgCanvas.width;
            const h = bgCanvas.height;
            bgCtx.clearRect(0, 0, w, h);

            bgCtx.save();
            bgCtx.translate(w / 2, h / 2);
            bgCtx.rotate(bgOccultAngle);
            bgOccultAngle += 0.0008;

            const mainC = '#1aff66';
            bgCtx.shadowBlur = 15;
            bgCtx.shadowColor = mainC;
            bgCtx.strokeStyle = mainC;
            bgCtx.fillStyle = mainC;
            bgCtx.lineWidth = 1.5;

            // 1. Внешнее кольцо
            bgCtx.beginPath();
            bgCtx.arc(0, 0, 250, 0, Math.PI * 2);
            bgCtx.stroke();

            // 2. Внутреннее кольцо
            bgCtx.lineWidth = 2.5;
            bgCtx.beginPath();
            bgCtx.arc(0, 0, 210, 0, Math.PI * 2);
            bgCtx.stroke();
            bgCtx.lineWidth = 1;

            // 3. Декоративное кольцо со штрихами
            bgCtx.beginPath();
            bgCtx.arc(0, 0, 180, 0, Math.PI * 2);
            bgCtx.stroke();
            
            bgCtx.lineWidth = 0.5;
            for (let a = 0; a < Math.PI * 2; a += Math.PI / 30) {
                const cos = Math.cos(a);
                const sin = Math.sin(a);
                bgCtx.beginPath();
                bgCtx.moveTo(180 * cos, 180 * sin);
                bgCtx.lineTo(210 * cos, 210 * sin);
                bgCtx.stroke();
            }
            bgCtx.lineWidth = 1;

            // 4. Семиконечная звезда
            const numPoints = 7;
            const points = [];
            for (let i = 0; i < numPoints; i++) {
                const a = (i * Math.PI * 2) / numPoints - Math.PI / 2;
                points.push({ x: 180 * Math.cos(a), y: 180 * Math.sin(a) });
            }
            
            bgCtx.beginPath();
            let curr = 0;
            bgCtx.moveTo(points[curr].x, points[curr].y);
            for (let i = 0; i < numPoints; i++) {
                curr = (curr + 3) % numPoints;
                bgCtx.lineTo(points[curr].x, points[curr].y);
            }
            bgCtx.closePath();
            bgCtx.stroke();

            points.forEach(p => {
                bgCtx.beginPath();
                bgCtx.arc(p.x, p.y, 4, 0, Math.PI * 2);
                bgCtx.fill();
            });

            // 5. Древние руны по кругу
            const runes = ['ᚠ', 'ᚢ', 'ᚦ', 'ᚨ', 'ᚱ', 'ᚲ', 'ᚷ', 'ᚹ', 'ᚺ', 'ᚾ', 'ᛁ', 'ᛃ', 'ᛇ', 'ᛈ', 'ᛉ', 'ᛊ', 'ᛏ', 'ᛒ', 'ᛖ', 'ᛗ', 'ᛚ', 'ᛜ', 'ᛞ', 'ᛟ'];
            bgCtx.font = '18px monospace';
            bgCtx.textAlign = 'center';
            bgCtx.textBaseline = 'middle';
            
            runes.forEach((rune, index) => {
                const a = (index * Math.PI * 2) / runes.length - Math.PI / 2;
                bgCtx.save();
                bgCtx.rotate(a);
                bgCtx.fillText(rune, 0, -230);
                bgCtx.restore();
            });

            bgCtx.restore();
        }
        drawBgOccult();
    </script>
</body>
</html>
