<div align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=0:000000,100:003300&height=250&section=header&text=Necromancer&fontSize=90&fontAlignY=38&desc=Ex%20Libris%20Daemonium&descAlignY=61&descAlign=62&fontColor=00FF00" />
</div>

<h1 align="center" style="color: #00FF00; text-shadow: 0 0 10px #00FF00;">
  <img src="https://readme-typing-svg.demolab.com?font=Caveat&size=40&pause=1000&color=00FF00&center=true&vCenter=true&width=600&lines=Tractatus+De+Necromantia;The+Ancient+Oracle+Chat" alt="Typing SVG" />
</h1>

<p align="center">
  <em style="font-family: 'Caveat', 'Brush Script MT', 'Comic Sans MS', cursive; font-size: 1.4em; color: #aaaaaa;">
  "Hoc est repositorium in quo vivit chat antiquus et arcanus..."<br>
  (This is the repository where an ancient and arcane chat dwells...)
  </em>
</p>

---

## 📜 Prologus (Introduction)

**Necromancer** is an arcane chat interface and local backend system wrapped in the aesthetics of a retro CRT terminal and the lore of dark magic. It is designed to act as a gateway to interact with an AI **Oraculum** (Oracle), using Latin incantations, occult themes, and ancient roman personas.

The system is highly modular, bridging ancient programming languages (Pascal) with modern web tech (PHP 8 + WebSockets/SSE) to communicate with modern LLM APIs (OpenAI, LocalAI, vLLM).

---

## 🏛️ Architectura Systematis (System Architecture)

The project is divided into three sacred pillars:

### 1. 🦇 Daemonium (The Core Backend)
* **Language**: FreePascal (`daemonium.pas`)
* **Role**: The heart and soul of the system. It operates on port `8080`, managing TCP sockets, users, chat histories, and the **RAG (Retrieval-Augmented Generation)** knowledge base. All internal logic, variables, and network protocols are meticulously written in Latin.

### 2. 👁️ Interpres (The Web Frontend & BFF)
* **Language**: PHP 8 (`index.php`, `fabulatio.php`, `api.php`)
* **Role**: The visual gateway. It renders an HTML 3.2 / 4.01 classical "console" style interface (green text, black background, CRT scanlines, visual glitches). It uses `fsockopen` to speak with the Daemonium and `cURL` to stream data from the OpenAI-compatible APIs via Server-Sent Events (SSE).

### 3. 🗃️ Tabularium (The Database)
* **Format**: Flat File System (`.txt`)
* **Role**: All data is preserved in physical text files, eschewing modern databases for ancient scrolls.
  - `usores.txt`: Registry of souls (users, emails, passwords).
  - `scientia/scientia.txt`: The occult knowledge base for the RAG system.
  - `fabulatio_*.txt`: The transcribed histories of conversations between users and the Oracle.

---

## ✨ Mystica (Features)

* **Genera Accessus (Login Modes)**:
  * **SPIRITUS**: Guest mode. Enter a nickname and wander the halls.
  * **ANIMA**: Full email & password authentication, with "Remember Me" capabilities.
* **Lingua Auto-Detect**: The Oracle adapts its tongue. Speak to it in Russian, and it responds in Russian. Speak English, it responds in English—always maintaining its ancient Roman philosopher persona.
* **Visus Glitch (Visual Effects)**: Unlockable CRT glitches, screen shakes, and chromatic aberrations as you converse more with the Oracle. Customizable themes (Electinum, Cruor, Matrix, Abyssus, etc.).
* **RAG & Search**: The Oracle can search the ancient `scientia` folder for local domain knowledge, or cast its gaze upon the World Wide Web via DuckDuckGo integration.

---

## 🔮 Magia Nigra (Docker Compose Setup)

The easiest way to summon the system is through the dark arts of Docker.

1. **Prepare the Incantation**:
   Copy `.env.example` to `.env` and provide your API keys.
   ```bash
   cp .env.example .env
   ```

2. **Cast the Spell**:
   ```bash
   docker-compose up --build -d
   ```

3. **Enter the Gateway**:
   Open your browser to [http://localhost:666](http://localhost:666).

---

## 🛠️ Quomodo incipere sine Magia (Manual Setup)

For the purists who wish to compile the magic themselves:

1. **Requirements**: Have `fpc` (FreePascal) and `php` installed on your machine.
2. **Environment**: 
   ```bash
   export OPENAI_API_KEY="your_api_key_here"
   export OPENAI_API_MODEL="gpt-4o-mini" # or your local model
   ```
3. **Awaken the Daemonium** (Terminal 1):
   ```bash
   cd daemonium
   fpc daemonium.pas
   ./daemonium
   ```
4. **Awaken the Interpres** (Terminal 2):
   ```bash
   cd interpres
   php -S 127.0.0.1:666
   ```
5. **Enter**: Navigate to `http://127.0.0.1:666`.

---

## ⚙️ Quomodo API configurare (API Configuration)

The system is agnostic. You can point it to **OpenAI**, **LocalAI**, **vLLM**, or any compatible API by adjusting your `.env` file:

```ini
OPENAI_API_URL=https://api.openai.com/v1/chat/completions
# Or Google Gemini proxy: https://generativelanguage.googleapis.com/v1beta/openai/chat/completions
OPENAI_API_MODEL=gpt-4o-mini
OPENAI_API_KEY=your_key_here
```

Reconnect the Docker containers after modifying:
```bash
docker-compose up --build -d
```

---

<p align="center">
  <em style="font-family: 'Caveat', 'Comic Sans MS', cursive; font-size: 1.3em; color: #a30000; text-shadow: 0 0 5px #ff0000;">
  * Memento: Omnes viae Romam ducunt! *<br>
  (Remember: All roads lead to Rome!)
  </em>
</p>
