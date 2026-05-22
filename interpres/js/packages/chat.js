// Necromancer - AJAX Chat Logic, SSE Streaming & Room Management

function loadChats(selectDefault = false) {
    fetch('api.php?action=list&t=' + Date.now())
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
                    let displayName = room;
                    if (userState.options && userState.options.chat_names && userState.options.chat_names[room]) {
                        displayName = userState.options.chat_names[room];
                    } else if (room.startsWith('fabulatio_')) {
                        displayName = 'Nova Fabulatio';
                    }
                    
                    const li = document.createElement('li');
                    li.className = 'chat-item' + (room === currentRoom ? ' active' : '');
                    li.onclick = () => selectRoom(room);
                    li.innerHTML = `<span class="chat-item-name" data-room="${room}" title="${displayName}">${displayName}</span> 
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
    
    let displayName = room;
    if (userState.options && userState.options.chat_names && userState.options.chat_names[room]) {
        displayName = userState.options.chat_names[room];
    } else if (room.startsWith('fabulatio_')) {
        displayName = 'Nova Fabulatio';
    }
    roomLabel.textContent = `[${displayName}]`;
    
    nuntiusInput.disabled = false;
    sendBtn.disabled = false;
    
    // Visual Update Without Full Fetch Before Load
    Array.from(chatListEl.children).forEach(li => {
        const nameEl = li.querySelector('.chat-item-name');
        if (nameEl) {
            li.classList.toggle('active', nameEl.getAttribute('data-room') === room);
        }
    });

    if (refreshSidebar) {
        // Ensure the list shows the currentRoom even if it's virtual
        loadChats(false);
    }

    fetch('api.php?action=load&room=' + encodeURIComponent(room) + '&t=' + Date.now())
        .then(r => r.text())
        .then(text => {
            if (!text || text.trim() === '') {
                chatEl.innerHTML = 'Nihil scriptum est...';
                return;
            }
            
            chatEl.innerHTML = '';
            const messageBlocks = text.split(/(?=^Tute:|^Oraculum:)/m);
            
            messageBlocks.forEach(block => {
                if (!block.trim()) return;
                
                const isUser = block.startsWith('Tute:');
                const isOracle = block.startsWith('Oraculum:');
                
                let cleanBlock = block;
                const msgDiv = document.createElement('div');
                msgDiv.className = isUser ? 'msg-user' : 'msg-oracle';

                if (isOracle) {
                    cleanBlock = block.replace(/^Oraculum:\s*/, '');
                    let thoughtMatch = cleanBlock.match(/<thought>(.*?)<\/thought>(.*)/s);
                    if (thoughtMatch) {
                        let thought = thoughtMatch[1];
                        let actualMsg = thoughtMatch[2];
                        msgDiv.innerHTML = `<strong>Oraculum: </strong>
                            <details class="reasoning-details">
                                <summary>Cogitationes Oraculi...</summary>
                                <div class="reasoning-content">${DOMPurify.sanitize(marked.parse(thought))}</div>
                            </details>
                            <div>${DOMPurify.sanitize(marked.parse(actualMsg))}</div>`;
                    } else {
                        msgDiv.innerHTML = DOMPurify.sanitize(marked.parse('**Oraculum:** ' + cleanBlock));
                    }
                } else {
                    cleanBlock = block.replace(/^Tute:\s*/, '');
                    msgDiv.innerHTML = DOMPurify.sanitize(marked.parse('**Tute:** ' + cleanBlock));
                }
                
                renderMathInElement(msgDiv, {
                    delimiters: [
                        {left: '$$', right: '$$', display: true},
                        {left: '\\[', right: '\\]', display: true},
                        {left: '$', right: '$', display: false},
                        {left: '\\(', right: '\\)', display: false}
                    ],
                    throwOnError: false
                });
                if (typeof addCopyButtons === 'function') addCopyButtons(msgDiv);
                chatEl.appendChild(msgDiv);
            });
            
            chatEl.scrollTop = chatEl.scrollHeight;
        })
        .catch(err => console.error("Error loading chat:", err));
}

function createNewChat() {
    const tempRoomId = 'fabulatio_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    virtualRooms.push(tempRoomId);
    selectRoom(tempRoomId);
}

let roomToRename = '';

function renameRoom(room, event) {
    event.stopPropagation();
    roomToRename = room;
    
    let displayName = room;
    if (userState.options && userState.options.chat_names && userState.options.chat_names[room]) {
        displayName = userState.options.chat_names[room];
    } else if (room.startsWith('fabulatio_')) {
        displayName = 'Nova Fabulatio';
    }
    
    document.getElementById('rename-room-name').textContent = displayName;
    document.getElementById('rename-chat-modal').style.display = 'flex';
    document.getElementById('rename-chat-input').value = displayName;
    document.getElementById('rename-chat-input').select();
}

function closeRenameModal() {
    document.getElementById('rename-chat-modal').style.display = 'none';
}

function submitRenameChat() {
    const newName = document.getElementById('rename-chat-input').value.trim();
    
    let currentDisplayName = roomToRename;
    if (userState.options && userState.options.chat_names && userState.options.chat_names[roomToRename]) {
        currentDisplayName = userState.options.chat_names[roomToRename];
    } else if (roomToRename.startsWith('fabulatio_')) {
        currentDisplayName = 'Nova Fabulatio';
    }

    if (newName && newName !== currentDisplayName) {
        const formData = new URLSearchParams();
        formData.append('action', 'rename');
        formData.append('room', roomToRename);
        formData.append('new_room', newName);
        
        fetch('api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'ok') {
                    if (!userState.options) {
                        userState.options = {};
                    }
                    if (!userState.options.chat_names) {
                        userState.options.chat_names = {};
                    }
                    userState.options.chat_names[roomToRename] = newName;
                    if (currentRoom === roomToRename) {
                        roomLabel.textContent = `[${newName}]`;
                    }
                    loadChats();
                    closeRenameModal();
                } else {
                    if (typeof configAlert === 'function') configAlert("Error renaming: " + data.message, true);
                }
            })
            .catch(err => {
                console.error(err);
                if (typeof configAlert === 'function') configAlert("Rename failed", true);
            });
    } else {
        closeRenameModal();
    }
}

let roomToDelete = '';

function deleteRoom(room, event) {
    event.stopPropagation();
    roomToDelete = room;
    
    let displayName = room;
    if (userState.options && userState.options.chat_names && userState.options.chat_names[room]) {
        displayName = userState.options.chat_names[room];
    } else if (room.startsWith('fabulatio_')) {
        displayName = 'Nova Fabulatio';
    }
    
    const roomSpan = document.getElementById('delete-room-name');
    if (roomSpan) roomSpan.textContent = displayName;
    const modal = document.getElementById('delete-chat-modal');
    if (modal) modal.style.display = 'flex';
}

function closeDeleteModal() {
    const modal = document.getElementById('delete-chat-modal');
    if (modal) modal.style.display = 'none';
}

function submitDeleteChat() {
    if (roomToDelete) {
        const formData = new URLSearchParams();
        formData.append('action', 'delete');
        formData.append('room', roomToDelete);
        
        fetch('api.php', { method: 'POST', body: formData })
            .then(() => {
                // Also remove from virtualRooms if it was just drafted
                virtualRooms = virtualRooms.filter(r => r !== roomToDelete);
                if (userState.options && userState.options.chat_names && userState.options.chat_names[roomToDelete]) {
                    delete userState.options.chat_names[roomToDelete];
                }
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

chatForm.onsubmit = function(e) {
    e.preventDefault();
    const msg = nuntiusInput.value.trim();
    if (!msg || !currentRoom) return;

    if (typeof playHDDSound === 'function') playHDDSound();

    nuntiusInput.value = '';
    nuntiusInput.disabled = true;
    sendBtn.disabled = true;

    if (chatEl.textContent === 'Nihil scriptum est...') {
        chatEl.innerHTML = '';
    }

    const userMsg = document.createElement('div');
    userMsg.className = 'msg-user';
    userMsg.innerHTML = DOMPurify.sanitize(marked.parse(`**Tute:** ${msg}`));
    renderMathInElement(userMsg, {
        delimiters: [
            {left: '$$', right: '$$', display: true},
            {left: '\\[', right: '\\]', display: true},
            {left: '$', right: '$', display: false},
            {left: '\\(', right: '\\)', display: false}
        ], throwOnError: false
    });
    if (typeof addCopyButtons === 'function') addCopyButtons(userMsg);
    chatEl.appendChild(userMsg);

    chatEl.scrollTop = chatEl.scrollHeight;

    const formData = new URLSearchParams();
    formData.append('action', 'send');
    formData.append('room', currentRoom);
    formData.append('nuntius', msg);
    formData.append('lingua', currentLangMode);
    formData.append('search', currentSearchMode);
    formData.append('timezone', Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');
    formData.append('local_time', new Date().toString());

    let oraclePrefix = null;
    let reasoningSpan = null;
    let normalTextSpan = null;

    function ensureOraclePrefix() {
        if (oraclePrefix) return;
        oraclePrefix = document.createElement('div');
        oraclePrefix.className = 'msg-oracle';
        oraclePrefix.innerHTML = `<strong>Oraculum: </strong>`;
        normalTextSpan = document.createElement('span');
        oraclePrefix.appendChild(normalTextSpan);
        chatEl.appendChild(oraclePrefix);
    }

    function completeActiveToolSpans() {
        if (!window.streamingState || !window.streamingState.activeToolSpans) return;
        window.streamingState.activeToolSpans.forEach(toolEl => toolEl.classList.add('completed'));
        window.streamingState.activeToolSpans = [];
    }

    window.streamingState = null;
    fetch('api.php', {
        method: 'POST',
        body: formData,
    }).then(response => {
        const reader = response.body.getReader();
        const decoder = new TextDecoder('utf-8');
        let sseBuffer = '';

        function read() {
            reader.read().then(({ done, value }) => {
                if (done) {
                    nuntiusInput.disabled = false;
                    sendBtn.disabled = false;
                    nuntiusInput.focus();
                    loadChats(); // refresh list in case it was a new chat
                    completeActiveToolSpans();
                    if (reasoningSpan && typeof addCopyButtons === 'function') addCopyButtons(reasoningSpan);
                    if (normalTextSpan && typeof addCopyButtons === 'function') addCopyButtons(normalTextSpan);
                    return;
                }
                sseBuffer += decoder.decode(value, {stream: true});
                const lines = sseBuffer.split('\n');
                sseBuffer = lines.pop(); // Keep the last incomplete chunk in buffer
                
                for (let line of lines) {
                    if (line.startsWith('data: ')) {
                        const dataStr = line.substring(6).trim();
                        if (dataStr === '[DONE]') {
                            if (typeof loadUserState === 'function') loadUserState(); // Update level after chat completes
                            continue;
                        }
                        try {
                            const dataNode = JSON.parse(dataStr);
                            if (dataNode.event === 'renamed') {
                                if (!userState.options) {
                                    userState.options = {};
                                }
                                if (!userState.options.chat_names) {
                                    userState.options.chat_names = {};
                                }
                                userState.options.chat_names[currentRoom] = dataNode.new_name;
                                roomLabel.textContent = `[${dataNode.new_name}]`;
                                virtualRooms = virtualRooms.filter(r => r !== currentRoom);
                                loadChats();
                            } else if (dataNode.event === 'clear_fallback') {
                                // FALLBACK CLEANUP: Model outputted tool call as text.
                                // Clear the wrongly rendered JSON from the chat bubble.
                                if (normalTextSpan) {
                                    normalTextSpan.innerHTML = '';
                                }
                                if (window.streamingState) {
                                    window.streamingState.content = '';
                                }
                            } else if (dataNode.event === 'tool_call') {
                                // 1. Принудительный синхронный рендеринг накопленного контента Шага 1 перед переходом к инструменту
                                if (window.streamingState) {
                                    const s = window.streamingState;
                                    if (s.rid) {
                                        cancelAnimationFrame(s.rid);
                                        s.rid = 0;
                                    }
                                    // Рендерим накопленный текст в СТАРЫЙ normalTextSpan
                                    if (s.content) {
                                        ensureOraclePrefix();
                                        normalTextSpan.innerHTML = DOMPurify.sanitize(marked.parse(s.content));
                                        if (typeof renderMathInElement === 'function') {
                                            renderMathInElement(normalTextSpan, {
                                                delimiters: [
                                                    {left: '$$', right: '$$', display: true}, {left: '\\[', right: '\\]', display: true},
                                                    {left: '$', right: '$', display: false}, {left: '\\(', right: '\\)', display: false}
                                                ], throwOnError: false
                                            });
                                        }
                                    }
                                    if (s.reasoning && reasoningSpan) {
                                        const contentDiv = reasoningSpan.querySelector('.reasoning-content');
                                        if (contentDiv) {
                                            contentDiv.innerHTML = DOMPurify.sanitize(marked.parse(s.reasoning));
                                        }
                                    }
                                }

                                let latinToolName = `[EVOCATIO: ${dataNode.name.toUpperCase()}]`;
                                if (dataNode.name === 'search_web') {
                                    latinToolName = `[EVOCATIO: Investigatio in Tela]`;
                                } else if (dataNode.name === 'search_knowledge_base') {
                                    latinToolName = `[EVOCATIO: Scripturae Necronomiconis]`;
                                } else if (dataNode.name === 'check_weather') {
                                    latinToolName = `[EVOCATIO: Tempestatis et Caeli]`;
                                }

                                const toolSpan = document.createElement('div');
                                toolSpan.className = 'tool-text';
                                toolSpan.textContent = latinToolName;
                                toolSpan.setAttribute('data-text', latinToolName);
                                chatEl.appendChild(toolSpan);
                                
                                if (window.streamingState) {
                                     window.streamingState.content = "";
                                     window.streamingState.reasoning = "";
                                     if (!window.streamingState.activeToolSpans) {
                                         window.streamingState.activeToolSpans = [];
                                     }
                                     window.streamingState.activeToolSpans.push(toolSpan);
                                 } else {
                                     window.streamingState = { reasoning: "", content: "", inThought: false, rid: 0, activeToolSpans: [toolSpan] };
                                 }
                                
                                chatEl.scrollTop = chatEl.scrollHeight;
                             } else if (dataNode.choices && dataNode.choices[0].delta) {
                                 const delta = dataNode.choices[0].delta;
                                  if (delta.reasoning_content || delta.content) {
                                      if (!window.streamingState) {
                                          window.streamingState = { reasoning: "", content: "", inThought: false, rid: 0, activeToolSpans: [] };
                                      }
                                      const s = window.streamingState;
                                      completeActiveToolSpans();
                                      if (delta.reasoning_content) s.reasoning += delta.reasoning_content;
                                     if (delta.content) {
                                         let c = delta.content;
                                         if (c.includes("<thought>")) { s.inThought = true; c = c.replace("<thought>", ""); }
                                         if (c.includes("</thought>")) { s.inThought = false; c = c.replace("</thought>", ""); }
                                         if (s.inThought) s.reasoning += c; else s.content += c;
                                     }
                                     if (!s.rid) {
                                         s.rid = requestAnimationFrame(() => {
                                              if (!window.streamingState) return;
                                              if (s.reasoning) {
                                                  if (!reasoningSpan) {
                                                      reasoningSpan = document.createElement('details');
                                                      reasoningSpan.className = 'reasoning-details';
                                                      reasoningSpan.innerHTML = '<summary>Cogitationes Oraculi...</summary><div class="reasoning-content"></div>';
                                                      if (oraclePrefix) {
                                                          chatEl.insertBefore(reasoningSpan, oraclePrefix);
                                                      } else {
                                                          chatEl.appendChild(reasoningSpan);
                                                      }
                                                 }
                                                 const contentDiv = reasoningSpan.querySelector('.reasoning-content');
                                                 if (contentDiv) contentDiv.innerHTML = DOMPurify.sanitize(marked.parse(s.reasoning));
                                              }
                                              if (s.content) {
                                                  ensureOraclePrefix();
                                                  normalTextSpan.innerHTML = DOMPurify.sanitize(marked.parse(s.content));
                                                  renderMathInElement(normalTextSpan, {
                                                     delimiters: [
                                                         {left: '$$', right: '$$', display: true}, {left: '\\[', right: '\\]', display: true},
                                                         {left: '$', right: '$', display: false}, {left: '\\(', right: '\\)', display: false}
                                                     ], throwOnError: false
                                                 });
                                             }
                                             chatEl.scrollTop = chatEl.scrollHeight;
                                             s.rid = 0;
                                         });
                                     }
                                 }
                             }
                        } catch(e) {}
                    }
                }
                read();
            });
        }
        read();
    }).catch(err => {
        completeActiveToolSpans();
        const errSpan = document.createElement('span');
        errSpan.textContent = "\nError: " + err;
        chatEl.appendChild(errSpan);
        nuntiusInput.disabled = false;
        sendBtn.disabled = false;
    });
};

window.onload = () => {
    if (typeof loadUserState === 'function') {
        loadUserState(true);
    }
    if (typeof typeWriterWelcome === 'function') {
        typeWriterWelcome();
    }
};
