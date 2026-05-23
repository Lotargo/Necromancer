<?php
session_start();

if (!isset($_SESSION["usor"])) {
    header("Location: index.php");
    exit();
}

$usor = $_SESSION["usor"];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["exire"])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fabulatio - Necronomicon</title>

    <!-- Markdown Parser: Marked.js -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <!-- HTML Sanitizer: DOMPurify -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
    
    <!-- Math Renderer: KaTeX -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/katex.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.8/dist/contrib/auto-render.min.js"></script>

    <link rel="stylesheet" href="css/packages/common.css">
    <link rel="stylesheet" href="css/packages/layout.css">
    <link rel="stylesheet" href="css/packages/chat.css">
    <link rel="stylesheet" href="css/packages/components.css">
    <link rel="stylesheet" href="css/packages/modals.css">
    <link rel="stylesheet" href="css/packages/effects.css">
</head>
<body>

    <div class="guest-pacman"></div>
    <div class="guest-mk2"></div>

    <canvas id="matrixCanvas"></canvas>
    <canvas id="advCanvas"></canvas>
    <div id="welcome-modal">
        <div class="welcome-content">
            <div class="welcome-text" id="welcome-typewriter"></div>
        </div>
    </div>


    <!-- Rename Chat Modal -->
    <div id="rename-chat-modal">
        <div class="rename-chat-content">
            <h2 style="margin-top: 0; text-shadow: 0 0 5px #00FF00;">Renominare Fabulationem</h2>
            <p style="font-size: 24px;">Novum nomen pro: <span id="rename-room-name" style="color:#ffff00;"></span>?</p>
            <input type="text" id="rename-chat-input" autocomplete="off" onkeydown="if(event.key === 'Enter') submitRenameChat()">
            <div style="margin-top: 20px;">
                <button onclick="submitRenameChat()" style="margin-right: 15px;">Renominare (Rename)</button>
                <button onclick="closeRenameModal()" class="cancel-btn">Inducere (Cancel)</button>
            </div>
        </div>
    </div>

    <!-- Delete Chat Modal -->
    <div id="delete-chat-modal">
        <div class="delete-chat-content">
            <h2 style="margin-top: 0; color: #ff0000; text-shadow: 0 0 5px #ff0000;">Delere Fabulationem</h2>
            <p style="font-size: 24px; color: #ff3333;">Visne vere delere fabulationem: <span id="delete-room-name" style="color:#ffff00;"></span>?</p>
            <div style="margin-top: 20px;">
                <button onclick="submitDeleteChat()" class="cancel-btn" style="margin-right: 15px;">Ita, Delere (Yes, Delete)</button>
                <button onclick="closeDeleteModal()">Inducere (Cancel)</button>
            </div>
        </div>
    </div>

    <!-- Configuration Modal -->
    <div id="config-modal">
        <div class="config-content">
            <h2 style="margin-top: 0; text-shadow: 0 0 5px #00FF00; text-align: center;">Configuratio Rationis (Account Settings)</h2>
            <div id="config-alert" style="color: #ffff00; text-align: center; margin-bottom: 15px; font-weight: bold;"></div>

            <div class="config-section" id="level-display" style="display: none;">
                <!-- Level system hidden -->
            </div>

            <div class="config-section">
                <h3>Thema et Effectus (Theme & Effects)</h3>
                <select id="config-theme" class="config-select" onchange="previewTheme()">
                    <option value="0">0. Viridis (Classic Green)</option>
                    <!-- More options populated via JS -->
                </select>

                <div id="glitches-container" style="margin-top: 15px;">
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-10" onchange="previewGlitches()"> Terraemotus (Shake)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-11" onchange="previewGlitches()"> Aberratio (Chromatic)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-12" onchange="previewGlitches()"> Fines fracti (Broken Borders)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-13" onchange="previewGlitches()"> Caligo (CRT Noise)</label>
                    <hr style="border-color: var(--dim-color); margin: 10px 0;">
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-20" onchange="previewGlitches()"> Lineae Cathodicae (CRT Scanlines)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-21" onchange="previewGlitches()"> Tenebrae Spirantes (Breathing Shadows)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-22" onchange="previewGlitches()"> Imber Codicis (Matrix Rain)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-23" onchange="previewGlitches()"> Ignis Fatuus (Necromantic Embers)</label>
                    <hr style="border-color: var(--dim-color); margin: 10px 0;">
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-24" onchange="previewGlitches()"> Sanguis Stillans (Dripping Blood)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-25" onchange="previewGlitches()"> Astrum Cadens (Space Flight)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-26" onchange="previewGlitches()"> Oculi Vigilantes (Watching Eyes)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-27" onchange="previewGlitches()"> Reticulum Araneae (Interactive Web)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-28" onchange="previewGlitches()"> Pulsus Infernalis (Infernal Pulse)</label>
                    <hr style="border-color: var(--dim-color); margin: 10px 0;">
                    <h4 style="margin: 5px 0; color: #aaa;">Hospites (Guests / Characters)</h4>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-31" onchange="previewGlitches()"> Hospes: Pac-Man (Chased in the abyss)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-32" onchange="previewGlitches()"> Hospes: Toasty (MK2 pop-up guy)</label>
                    <hr style="border-color: var(--dim-color); margin: 10px 0;">
                    <h4 style="margin: 5px 0; color: #aaa; font-family: 'Orbitron', sans-serif;">Soni (Sounds)</h4>
                    <!-- Master Volume -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; padding: 8px 12px; border: 1px solid var(--dim-color); border-radius: 6px; background: rgba(0,0,0,0.3); box-shadow: inset 0 0 5px rgba(0,0,0,0.5); min-height: 38px; box-sizing: border-box;">
                        <span style="color: var(--warn-color); font-size: 11px; font-family: 'Orbitron', sans-serif; white-space: nowrap; flex: 1; min-width: 0; display: flex; justify-content: space-between; align-items: center; margin-right: 4px;">
                            <span>Summa (Master):</span>
                            <span id="volume-master-val" style="color: #fff; font-weight: bold; margin-left: 4px;">80%</span>
                        </span>
                        <input type="range" id="volume-master" min="0" max="200" value="80" style="width: 40%; max-width: 120px; min-width: 70px; flex-shrink: 0; cursor: pointer; accent-color: var(--main-color);" oninput="updateVolumeLabel('master', this.value)" onchange="saveVisualOptions(true)">
                    </div>
                    <!-- SFX Volume -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; padding: 8px 12px; border: 1px solid var(--dim-color); border-radius: 6px; background: rgba(0,0,0,0.3); box-shadow: inset 0 0 5px rgba(0,0,0,0.5); min-height: 38px; box-sizing: border-box;">
                        <span style="color: var(--warn-color); font-size: 11px; font-family: 'Orbitron', sans-serif; white-space: nowrap; flex: 1; min-width: 0; display: flex; justify-content: space-between; align-items: center; margin-right: 4px;">
                            <span>Sonus (SFX):</span>
                            <span id="volume-sfx-val" style="color: #fff; font-weight: bold; margin-left: 4px;">80%</span>
                        </span>
                        <input type="range" id="volume-sfx" min="0" max="200" value="80" style="width: 40%; max-width: 120px; min-width: 70px; flex-shrink: 0; cursor: pointer; accent-color: var(--main-color);" oninput="updateVolumeLabel('sfx', this.value)" onchange="saveVisualOptions(true)">
                    </div>
                    <!-- Music Volume -->
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px; padding: 8px 12px; border: 1px solid var(--dim-color); border-radius: 6px; background: rgba(0,0,0,0.3); box-shadow: inset 0 0 5px rgba(0,0,0,0.5); min-height: 38px; box-sizing: border-box;">
                        <span style="color: var(--warn-color); font-size: 11px; font-family: 'Orbitron', sans-serif; white-space: nowrap; flex: 1; min-width: 0; display: flex; justify-content: space-between; align-items: center; margin-right: 4px;">
                            <span>Musica (Music):</span>
                            <span id="volume-music-val" style="color: #fff; font-weight: bold; margin-left: 4px;">80%</span>
                        </span>
                        <input type="range" id="volume-music" min="0" max="200" value="80" style="width: 40%; max-width: 120px; min-width: 70px; flex-shrink: 0; cursor: pointer; accent-color: var(--main-color);" oninput="updateVolumeLabel('music', this.value)" onchange="saveVisualOptions(true)">
                    </div>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-33" onchange="previewGlitches(this)"> Soni Extincti (Mute Clicks)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-35" onchange="previewGlitches(this)"> Claves Mechanicae (Mechanical Keyboard)</label>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 6px 0;">
                    <h4 style="margin: 3px 0; color: #888; font-size: 13px; font-family: 'Orbitron', sans-serif;">Cantus Retro (Chiptune Tracks)</h4>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-34" onchange="previewGlitches(this)"> Melodia Synthetica (Synth 8-Bit)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-41" onchange="previewGlitches(this)"> Melodia: Chibi Ninja (MP3)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-42" onchange="previewGlitches(this)"> Melodia: Underclocked (MP3)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-43" onchange="previewGlitches(this)"> Melodia: Dizzy Spells (MP3)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-44" onchange="previewGlitches(this)"> Melodia: Vampire Killer (MP3)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-45" onchange="previewGlitches(this)"> Melodia: Wicked Child (MP3)</label>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 6px 0;">
                    <h4 style="margin: 3px 0; color: #888; font-size: 13px; font-family: 'Orbitron', sans-serif;">Atmosphaera (Ambient SFX)</h4>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-36" onchange="previewGlitches(this)"> Ventus Obscurus (Ambient Wind)</label>
                    <label style="display:block; margin-top:2px; color:var(--warn-color);"><input type="checkbox" id="glitch-37" onchange="previewGlitches(this)"> Murmur Antiquum (50Hz Hum)</label>
                </div>
            </div>

            <div class="config-section">
                <h3>Renominare Usorem (Rename User)</h3>
                <input type="text" id="config-new-name" class="config-input" placeholder="Novum Nomen...">
                <button class="config-btn" onclick="renameUser()">Renominare</button>
            </div>

            <div class="config-section">
                <h3>Mutare Tessellam (Change Password) <span style="font-size: 16px; color: #008800;">- Tantum pro ANIMA (Only for Email users)</span></h3>
                <input type="password" id="config-old-pass" class="config-input" placeholder="Vetus Tessella (Old Password)...">
                <input type="password" id="config-new-pass" class="config-input" placeholder="Nova Tessella (New Password)...">
                <button class="config-btn" onclick="changePassword()">Mutare</button>
            </div>

            <div class="config-section" style="border-top: 2px dashed #ff0000; padding-top: 15px;">
                <h3 style="color: #ff3333; margin-top: 0;">Zona Periculosa (Danger Zone)</h3>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="config-btn danger-btn" onclick="deleteAllChats()">[!] Delere Omnes Fabulationes</button>
                    <button class="config-btn danger-btn" onclick="deleteAccount()">[!!!] Delere Rationem (Delete Account)</button>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button onclick="closeConfigModal()">Inducere (Close)</button>
            </div>
        </div>
    </div>

    <div class="layout-wrapper">
        <div class="sidebar">
            <h2>Pluteus (Chats)</h2>
            <button onclick="createNewChat()" style="margin-bottom: 15px; background-color: #003300;">+ Nova Fabulatio</button>
            <ul class="chat-list" id="chat-list">
                <!-- Chats will load here via JS -->
            </ul>
            <div style="margin-top: auto; display: flex; flex-direction: column; gap: 10px;">
                <button onclick="openConfigModal()" style="width: 100%; background: var(--container-bg); color: var(--main-color); border: 1px dashed var(--main-color); text-align: left; padding: 10px;">⚙ Configuratio (Settings)</button>
                <form method="POST" action="fabulatio.php" style="margin: 0;">
                    <input type="submit" name="exire" value="Exire (Logout)" style="width:100%; background: var(--danger-bg); color: var(--danger-color); border-color: var(--danger-color);">
                </form>
            </div>
        </div>

        <div class="main-chat">
            <h1>Forum: <?php echo htmlspecialchars($usor); ?> <span class="blink">_</span> <span id="current-room-label" style="font-size: 24px; color: var(--dim-color); float: right;"></span></h1>
            <div id="chat">Eligere fabulationem e pluteo...</div>

            <div class="toggles-bar">
                <button id="toggle-lang" class="toggle-btn" onclick="toggleLanguage()">Lingua: Latina [L]</button>
                <button id="toggle-search" class="toggle-btn" onclick="toggleSearch()">Investigatio: OFF [-]</button>
                <span id="toggles-info" style="font-size: 14px; color: #006600; font-family: monospace; flex-grow: 1; text-align: right;">MODUS: TRADITIO</span>
            </div>

            <form id="chat-form" style="display: flex; gap: 10px;">
                <input type="text" id="nuntius" name="nuntius" style="flex-grow: 1;" autocomplete="off" placeholder="Dicent..." disabled>
                <input type="submit" id="send-btn" value="Mittere (Send)" disabled>
            </form>
        </div>
    </div>

    <script>
        const USOR_NOMEN = <?php echo json_encode($usor); ?>;
    </script>
    <script src="js/packages/common.js"></script>
    <script src="js/packages/audio.js"></script>
    <script src="js/packages/effects.js"></script>
    <script src="js/packages/ui.js"></script>
    <script src="js/packages/config.js"></script>
    <script src="js/packages/chat.js"></script>
    <script type="module" src="js/packages/katex_player.js"></script>
    <script type="module" src="js/packages/canvas_player.js"></script>
</body>
</html>
