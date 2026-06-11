# 🏛️ System Architecture - Necromancer

This document provides a highly detailed breakdown of the technical components and network communications powering the **Necromancer** chat environment.

---

## 🏗️ High-Level Component Topology

The system operates as a distributed microservice stack composed of five primary containers, fully managed via `docker-compose.yml`:

```
   +-------------------------------------------------------+
   |                  Web Browser (Client)                 |
   +---------------------------+---------------------------+
                               |
                               | HTTP / SSE (Port 666)
                               v
   +-------------------------------------------------------+
   |                     chat_interpres                    |
   |                 (PHP 8.3 / Javascript)                |
   +-----+---------------------+---------------+-----------+
         |                     |               |
         | TCP Sockets         | TCP Sockets   | HTTP API
         | Port 8080           | Port 8082     | (Port 8081 Balance)
         v                     v               v
   +-----+---------+     +-----+---------+   +-+-----------+
   |chat_daemonium |     |chat_mechanica |   |AI Providers |
   | (FreePascal)  |     | (Pascal Run)  |   |(Gemini/Groq)|
   +-----+---------+     +---------------+   +-------------+
         |
         | libpq (Port 5432)
         v
   +-----+---------+
   |    chat_db    |
   | (PostgreSQL)  |
   +---------------+
```

---

## 🦇 1. Core Daemon: `Daemonium`

Compiled inside the Docker container using FreePascal (`fpc`), the `daemonium` executable is a high-performance, single-threaded socket server that handles all stateful transactions of the chat.

### 🏛️ Modular Unit Architecture

To ensure high maintainability, the codebase is cleanly modularized into several dedicated Object Pascal units:
* **[Auxilia.pas](file:///f:/lock-rep-stable-projects/Necromancer/daemonium/Auxilia.pas)**: Shared helpers, response string formation (`FormareResponsum`), file reader utilities, and lightweight Argon2id API key hashes.
* **[Cryptographia.pas](file:///f:/lock-rep-stable-projects/Necromancer/daemonium/Cryptographia.pas)**: High-security cryptography module driving **Argon2id** password hashing using the native `HashLib4Pascal` engine.
* **[Database.pas](file:///f:/lock-rep-stable-projects/Necromancer/daemonium/Database.pas)**: Handles database connections (`InitDatabase`), query states, and automatic schema initialization for PostgreSQL.
* **[ClavesLlm.pas](file:///f:/lock-rep-stable-projects/Necromancer/daemonium/ClavesLlm.pas)**: API key synchronizer, status checker, and rate-limit quarantine logger.
* **[Usores.pas](file:///f:/lock-rep-stable-projects/Necromancer/daemonium/Usores.pas)**: Manages full authentication lifecycles (guest Spiritus and credential-backed Anima profiles), session fingerprint checks, and profile deletion.
* **[Fabulatio.pas](file:///f:/lock-rep-stable-projects/Necromancer/daemonium/Fabulatio.pas)**: Drives chat room lists, message history loading, and JSON settings serialization.
* **[Scientia.pas](file:///f:/lock-rep-stable-projects/Necromancer/daemonium/Scientia.pas)**: High-speed keyword index scanner providing local RAG knowledge context.
* **[daemonium.pas](file:///f:/lock-rep-stable-projects/Necromancer/daemonium/daemonium.pas)**: The central main loop and listener. Coordinates socket bindings, listens on port `8080`, reads string buffers, and routes incoming commands to corresponding modular controllers.

### 🔌 Connection Lifecycle:
1. Opens a listening socket using the FPC `sockets` unit.
2. Blocks waiting for incoming connection requests via `accept()`.
3. Spawns an execution frame that reads the buffer until `\n`.
4. Parses the incoming pipe-separated string and executes the corresponding database transaction.
5. Formats the return payload using `FormareResponsum(Codex, Nuntius, Data)` and writes it back to the client socket.
6. Closes the socket immediately (stateless TCP communication).

---

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
  * Chooses between a single custom OpenAI-compatible provider from `.env` or the Lua balancer (`Aequilibrium`) depending on `AEQUILIBRIUM_ENABLED`.
  * Fetches rotated provider/key/model candidates from `Aequilibrium` on port `8081` when balancing is enabled.
  * Logs key outcomes back to `Daemonium`, which persists key health in PostgreSQL.
  * Sends requests to external LLM providers and streams the response back to the client using PHP's `event-stream` interface.
  * Uses the modern **`openai-php/client`** SDK for robust streaming and structured tool call handling.
  * Incorporates a custom **Guzzle Middleware** and **PSR-7 Stream Decorator** to natively preserve and inject `extra_content` fields, guaranteeing compatibility with Gemini API constraints without breaking SDK typings.
  * Implements and runs 3 specialized agentic tools:
    * **`solve_discrete_math`**: Generates and compiles Pascal programs via `Mechanica` to solve equations.
    * **`run_streaming_simulation`**: Spawns real-time interactive physics simulations streaming JSON coordinate lines.
    * **`save_to_knowledge_base`**: Commits verified code patterns and compilation hints back to the local RAG knowledge files.

---

---

## ⚖️ 3. Load Balancer: `Aequilibrium`

Written in pure **Lua** and executed via **LuaJIT**, `Aequilibrium` is an extremely lightweight proxy service that rotates API credentials and LLM endpoints.

* **Key Features**:
  * Rotates providers, API keys, and models using stateful Round-Robin indices.
  * Pins a session to one provider/key/model until `Interpres` explicitly unpins it.
  * Works together with PostgreSQL-backed key status from `Daemonium`, so resting or disabled keys can be skipped before use.
  * Employs LuaJIT's FFI (Foreign Function Interface) library to perform high-speed socket binds (`bind()`, `listen()`, `accept()`, `recv()`, `send()`).
  * Secured against system crashes using a globally wrapped `pcall` (protected call) framework, ensuring file descriptor auto-closure under heavy load.

---

## ⚙️ 4. Computational Sandbox: `Mechanica`

`Mechanica` is a high-security computation sandbox running as a FreePascal socket microservice on port `8082`.

* **Key Features**:
  * Receives, validates, compiles, and runs arbitrary Pascal programs generated by the Oracle.
  * Streams raw output (like physics coordinate updates) straight from the running process's standard output directly into the client socket stream.
  * Employs comment-stripping lexical filters to prevent signature bypasses of forbidden keywords (such as `system`, `shell`, `exec`, `asm`, `external`, `{$i`, or `{$link`).
  * Enforces a strict execution timeout (3 seconds for equations, 8 seconds for real-time simulations) to prevent CPU resource exhaustion and infinite loops.

---

## 🗃️ 5. Relational Storage: `PostgreSQL`

In modernizing the system, we migrated the storage layer from linear flat files to **PostgreSQL 15-alpine**.

* **Why PostgreSQL?**:
  * **Thread Safety**: Eliminates file locking and data corruption when multiple requests access the backend.
  * **Performance**: Replaces slow $O(N)$ and $O(N^2)$ sequential disk-scanning loops with high-speed indexes ($O(\log N)$ searches).
  * **Data Integrity**: Enforces strict primary keys, foreign key constraints, and relational cascades (`ON DELETE CASCADE`).
  * **LLM Key State**: Stores key lifecycle information such as active, resting, disabled, recent HTTP result, and key event history.

---

## 🔌 6. Socket Wire Protocol

All communication between `Interpres` and `Daemonium` occurs over raw TCP sockets using a string-based protocol:

### Format of Request:
`MANDATUM|PARAM1|PARAM2|...|\n`

### Format of Response:
`CODEX|NUNTIUS|DATA\n`

| Request Command | Parameters | Response Code | Description |
| :--- | :--- | :--- | :--- |
| `CREARE_USOREM` | `nomen, fingerprint` | `200 / 400` | Creates a Spiritus (guest) user. |
| `INTRARE` | `nomen, fingerprint` | `200 / 403` | Guest login with fingerprint verification. |
| `CREARE_USOREM_PLENUM` | `nomen, email, pass, fp` | `200 / 400` | Registers Anima user with Argon2id hashed password (dynamic salt). |
| `INTRARE_PLENUM` | `email, pass, fp` | `200 / 403` | Full secure authentication with Argon2id matching. |
| `INDEX_FABULATIONUM` | `nomen, fp` | `200` | Lists user's rooms (comma separated). |
| `ADDERE_NUNTIUM` | `nomen, room, message` | `200` | Appends a chat message to the DB. |
| `LEGENDE_NUNTIOS` | `nomen, room, fp` | `200` | Fetches full message history in flat text format. |
| `SERVARE_OPTIONES` | `nomen, options_json, fp` | `200` | Stores persistent JSON UI configurations. |
| `LEGERE_OPTIONES` | `nomen, fp` | `200` | Reads persistent JSON UI configurations. |
| `RENOMINARE_USOREM` | `vetus_nomen, novum_nomen` | `200` | Renames a user (cascades automatically in DB). |
| `MUTARE_TESSARAM` | `nomen, vetus_pass, nova_pass, fp` | `200 / 401 / 403` | Safely changes user password (verifies and stores as Argon2id). |
| `DELERE_RATIONEM` | `nomen, fp` | `200 / 403` | Erases user profile, options, and chat logs. |
| `STATUM_CLAVIS_LLM` | `provider, key` | `200` | Returns the current PostgreSQL-backed key state. |
| `NOTARE_EVENTUM_CLAVIS_LLM` | `provider, key, model, event_type, http_code, error_kind, detail` | `200` | Persists a key event and updates the key state. |
| `SYNC_CLAVES_LLM` | `provider` | `200` | Forces a provider key sync from `tabularium/provisores`. |
| `PETERE_RECUPERATIONEM` | `email` | `200` | Generates a 6-digit recovery code and appends to the log file. |
| `INVESTIGARE` | `query` | `200` | Scans local knowledge base files and returns matches (local RAG). |
| `SALVARE_SCIENTIAM` | `rule` | `200` | Appends a new fact or rule to the RAG knowledge files. |
| `DELE_FABULATIONEM` | `nomen, room, fingerprint` | `200 / 403` | Deletes a specific chat history. |
| `RENOMINARE_FABULATIONEM` | `nomen, old_room, new_room, fingerprint` | `200 / 403` | Renames a chat room ID. |
| `DELERE_OMNES_FABULATIONES` | `nomen, fingerprint` | `200 / 403` | Deletes all chat rooms and history. |
| `NUMERARE_NUNTIOS` | `nomen, fingerprint` | `200 / 403` | Returns the count of total messages sent by a user. |
