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
        #new-chat-input { width: 80%; margin-bottom: 20px; text-align: center; }
        .cancel-btn { background-color: #330000; color: #ff3333; border-color: #ff3333; }
        .cancel-btn:hover { background-color: #ff3333; color: #fff; }
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

            <form id="chat-form" style="display: flex; gap: 10px;">
                <input type="text" id="nuntius" name="nuntius" style="flex-grow: 1;" autocomplete="off" placeholder="Dicent..." disabled>
                <input type="submit" id="send-btn" value="Mittere (Send)" disabled>
            </form>
        </div>
    </div>

    <script>
        let currentRoom = '';
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
                    
                    if (currentRoom && currentRoom !== '' && !rooms.includes(currentRoom)) {
                        rooms.unshift(currentRoom); // Add newly created virtual chat to the list
                    }

                    if (rooms.length > 0) {
                        rooms.forEach(room => {
                            const li = document.createElement('li');
                            li.className = 'chat-item' + (room === currentRoom ? ' active' : '');
                            li.innerHTML = `<span class="chat-item-name" onclick="selectRoom('${room}')" title="${room}">${room}</span> 
                                          <div class="action-btns">
                                              <button class="ren-btn" onclick="renameRoom('${room}', event)" title="Renominare">[R]</button>
                                              <button class="del-btn" onclick="deleteRoom('${room}', event)" title="Delere">[X]</button>
                                          </div>`;
                            chatListEl.appendChild(li);
                        });
                    }
                    if (selectDefault && rooms.length > 0) {
                        selectRoom(rooms[0]);
                    } else if (selectDefault && rooms.length === 0) {
                        selectRoom('default'); 
                    }
                });
        }

        function selectRoom(room) {
            currentRoom = room;
            roomLabel.textContent = `[${room}]`;
            nuntiusInput.disabled = false;
            sendBtn.disabled = false;
            fetch('api.php?action=load&room=' + room)
                .then(r => r.text())
                .then(text => {
                    chatEl.textContent = text || 'Nihil scriptum est...';
                    chatEl.scrollTop = chatEl.scrollHeight;
                    loadChats(); // refresh active state in sidebar
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
                    selectRoom(safeName);
                    closeNewChatModal();
                }
            }
        }

        function renameRoom(room, event) {
            event.stopPropagation();
            const newName = prompt("Novum nomen pro: " + room + "? (New name?)", room);
            if (newName && newName !== room) {
                const safeName = newName.replace(/[^a-zA-Z0-9_-]/g, '');
                if (safeName) {
                    const formData = new URLSearchParams();
                    formData.append('action', 'rename');
                    formData.append('room', room);
                    formData.append('new_room', safeName);
                    
                    fetch('api.php', { method: 'POST', body: formData })
                        .then(() => {
                            if (currentRoom === room) currentRoom = safeName;
                            loadChats();
                        });
                }
            }
        }

        function deleteRoom(room, event) {
            event.stopPropagation();
            if (confirm("Visne delere fabulationem: " + room + "?")) {
                fetch('api.php?action=delete&room=' + room)
                    .then(() => {
                        if (currentRoom === room) {
                            chatEl.textContent = "Eligere fabulationem e pluteo...";
                            currentRoom = '';
                            nuntiusInput.disabled = true;
                            sendBtn.disabled = true;
                            roomLabel.textContent = '';
                        }
                        loadChats();
                    });
            }
        }

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
                                        currentRoom = dataNode.new_room;
                                        roomLabel.textContent = `[${currentRoom}]`;
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
