<div align="center">
  <img src="assets/logo.svg" width="100%" alt="Necromancer Cyber-Occult Logo" />
</div>

<h1 align="center" style="color: #00FF00; text-shadow: 0 0 10px #00FF00;">
  <img src="https://readme-typing-svg.demolab.com?font=Caveat&size=40&pause=1000&color=00FF00&center=true&vCenter=true&width=600&lines=Tractatus+De+Necromantia;The+Ancient+Oracle+Chat;Ex+Libris+Daemonium+Postgres" alt="Typing SVG" />
</h1>

<div align="center">
  <img src="assets/subtitle.svg" width="100%" alt="Hoc est repositorium in quo vivit chat antiquus et arcanus, nunc potentia SQL confirmatus... (This is the repository where an arcane chat dwells, now fortified by the power of SQL...)" />
</div>

---

<img src="assets/headers/prologus.svg" width="100%" alt="Prologus (Introduction)" />

**Necromancer** is an arcane chat interface and local backend system wrapped in the aesthetics of a retro CRT terminal and the lore of dark magic. It acts as a gateway to interact with an AI **Oraculum** (Oracle), using Latin incantations, occult themes, and ancient Roman personas.

While the project preserves its **retro CRT shell** and immersive mechanical soundscape, we have evolved its underlying architecture. The backend has migrated from fragile flat-text scroll files to a robust **PostgreSQL** database, coupled with the state-of-the-art **Argon2id hashing** (with seamless legacy **SHA-1 migration**) for user credentials. The AI gateway has also been modernized to utilize the industry-standard **`openai-php/client`** GenAI SDK for robust model orchestration and strict typing. This ensures transactional integrity and security without losing a single drop of its gothic visual and auditory atmosphere.

Detailed internal structures and setup guidelines can be found in our **[Occult Library / Docs](docs/)**.

---

<img src="assets/headers/architectura.svg" width="100%" alt="Architectura Systematis (System Architecture)" />

The system is divided into five sacred pillars:

```mermaid
graph TD
    A[BFF Front: Interpres PHP / JS] <-->|Sockets TCP / Port 8080| B[Backend Core: Daemonium Pascal]
    B <-->|libpq / Port 5432| C[(Database: PostgreSQL)]
    A <-->|TCP / Port 8081| D[Balancer: Aequilibrium Lua]
    D -->|Rotation| E[AI Providers: Gemini / Groq / Cerebras]
    A <-->|Sockets TCP / Port 8082| M[Sandbox: Mechanica Pascal]
```

### 1. Daemonium (The Core Backend)
* **Language**: FreePascal (`daemonium/`)
* **Role**: The heart and soul of the system. Operating on port `8080`, it manages socket connections, authorization, and message histories.
* **Modularized Architecture**: The daemon is split into 7 specialized units:
  * `Auxilia`: Common helpers and response wrappers.
  * `Cryptographia`: Pure Pascal **Argon2id** password hashing using `HashLib4Pascal`.
  * `Database`: Thread-safe PostgreSQL connection and schema initialization.
  * `ClavesLlm`: Dynamic API key status, rate-limit quarantine, and synchronization.
  * `Usores`: User registration, profile management, and automatic legacy SHA-1 migration.
  * `Fabulatio`: Interactive chat room lists and message history.
  * `Scientia`: Local keyword-indexed RAG search.
  * `daemonium.pas`: Core high-performance TCP socket dispatcher.
* **LLM Key Registry**: Protected by a fast Argon2id variant, it stores provider key health and failure events in PostgreSQL to filter disabled or resting keys automatically.

### 2. Interpres (The Web Frontend & BFF)
* **Language**: PHP 8.3 + Vanilla JS
* **Role**: The visual gateway. It renders a classical CRT console interface with:
    * **SSE (Server-Sent Events)**: For real-time streamed responses from the Oracle.
    * **Advanced Audio Mixer**: A three-channel audio control center (Summa/Master, Sonus/SFX, Musica/Music) utilizing the **Web Audio API**. Boost volumes up to 200% with dynamic visual crimson overdrive glows above 100% and enjoy looped occult background scores.
    * **HTML5 Canvas**: Eldritch visual overlays (Matrix Rain, Blood Drips, Watching Eyes, and Ignis Fatuus reactive embers).
    * **Sanitization**: Deep input parsing to prevent pipe injection attacks over TCP sockets.
    * **Provider Failover**: Retries early provider failures by unpinning the current balancer session and requesting the next available provider/model candidate.

### 3. Aequilibrium (The Load Balancer)
* **Language**: Lua / LuaJIT (`aequilibrium/aequilibrium.lua`)
* **Role**: Runs on port `8081`. Provides provider rotation, key rotation, and session pinning for LLM connections when `AEQUILIBRIUM_ENABLED=true`.
* **Resiliency**: Built-in crash protection (`pcall`) and socket descriptor auto-closure to handle high concurrent traffic.

### 4. Tabularium (The Database)
* **System**: **PostgreSQL 15-alpine**
* **Role**: Replaces the ancient flat-text database. It stores structured tables:
  * `usores`: Nicknames, emails, secure Argon2id (and legacy SHA-1) password hashes, and user types.
  * `optiones`: Persisted user UI configurations stored as validated JSON objects.
  * `fabulatio`: Full conversational logs, automatically indexed for instantaneous search and fast retrieval.
  * **Relational Cascades**: Integrated `ON DELETE CASCADE ON UPDATE CASCADE` rules automatically clean user settings and chats when a soul is deleted.

### 5. Mechanica (The Computational Sandbox)
* **Language**: FreePascal (`mechanica/`)
* **Role**: The compilation and execution core running on port `8082`. It receives Pascal code from `Interpres`, verifies safety, compiles it using `fpc` in a secure sandbox workspace, streams the real-time execution outputs back to the socket connection, and performs atomic cleanups.

---

<img src="assets/headers/mystica.svg" width="100%" alt="Mystica (Features)" />

### Genera Accessus (Login Modes)
* **SPIRITUS**: Anonymous guest entry. Sign in with a nickname and explore the forum.
* **ANIMA**: Advanced email and password registration. Credentials are cryptographically protected using state-of-the-art **Argon2id** (with seamless backwards-compatible **SHA-1 migration** upon login).

### Oraculum Agenticum (Agentic Features)
* **Instrumenta (Tools)**: The Oracle dynamically utilizes:
  * `search_web`: Queries external search engines via a local DDG scraper.
  * `search_knowledge_base`: Retrieves RAG context from local occult texts.
  * `solve_discrete_math`: Compiles and executes custom Pascal code on the `Mechanica` microservice to solve logic/math problems.
  * `run_streaming_simulation`: Generates physical simulations inside `Mechanica` that stream real-time coordinate data to the HTML5 Canvas client player.
  * `save_to_knowledge_base`: Automatically stores successful bug fixes or compiler library patterns back into the RAG directory.
* **Ratio Cogitandi (Reasoning)**: Streams the Oracle's thought process step-by-step prior to writing the final Latin response.
* **Nomina Automatica**: The Oracle automatically names new chat sessions with elegant Latin phrases based on the first message context.
* **Key Quarantine**: Repeated `429` responses place a key into a 30-minute rest period, while invalid or billing-blocked keys are disabled automatically.

### Visus & Auditio (CRT Aesthetics)
* **Screen Shaking & CRT Glitches**: Fluid scanlines, chromatic aberration, and simulated mechanical hardware noise recreate a classic terminal feel.
* **Occult Skins**: Seven interactive full-screen HTML5 Canvas backgrounds including *Imber Codicis* (Matrix rain), *Sanguis Stillans* (dripping blood), *Nexus Fati* (web of fate), and *Ignis Fatuus* (Necromantic Embers - reactive floating particles that gather around your cursor).
* **Eldritch Acoustics**: Highly immersive, layered acoustics utilizing the Web Audio API:
  * **HDD Seek**: Realistic mechanical clicks and hard-drive seek noises synchronized with text generation.
  * **Haptic Keyboard**: Physical mechanical clicks for every key pressed.
  * **Laboratory Ambience**: Eerie, loopable white noise (wind, CRT hums, static electricity) to set a dark gothic atmosphere.

### Progressio & Moderatio (Initiation & Account Control)
* **User Levels**: Ascend through 13 secret levels of occult initiation based on the volume and depth of your interactions with the Oracle.
* **Profile Management**: Safely rename your soul (cascading across the database), change password keys, or completely erase your registration and histories (with complete atomic database cleanup).

---

<img src="assets/headers/inscriptio.svg" width="100%" alt="Inscriptio (Registration)" />

### Securing Your Soul (Registration & Authorization)

Necromancer supports two distinct paths to cross the threshold into the Oracle's presence:

* **SPIRITUS (Anonymous Entry)**: A quick, guest entrance. Simply choose a custom nickname and enter. No passwords or credentials are required. Ideal for rapid incantations.
* **ANIMA (Permanent Enrollment)**: A secure registration using a valid email and password. This unlocks personalized profiles, user level progression tracking, and persistent chat archives across devices.

#### Cryptographic Security
To register an **ANIMA** account, provide a nickname, email, and password. The backend (**Daemonium**) automatically hashes the credentials using the industry-standard **Argon2id** algorithm, guaranteeing cryptographic resistance against brute-force attacks.

If you have a legacy account from the ancient Necromancer version, the system automatically and transparently migrates your legacy **SHA-1** hash to the modern **Argon2id** hash the next time you log in, upgrading your security with zero friction.

<div align="center">
  <br>
  <h3>Occult Registration Terminal</h3>
  <img src="assets/necromancer_registration.png" width="800" alt="Occult Registration Terminal" />
  <p><em>The ANIMA registration gateway, wrapped in terminal phosphors and retro scanlines.</em></p>
</div>

---

<img src="assets/headers/gallery.svg" width="100%" alt="Media Expositio (Gallery)" />

<div align="center">
  <h3>Occult CRT Terminal V2</h3>
  <img src="assets/necromancer_interface_v2.png" width="800" alt="CRT Occult Terminal Screenshot" />
  <p><em>Behold the neon-green glowing phosphor interface with live AI Oracle reasoning.</em></p>
  
  <br>

  <h3>Eldritch Visual Overlays</h3>
  <img src="assets/effects_cruor_theme.png" width="800" alt="Eldritch Effects Screenshot" />
  <p><em>Advanced HTML5 canvas rendering - Dripping Blood (Sanguis Stillans) and Necromantic Embers (Ignis Fatuus) in Cruor theme.</em></p>

  <br>

  <details>
    <summary><b>More Visual Proofs (Additional Interface Screenshots)</b></summary>
    <br>
    <h4>Matrix Rain Theme (Imber Codicis)</h4>
    <img src="assets/effects_matrix_rain.png" width="800" alt="Matrix Rain Overlay" />
    <br><br>
    <h4>Ignis Fatuus Embers Theme</h4>
    <img src="assets/effects_ignis_fatuus.png" width="800" alt="Ignis Fatuus Overlay" />
    <br><br>
    <h4>Cruor Blood Drips (Sanguis Stillans)</h4>
    <img src="assets/effects_blood_drips.png" width="800" alt="Blood Drips Overlay" />
    <br>
  </details>

  <br>

  <h3>Oraculum Chat Interaction Flow</h3>
  <img src="assets/necromancer_chat_demo.gif" width="800" alt="Oraculum Chat Demo" />
  <p><em>Real-time streaming responses and detailed step-by-step thinking process of the Oraculum.</em></p>

  <br>

  <h3>Real-Time Computational Sandbox (Mechanica)</h3>
  <img src="assets/mechanica_simulation_demo.gif" width="800" alt="Mechanica Simulation Demo" />
  <p><em>Interactive physical simulation coordinates compiled in Pascal and streamed live to the Canvas player.</em></p>
</div>

---

<img src="assets/headers/api_config.svg" width="100%" alt="API Configuration (Quomodo API configurare)" />

The Oraculum supports two explicit LLM connection modes:

1. **Aequilibrium enabled**: requests use the Lua balancer and provider data from `tabularium/provisores/`.
2. **Aequilibrium disabled**: requests use a single custom OpenAI-compatible provider from `.env`.

Create `config.env` from `config.env.example` and choose the mode:

```ini
AEQUILIBRIUM_ENABLED=true
```

When `AEQUILIBRIUM_ENABLED=false`, configure your OpenAI-compatible provider inside `.env`:

```ini
# OpenAI or compatible LLM API endpoints
OPENAI_API_URL=https://your-provider.example/v1/chat/completions
OPENAI_API_MODEL=your-model-name-here
OPENAI_API_KEY=your_secret_api_key_here
```

To configure **Google Gemini** via its OpenAI-compatible endpoint:
```ini
OPENAI_API_URL=https://generativelanguage.googleapis.com/v1beta/openai/chat/completions
OPENAI_API_MODEL=gemini-2.5-flash-lite
OPENAI_API_KEY=your_gemini_api_key_here
```

When `AEQUILIBRIUM_ENABLED=true`, the `.env` OpenAI values are ignored and the active providers are loaded from `tabularium/provisores/`.
The currently maintained balancer providers in this repository are `gemini`, `groq`, and `cerebras`.

After modifying `.env` or `config.env`, rebuild your Docker containers:
```bash
docker-compose up --build -d
```

---

<img src="assets/headers/magia_nigra.svg" width="100%" alt="Magia Nigra (Docker Compose Setup)" />

The easiest way to summon the entire stack is through the containerization arts.

1. **Prepare the Incantation**:
   Copy `.env.example` to `.env`, copy `config.env.example` to `config.env`, then choose your LLM mode.
   ```bash
   cp .env.example .env
   cp config.env.example config.env
   ```

2. **Cast the Spell**:
   ```bash
   docker-compose up --build -d
   ```

3. **Enter the Gateway**:
   Point your browser to **[http://localhost:666](http://localhost:666)**.

---

<img src="assets/headers/manual_setup.svg" width="100%" alt="Quomodo incipere sine Magia (Manual Setup)" />

For developers wishing to run the components natively on Windows/Linux:

1. **Prerequisites**: Install FreePascal (`fpc`), `php`, and a local **PostgreSQL** server.
2. **Setup Schema**: Import the tables schema defined in **[Database Documentation](docs/database.md)**.
3. **Configure environment**:
   ```bash
   export DB_HOST="localhost"
   export DB_PORT="5432"
   export DB_NAME="necromancer"
   export DB_USER="postgres"
   export DB_PASS="your_password"
   ```
4. **Compile & Run Daemonium**:
   ```bash
   cd daemonium
   fpc @hashlib.cfg daemonium.pas
   ./daemonium
   ```
5. **Run Interpres**:
   ```bash
   cd interpres
   composer install
   php -S 127.0.0.1:666
   ```

---

<div align="center">
  <img src="assets/footer.svg" width="100%" alt="* Memento: Omnes viae Romam ducunt! * (Remember: All roads lead to Rome!)" />
</div>
