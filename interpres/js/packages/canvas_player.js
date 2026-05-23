/**
 * Necromancer - Magia Alta: Canvas Player Component
 * Высокопроизводительный интерактивный HTML5 Canvas плеер в стиле ретро-CRT терминала.
 * Поддерживает три режима: oscilloscope (осциллограф), radar (радар) и graph (бегущий график).
 */

const TEMPLATE = `
<style>
    :host {
        display: block;
        margin: 20px auto;
        max-width: 800px;
        width: 100%;
        font-family: 'Courier New', Courier, monospace;
        color: #00ff66;
        background-color: #050a05;
        border: 2px solid #005511;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0, 255, 102, 0.15), inset 0 0 15px rgba(0, 85, 17, 0.3);
        overflow: hidden;
        position: relative;
    }

    /* Эффект стеклянного CRT монитора */
    .crt-monitor {
        position: relative;
        padding: 10px;
        background: radial-gradient(circle, #0c1a0c 0%, #050a05 100%);
    }

    .crt-monitor::after {
        content: " ";
        display: block;
        position: absolute;
        top: 0; left: 0; bottom: 0; right: 0;
        background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
        aspect-ratio: 16/9;
        background-size: 100% 4px, 6px 100%;
        pointer-events: none;
        z-index: 10;
        opacity: 0.85;
    }

    /* Рамка холста */
    .canvas-container {
        position: relative;
        border: 1px solid #00aa33;
        border-radius: 4px;
        background-color: #020502;
        overflow: hidden;
        box-shadow: inset 0 0 20px rgba(0, 255, 102, 0.4);
    }

    canvas {
        display: block;
        width: 100%;
        height: auto;
        aspect-ratio: 16/9;
    }

    /* Панель управления */
    .control-panel {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px;
        background-color: #030703;
        border-top: 1px solid #005511;
        font-size: 12px;
        z-index: 20;
        position: relative;
    }

    .controls-left, .controls-right {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .btn {
        background-color: #051405;
        color: #00ff66;
        border: 1px solid #00aa33;
        padding: 5px 12px;
        border-radius: 3px;
        cursor: pointer;
        font-family: inherit;
        text-transform: uppercase;
        font-weight: bold;
        transition: all 0.2s ease;
        box-shadow: 0 0 5px rgba(0, 255, 102, 0.1);
    }

    .btn:hover {
        background-color: #00ff66;
        color: #020502;
        box-shadow: 0 0 10px rgba(0, 255, 102, 0.5);
    }

    .btn:active {
        transform: scale(0.95);
    }

    .btn.active {
        background-color: #00aa33;
        color: #020502;
    }

    /* Слайдеры */
    .slider-container {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .slider {
        -webkit-appearance: none;
        appearance: none;
        width: 80px;
        height: 4px;
        background: #003308;
        border-radius: 2px;
        outline: none;
    }

    .slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #00ff66;
        cursor: pointer;
        box-shadow: 0 0 5px #00ff66;
    }

    /* Заголовок и телеметрия */
    .telemetry-header {
        display: flex;
        justify-content: space-between;
        padding: 5px 10px;
        font-size: 11px;
        color: #00aa33;
        border-bottom: 1px solid #003308;
    }

    .status-indicator {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #ff3333;
        box-shadow: 0 0 5px #ff3333;
    }

    .status-dot.active {
        background-color: #00ff66;
        box-shadow: 0 0 5px #00ff66;
        animation: blink 1s infinite alternate;
    }

    @keyframes blink {
        from { opacity: 0.4; }
        to { opacity: 1; }
    }

    .hidden {
        display: none !important;
    }
</style>

<div class="telemetry-header">
    <span id="title-text">SYSTEMA VISUALIS: [INCOGNITA]</span>
    <div class="status-indicator">
        <span class="status-dot" id="status-dot"></span>
        <span id="status-text">DISCONNECTED</span>
    </div>
</div>

<div class="crt-monitor">
    <div class="canvas-container">
        <canvas id="screen" width="1600" height="900"></canvas>
    </div>
</div>

<div class="control-panel">
    <div class="controls-left">
        <button class="btn" id="btn-play">PAUSE</button>
        <button class="btn" id="btn-clear">PURGE</button>
        <div class="slider-container">
            <span>VELOCITY:</span>
            <input type="range" class="slider" id="slider-speed" min="1" max="15" value="5">
        </div>
    </div>
    <div class="controls-right">
        <button class="btn active" id="btn-mode-oscilloscope">OSC</button>
        <button class="btn" id="btn-mode-radar">RAD</button>
        <button class="btn" id="btn-mode-graph">GRA</button>
    </div>
</div>
`;

export class CanvasPlayer extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this.shadowRoot.innerHTML = TEMPLATE;

        // Элементы
        this.canvas = this.shadowRoot.getElementById('screen');
        this.ctx = this.canvas.getContext('2d');
        this.btnPlay = this.shadowRoot.getElementById('btn-play');
        this.btnClear = this.shadowRoot.getElementById('btn-clear');
        this.sliderSpeed = this.shadowRoot.getElementById('slider-speed');
        this.statusDot = this.shadowRoot.getElementById('status-dot');
        this.statusText = this.shadowRoot.getElementById('status-text');
        this.titleText = this.shadowRoot.getElementById('title-text');
        
        this.btnOsc = this.shadowRoot.getElementById('btn-mode-oscilloscope');
        this.btnRad = this.shadowRoot.getElementById('btn-mode-radar');
        this.btnGra = this.shadowRoot.getElementById('btn-mode-graph');

        // Состояние
        this.simId = this.getAttribute('sim-id') || '';
        this.simTitle = this.getAttribute('title') || 'PHYSICA SIMULATIO';
        this.mode = this.getAttribute('type') || 'oscilloscope'; // oscilloscope, radar, graph
        
        this.isPlaying = true;
        this.queue = [];
        this.speed = parseInt(this.sliderSpeed.value); // Точек за кадр
        this.points = []; // История обработанных точек
        this.isFinished = false;

        // Поля авто-масштабирования
        this.minX = -1;
        this.maxX = 1;
        this.minY = -1;
        this.maxY = 1;
        this.rangeInitialized = false;

        // Параметры радара
        this.radarAngle = 0;
        this.radarSweepSpeed = 0.02; // Скорость вращения луча

        // Привязка обработчиков событий
        this.btnPlay.onclick = () => this.togglePlay();
        this.btnClear.onclick = () => this.purge();
        this.sliderSpeed.oninput = (e) => this.speed = parseInt(e.target.value);
        
        this.btnOsc.onclick = () => this.setMode('oscilloscope');
        this.btnRad.onclick = () => this.setMode('radar');
        this.btnGra.onclick = () => this.setMode('graph');

        // Глобальные прослушиватели событий симуляции
        this.onInitEvent = this.handleInit.bind(this);
        this.onDataEvent = this.handleData.bind(this);
        this.onEndEvent = this.handleEnd.bind(this);
    }

    connectedCallback() {
        this.titleText.textContent = `SYSTEMA VISUALIS: [${this.simTitle.toUpperCase()}]`;
        this.setMode(this.mode);
        this.purge();

        // Подписываемся на события
        window.addEventListener(`sim_init_${this.simId}`, this.onInitEvent);
        window.addEventListener(`sim_data_${this.simId}`, this.onDataEvent);
        window.addEventListener(`sim_end_${this.simId}`, this.onEndEvent);

        // Если симуляция уже идет или только что инициализирована
        this.statusDot.classList.add('active');
        this.statusText.textContent = 'COMPUTING...';

        // Запуск бесконечного цикла отрисовки
        this.tick();
    }

    disconnectedCallback() {
        window.removeEventListener(`sim_init_${this.simId}`, this.onInitEvent);
        window.removeEventListener(`sim_data_${this.simId}`, this.onDataEvent);
        window.removeEventListener(`sim_end_${this.simId}`, this.onEndEvent);
        this.isPlaying = false;
    }

    handleInit(e) {
        if (e.detail && e.detail.title) {
            this.simTitle = e.detail.title;
            this.titleText.textContent = `SYSTEMA VISUALIS: [${this.simTitle.toUpperCase()}]`;
        }
        this.statusDot.classList.add('active');
        this.statusText.textContent = 'COMPUTING...';
    }

    handleData(e) {
        if (e.detail && e.detail.data) {
            // Добавляем точку в очередь
            this.queue.push(e.detail.data);
        }
    }

    handleEnd() {
        this.isFinished = true;
        this.statusDot.classList.remove('active');
        this.statusText.textContent = 'TERMINATED';
    }

    togglePlay() {
        this.isPlaying = !this.isPlaying;
        this.btnPlay.textContent = this.isPlaying ? 'PAUSE' : 'RESUME';
        if (this.isPlaying) {
            this.tick();
        }
    }

    purge() {
        this.queue = [];
        this.points = [];
        this.rangeInitialized = false;
        this.minX = -1; this.maxX = 1;
        this.minY = -1; this.maxY = 1;
        this.ctx.fillStyle = '#020502';
        this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
    }

    setMode(mode) {
        this.mode = mode;
        this.btnOsc.classList.toggle('active', mode === 'oscilloscope');
        this.btnRad.classList.toggle('active', mode === 'radar');
        this.btnGra.classList.toggle('active', mode === 'graph');
        this.purge();
    }

    updateAutoRange(x, y) {
        if (!this.rangeInitialized) {
            this.minX = x;
            this.maxX = x;
            this.minY = y;
            this.maxY = y;
            this.rangeInitialized = true;
        } else {
            if (x < this.minX) this.minX = x;
            if (x > this.maxX) this.maxX = x;
            if (y < this.minY) this.minY = y;
            if (y > this.maxY) this.maxY = y;
        }

        // Предотвращение нулевого деления при одинаковых точках
        if (this.minX === this.maxX) { this.minX -= 1; this.maxX += 1; }
        if (this.minY === this.maxY) { this.minY -= 1; this.maxY += 1; }
    }

    // Перевод координат симуляции в экранные координаты Canvas
    toScreenCoords(x, y) {
        const padding = 80;
        const w = this.canvas.width;
        const h = this.canvas.height;

        const xSpan = this.maxX - this.minX;
        const ySpan = this.maxY - this.minY;

        const screenX = padding + ((x - this.minX) / xSpan) * (w - 2 * padding);
        // Инвертируем Y, так как в Canvas Y идет сверху вниз
        const screenY = h - padding - ((y - this.minY) / ySpan) * (h - 2 * padding);

        return { x: screenX, y: screenY };
    }

    tick() {
        if (!this.isPlaying) return;

        // Обрабатываем порцию точек из очереди в соответствии с выбранной скоростью
        if (this.queue.length > 0) {
            const count = Math.min(this.queue.length, this.speed);
            for (let i = 0; i < count; i++) {
                const pt = this.queue.shift();
                if (pt && typeof pt.x === 'number' && typeof pt.y === 'number') {
                    this.updateAutoRange(pt.x, pt.y);
                    this.points.push(pt);
                    // Ограничиваем историю точек для оптимизации памяти
                    if (this.points.length > 3000) {
                        this.points.shift();
                    }
                }
            }
        }

        // Рендерим холст в зависимости от текущего режима
        switch (this.mode) {
            case 'oscilloscope':
                this.drawOscilloscope();
                break;
            case 'radar':
                this.drawRadar();
                break;
            case 'graph':
                this.drawGraph();
                break;
        }

        // Рендерим CRT сетку разметки поверх изображения
        this.drawGrid();

        requestAnimationFrame(() => this.tick());
    }

    drawOscilloscope() {
        const ctx = this.ctx;
        const w = this.canvas.width;
        const h = this.canvas.height;

        // Создаем эффект затухания фосфора (afterglow) через полупрозрачное закрашивание
        ctx.fillStyle = 'rgba(2, 5, 2, 0.06)';
        ctx.fillRect(0, 0, w, h);

        if (this.points.length < 2) return;

        ctx.shadowBlur = 10;
        ctx.shadowColor = '#00ff66';
        ctx.lineWidth = 3;
        ctx.strokeStyle = 'rgba(0, 255, 102, 0.85)';
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        ctx.beginPath();
        // Рисуем последние добавленные точки с максимальной яркостью
        const startIndex = Math.max(0, this.points.length - 200);
        const startPt = this.toScreenCoords(this.points[startIndex].x, this.points[startIndex].y);
        ctx.moveTo(startPt.x, startPt.y);

        for (let i = startIndex + 1; i < this.points.length; i++) {
            const pt = this.toScreenCoords(this.points[i].x, this.points[i].y);
            ctx.lineTo(pt.x, pt.y);
        }
        ctx.stroke();
        ctx.shadowBlur = 0; // Сбрасываем размытие для оптимизации
    }

    drawRadar() {
        const ctx = this.ctx;
        const w = this.canvas.width;
        const h = this.canvas.height;
        const cx = w / 2;
        const cy = h / 2;
        const maxRadius = Math.min(w, h) / 2 - 40;

        // Эффект затухания радара
        ctx.fillStyle = 'rgba(2, 5, 2, 0.04)';
        ctx.fillRect(0, 0, w, h);

        // Рисуем фоновую круговую сетку
        ctx.strokeStyle = 'rgba(0, 85, 17, 0.3)';
        ctx.lineWidth = 1;
        for (let r = maxRadius / 4; r <= maxRadius; r += maxRadius / 4) {
            ctx.beginPath();
            ctx.arc(cx, cy, r, 0, Math.PI * 2);
            ctx.stroke();
        }

        // Линии перекрестия
        ctx.beginPath();
        ctx.moveTo(cx - maxRadius, cy);
        ctx.lineTo(cx + maxRadius, cy);
        ctx.moveTo(cx, cy - maxRadius);
        ctx.lineTo(cx, cy + maxRadius);
        ctx.stroke();

        // Рисуем вращающийся луч
        this.radarAngle = (this.radarAngle + this.radarSweepSpeed) % (Math.PI * 2);
        
        const grad = ctx.createRadialGradient(cx, cy, 0, cx, cy, maxRadius);
        grad.addColorStop(0, 'rgba(0, 255, 102, 0.15)');
        grad.addColorStop(1, 'rgba(0, 85, 17, 0)');

        ctx.fillStyle = grad;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        // Сектор засветки луча радара
        ctx.arc(cx, cy, maxRadius, this.radarAngle - 0.2, this.radarAngle);
        ctx.lineTo(cx, cy);
        ctx.fill();

        // Сам тонкий сканирующий луч
        ctx.strokeStyle = 'rgba(0, 255, 102, 0.8)';
        ctx.lineWidth = 2;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.lineTo(cx + Math.cos(this.radarAngle) * maxRadius, cy + Math.sin(this.radarAngle) * maxRadius);
        ctx.stroke();

        // Отображение точек (целей)
        if (this.points.length === 0) return;

        ctx.shadowBlur = 12;
        ctx.shadowColor = '#ff3333';

        this.points.forEach(pt => {
            // Переводим декартовы координаты симуляции в полярные относительно центра
            const xSpan = this.maxX - this.minX || 2;
            const ySpan = this.maxY - this.minY || 2;
            
            const normX = ((pt.x - this.minX) / xSpan) * 2 - 1; // -1 to 1
            const normY = ((pt.y - this.minY) / ySpan) * 2 - 1; // -1 to 1

            const distance = Math.sqrt(normX * normX + normY * normY) * maxRadius;
            if (distance > maxRadius) return; // За пределами экрана радара

            const angle = Math.atan2(normY, normX);

            // Разница углов между лучом и целью
            let angleDiff = this.radarAngle - angle;
            while (angleDiff < 0) angleDiff += Math.PI * 2;
            angleDiff = angleDiff % (Math.PI * 2);

            // Точка светится ярко, когда луч проходит над ней, и плавно гаснет
            if (angleDiff < 1.0) {
                const alpha = 1.0 - angleDiff; // Яркость спадает по мере отдаления луча
                ctx.fillStyle = `rgba(255, 51, 51, ${alpha})`;
                ctx.beginPath();
                ctx.arc(cx + Math.cos(angle) * distance, cy + Math.sin(angle) * distance, 6, 0, Math.PI * 2);
                ctx.fill();
            }
        });

        ctx.shadowBlur = 0;
    }

    drawGraph() {
        const ctx = this.ctx;
        const w = this.canvas.width;
        const h = this.canvas.height;
        const padding = 80;

        ctx.fillStyle = '#020502';
        ctx.fillRect(0, 0, w, h);

        if (this.points.length < 2) return;

        // Рисуем бегущий непрерывный график.
        // Масштабируем по оси X не на весь массив, а сдвигаем во времени (показываем последние N точек)
        const showCount = 400;
        const startIndex = Math.max(0, this.points.length - showCount);
        
        ctx.shadowBlur = 8;
        ctx.shadowColor = '#00ff66';
        ctx.strokeStyle = '#00ff66';
        ctx.lineWidth = 3;
        ctx.lineCap = 'round';
        ctx.beginPath();

        for (let i = startIndex; i < this.points.length; i++) {
            const pt = this.points[i];
            
            // X рассчитывается на основе позиции в отображаемом слайсе
            const xPercent = (i - startIndex) / (showCount - 1);
            const screenX = padding + xPercent * (w - 2 * padding);

            // Y масштабируется по полной авто-шкале
            const ySpan = this.maxY - this.minY || 2;
            const screenY = h - padding - ((pt.y - this.minY) / ySpan) * (h - 2 * padding);

            if (i === startIndex) {
                ctx.moveTo(screenX, screenY);
            } else {
                ctx.lineTo(screenX, screenY);
            }
        }
        ctx.stroke();
        ctx.shadowBlur = 0;

        // Рендерим текущие координаты в углу в текстовом формате
        const currentPt = this.points[this.points.length - 1];
        ctx.fillStyle = '#00aa33';
        ctx.font = '24px Courier New';
        ctx.fillText(`X: ${currentPt.x.toFixed(4)}`, padding + 20, padding - 20);
        ctx.fillText(`Y: ${currentPt.y.toFixed(4)}`, padding + 240, padding - 20);
    }

    drawGrid() {
        const ctx = this.ctx;
        const w = this.canvas.width;
        const h = this.canvas.height;
        const padding = 80;

        // В режиме радара сетка рисуется отдельно внутри drawRadar
        if (this.mode === 'radar') return;

        ctx.strokeStyle = 'rgba(0, 85, 17, 0.15)';
        ctx.lineWidth = 1;

        // Вертикальная и горизонтальная сетка
        const cols = 10;
        const rows = 6;

        ctx.beginPath();
        for (let i = 0; i <= cols; i++) {
            const x = padding + (i / cols) * (w - 2 * padding);
            ctx.moveTo(x, padding);
            ctx.lineTo(x, h - padding);
        }
        for (let j = 0; j <= rows; j++) {
            const y = padding + (j / rows) * (h - 2 * padding);
            ctx.moveTo(padding, y);
            ctx.lineTo(w - padding, y);
        }
        ctx.stroke();

        // Рисуем границы экрана осциллографа
        ctx.strokeStyle = '#005511';
        ctx.lineWidth = 2;
        ctx.strokeRect(padding, padding, w - 2 * padding, h - 2 * padding);
    }
}

// Регистрируем кастомный элемент
customElements.define('canvas-player', CanvasPlayer);
