// Necromancer - UI & Animation Module

// Inject copy buttons to all code pre blocks
function addCopyButtons(container) {
    if (!container) return;
    const pres = container.querySelectorAll('pre');
    pres.forEach(pre => {
        if (pre.querySelector('.copy-code-btn')) return;
        pre.style.position = 'relative';
        const btn = document.createElement('button');
        btn.className = 'copy-code-btn';
        btn.type = 'button';
        btn.innerHTML = 'Copy';
        btn.onclick = function(e) {
            e.stopPropagation();
            const code = pre.querySelector('code');
            const textToCopy = code ? code.innerText : pre.innerText.replace(/Copy$/, '');
            navigator.clipboard.writeText(textToCopy).then(() => {
                btn.innerHTML = 'Copied!';
                btn.style.borderColor = 'var(--main-color)';
                btn.style.color = 'var(--main-color)';
                setTimeout(() => {
                    btn.innerHTML = 'Copy';
                    btn.style.borderColor = 'rgba(0, 255, 102, 0.3)';
                    btn.style.color = 'rgba(0, 255, 102, 0.7)';
                }, 2000);
            });
        };
        pre.appendChild(btn);
    });
}

// Retro keystroke sound effects for the main input field
nuntiusInput.addEventListener('keydown', function(e) {
    if (e.repeat) return;
    if (typeof initHumSound === 'function') initHumSound();
    if (typeof initWindSound === 'function') initWindSound();
    if (typeof initMelody === 'function') initMelody();
    if (typeof syncBackgroundMusic === 'function') syncBackgroundMusic();
    if (document.body.classList.contains('mute-sounds')) return;
    if (e.key.length === 1 || e.key === 'Backspace' || e.key === 'Delete' || e.key === 'Enter') {
        if (document.body.classList.contains('mech-clicks')) {
            if (typeof playMechClickSound === 'function') playMechClickSound();
        } else {
            if (typeof playClickSound === 'function') playClickSound();
        }
    }
});

// Welcome Animation Logic
const welcomeText = "CONEXIO STABILITA...\nSALVE, " + USOR_NOMEN + ".\nORACULUM TE EXSPECTAT.";
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

modalEl.addEventListener('click', closeWelcomeModal);

// Close Modals on Outside Click
window.addEventListener('click', function(event) {
    const configModal = document.getElementById('config-modal');
    const renameModal = document.getElementById('rename-chat-modal');
    const deleteModal = document.getElementById('delete-chat-modal');
    
    if (event.target === configModal) {
        if (typeof closeConfigModal === 'function') closeConfigModal();
    }
    if (event.target === renameModal) {
        if (typeof closeRenameModal === 'function') closeRenameModal();
    }
    if (event.target === deleteModal) {
        if (typeof closeDeleteModal === 'function') closeDeleteModal();
    }
});

// Toggles State Management
function updateToggleUI() {
    const btnLang = document.getElementById('toggle-lang');
    const btnSearch = document.getElementById('toggle-search');
    const info = document.getElementById('toggles-info');

    if (!btnLang || !btnSearch || !info) return;

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
