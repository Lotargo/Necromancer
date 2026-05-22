// Necromancer - Audio Module (Web Audio API Retro Sounds)

const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

function getMasterCoeff() {
    if (typeof userState !== 'undefined' && userState.options && typeof userState.options.volume !== 'undefined') {
        return userState.options.volume / 100;
    }
    return 0.8;
}

function getSfxCoeff() {
    if (typeof userState !== 'undefined' && userState.options && typeof userState.options.sfxVolume !== 'undefined') {
        return userState.options.sfxVolume / 100;
    }
    return 0.8;
}

function getMusicCoeff() {
    if (typeof userState !== 'undefined' && userState.options && typeof userState.options.musicVolume !== 'undefined') {
        return userState.options.musicVolume / 100;
    }
    return 0.8;
}

function getFinalSfxCoeff() {
    return getMasterCoeff() * getSfxCoeff();
}

function getFinalMusicCoeff() {
    return getMasterCoeff() * getMusicCoeff();
}

function playClickSound() {
    if (audioCtx.state === 'suspended') { audioCtx.resume(); }
    const oscillator = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();
    
    oscillator.type = 'square';
    oscillator.frequency.setValueAtTime(300, audioCtx.currentTime);
    oscillator.frequency.exponentialRampToValueAtTime(40, audioCtx.currentTime + 0.1);
    
    const volCoeff = getFinalSfxCoeff();
    gainNode.gain.setValueAtTime(0.08 * volCoeff, audioCtx.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.1);
    
    oscillator.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    
    oscillator.start();
    oscillator.stop(audioCtx.currentTime + 0.1);
}

let humOscillator = null;
let humGain = null;

function updateHumSound() {
    const isHumEnabled = document.body.classList.contains('hum-sound');
    
    if (audioCtx.state === 'suspended' && isHumEnabled) { audioCtx.resume(); }
    
    if (humGain) {
        if (isHumEnabled) {
            const volCoeff = getFinalSfxCoeff();
            humGain.gain.setTargetAtTime(0.015 * volCoeff, audioCtx.currentTime, 0.1);
        } else {
            humGain.gain.setTargetAtTime(0, audioCtx.currentTime, 0.1);
        }
    }
}

function initHumSound() {
    if (humOscillator) return;
    if (audioCtx.state === 'suspended') { audioCtx.resume(); }
    
    humOscillator = audioCtx.createOscillator();
    humGain = audioCtx.createGain();
    let filter = audioCtx.createBiquadFilter();
    
    humOscillator.type = 'sawtooth';
    humOscillator.frequency.setValueAtTime(50, audioCtx.currentTime); // 50Hz mains hum
    
    filter.type = 'lowpass';
    filter.frequency.setValueAtTime(120, audioCtx.currentTime);
    
    const isHumEnabled = document.body.classList.contains('hum-sound');
    const volCoeff = getFinalSfxCoeff();
    humGain.gain.setValueAtTime(isHumEnabled ? 0.015 * volCoeff : 0, audioCtx.currentTime);
    
    humOscillator.connect(filter);
    filter.connect(humGain);
    humGain.connect(audioCtx.destination);
    
    humOscillator.start();
    
    const observer = new MutationObserver(updateHumSound);
    observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
}

function playHDDSound() {
    if (audioCtx.state === 'suspended') { audioCtx.resume(); }

    let t = audioCtx.currentTime;
    const volCoeff = getFinalSfxCoeff();
    
    // Low spindle rumble
    let rumbleOsc = audioCtx.createOscillator();
    let rumbleGain = audioCtx.createGain();
    rumbleOsc.type = 'sawtooth';
    rumbleOsc.frequency.setValueAtTime(20, t);
    rumbleOsc.frequency.linearRampToValueAtTime(70, t + 1.5);
    rumbleOsc.frequency.linearRampToValueAtTime(75, t + 3.0);
    rumbleGain.gain.setValueAtTime(0.01 * volCoeff, t);
    rumbleGain.gain.linearRampToValueAtTime(0.05 * volCoeff, t + 1.5);
    rumbleGain.gain.setTargetAtTime(0, t + 2.5, 0.5);
    
    // High motor whine
    let whineOsc = audioCtx.createOscillator();
    let whineGain = audioCtx.createGain();
    whineOsc.type = 'triangle';
    whineOsc.frequency.setValueAtTime(80, t);
    whineOsc.frequency.exponentialRampToValueAtTime(1200, t + 1.5);
    whineOsc.frequency.linearRampToValueAtTime(1250, t + 3.0);
    whineGain.gain.setValueAtTime(0.001 * volCoeff, t);
    whineGain.gain.linearRampToValueAtTime(0.02 * volCoeff, t + 1.5);
    whineGain.gain.setTargetAtTime(0, t + 2.5, 0.5);
    
    let filter = audioCtx.createBiquadFilter();
    filter.type = 'lowpass';
    filter.frequency.setValueAtTime(2000, t);
    
    rumbleOsc.connect(rumbleGain);
    whineOsc.connect(whineGain);
    rumbleGain.connect(filter);
    whineGain.connect(filter);
    filter.connect(audioCtx.destination);
    
    rumbleOsc.start(t);
    whineOsc.start(t);
    rumbleOsc.stop(t + 3.5);
    whineOsc.stop(t + 3.5);
    
    // Seeking clicks
    let seekInterval = setInterval(() => {
        let cTime = audioCtx.currentTime;
        let clickOsc = audioCtx.createOscillator();
        let clickGain = audioCtx.createGain();
        clickOsc.type = 'square';
        clickOsc.frequency.setValueAtTime(600 + Math.random() * 600, cTime);
        
        const seekVolCoeff = getFinalSfxCoeff();
        clickGain.gain.setValueAtTime((0.015 + Math.random() * 0.015) * seekVolCoeff, cTime);
        clickGain.gain.exponentialRampToValueAtTime(0.001, cTime + 0.015);
        
        let clickFilter = audioCtx.createBiquadFilter();
        clickFilter.type = 'bandpass';
        clickFilter.frequency.setValueAtTime(1500, cTime);
        
        clickOsc.connect(clickGain);
        clickGain.connect(clickFilter);
        clickFilter.connect(audioCtx.destination);
        
        clickOsc.start(cTime);
        clickOsc.stop(cTime + 0.015);
    }, 35);
    
    setTimeout(() => clearInterval(seekInterval), 2500);
}

function playMechClickSound() {
    if (audioCtx.state === 'suspended') { audioCtx.resume(); }
    let t = audioCtx.currentTime;
    const volCoeff = getFinalSfxCoeff();
    
    // "Thock" sound (low frequency)
    let osc = audioCtx.createOscillator();
    let gain = audioCtx.createGain();
    osc.type = 'triangle';
    osc.frequency.setValueAtTime(150, t);
    osc.frequency.exponentialRampToValueAtTime(40, t + 0.05);
    gain.gain.setValueAtTime(0.3 * volCoeff, t);
    gain.gain.exponentialRampToValueAtTime(0.001, t + 0.05);
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.start(t);
    osc.stop(t + 0.05);

    // High frequency "clack"
    let osc2 = audioCtx.createOscillator();
    let gain2 = audioCtx.createGain();
    osc2.type = 'square';
    osc2.frequency.setValueAtTime(800, t);
    osc2.frequency.exponentialRampToValueAtTime(200, t + 0.02);
    gain2.gain.setValueAtTime(0.05 * volCoeff, t);
    gain2.gain.exponentialRampToValueAtTime(0.001, t + 0.02);
    
    let filter = audioCtx.createBiquadFilter();
    filter.type = 'highpass';
    filter.frequency.setValueAtTime(1000, t);
    
    osc2.connect(gain2);
    gain2.connect(filter);
    filter.connect(audioCtx.destination);
    osc2.start(t);
    osc2.stop(t + 0.02);
}

let windBufferSrc = null;
let windGain = null;
let windFilter = null;

function initWindSound() {
    if (windBufferSrc) return;
    if (audioCtx.state === 'suspended') { audioCtx.resume(); }
    
    let bufferSize = audioCtx.sampleRate * 2;
    let buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
    let data = buffer.getChannelData(0);
    for (let i = 0; i < bufferSize; i++) {
        data[i] = Math.random() * 2 - 1;
    }
    
    windBufferSrc = audioCtx.createBufferSource();
    windBufferSrc.buffer = buffer;
    windBufferSrc.loop = true;
    
    windFilter = audioCtx.createBiquadFilter();
    windFilter.type = 'bandpass';
    windFilter.frequency.setValueAtTime(400, audioCtx.currentTime);
    windFilter.Q.setValueAtTime(0.8, audioCtx.currentTime);
    
    let lfo = audioCtx.createOscillator();
    lfo.type = 'sine';
    lfo.frequency.setValueAtTime(0.15, audioCtx.currentTime);
    let lfoGain = audioCtx.createGain();
    lfoGain.gain.setValueAtTime(300, audioCtx.currentTime);
    lfo.connect(lfoGain);
    lfoGain.connect(windFilter.frequency);
    lfo.start();
    
    windGain = audioCtx.createGain();
    windGain.gain.setValueAtTime(0, audioCtx.currentTime);
    
    windBufferSrc.connect(windFilter);
    windFilter.connect(windGain);
    windGain.connect(audioCtx.destination);
    windBufferSrc.start();
    
    setInterval(() => {
        const isWindy = document.body.classList.contains('ambient-wind');
        const volCoeff = getFinalSfxCoeff();
        let targetGain = isWindy ? 0.06 * volCoeff : 0;
        windGain.gain.setTargetAtTime(targetGain, audioCtx.currentTime, 1.0);
    }, 1000);
}

function updateVolumeLabel(type, val) {
    const intVal = parseInt(val);
    if (type === 'master') {
        const lbl = document.getElementById('volume-master-val');
        if (lbl) lbl.textContent = val + '%';
        if (typeof userState !== 'undefined' && userState.options) {
            userState.options.volume = intVal;
        }
        const slider = document.getElementById('volume-master');
        if (slider) slider.style.accentColor = intVal > 100 ? '#ff3333' : 'var(--main-color)';
        updateHumSound();
        updateWindSoundVolume();
        updateMusicVolumeOnFly();
    } else if (type === 'sfx') {
        const lbl = document.getElementById('volume-sfx-val');
        if (lbl) lbl.textContent = val + '%';
        if (typeof userState !== 'undefined' && userState.options) {
            userState.options.sfxVolume = intVal;
        }
        const slider = document.getElementById('volume-sfx');
        if (slider) slider.style.accentColor = intVal > 100 ? '#ff3333' : 'var(--main-color)';
        updateHumSound();
        updateWindSoundVolume();
    } else if (type === 'music') {
        const lbl = document.getElementById('volume-music-val');
        if (lbl) lbl.textContent = val + '%';
        if (typeof userState !== 'undefined' && userState.options) {
            userState.options.musicVolume = intVal;
        }
        const slider = document.getElementById('volume-music');
        if (slider) slider.style.accentColor = intVal > 100 ? '#ff3333' : 'var(--main-color)';
        updateMusicVolumeOnFly();
    }
}

function updateWindSoundVolume() {
    if (windGain) {
        const isWindy = document.body.classList.contains('ambient-wind');
        const finalSfxCoeff = getFinalSfxCoeff();
        let targetGain = isWindy ? 0.06 * finalSfxCoeff : 0;
        windGain.gain.setTargetAtTime(targetGain, audioCtx.currentTime, 0.2);
    }
}

function updateMusicVolumeOnFly() {
    if (currentBgMusic) {
        const targetVol = getFinalMusicCoeff() * 0.25;
        currentBgMusic.volume = Math.min(1.0, Math.max(0.0, targetVol));
    }
}

let currentBgMusic = null;
let currentBgMusicUrl = '';

function stopAllMelodies() {
    if (currentBgMusic) {
        try {
            currentBgMusic.pause();
            currentBgMusic.currentTime = 0;
        } catch(e) {}
        currentBgMusic = null;
        currentBgMusicUrl = '';
    }
    if (melodyInterval) {
        clearInterval(melodyInterval);
        melodyInterval = null;
    }
}

function syncBackgroundMusic() {
    const isChibi = document.body.classList.contains('play-chibi');
    const isUnderclocked = document.body.classList.contains('play-underclocked');
    const isDizzy = document.body.classList.contains('play-dizzy');
    const isVampire = document.body.classList.contains('play-vampire');
    const isWicked = document.body.classList.contains('play-wicked');
    const isSynth = document.body.classList.contains('play-melody');

    let targetUrl = '';
    if (isChibi) targetUrl = 'audio/chibi-ninja.mp3';
    else if (isUnderclocked) targetUrl = 'audio/underclocked.mp3';
    else if (isDizzy) targetUrl = 'audio/dizzy-spells.mp3';
    else if (isVampire) targetUrl = 'audio/vampire-killer.mp3';
    else if (isWicked) targetUrl = 'audio/wicked-child.mp3';

    const volCoeff = getFinalMusicCoeff();

    if (!targetUrl) {
        if (currentBgMusic) {
            try {
                currentBgMusic.pause();
            } catch(e) {}
            currentBgMusic = null;
            currentBgMusicUrl = '';
        }
        
        if (isSynth) {
            if (!melodyInterval) {
                initMelody();
            }
        } else {
            if (melodyInterval) {
                clearInterval(melodyInterval);
                melodyInterval = null;
            }
        }
    } else {
        if (melodyInterval) {
            clearInterval(melodyInterval);
            melodyInterval = null;
        }

        if (currentBgMusicUrl !== targetUrl) {
            if (currentBgMusic) {
                try {
                    currentBgMusic.pause();
                } catch(e) {}
            }
            currentBgMusicUrl = targetUrl;
            currentBgMusic = new Audio(targetUrl);
            currentBgMusic.loop = true;
            currentBgMusic.volume = Math.min(1.0, Math.max(0.0, volCoeff * 0.25));
            currentBgMusic.play().catch(err => {
                console.log("Autoplay blocked or playback error:", err);
            });
        } else {
            if (currentBgMusic) {
                currentBgMusic.volume = Math.min(1.0, Math.max(0.0, volCoeff * 0.25));
                if (currentBgMusic.paused) {
                    currentBgMusic.play().catch(err => {});
                }
            }
        }
    }
}

let melodyInterval = null;
const scale = [261.63, 293.66, 329.63, 349.23, 392.00, 440.00, 493.88, 523.25]; // C major
let noteIndex = 0;
const melodyPattern = [0, 2, 4, 7, 7, 4, 2, 0, 1, 3, 5, 7, 5, 3, 1, 0];

function initMelody() {
    if (melodyInterval) return;
    if (audioCtx.state === 'suspended') { audioCtx.resume(); }
    melodyInterval = setInterval(() => {
        const playM = document.body.classList.contains('play-melody');
        
        if (playM) {
            let t = audioCtx.currentTime;
            let freq = scale[melodyPattern[noteIndex]] || scale[0];
            noteIndex = (noteIndex + 1) % melodyPattern.length;
            
            let osc = audioCtx.createOscillator();
            let gain = audioCtx.createGain();
            osc.type = 'square';
            osc.frequency.setValueAtTime(freq / 2, t);
            
            const volCoeff = getFinalMusicCoeff();
            gain.gain.setValueAtTime(0.015 * volCoeff, t);
            gain.gain.exponentialRampToValueAtTime(0.001, t + 0.15);
            
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start(t);
            osc.stop(t + 0.2);
        }
    }, 200);
}

document.addEventListener('click', function(e) {
    initHumSound();
    initWindSound();
    initMelody();
    if (typeof syncBackgroundMusic === 'function') syncBackgroundMusic();
    if (document.body.classList.contains('mute-sounds')) return;
    if (e.target.closest('button') || 
        e.target.closest('input[type="submit"]') || 
        e.target.closest('input[type="checkbox"]') || 
        e.target.closest('.chat-item')) {
        if (document.body.classList.contains('mech-clicks')) {
            playMechClickSound();
        } else {
            playClickSound();
        }
    }
});
