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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=VT323&display=swap');

        :root {
            --main-color: #00FF00;
            --bg-color: #050505;
            --container-bg: #000;
            --dim-color: #008800;
            --dark-color: #003300;
            --hover-color: #002200;
            --danger-color: #ff3333;
            --danger-bg: #330000;
            --danger-hover: #ff0000;
            --warn-color: #ffff00;
        }

        body { 
            background-color: var(--bg-color); color: var(--main-color);
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

        .layout-wrapper {
            display: flex; gap: 20px; width: 95%; max-width: 1400px; height: 80vh;
            position: relative; z-index: 10;
        }

        /* Bookshelf Sidebar */
        .sidebar {
            width: 250px; border: 2px solid var(--main-color); padding: 15px;
            box-shadow: 0 0 20px var(--main-color), inset 0 0 10px var(--main-color); background-color: var(--container-bg);
            display: flex; flex-direction: column; height: 100%; box-sizing: border-box;
        }
        
        .sidebar h2 { margin-top: 0; text-shadow: 0 0 5px var(--main-color); border-bottom: 1px dotted var(--main-color); padding-bottom: 10px; }

        .chat-list { list-style: none; padding: 0; flex-grow: 1; overflow-y: auto; margin-top: 0; }
        
        .chat-item {
            padding: 10px; border: 1px dashed var(--dim-color); margin-bottom: 10px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center; font-size: 20px;
        }
        
        .chat-item:hover, .chat-item.active { background-color: var(--hover-color); border-style: solid; border-color: var(--main-color); }
        
        .chat-item-name { flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;}
        
        .action-btns { display: flex; gap: 5px; }
        .ren-btn { color: #ffff33; cursor: pointer; border: none; background: none; font-family: inherit; font-size: 20px; text-shadow: 0 0 2px yellow;}
        .ren-btn:hover { color: #ffff00; font-weight: bold; background: #333300;}
        .del-btn { color: var(--danger-color); cursor: pointer; border: none; background: none; font-family: inherit; font-size: 20px; text-shadow: 0 0 2px red;}
        .del-btn:hover { color: var(--danger-hover); font-weight: bold; background: var(--danger-bg);}

        /* Main Chat Window */
        .main-chat {
            flex-grow: 1; border: 2px solid var(--main-color); padding: 30px;
            box-shadow: 0 0 20px var(--main-color), inset 0 0 10px var(--main-color); background-color: var(--container-bg);
            display: flex; flex-direction: column; overflow: hidden; height: 100%; box-sizing: border-box;
        }

        h1 { font-size: 36px; text-shadow: 0 0 5px var(--main-color); margin-top: 0;}
        
        input[type="text"], input[type="submit"], button { 
            background-color: var(--container-bg); color: var(--main-color); border: 1px solid var(--main-color); padding: 10px;
            font-family: 'VT323', "Courier New", Courier, monospace; font-size: 24px;
        }
        input[type="submit"]:hover, button:hover { background-color: var(--main-color); color: var(--container-bg); cursor: pointer; }
        input[type="submit"]:disabled, input[type="text"]:disabled { opacity: 0.5; cursor: not-allowed; }
        
        #chat { 
            width: 100%; flex-grow: 1; border: 1px solid var(--main-color); overflow-y: auto;
            padding: 15px; white-space: pre-wrap; margin-bottom: 20px;
            box-sizing: border-box; font-size: 22px;
            box-shadow: inset 0 0 10px var(--main-color); scroll-behavior: smooth;
        }

        .toggles-bar {
            display: flex; gap: 15px; margin-bottom: 10px; align-items: center;
            border-top: 1px dotted var(--dim-color); padding-top: 10px;
        }
        .toggle-btn {
            font-size: 18px; padding: 5px 12px; min-width: 140px; text-align: center;
        }
        .toggle-active {
            background-color: var(--main-color) !important; color: var(--container-bg) !important;
            box-shadow: 0 0 10px var(--main-color);
        }
        .toggle-inactive {
            color: var(--dim-color); border-color: var(--dim-color);
        }
        
        .blink { animation: blink-animation 1s steps(5, start) infinite; -webkit-animation: blink-animation 1s steps(5, start) infinite; }
        @keyframes blink-animation { to { visibility: hidden; } }
        @-webkit-keyframes blink-animation { to { visibility: hidden; } }

        /* Welcome Modal Styles */
        #welcome-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: var(--container-bg); z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            opacity: 1; transition: opacity 0.5s ease;
        }
        .welcome-content { text-align: center; color: var(--main-color); }
        .welcome-text {
            font-size: 36px; font-weight: bold; overflow: hidden; white-space: pre-wrap; margin: 0 auto;
            border-right: .15em solid var(--main-color); animation: blink-caret .75s step-end infinite;
        }

        /* New Chat Modal Styles */
        #new-chat-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9998;
            display: none; justify-content: center; align-items: center;
        }
        .new-chat-content {
            border: 2px solid var(--main-color); padding: 30px; text-align: center;
            background-color: var(--container-bg); box-shadow: 0 0 20px var(--main-color);
        }
        #new-chat-input, #rename-chat-input { width: 80%; margin-bottom: 20px; text-align: center; }
        .cancel-btn { background-color: var(--danger-bg); color: var(--danger-color); border-color: var(--danger-color); }
        .cancel-btn:hover { background-color: var(--danger-color); color: #fff; }

        /* Rename Chat Modal Styles */
        #rename-chat-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9998;
            display: none; justify-content: center; align-items: center;
        }
        .rename-chat-content {
            border: 2px solid var(--main-color); padding: 30px; text-align: center;
            background-color: var(--container-bg); box-shadow: 0 0 20px var(--main-color);
        }

        /* Delete Chat Modal Styles */
        #delete-chat-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9998;
            display: none; justify-content: center; align-items: center;
        }
        .delete-chat-content {
            border: 2px solid #ff0000; padding: 30px; text-align: center;
            background-color: #000; box-shadow: 0 0 20px #ff0000;
        }

        /* Config Modal Styles */
        #config-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9999;
            display: none; justify-content: center; align-items: center;
        }
        .config-content {
            border: 2px solid var(--main-color); padding: 30px; text-align: left;
            background-color: var(--container-bg); box-shadow: 0 0 20px var(--main-color);
            width: 80%; max-width: 600px;
            max-height: 90vh; overflow-y: auto;
        }
        .config-section {
            margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px dotted var(--main-color);
        }
        .config-section:last-child {
            border-bottom: none; margin-bottom: 0; padding-bottom: 0;
        }
        .config-input, .config-select {
            width: 100%; margin-bottom: 10px; margin-top: 5px; box-sizing: border-box;
            background-color: var(--container-bg); color: var(--main-color); border: 1px solid var(--main-color); padding: 10px;
            font-family: inherit; font-size: 20px;
        }
        .config-btn { margin-top: 10px; }
        .danger-btn { background-color: var(--danger-bg); color: var(--danger-color); border-color: var(--danger-color); }
        .danger-btn:hover { background-color: var(--danger-hover); color: #fff; }

        /* Glitches - Applied conditionally */
        body.glitch-shake .layout-wrapper {
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both infinite;
            transform: translate3d(0, 0, 0);
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        body.glitch-chromatic * {
            text-shadow: 2px 0px #f00, -2px 0px #0ff;
        }

        body.glitch-borders .sidebar, body.glitch-borders .main-chat, body.glitch-borders .config-content {
            animation: border-glitch 2s linear infinite;
        }
        @keyframes border-glitch {
            0% { border-style: solid; border-width: 2px; }
            25% { border-style: dashed; border-width: 4px; }
            50% { border-style: dotted; border-width: 1px; }
            75% { border-style: double; border-width: 5px; }
            100% { border-style: solid; border-width: 2px; }
        }

        body.glitch-noise::before {
            content: " "; display: block; position: fixed; top: 0; left: 0; bottom: 0; right: 0;
            background: url('data:image/svg+xml,%3Csvg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"%3E%3Cfilter id="noiseFilter"%3E%3CfeTurbulence type="fractalNoise" baseFrequency="0.65" numOctaves="3" stitchTiles="stitch"/%3E%3C/filter%3E%3Crect width="100%25" height="100%25" filter="url(%23noiseFilter)" opacity="0.15"/%3E%3C/svg%3E');
            z-index: 100; pointer-events: none; opacity: 0.15;
            animation: noise 0.2s infinite alternate;
        }
        @keyframes noise {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-5px, 5px); }
        }

    </style>
</head>
<body>
    <div id="welcome-modal">
        <div class="welcome-content">
            <div class="welcome-text" id="welcome-typewriter"></div>
        </div>
    </div>

    <!-- New Chat Modal -->
    <div id="new-chat-modal">
        <div class="new-chat-content">
            <h2 style="margin-top: 0; text-shadow: 0 0 5px #00FF00;">Nova Fabulatio</h2>
            <p style="font-size: 24px;">Quod est nomen novae fabulationis? (Name of new chat?)</p>
            <input type="text" id="new-chat-input" autocomplete="off" onkeydown="if(event.key === 'Enter') submitNewChat()">
            <div style="margin-top: 20px;">
                <button onclick="submitNewChat()" style="margin-right: 15px;">Creare (Create)</button>
                <button onclick="closeNewChatModal()" class="cancel-btn">Inducere (Cancel)</button>
            </div>
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

            <div class="config-section" id="level-display" style="color: var(--warn-color); font-size: 24px;">
                Gradus (Level): <span id="user-level">1</span> | Nuntii (Messages): <span id="user-messages">0</span>
            </div>

            <div class="config-section">
                <h3>Thema et Effectus (Theme & Effects)</h3>
                <select id="config-theme" class="config-select" onchange="previewTheme()">
                    <option value="0">0. Viridis (Classic Green)</option>
                    <!-- More options populated via JS -->
                </select>

                <div id="glitches-container" style="margin-top: 15px;">
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-10" onchange="previewGlitches()"> L10: Terraemotus (Shake)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-11" onchange="previewGlitches()"> L11: Aberratio (Chromatic)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-12" onchange="previewGlitches()"> L12: Fines fracti (Broken Borders)</label>
                    <label style="display:block; margin-top:5px; color:var(--warn-color);"><input type="checkbox" id="glitch-13" onchange="previewGlitches()"> L13: Caligo (CRT Noise)</label>
                </div>
                <button class="config-btn" onclick="saveVisualOptions()" style="margin-top: 15px;">Servare (Save)</button>
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
        let currentRoom = '';
        let virtualRooms = []; // Newly created rooms without history yet
        const chatEl = document.getElementById("chat");
        const chatListEl = document.getElementById("chat-list");
        const chatForm = document.getElementById("chat-form");
        const nuntiusInput = document.getElementById("nuntius");
        const sendBtn = document.getElementById("send-btn");
        const roomLabel = document.getElementById("current-room-label");

        // Welcome Animation Logic
        const welcomeText = "CONEXIO STABILITA...\nSALVE, <?php echo htmlspecialchars($usor); ?>.\nORACULUM TE EXSPECTAT.";
        const typeEl = document.getElementById("welcome-typewriter");
        const modalEl = document.getElementById("welcome-modal");
        let typeI = 0;

        function typeWriterWelcome() {
            if (typeI < welcomeText.length) {
                typeEl.innerHTML += welcomeText.charAt(typeI) === '\n' ? '<br/>' : welcomeText.charAt(typeI);
                typeI++;
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
            loadChats(true);
            typeWriterWelcome();
        };

        modalEl.addEventListener('click', closeWelcomeModal);

        // AJAX Chat Logic
        function loadChats(selectDefault = false) {
            fetch('api.php?action=list')
                .then(r => r.json())
                .then(data => {
                    chatListEl.innerHTML = '';
                    let rooms = data.rooms || [];
                    if (rooms.length === 1 && rooms[0] === "") rooms = [];
                    
                    // Merge physical rooms with virtual unsaved rooms
                    virtualRooms.forEach(vr => {
                        if (!rooms.includes(vr)) rooms.unshift(vr);
                    });
                    
                    // If current room still isn't in there (e.g. just renamed before refresh), add it
                    if (currentRoom && currentRoom !== '' && !rooms.includes(currentRoom)) {
                         rooms.unshift(currentRoom);
                    }

                    if (rooms.length > 0) {
                        rooms.forEach(room => {
                            const li = document.createElement('li');
                            li.className = 'chat-item' + (room === currentRoom ? ' active' : '');
                            li.onclick = () => selectRoom(room);
                            li.innerHTML = `<span class="chat-item-name" title="${room}">${room}</span> 
                                          <div class="action-btns">
                                              <button class="ren-btn" onclick="renameRoom('${room}', event)" title="Renominare">[R]</button>
                                              <button class="del-btn" onclick="deleteRoom('${room}', event)" title="Delere">[X]</button>
                                          </div>`;
                            chatListEl.appendChild(li);
                        });
                    }
                    if (selectDefault) {
                        const roomToSelect = rooms.length > 0 ? rooms[0] : 'default';
                        selectRoom(roomToSelect, false); // Don't refresh sidebar again during selection
                    }
                });
        }

        function selectRoom(room, refreshSidebar = true) {
            if (currentRoom === room && !refreshSidebar) return; 
            currentRoom = room;
            roomLabel.textContent = `[${room}]`;
            nuntiusInput.disabled = false;
            sendBtn.disabled = false;
            
            // Visual Update Without Full Fetch Before Load
            Array.from(chatListEl.children).forEach(li => {
                const nameEl = li.querySelector('.chat-item-name');
                if (nameEl) {
                    li.classList.toggle('active', nameEl.textContent === room);
                }
            });

            if (refreshSidebar) {
                // Ensure the list shows the currentRoom even if it's virtual
                loadChats(false);
            }

            fetch('api.php?action=load&room=' + room)
                .then(r => r.text())
                .then(text => {
                    chatEl.textContent = text || 'Nihil scriptum est...';
                    chatEl.scrollTop = chatEl.scrollHeight;
                })
                .catch(err => console.error("Error loading chat:", err));
        }

        function createNewChat() {
            document.getElementById('new-chat-modal').style.display = 'flex';
            document.getElementById('new-chat-input').value = '';
            document.getElementById('new-chat-input').focus();
        }

        function closeNewChatModal() {
            document.getElementById('new-chat-modal').style.display = 'none';
        }

        function submitNewChat() {
            const name = document.getElementById('new-chat-input').value.trim();
            if (name) {
                const safeName = name.replace(/[^a-zA-Z0-9_-]/g, '');
                if (safeName) {
                    if (!virtualRooms.includes(safeName)) {
                        virtualRooms.push(safeName);
                    }
                    selectRoom(safeName);
                    closeNewChatModal();
                }
            }
        }

        let roomToRename = '';

        function renameRoom(room, event) {
            event.stopPropagation();
            roomToRename = room;
            document.getElementById('rename-room-name').textContent = room;
            document.getElementById('rename-chat-modal').style.display = 'flex';
            document.getElementById('rename-chat-input').value = room;
            document.getElementById('rename-chat-input').select();
        }

        function closeRenameModal() {
            document.getElementById('rename-chat-modal').style.display = 'none';
        }

        function submitRenameChat() {
            const newName = document.getElementById('rename-chat-input').value.trim();
            if (newName && newName !== roomToRename) {
                const safeName = newName.replace(/[^a-zA-Z0-9_-]/g, '');
                if (safeName) {
                    const formData = new URLSearchParams();
                    formData.append('action', 'rename');
                    formData.append('room', roomToRename);
                    formData.append('new_room', safeName);
                    
                    fetch('api.php', { method: 'POST', body: formData })
                        .then(() => {
                            if (currentRoom === roomToRename) {
                                currentRoom = safeName;
                                roomLabel.textContent = `[${currentRoom}]`;
                            }
                            loadChats();
                            closeRenameModal();
                        });
                }
            } else {
                closeRenameModal();
            }
        }

        let roomToDelete = '';

        function deleteRoom(room, event) {
            event.stopPropagation();
            roomToDelete = room;
            const roomSpan = document.getElementById('delete-room-name');
            if (roomSpan) roomSpan.textContent = room;
            const modal = document.getElementById('delete-chat-modal');
            if (modal) modal.style.display = 'flex';
        }

        function closeDeleteModal() {
            const modal = document.getElementById('delete-chat-modal');
            if (modal) modal.style.display = 'none';
        }

        function submitDeleteChat() {
            if (roomToDelete) {
                fetch('api.php?action=delete&room=' + roomToDelete)
                    .then(() => {
                        // Also remove from virtualRooms if it was just drafted
                        virtualRooms = virtualRooms.filter(r => r !== roomToDelete);
                        if (currentRoom === roomToDelete) {
                            chatEl.textContent = "Eligere fabulationem e pluteo...";
                            currentRoom = '';
                            nuntiusInput.disabled = true;
                            sendBtn.disabled = true;
                            roomLabel.textContent = '';
                        }
                        loadChats();
                        closeDeleteModal();
                    });
            }
        }

        // Toggles State Management
        let currentLangMode = sessionStorage.getItem('lang_mode') || 'latin';
        let currentSearchMode = sessionStorage.getItem('search_mode') || 'off';

        function updateToggleUI() {
            const btnLang = document.getElementById('toggle-lang');
            const btnSearch = document.getElementById('toggle-search');
            const info = document.getElementById('toggles-info');

            if (currentLangMode === 'latin') {
                btnLang.textContent = "Lingua: Latina [L]";
                btnLang.classList.remove('toggle-active');
            } else {
                btnLang.textContent = "Lingua: Auto [A]";
                btnLang.classList.add('toggle-active');
            }

            if (currentSearchMode === 'off') {
                btnSearch.textContent = "Investigatio: OFF [-]";
                btnSearch.classList.remove('toggle-active');
            } else {
                btnSearch.textContent = "Investigatio: DDG [S]";
                btnSearch.classList.add('toggle-active');
            }

            info.textContent = `MODUS: ${currentLangMode.toUpperCase()} | SEARCH: ${currentSearchMode.toUpperCase()}`;
        }

        function toggleLanguage() {
            currentLangMode = currentLangMode === 'latin' ? 'auto' : 'latin';
            sessionStorage.setItem('lang_mode', currentLangMode);
            updateToggleUI();
        }

        function toggleSearch() {
            currentSearchMode = currentSearchMode === 'off' ? 'on' : 'off';
            sessionStorage.setItem('search_mode', currentSearchMode);
            updateToggleUI();
        }

        // Configuration Actions
        function openConfigModal() {
            document.getElementById('config-modal').style.display = 'flex';
            document.getElementById('config-alert').textContent = '';
            document.getElementById('config-new-name').value = '';
            document.getElementById('config-old-pass').value = '';
            document.getElementById('config-new-pass').value = '';
        }

        function closeConfigModal() {
            document.getElementById('config-modal').style.display = 'none';
            applyOptionsToDOM(); // Revert preview if not saved
        }

        function configAlert(msg, isError = false) {
            const el = document.getElementById('config-alert');
            el.style.color = isError ? '#ff3333' : '#00ff00';
            el.textContent = msg;
            setTimeout(() => { el.textContent = ''; }, 4000);
        }

        function renameUser() {
            const newName = document.getElementById('config-new-name').value.trim();
            if (!newName) return;
            const formData = new URLSearchParams();
            formData.append('action', 'renominare_usorem');
            formData.append('novum_nomen', newName);

            fetch('api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        configAlert("Nomen mutatum est. Reficiens... (Reloading)");
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        configAlert(data.message, true);
                    }
                });
        }

        function changePassword() {
            const oldPass = document.getElementById('config-old-pass').value;
            const newPass = document.getElementById('config-new-pass').value;
            if (!oldPass || !newPass) return;
            const formData = new URLSearchParams();
            formData.append('action', 'mutare_tessaram');
            formData.append('vetus_pass', oldPass);
            formData.append('nova_pass', newPass);

            fetch('api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        configAlert("Tessella mutata est (Password changed).");
                        document.getElementById('config-old-pass').value = '';
                        document.getElementById('config-new-pass').value = '';
                    } else {
                        configAlert(data.message, true);
                    }
                });
        }

        function deleteAllChats() {
            if (confirm("Visne vere delere omnes fabulationes? (Are you sure you want to delete all chats?)")) {
                const formData = new URLSearchParams();
                formData.append('action', 'delere_omnes_fabulationes');
                fetch('api.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            configAlert("Omnes fabulationes deletae sunt. Reficiens...");
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            configAlert(data.message, true);
                        }
                    });
            }
        }

        function deleteAccount() {
            if (confirm("MONITUM! (WARNING!)\nVisne vere delere rationem et omnia data tua? Haec actio non potest revocari!\n(Are you sure you want to delete your account and all data? This cannot be undone!)")) {
                const formData = new URLSearchParams();
                formData.append('action', 'delere_rationem');
                fetch('api.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'ok') {
                            location.href = 'index.php';
                        } else {
                            configAlert(data.message, true);
                        }
                    });
            }
        }

        // --- Levels & Visual Options System ---

        const THEMES = [
            { name: "Viridis (Green)", colors: { main: '#00FF00', bg: '#050505', cont: '#000', dim: '#008800', dark: '#003300', hov: '#002200' } },
            { name: "Electinum (Amber)", colors: { main: '#FFB000', bg: '#100800', cont: '#000', dim: '#885500', dark: '#331100', hov: '#221100' } },
            { name: "Cyanus (Cyan)", colors: { main: '#00FFFF', bg: '#000810', cont: '#000', dim: '#008888', dark: '#003333', hov: '#002222' } },
            { name: "Cruor (Blood Red)", colors: { main: '#FF0000', bg: '#100000', cont: '#000', dim: '#880000', dark: '#330000', hov: '#220000' } },
            { name: "Matrix", colors: { main: '#00FF41', bg: '#000000', cont: '#001100', dim: '#008F11', dark: '#003B00', hov: '#002200' } },
            { name: "Purpura (Purple)", colors: { main: '#FF00FF', bg: '#080010', cont: '#000', dim: '#880088', dark: '#330033', hov: '#220022' } },
            { name: "Aureus (Gold)", colors: { main: '#FFD700', bg: '#101000', cont: '#000', dim: '#886600', dark: '#332200', hov: '#221100' } },
            { name: "Nix (White/Ice)", colors: { main: '#E0E0FF', bg: '#050510', cont: '#000', dim: '#8888AA', dark: '#222233', hov: '#111122' } },
            { name: "Neon (Vaporwave)", colors: { main: '#00FFFF', bg: '#2B00FF', cont: '#000000', dim: '#FF00FF', dark: '#880088', hov: '#110033' } },
            { name: "Cinereus (Ash)", colors: { main: '#AAAAAA', bg: '#111111', cont: '#000', dim: '#555555', dark: '#222222', hov: '#111111' } },
            { name: "Infernus (Inferno)", colors: { main: '#FF4500', bg: '#1A0000', cont: '#000', dim: '#AA2200', dark: '#440000', hov: '#220000' } },
            { name: "Abyssus (Deep Blue)", colors: { main: '#4169E1', bg: '#00001A', cont: '#000', dim: '#2233AA', dark: '#000044', hov: '#000022' } },
            { name: "Tenebrae (Pitch Black)", colors: { main: '#333333', bg: '#000', cont: '#000', dim: '#111111', dark: '#050505', hov: '#050505' } }
        ];

        let userState = { level: 1, messages: 0, options: { theme: 0, glitches: [] } };

        function loadUserState() {
            fetch('api.php?action=get_user_state')
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        userState.level = data.level || 1;
                        userState.messages = data.messages || 0;
                        if (data.options && typeof data.options.theme !== 'undefined') {
                            userState.options = data.options;
                        }
                        updateOptionsUI();
                        applyOptionsToDOM();
                    }
                });
        }

        function updateOptionsUI() {
            document.getElementById('user-level').textContent = userState.level;
            document.getElementById('user-messages').textContent = userState.messages;

            const themeSelect = document.getElementById('config-theme');
            themeSelect.innerHTML = '';
            THEMES.forEach((t, i) => {
                const opt = document.createElement('option');
                opt.value = i;
                opt.textContent = `L${i+1}: ${t.name}`;
                if (i + 1 > userState.level) opt.disabled = true;
                themeSelect.appendChild(opt);
            });
            themeSelect.value = userState.options.theme || 0;

            document.getElementById('glitch-10').parentElement.style.display = userState.level >= 10 ? 'block' : 'none';
            document.getElementById('glitch-11').parentElement.style.display = userState.level >= 11 ? 'block' : 'none';
            document.getElementById('glitch-12').parentElement.style.display = userState.level >= 12 ? 'block' : 'none';
            document.getElementById('glitch-13').parentElement.style.display = userState.level >= 13 ? 'block' : 'none';

            const g = userState.options.glitches || [];
            document.getElementById('glitch-10').checked = g.includes(10);
            document.getElementById('glitch-11').checked = g.includes(11);
            document.getElementById('glitch-12').checked = g.includes(12);
            document.getElementById('glitch-13').checked = g.includes(13);
        }

        function applyTheme(themeIndex) {
            const t = THEMES[themeIndex] || THEMES[0];
            const root = document.documentElement;
            root.style.setProperty('--main-color', t.colors.main);
            root.style.setProperty('--bg-color', t.colors.bg);
            root.style.setProperty('--container-bg', t.colors.cont);
            root.style.setProperty('--dim-color', t.colors.dim);
            root.style.setProperty('--dark-color', t.colors.dark);
            root.style.setProperty('--hover-color', t.colors.hov);
        }

        function previewTheme() {
            applyTheme(parseInt(document.getElementById('config-theme').value));
        }

        function previewGlitches() {
            document.body.classList.toggle('glitch-shake', document.getElementById('glitch-10').checked);
            document.body.classList.toggle('glitch-chromatic', document.getElementById('glitch-11').checked);
            document.body.classList.toggle('glitch-borders', document.getElementById('glitch-12').checked);
            document.body.classList.toggle('glitch-noise', document.getElementById('glitch-13').checked);
        }

        function applyOptionsToDOM() {
            applyTheme(userState.options.theme || 0);
            const g = userState.options.glitches || [];
            document.body.classList.toggle('glitch-shake', g.includes(10));
            document.body.classList.toggle('glitch-chromatic', g.includes(11));
            document.body.classList.toggle('glitch-borders', g.includes(12));
            document.body.classList.toggle('glitch-noise', g.includes(13));
        }

        function saveVisualOptions() {
            const theme = parseInt(document.getElementById('config-theme').value);
            const glitches = [];
            if (document.getElementById('glitch-10').checked) glitches.push(10);
            if (document.getElementById('glitch-11').checked) glitches.push(11);
            if (document.getElementById('glitch-12').checked) glitches.push(12);
            if (document.getElementById('glitch-13').checked) glitches.push(13);

            userState.options = { theme, glitches };

            const formData = new URLSearchParams();
            formData.append('action', 'save_options');
            formData.append('options', JSON.stringify(userState.options));

            fetch('api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'ok') {
                        configAlert("Optiones servatae sunt. (Options saved)");
                        applyOptionsToDOM();
                    } else {
                        configAlert(data.message, true);
                    }
                });
        }

        // Initial UI Update
        updateToggleUI();
        loadUserState();

        chatForm.onsubmit = function(e) {
            e.preventDefault();
            const msg = nuntiusInput.value.trim();
            if (!msg || !currentRoom) return;

            nuntiusInput.value = '';
            nuntiusInput.disabled = true;
            sendBtn.disabled = true;

            const divider = chatEl.textContent === 'Nihil scriptum est...' ? '' : '\n';
            chatEl.textContent += divider + "Tute: " + msg + "\nOraculum: ";
            chatEl.scrollTop = chatEl.scrollHeight;

            const formData = new URLSearchParams();
            formData.append('action', 'send');
            formData.append('room', currentRoom);
            formData.append('nuntius', msg);
            formData.append('lingua', currentLangMode);
            formData.append('search', currentSearchMode);

            fetch('api.php', {
                method: 'POST',
                body: formData,
            }).then(response => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder('utf-8');

                function read() {
                    reader.read().then(({ done, value }) => {
                        if (done) {
                            nuntiusInput.disabled = false;
                            sendBtn.disabled = false;
                            nuntiusInput.focus();
                            loadChats(); // refresh list in case it was a new chat
                            return;
                        }
                        const chunk = decoder.decode(value, {stream: true});
                        const lines = chunk.split('\n');
                        for (let line of lines) {
                            if (line.startsWith('data: ')) {
                                const dataStr = line.substring(6).trim();
                                if (dataStr === '[DONE]') {
                                    loadUserState(); // Update level after chat completes
                                    continue;
                                }
                                try {
                                    const dataNode = JSON.parse(dataStr);
                                    if (dataNode.event === 'renamed') {
                                        const oldRoom = currentRoom;
                                        currentRoom = dataNode.new_room;
                                        roomLabel.textContent = `[${currentRoom}]`;
                                        // Clean up virtualRooms
                                        virtualRooms = virtualRooms.filter(r => r !== oldRoom);
                                        loadChats();
                                    } else if (dataNode.event === 'debug') {
                                        console.log("SSE Debug:", dataNode);
                                    } else if (dataNode.choices && dataNode.choices[0].delta.content) {
                                        chatEl.textContent += dataNode.choices[0].delta.content;
                                        chatEl.scrollTop = chatEl.scrollHeight;
                                    }
                                } catch(e) {}
                            }
                        }
                        read();
                    });
                }
                read();
            }).catch(err => {
                chatEl.textContent += "\nError: " + err;
                nuntiusInput.disabled = false;
                sendBtn.disabled = false;
            });
        };
    </script>
</body>
</html>
