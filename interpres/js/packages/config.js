// Necromancer - Configuration, Themes & Glitches Module

const THEMES = [
    { name: "Viridis (Green)", colors: { main: '#1aff66', bg: '#010501', cont: 'rgba(2, 8, 2, 0.85)', dim: '#0d8033', dark: '#03200b', hov: '#063b15' } },
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
    if (!el) return;
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

function loadUserState(refreshChats = false) {
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
                if (refreshChats && typeof loadChats === 'function') {
                    loadChats(true);
                }
            }
        });
}

function updateOptionsUI() {
    const themeSelect = document.getElementById('config-theme');
    if (!themeSelect) return;
    themeSelect.innerHTML = '';
    THEMES.forEach((t, i) => {
        const opt = document.createElement('option');
        opt.value = i;
        opt.textContent = t.name;
        themeSelect.appendChild(opt);
    });
    themeSelect.value = userState.options.theme || 0;

    document.getElementById('glitch-10').parentElement.style.display = 'block';
    document.getElementById('glitch-11').parentElement.style.display = 'block';
    document.getElementById('glitch-12').parentElement.style.display = 'block';
    document.getElementById('glitch-13').parentElement.style.display = 'block';

    const g = userState.options.glitches || [];
    const masterVol = (typeof userState.options.volume !== 'undefined') ? userState.options.volume : 80;
    const sfxVol = (typeof userState.options.sfxVolume !== 'undefined') ? userState.options.sfxVolume : 80;
    const musicVol = (typeof userState.options.musicVolume !== 'undefined') ? userState.options.musicVolume : 80;

    const masterSlider = document.getElementById('volume-master');
    if (masterSlider) {
        masterSlider.value = masterVol;
        masterSlider.style.accentColor = masterVol > 100 ? '#ff3333' : 'var(--main-color)';
    }
    const masterText = document.getElementById('volume-master-val');
    if (masterText) masterText.textContent = masterVol + '%';

    const sfxSlider = document.getElementById('volume-sfx');
    if (sfxSlider) {
        sfxSlider.value = sfxVol;
        sfxSlider.style.accentColor = sfxVol > 100 ? '#ff3333' : 'var(--main-color)';
    }
    const sfxText = document.getElementById('volume-sfx-val');
    if (sfxText) sfxText.textContent = sfxVol + '%';

    const musicSlider = document.getElementById('volume-music');
    if (musicSlider) {
        musicSlider.value = musicVol;
        musicSlider.style.accentColor = musicVol > 100 ? '#ff3333' : 'var(--main-color)';
    }
    const musicText = document.getElementById('volume-music-val');
    if (musicText) musicText.textContent = musicVol + '%';

    document.getElementById('glitch-10').checked = g.includes(10);
    document.getElementById('glitch-11').checked = g.includes(11);
    document.getElementById('glitch-12').checked = g.includes(12);
    document.getElementById('glitch-13').checked = g.includes(13);
    document.getElementById('glitch-20').checked = g.includes(20);
    document.getElementById('glitch-21').checked = g.includes(21);
    document.getElementById('glitch-22').checked = g.includes(22);
    document.getElementById('glitch-23').checked = g.includes(23);
    document.getElementById('glitch-24').checked = g.includes(24);
    document.getElementById('glitch-25').checked = g.includes(25);
    document.getElementById('glitch-26').checked = g.includes(26);
    document.getElementById('glitch-27').checked = g.includes(27);
    document.getElementById('glitch-28').checked = g.includes(28);

    if(document.getElementById('glitch-31')) document.getElementById('glitch-31').checked = g.includes(31);
    if(document.getElementById('glitch-32')) document.getElementById('glitch-32').checked = g.includes(32);
    if(document.getElementById('glitch-33')) document.getElementById('glitch-33').checked = g.includes(33);
    if(document.getElementById('glitch-34')) document.getElementById('glitch-34').checked = g.includes(34);
    if(document.getElementById('glitch-35')) document.getElementById('glitch-35').checked = g.includes(35);
    if(document.getElementById('glitch-36')) document.getElementById('glitch-36').checked = g.includes(36);
    if(document.getElementById('glitch-37')) document.getElementById('glitch-37').checked = g.includes(37);
    if(document.getElementById('glitch-41')) document.getElementById('glitch-41').checked = g.includes(41);
    if(document.getElementById('glitch-42')) document.getElementById('glitch-42').checked = g.includes(42);
    if(document.getElementById('glitch-43')) document.getElementById('glitch-43').checked = g.includes(43);
    if(document.getElementById('glitch-44')) document.getElementById('glitch-44').checked = g.includes(44);
    if(document.getElementById('glitch-45')) document.getElementById('glitch-45').checked = g.includes(45);
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
    saveVisualOptions(true);
}

function previewGlitches(triggeredBy) {
    if (triggeredBy) {
        const melodyIds = ['glitch-34', 'glitch-41', 'glitch-42', 'glitch-43', 'glitch-44', 'glitch-45'];
        if (melodyIds.includes(triggeredBy.id) && triggeredBy.checked) {
            melodyIds.forEach(id => {
                if (id !== triggeredBy.id) {
                    const el = document.getElementById(id);
                    if (el) el.checked = false;
                }
            });
        }
    }

    document.body.classList.toggle('glitch-shake', document.getElementById('glitch-10').checked);
    document.body.classList.toggle('glitch-chromatic', document.getElementById('glitch-11').checked);
    document.body.classList.toggle('glitch-borders', document.getElementById('glitch-12').checked);
    document.body.classList.toggle('glitch-noise', document.getElementById('glitch-13').checked);
    document.body.classList.toggle('glitch-scanlines', document.getElementById('glitch-20').checked);
    document.body.classList.toggle('glitch-vignette', document.getElementById('glitch-21').checked);
    document.body.classList.toggle('glitch-matrix', document.getElementById('glitch-22').checked);
    document.body.classList.toggle('glitch-fog', document.getElementById('glitch-23').checked);
    document.body.classList.toggle('glitch-blood', document.getElementById('glitch-24').checked);
    document.body.classList.toggle('glitch-stars', document.getElementById('glitch-25').checked);
    document.body.classList.toggle('glitch-eyes', document.getElementById('glitch-26').checked);
    document.body.classList.toggle('glitch-web', document.getElementById('glitch-27').checked);
    document.body.classList.toggle('glitch-pulse', document.getElementById('glitch-28').checked);
    document.body.classList.toggle('show-pacman', document.getElementById('glitch-31') && document.getElementById('glitch-31').checked);
    document.body.classList.toggle('show-mk2', document.getElementById('glitch-32') && document.getElementById('glitch-32').checked);
    document.body.classList.toggle('mute-sounds', document.getElementById('glitch-33') && document.getElementById('glitch-33').checked);
    document.body.classList.toggle('play-melody', document.getElementById('glitch-34') && document.getElementById('glitch-34').checked);
    document.body.classList.toggle('mech-clicks', document.getElementById('glitch-35') && document.getElementById('glitch-35').checked);
    document.body.classList.toggle('ambient-wind', document.getElementById('glitch-36') && document.getElementById('glitch-36').checked);
    document.body.classList.toggle('hum-sound', document.getElementById('glitch-37') && document.getElementById('glitch-37').checked);
    document.body.classList.toggle('play-chibi', document.getElementById('glitch-41') && document.getElementById('glitch-41').checked);
    document.body.classList.toggle('play-underclocked', document.getElementById('glitch-42') && document.getElementById('glitch-42').checked);
    document.body.classList.toggle('play-dizzy', document.getElementById('glitch-43') && document.getElementById('glitch-43').checked);
    document.body.classList.toggle('play-vampire', document.getElementById('glitch-44') && document.getElementById('glitch-44').checked);
    document.body.classList.toggle('play-wicked', document.getElementById('glitch-45') && document.getElementById('glitch-45').checked);
    
    if (typeof syncBackgroundMusic === 'function') {
        syncBackgroundMusic();
    }
    saveVisualOptions(true);
}

function applyOptionsToDOM() {
    applyTheme(userState.options.theme || 0);
    const g = userState.options.glitches || [];
    document.body.classList.toggle('glitch-shake', g.includes(10));
    document.body.classList.toggle('glitch-chromatic', g.includes(11));
    document.body.classList.toggle('glitch-borders', g.includes(12));
    document.body.classList.toggle('glitch-noise', g.includes(13));
    document.body.classList.toggle('glitch-scanlines', g.includes(20));
    document.body.classList.toggle('glitch-vignette', g.includes(21));
    document.body.classList.toggle('glitch-matrix', g.includes(22));
    document.body.classList.toggle('glitch-fog', g.includes(23));
    document.body.classList.toggle('glitch-blood', g.includes(24));
    document.body.classList.toggle('glitch-stars', g.includes(25));
    document.body.classList.toggle('glitch-eyes', g.includes(26));
    document.body.classList.toggle('glitch-web', g.includes(27));
    document.body.classList.toggle('glitch-pulse', g.includes(28));
    document.body.classList.toggle('show-pacman', g.includes(31));
    document.body.classList.toggle('show-mk2', g.includes(32));
    document.body.classList.toggle('mute-sounds', g.includes(33));
    document.body.classList.toggle('play-melody', g.includes(34));
    document.body.classList.toggle('mech-clicks', g.includes(35));
    document.body.classList.toggle('ambient-wind', g.includes(36));
    document.body.classList.toggle('hum-sound', g.includes(37));
    document.body.classList.toggle('play-chibi', g.includes(41));
    document.body.classList.toggle('play-underclocked', g.includes(42));
    document.body.classList.toggle('play-dizzy', g.includes(43));
    document.body.classList.toggle('play-vampire', g.includes(44));
    document.body.classList.toggle('play-wicked', g.includes(45));
    
    if (typeof syncBackgroundMusic === 'function') {
        syncBackgroundMusic();
    }
}

function saveVisualOptions(silent = false) {
    const theme = parseInt(document.getElementById('config-theme').value);
    const glitches = [];
    if (document.getElementById('glitch-10').checked) glitches.push(10);
    if (document.getElementById('glitch-11').checked) glitches.push(11);
    if (document.getElementById('glitch-12').checked) glitches.push(12);
    if (document.getElementById('glitch-13').checked) glitches.push(13);
    if (document.getElementById('glitch-20').checked) glitches.push(20);
    if (document.getElementById('glitch-21').checked) glitches.push(21);
    if (document.getElementById('glitch-22').checked) glitches.push(22);
    if (document.getElementById('glitch-23').checked) glitches.push(23);
    if (document.getElementById('glitch-24').checked) glitches.push(24);
    if (document.getElementById('glitch-25').checked) glitches.push(25);
    if (document.getElementById('glitch-26').checked) glitches.push(26);
    if (document.getElementById('glitch-27').checked) glitches.push(27);
    if (document.getElementById('glitch-28').checked) glitches.push(28);
    if (document.getElementById('glitch-31') && document.getElementById('glitch-31').checked) glitches.push(31);
    if (document.getElementById('glitch-32') && document.getElementById('glitch-32').checked) glitches.push(32);
    if (document.getElementById('glitch-33') && document.getElementById('glitch-33').checked) glitches.push(33);
    if (document.getElementById('glitch-34') && document.getElementById('glitch-34').checked) glitches.push(34);
    if (document.getElementById('glitch-35') && document.getElementById('glitch-35').checked) glitches.push(35);
    if (document.getElementById('glitch-36') && document.getElementById('glitch-36').checked) glitches.push(36);
    if (document.getElementById('glitch-37') && document.getElementById('glitch-37').checked) glitches.push(37);
    if (document.getElementById('glitch-41') && document.getElementById('glitch-41').checked) glitches.push(41);
    if (document.getElementById('glitch-42') && document.getElementById('glitch-42').checked) glitches.push(42);
    if (document.getElementById('glitch-43') && document.getElementById('glitch-43').checked) glitches.push(43);
    if (document.getElementById('glitch-44') && document.getElementById('glitch-44').checked) glitches.push(44);
    if (document.getElementById('glitch-45') && document.getElementById('glitch-45').checked) glitches.push(45);

    const masterSlider = document.getElementById('volume-master');
    const volume = masterSlider ? parseInt(masterSlider.value) : 80;

    const sfxSlider = document.getElementById('volume-sfx');
    const sfxVolume = sfxSlider ? parseInt(sfxSlider.value) : 80;

    const musicSlider = document.getElementById('volume-music');
    const musicVolume = musicSlider ? parseInt(musicSlider.value) : 80;

    userState.options = { theme, glitches, volume, sfxVolume, musicVolume };

    const formData = new URLSearchParams();
    formData.append('action', 'save_options');
    formData.append('options', JSON.stringify(userState.options));

    fetch('api.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'ok') {
                if (!silent) {
                    configAlert("Optiones servatae sunt. (Options saved)");
                }
                applyOptionsToDOM();
            } else {
                configAlert(data.message, true);
            }
        });
}
