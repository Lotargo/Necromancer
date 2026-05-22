# 🏛️ System Architecture - Necromancer

This document provides a highly detailed breakdown of the technical components and network communications powering the **Necromancer** chat environment.

---

## 🏗️ High-Level Component Topology

The system operates as a distributed microservice stack composed of four primary containers, fully managed via `docker-compose.yml`:

```
   +---------------------------------------------+
   |             Web Browser (Client)            |
   +-----------------------+---------------------+
                           |
            HTTP / SSE     |      TCP Sockets (Key Rotation)
            Port 666       |      Port 8081
                           v
   +-----------------------+---------------------+
   |              chat_interpres                 |
   |           (PHP 8.3 / Javascript)            |
   +-----------+---------------------+-----------+
               |                     |
               | TCP Sockets         | HTTP API
               | Port 8080           | (JSON Streaming)
               v                     v
   +-----------+-----------+   +-----+-----------+
   |      chat_daemonium   |   |   AI Providers  |
   |    (FreePascal Core)  |   | (Gemini/OpenAI) |
   +-----------+-----------+   +-----------------+
               |
               | libpq (SQL connection)
               | Port 5432
               v
   +-----------+-----------+
   |        chat_db        |
   |  (PostgreSQL 15-alp)  |
   +-----------------------+
```

---

## 🦇 1. Core Daemon: `Daemonium`

Compiled inside the Docker container using FreePascal (`fpc`), the `daemonium` executable is a high-performance, single-threaded socket server that handles all stateful transactions of the chat.

* **Core Responsibilities**:
  * Binding to port `8080` and listening for raw TCP socket connections.
  * Parsing custom text-based commands sent by `Interpres`.
  * Communicating with the `PostgreSQL` instance to store/load users, options, and histories.
  * Running RAG search queries against the local knowledge base (`tabularium/scientia/`).
* **Connection Lifecycle**:
  1. Opens a listening socket using the FPC `sockets` unit.
  2. Blocks waiting for incoming connection requests via `accept()`.
  3. Spawns an execution frame that reads the buffer until `\n`.
  4. Parses the incoming pipe-separated string and executes the corresponding database transaction.
  5. Formats the return payload using `FormareResponsum(Codex, Nuntius, Data)` and writes it back to the client socket.
  6. Closes the socket immediately (stateless TCP communication).

---

## 👁️ 2. Web Frontend & BFF: `Interpres`

Built on top of PHP 8.3 and Vanilla HTML5/CSS3/Javascript, `Interpres` acts as both the Backend-for-Frontend (BFF) and the user-facing web portal.

* **Client UI (Vanilla JS & HTML5)**:
  * **CRT Monitor Shader**: A sophisticated combination of CSS radial gradients, keyframe flickers, scanline overlays, and border-box glows.
  * **Occult Canvas Skins**: Renders complex interactive visual effects on a full-screen HTML5 Canvas (such as blood drips, matrix rain, floating necromantic embers - Ignis Fatuus, and moving stargates).
  * **Advanced Audio Mixer**: Synthesizes immersive white noise (ambient laboratory winds, CRT electrical hums), plays mechanical clicks on keypresses, and provides a 3-channel audio mixer (Summa, Sonus, Musica) supporting overdrive amplification up to 200% with reactive crimson slider styling. Uses the **Web Audio API**.
  * **SSE Listener**: Listens to real-time streams from `api.php?action=send` to display chunk-by-chunk characters as the Oracle speaks.
* **BFF Logic (`api.php`)**:
  * Intercepts AJAX requests from the browser.
  * Validates session data (`$_SESSION['usor']` and `$_SESSION['fp']`).
  * Establishes brief TCP socket connections to `chat_daemonium` to synchronize state (e.g. validating password hashes or pulling options).
  * Fetches API keys from the Lua balancer (`Aequilibrium`) on port `8081`.
  * Sends requests to external LLM providers and streams the raw response back to the client using PHP's `event-stream` interface.

---

## ⚖️ 3. Load Balancer: `Aequilibrium`

Written in pure **Lua** and executed via **LuaJIT**, `Aequilibrium` is an extremely lightweight proxy service that rotates API credentials and LLM endpoints.

* **Key Features**:
  * Rotates API keys using a stateful Round-Robin index mapped per user session.
  * Decouples the frontend from directly accessing keys or knowing backend endpoints.
  * Employs LuaJIT's FFI (Foreign Function Interface) library to perform high-speed socket binds (`bind()`, `listen()`, `accept()`, `recv()`, `send()`).
  * Secured against system crashes using a globally wrapped `pcall` (protected call) framework, ensuring file descriptor auto-closure under heavy load.

---

## 🗃️ 4. Relational Storage: `PostgreSQL`

In modernizing the system, we migrated the storage layer from linear flat files to **PostgreSQL 15-alpine**.

* **Why PostgreSQL?**:
  * **Thread Safety**: Eliminates file locking and data corruption when multiple requests access the backend.
  * **Performance**: Replaces slow $O(N)$ and $O(N^2)$ sequential disk-scanning loops with high-speed indexes ($O(\log N)$ searches).
  * **Data Integrity**: Enforces strict primary keys, foreign key constraints, and relational cascades (`ON DELETE CASCADE`).

---

## 🔌 5. Socket Wire Protocol

All communication between `Interpres` and `Daemonium` occurs over raw TCP sockets using a string-based protocol:

### Format of Request:
`MANDATUM|PARAM1|PARAM2|...|\n`

### Format of Response:
`CODEX|NUNTIUS|DATA\n`

| Request Command | Parameters | Response Code | Description |
| :--- | :--- | :--- | :--- |
| `CREARE_USOREM` | `nomen, fingerprint` | `200 / 400` | Creates a Spiritus (guest) user. |
| `INTRARE` | `nomen, fingerprint` | `200 / 403` | Guest login with fingerprint verification. |
| `CREARE_USOREM_PLENUM` | `nomen, email, pass, fp` | `200 / 400` | Registers Anima user with SHA-1 hashed password. |
| `INTRARE_PLENUM` | `email, pass, fp` | `200 / 403` | Full secure authentication with SHA-1 matching. |
| `INDEX_FABULATIONUM` | `nomen, fp` | `200` | Lists user's rooms (comma separated). |
| `ADDERE_NUNTIUM` | `nomen, room, message` | `200` | Appends a chat message to the DB. |
| `LEGENDE_NUNTIOS` | `nomen, room, fp` | `200` | Fetches full message history in flat text format. |
| `SERVARE_OPTIONES` | `nomen, options_json, fp` | `200` | Stores persistent JSON UI configurations. |
| `LEGERE_OPTIONES` | `nomen, fp` | `200` | Reads persistent JSON UI configurations. |
| `RENOMINARE_USOREM` | `vetus_nomen, novum_nomen` | `200` | Renames a user (cascades automatically in DB). |
| `DELERE_RATIONEM` | `nomen` | `200` | Erases user profile, options, and chat logs. |
