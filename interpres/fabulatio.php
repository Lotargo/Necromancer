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

        .layout-wrapper {
            display: flex; gap: 20px; width: 95%; max-width: 1400px; height: 80vh;
            position: relative; z-index: 10;
        }

        /* Bookshelf Sidebar */
        .sidebar {
            width: 250px; border: 2px solid #00FF00; padding: 15px;
            box-shadow: 0 0 20px #00FF00, inset 0 0 10px #00FF00; background-color: #000;
            display: flex; flex-direction: column;
        }
        
        .sidebar h2 { margin-top: 0; text-shadow: 0 0 5px #00FF00; border-bottom: 1px dotted #00FF00; padding-bottom: 10px; }

        .chat-list { list-style: none; padding: 0; flex-grow: 1; overflow-y: auto; margin-top: 0; }
        
        .chat-item {
            padding: 10px; border: 1px dashed #007700; margin-bottom: 10px; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center; font-size: 20px;
        }
        
        .chat-item:hover, .chat-item.active { background-color: #002200; border-style: solid; border-color: #00FF00; }
        
        .chat-item-name { flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;}
        
        .action-btns { display: flex; gap: 5px; }
        .ren-btn { color: #ffff33; cursor: pointer; border: none; background: none; font-family: inherit; font-size: 20px; text-shadow: 0 0 2px yellow;}
        .ren-btn:hover { color: #ffff00; font-weight: bold; background: #333300;}
        .del-btn { color: #ff3333; cursor: pointer; border: none; background: none; font-family: inherit; font-size: 20px; text-shadow: 0 0 2px red;}
        .del-btn:hover { color: #ff0000; font-weight: bold; background: #330000;}

        /* Main Chat Window */
        .main-chat {
            flex-grow: 1; border: 2px solid #00FF00; padding: 30px; 
            box-shadow: 0 0 20px #00FF00, inset 0 0 10px #00FF00; background-color: #000;
            display: flex; flex-direction: column;
        }

        h1 { font-size: 36px; text-shadow: 0 0 5px #00FF00; margin-top: 0;}
        
        input[type="text"], input[type="submit"], button { 
            background-color: #000; color: #00FF00; border: 1px solid #00FF00; padding: 10px; 
            font-family: 'VT323', "Courier New", Courier, monospace; font-size: 24px;
        }
        input[type="submit"]:hover, button:hover { background-color: #00FF00; color: #000; cursor: pointer; }
        input[type="submit"]:disabled, input[type="text"]:disabled { opacity: 0.5; cursor: not-allowed; }
        
        #chat { 
            width: 100%; flex-grow: 1; border: 1px solid #00FF00; overflow-y: auto; 
            padding: 15px; white-space: pre-wrap; margin-bottom: 20px;
            box-sizing: border-box; font-size: 22px;
            box-shadow: inset 0 0 10px #00FF00; scroll-behavior: smooth;
        }

        .toggles-bar {
            display: flex; gap: 15px; margin-bottom: 10px; align-items: center;
            border-top: 1px dotted #008800; padding-top: 10px;
        }
        .toggle-btn {
            font-size: 18px; padding: 5px 12px; min-width: 140px; text-align: center;
        }
        .toggle-active {
            background-color: #00FF00 !important; color: #000 !important;
            box-shadow: 0 0 10px #00FF00;
        }
        .toggle-inactive {
            color: #008800; border-color: #008800;
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
        .welcome-content { text-align: center; color: #00FF00; }
        .welcome-text {
            font-size: 36px; font-weight: bold; overflow: hidden; white-space: pre-wrap; margin: 0 auto;
            border-right: .15em solid #00FF00; animation: blink-caret .75s step-end infinite;
        }

        /* New Chat Modal Styles */
        #new-chat-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9998;
            display: none; justify-content: center; align-items: center;
        }
        .new-chat-content {
            border: 2px solid #00FF00; padding: 30px; text-align: center;
            background-color: #000; box-shadow: 0 0 20px #00FF00;
        }
        #new-chat-input, #rename-chat-input { width: 80%; margin-bottom: 20px; text-align: center; }
        .cancel-btn { background-color: #330000; color: #ff3333; border-color: #ff3333; }
        .cancel-btn:hover { background-color: #ff3333; color: #fff; }

        /* Rename Chat Modal Styles */
        #rename-chat-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0,0,0,0.85); z-index: 9998;
            display: none; justify-content: center; align-items: center;
        }
        .rename-chat-content {
            border: 2px solid #00FF00; padding: 30px; text-align: center;
            background-color: #000; box-shadow: 0 0 20px #00FF00;
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

    <div class="layout-wrapper">
        <div class="sidebar">
            <h2>Pluteus (Chats)</h2>
            <button onclick="createNewChat()" style="margin-bottom: 15px; background-color: #003300;">+ Nova Fabulatio</button>
            <ul class="chat-list" id="chat-list">
                <!-- Chats will load here via JS -->
            </ul>
            <form method="POST" action="fabulatio.php" style="margin-top:auto;">
                <input type="submit" name="exire" value="Exire (Logout)" style="width:100%; background: #330000; color: #ff3333; border-color: #ff3333;">
            </form>
        </div>

        <div class="main-chat">
            <h1>Forum: <?php echo htmlspecialchars($usor); ?> <span class="blink">_</span> <span id="current-room-label" style="font-size: 24px; color: #008800; float: right;"></span></h1>
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

        // Initial UI Update
        updateToggleUI();

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
                                if (dataStr === '[DONE]') continue;
                                try {
                                    const dataNode = JSON.parse(dataStr);
                                    if (dataNode.event === 'renamed') {
                                        const oldRoom = currentRoom;
                                        currentRoom = dataNode.new_room;
                                        roomLabel.textContent = `[${currentRoom}]`;
                                        // Clean up virtualRooms
                                        virtualRooms = virtualRooms.filter(r => r !== oldRoom);
                                        loadChats();
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
