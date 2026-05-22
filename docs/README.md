# 📖 Bibliotheca Arcana (Occult Library)

Welcome, seeker of occult knowledge. This directory houses the comprehensive architectural scrollwork and operational guidelines for the **Necromancer** system.

Herein lies the complete documentation of our transition from a paper-thin retro-flat system to a highly performant, secure, **PostgreSQL-fortified and SHA-1 cryptographically hashed** cyber-necromancy environment.

---

## 🗂️ Index Codex (Table of Contents)

### 1. 🏛️ [System Architecture](architecture.md)
Detailed analysis of the four pillars: the FreePascal **Daemonium**, the PHP/JS **Interpres**, the LuaJIT **Aequilibrium**, and the **PostgreSQL** repository. Includes socket protocols and network communication specifications.

### 2. 🗃️ [PostgreSQL Database Schema](database.md)
Full descriptions of the relational tables (`usores`, `optiones`, `fabulatio`), plus the PostgreSQL-backed LLM key-state tables used for quarantine, logging, and synchronization.

### 3. 🛡️ [Security & Cryptography](security.md)
Deep dive into our implementation of salted **SHA-1 hashing**, parameterized SQL injection guards, socket protocol sanitization, and path traversal mitigations.

### 4. 🔮 [Deployment & Summoning Guide](deployment.md)
Comprehensive instructions to deploy the stack utilizing Docker Compose, environment variables configuration, and native compiling manuals for FreePascal and PHP development environments.

### 5. ⚙️ [LLM Providers & Load Balancing Configuration](providers.md)
Detailed setup instructions for LLM API keys and model rotations inside the `tabularium/provisores/` directory, explanation of Aequilibrium's hot-reload mechanics, PostgreSQL-backed key-state sync, and security guidelines.


---

## 🖤 The Philosophy of Occult Engineering

The **Necromancer** project bridges the gap between old-world digital aesthetics (retro green phosphor CRT displays, hardware sound effects) and modern database engineering. 

We do not compromise:
* **The Interface** looks and sounds like it was running on an IBM 5151 terminal in a dark, candle-lit laboratory.
* **The Code** utilizes Free Pascal (a classic compiled language) paired with raw socket connections.
* **The Infrastructure** uses PostgreSQL, preventing common concurrency issues, race conditions, and race-to-disk flat file corruption bugs.
* **The Security** employs cryptographically secure, salted hashing to protect the souls (credentials) of users.

> *"Omnia mutantur, nihil interit."*  
> (Everything changes, nothing is lost. - Ovid)
