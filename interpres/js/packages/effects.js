// Necromancer - Canvas Effects Module (Matrix Rain and Advanced Occult Canvas Shaders)

// Matrix Rain Implementation
const canvas = document.getElementById('matrixCanvas');
const ctx = canvas.getContext('2d');
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;
const alphabet = 'АァカサтанахмаяяравагазадабапаиикисичинихимиривигизидибипиууксуцнуфумуююлугузбудупуеекесетенехемелегезедебепеоокосотонохомогологозодобоповнABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
const fontSize = 16;
const columns = Math.floor(canvas.width / fontSize);
const drops = [];
for(let x = 0; x < columns; x++) drops[x] = 1;

function drawMatrix() {
    if (!document.body.classList.contains('glitch-matrix')) return;
    ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    const color = getComputedStyle(document.documentElement).getPropertyValue('--main-color').trim() || '#0F0';
    ctx.fillStyle = color;
    ctx.font = fontSize + 'px monospace';
    for(let i = 0; i < drops.length; i++) {
        const text = alphabet.charAt(Math.floor(Math.random() * alphabet.length));
        ctx.fillText(text, i * fontSize, drops[i] * fontSize);
        if(drops[i] * fontSize > canvas.height && Math.random() > 0.975) drops[i] = 0;
        drops[i]++;
    }
}
setInterval(drawMatrix, 33);

// Advanced Effects Canvas (Blood, Stars, Eyes, Web)
const advCanvas = document.getElementById('advCanvas');
const advCtx = advCanvas.getContext('2d');
advCanvas.width = window.innerWidth;
advCanvas.height = window.innerHeight;

let mouseX = 0; let mouseY = 0;
window.addEventListener('mousemove', e => { mouseX = e.clientX; mouseY = e.clientY; });

const pWeb = [];
for(let i=0; i<80; i++) pWeb.push({x: Math.random()*window.innerWidth, y: Math.random()*window.innerHeight, vx: (Math.random()-0.5)*1.5, vy: (Math.random()-0.5)*1.5});

const pStars = [];
for(let i=0; i<200; i++) pStars.push({x: (Math.random()-0.5)*window.innerWidth*2, y: (Math.random()-0.5)*window.innerHeight*2, z: Math.random()*2000});

const pEyes = [];
function spawnEye() {
    if (pEyes.length < 5) pEyes.push({x: Math.random()*advCanvas.width, y: Math.random()*advCanvas.height, life: 0, maxLife: 100 + Math.random()*100});
    setTimeout(spawnEye, 1000 + Math.random()*3000);
}
spawnEye();

// Инициализация парящих искр (Ignis Fatuus)
const pEmbers = [];
const numEmbers = 120;

function hexToRgb(hex) {
    hex = hex.replace(/^#/, '');
    if (hex.length === 3) {
        hex = hex.split('').map(c => c + c).join('');
    }
    const num = parseInt(hex, 16);
    return {
        r: (num >> 16) & 255,
        g: (num >> 8) & 255,
        b: num & 255
    };
}

function initEmbers() {
    pEmbers.length = 0;
    const w = advCanvas.width;
    const h = advCanvas.height;
    for (let i = 0; i < numEmbers; i++) {
        pEmbers.push({
            x: Math.random() * w,
            y: Math.random() * h,
            size: 1 + Math.random() * 2.5,
            speedY: 0.3 + Math.random() * 0.7,
            amplitude: 0.1 + Math.random() * 0.4,
            frequency: 0.005 + Math.random() * 0.015,
            angle: Math.random() * Math.PI * 2,
            pulseSpeed: 0.005 + Math.random() * 0.01,
            pulsePhase: Math.random() * Math.PI * 2,
            alpha: Math.random()
        });
    }
}
initEmbers();

const bloodStreams = [];
const bloodDrops = [];
const PIXEL_SIZE = 6;

function initBlood() {
    bloodStreams.length = 0;
    bloodDrops.length = 0;
    const cols = Math.ceil(window.innerWidth / (PIXEL_SIZE * 3));
    for(let i = 0; i < cols; i++) {
        bloodStreams.push({
            x: i * PIXEL_SIZE * 3,
            yStart: 0,
            yEnd: -Math.random() * 200,
            speed: 0.5 + Math.random() * 2.0,
            width: PIXEL_SIZE * (2 + Math.floor(Math.random() * 2)),
            maxHeight: window.innerHeight * (0.2 + Math.random() * 0.6),
            state: 'growing'
        });
    }
}
initBlood();

window.addEventListener('resize', () => {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    advCanvas.width = window.innerWidth;
    advCanvas.height = window.innerHeight;
    initBlood();
    initEmbers();
});

let occultAngle = 0;

function drawOccultCircles(ctx, w, h) {
    const mainC = getComputedStyle(document.documentElement).getPropertyValue('--main-color').trim() || '#1aff66';
    ctx.save();
    ctx.translate(w / 2, h / 2);
    ctx.rotate(occultAngle);
    occultAngle += 0.0008; // Медленное плавное вращение

    // Свечение
    ctx.shadowBlur = 15;
    ctx.shadowColor = mainC;
    ctx.strokeStyle = mainC;
    ctx.fillStyle = mainC;
    ctx.lineWidth = 1.5;

    // 1. Внешнее кольцо
    ctx.beginPath();
    ctx.arc(0, 0, 250, 0, Math.PI * 2);
    ctx.stroke();

    // 2. Внутреннее кольцо
    ctx.lineWidth = 2.5;
    ctx.beginPath();
    ctx.arc(0, 0, 210, 0, Math.PI * 2);
    ctx.stroke();
    ctx.lineWidth = 1;

    // 3. Декоративное кольцо со штрихами
    ctx.beginPath();
    ctx.arc(0, 0, 180, 0, Math.PI * 2);
    ctx.stroke();
    
    ctx.lineWidth = 0.5;
    for (let a = 0; a < Math.PI * 2; a += Math.PI / 30) {
        const cos = Math.cos(a);
        const sin = Math.sin(a);
        ctx.beginPath();
        ctx.moveTo(180 * cos, 180 * sin);
        ctx.lineTo(210 * cos, 210 * sin);
        ctx.stroke();
    }
    ctx.lineWidth = 1;

    // 4. Семиконечная оккультная звезда
    const numPoints = 7;
    const points = [];
    for (let i = 0; i < numPoints; i++) {
        const a = (i * Math.PI * 2) / numPoints - Math.PI / 2;
        points.push({ x: 180 * Math.cos(a), y: 180 * Math.sin(a) });
    }
    
    ctx.beginPath();
    let curr = 0;
    ctx.moveTo(points[curr].x, points[curr].y);
    for (let i = 0; i < numPoints; i++) {
        curr = (curr + 3) % numPoints;
        ctx.lineTo(points[curr].x, points[curr].y);
    }
    ctx.closePath();
    ctx.stroke();

    points.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, 4, 0, Math.PI * 2);
        ctx.fill();
    });

    // 5. Древние руны по кругу
    const runes = ['ᚠ', 'ᚢ', 'ᚦ', 'ᚨ', 'ᚱ', 'ᚲ', 'ᚷ', 'ᚹ', 'ᚺ', 'ᚾ', 'ᛁ', 'ᛃ', 'ᛇ', 'ᛈ', 'ᛉ', 'ᛊ', 'ᛏ', 'ᛒ', 'ᛖ', 'ᛗ', 'ᛚ', 'ᛜ', 'ᛞ', 'ᛟ'];
    ctx.font = '18px monospace';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    
    runes.forEach((rune, index) => {
        const a = (index * Math.PI * 2) / runes.length - Math.PI / 2;
        ctx.save();
        ctx.rotate(a);
        ctx.fillText(rune, 0, -230);
        ctx.restore();
    });

    ctx.restore();
}

function drawAdv() {
    requestAnimationFrame(drawAdv);
    const w = advCanvas.width; const h = advCanvas.height;
    const bBody = document.body;
    
    const isWeb = bBody.classList.contains('glitch-web');
    const isStars = bBody.classList.contains('glitch-stars');
    const isEyes = bBody.classList.contains('glitch-eyes');
    const isBlood = bBody.classList.contains('glitch-blood');
    const isEmbers = bBody.classList.contains('glitch-fog');
    
    if (isStars) {
        advCtx.fillStyle = 'rgba(0,0,0,0.3)';
        advCtx.fillRect(0,0,w,h);
    } else {
        advCtx.clearRect(0,0,w,h);
    }
    
    // Рисуем оккультные круги
    drawOccultCircles(advCtx, w, h);

    if (isEmbers) {
        const mainC = getComputedStyle(document.documentElement).getPropertyValue('--main-color').trim() || '#1aff66';
        const rgb = hexToRgb(mainC);
        
        pEmbers.forEach(p => {
            // Медленное движение вверх
            p.y -= p.speedY;
            
            // Синусоидальные горизонтальные колебания
            p.angle += p.frequency;
            p.x += Math.sin(p.angle) * p.amplitude;
            
            // Интерактивное притяжение к курсору мыши
            const dx = mouseX - p.x;
            const dy = mouseY - p.y;
            const dist = Math.sqrt(dx*dx + dy*dy);
            if (dist < 150) {
                const force = (150 - dist) / 150 * 0.25;
                p.x += dx * force * 0.08;
                p.y += dy * force * 0.08;
            }
            
            // Заворачивание за верх экрана
            if (p.y < -10) {
                p.y = h + 10;
                p.x = Math.random() * w;
                p.alpha = 0;
            }
            if (p.x < -10) p.x = w + 10;
            if (p.x > w + 10) p.x = -10;
            
            // Мерцание прозрачности
            p.pulsePhase += p.pulseSpeed;
            const curAlpha = 0.2 + 0.8 * Math.sin(p.pulsePhase);
            
            // 1. Мягкое неоновое свечение (Ореол)
            const grad = advCtx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.size * 3.5);
            grad.addColorStop(0, `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${curAlpha * 0.85})`);
            grad.addColorStop(0.3, `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${curAlpha * 0.35})`);
            grad.addColorStop(1, 'rgba(0,0,0,0)');
            
            advCtx.fillStyle = grad;
            advCtx.beginPath();
            advCtx.arc(p.x, p.y, p.size * 3.5, 0, Math.PI * 2);
            advCtx.fill();
            
            // 2. Яркое ядро искры
            advCtx.fillStyle = `rgba(255, 255, 255, ${curAlpha * 0.9})`;
            advCtx.beginPath();
            advCtx.arc(p.x, p.y, p.size * 0.7, 0, Math.PI * 2);
            advCtx.fill();
        });
    }

    const mainC = getComputedStyle(document.documentElement).getPropertyValue('--main-color').trim() || '#1aff66';

    if (isStars) {
        advCtx.fillStyle = mainC;
        pStars.forEach(s => {
            s.z -= 5;
            if (s.z <= 0) { s.z = 2000; s.x = (Math.random()-0.5)*w*2; s.y = (Math.random()-0.5)*h*2; }
            const px = (s.x / s.z) * 1000 + w/2;
            const py = (s.y / s.z) * 1000 + h/2;
            const size = (1 - s.z/2000) * 3;
            advCtx.beginPath(); advCtx.arc(px,py,size,0,Math.PI*2); advCtx.fill();
        });
    }

    if (isBlood) {
        // 1. Draw Streams dripping from top of the screen
        bloodStreams.forEach(s => {
            if (s.state === 'growing') {
                s.yEnd += s.speed;
                // Splash drops occasionally from the tip
                if (Math.random() < 0.015 && s.yEnd > 0) {
                    bloodDrops.push({
                        x: s.x + s.width / 2,
                        y: s.yEnd,
                        vy: s.speed + 0.5 + Math.random() * 1.5,
                        size: PIXEL_SIZE
                    });
                }
                if (s.yEnd >= s.maxHeight) {
                    s.state = 'drying';
                }
            } else if (s.state === 'drying') {
                s.yStart += s.speed * 1.3;
                s.yEnd += s.speed * 0.15; // Slow sliding downwards (inertia)

                if (s.yStart >= s.yEnd) {
                    // Reset stream
                    s.state = 'growing';
                    s.yStart = 0;
                    s.yEnd = -Math.random() * 150;
                    s.speed = 0.5 + Math.random() * 2.0;
                    s.width = PIXEL_SIZE * (2 + Math.floor(Math.random() * 2));
                    s.maxHeight = h * (0.2 + Math.random() * 0.6);
                }
            }

            const curYStart = Math.floor(s.yStart / PIXEL_SIZE) * PIXEL_SIZE;
            const curYEnd = Math.floor(s.yEnd / PIXEL_SIZE) * PIXEL_SIZE;
            const curH = curYEnd - curYStart;
            if (curH <= 0) return;

            const curX = s.x;
            const curW = s.width;

            // Volumetric 3D pixel effect
            // 1. Shadow background (Dark Maroon) - 1px wider on each side
            advCtx.fillStyle = '#4A0000';
            advCtx.fillRect(curX - PIXEL_SIZE, curYStart, curW + PIXEL_SIZE * 2, curH);
            // Stream Tip
            advCtx.fillRect(curX, curYEnd, curW, PIXEL_SIZE);

            // 2. Base Blood Red Color
            advCtx.fillStyle = '#800000';
            advCtx.fillRect(curX, curYStart, curW, curH);

            // 3. Bright Red Highlights (wet glare)
            advCtx.fillStyle = '#D00000';
            advCtx.fillRect(curX, curYStart, PIXEL_SIZE, curH - PIXEL_SIZE);
        });

        // 2. Draw Falling Pixels (Splatter Drops with gravity)
        for (let i = bloodDrops.length - 1; i >= 0; i--) {
            const d = bloodDrops[i];
            d.y += d.vy;
            d.vy += 0.15; // Gravity acceleration

            if (d.y > h) {
                bloodDrops.splice(i, 1);
                continue;
            }

            const px = Math.floor(d.x / PIXEL_SIZE) * PIXEL_SIZE;
            const py = Math.floor(d.y / PIXEL_SIZE) * PIXEL_SIZE;

            // 3D Pixel Droplet
            // Shadow / Border
            advCtx.fillStyle = '#4A0000';
            advCtx.fillRect(px - PIXEL_SIZE, py - PIXEL_SIZE, d.size + PIXEL_SIZE * 2, d.size + PIXEL_SIZE * 2);

            // Drop Core
            advCtx.fillStyle = '#800000';
            advCtx.fillRect(px, py, d.size, d.size);

            // Drop Highlights
            advCtx.fillStyle = '#D00000';
            advCtx.fillRect(px, py, PIXEL_SIZE, PIXEL_SIZE);
        }
    }

    if (isEyes) {
        pEyes.forEach((e, i) => {
            e.life++;
            if(e.life > e.maxLife) { pEyes.splice(i,1); return; }
            const alpha = Math.sin((e.life/e.maxLife)*Math.PI) * 0.8;
            advCtx.fillStyle = mainC;
            advCtx.globalAlpha = alpha;
            advCtx.beginPath(); advCtx.ellipse(e.x - 15, e.y, 8, 4, 0, 0, Math.PI*2); advCtx.fill();
            advCtx.beginPath(); advCtx.ellipse(e.x + 15, e.y, 8, 4, 0, 0, Math.PI*2); advCtx.fill();
            advCtx.fillStyle = '#000';
            advCtx.beginPath(); advCtx.arc(e.x - 15, e.y, 2, 0, Math.PI*2); advCtx.fill();
            advCtx.beginPath(); advCtx.arc(e.x + 15, e.y, 2, 0, Math.PI*2); advCtx.fill();
            advCtx.globalAlpha = 1.0;
        });
    }

    if (isWeb) {
        advCtx.fillStyle = mainC;
        advCtx.strokeStyle = mainC;
        pWeb.forEach(p => {
            p.x += p.vx; p.y += p.vy;
            if(p.x < 0 || p.x > w) p.vx *= -1;
            if(p.y < 0 || p.y > h) p.vy *= -1;
            advCtx.beginPath(); advCtx.arc(p.x, p.y, 1.5, 0, Math.PI*2); advCtx.fill();
        });
        for(let i=0; i<pWeb.length; i++){
            for(let j=i+1; j<pWeb.length; j++){
                const dx = pWeb[i].x - pWeb[j].x;
                const dy = pWeb[i].y - pWeb[j].y;
                const dist = Math.sqrt(dx*dx + dy*dy);
                if(dist < 100) {
                    advCtx.globalAlpha = 1 - dist/100;
                    advCtx.beginPath(); advCtx.moveTo(pWeb[i].x, pWeb[i].y); advCtx.lineTo(pWeb[j].x, pWeb[j].y); advCtx.stroke();
                }
            }
            const mdX = pWeb[i].x - mouseX;
            const mdY = pWeb[i].y - mouseY;
            const mDist = Math.sqrt(mdX*mdX + mdY*mdY);
            if(mDist < 150) {
                advCtx.globalAlpha = 1 - mDist/150;
                advCtx.beginPath(); advCtx.moveTo(pWeb[i].x, pWeb[i].y); advCtx.lineTo(mouseX, mouseY); advCtx.stroke();
            }
        }
        advCtx.globalAlpha = 1.0;
    }
}
drawAdv();
