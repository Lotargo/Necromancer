# 🗃️ PostgreSQL Database Schema - Necromancer

This document details the database schema, relational dependencies, indices, and performance strategies used in the modernized **Necromancer** storage layer.

---

## 🗄️ Relational Entity Diagram

```
 +------------------+
 |     usores       |
 +------------------+
 | nomen (PK)       |<--------------+
 | email (Unique)   |               |
 | password_hash    |               |
 | reg_type         |               |
 | fingerprint      |               |
 | created_at       |               |
 +------------------+               |
          |                         |
          | 1 : 1                   | 1 : N
          v                         |
 +------------------+               |
 |    optiones      |               |
 +------------------+               |
 | nomen (PK, FK)   |               |
 | optiones_json    |               |
 | updated_at       |               |
 +------------------+               |
                                    |
                           +------------------+
                           |    fabulatio     |
                           +------------------+
                           | id (PK)          |
                           | nomen (FK)       |
                           | cubiculum        |
                           | nuntius          |
                           | created_at       |
                           +------------------+

 +------------------+      +----------------------+
 | llm_key_status   |<-----|    llm_key_events    |
 +------------------+      +----------------------+
 | provider (PK*)   |      | id (PK)              |
 | key_hash (PK*)   |      | provider             |
 | key_hint         |      | key_hash             |
 | status           |      | key_hint             |
 | quarantine_until |      | model                |
 | disabled_reason  |      | event_type           |
 | last_http_code   |      | http_code            |
 | last_error_kind  |      | error_kind           |
 | success_count    |      | detail               |
 | failure_count    |      | created_at           |
 | last_success_at  |      +----------------------+
 | last_failure_at  |
 | updated_at       |
 +------------------+
```

---

## 🏛️ Table Specifications

### 1. `usores` (The Table of Souls)
Stores user credentials and profile configurations. It supports both legacy anonymous `SPIRITUS` profiles and fully authenticated `ANIMA` profiles.

```sql
CREATE TABLE IF NOT EXISTS usores (
    nomen VARCHAR(255) PRIMARY KEY,
    email VARCHAR(255) UNIQUE,
    password_hash VARCHAR(255),
    reg_type VARCHAR(50) NOT NULL,
    fingerprint VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Fields Description:
* **`nomen`**: *Primary Key*. Unique alphanumeric user nickname.
* **`email`**: *Unique*. User's email address (mandatory for Anima registrations). Nullable for Spiritus users.
* **`password_hash`**: Stores the cryptographically secure **salted SHA-1 hash** of the user's password. Nullable for Spiritus users.
* **`reg_type`**: Type of registration. Restricted to `'ANIMA'` or `'SPIRITUS'`.
* **`fingerprint`**: Browser/Client hardware fingerprint, used to authenticate Spiritus profiles and bind sessions.
* **`created_at`**: Creation timestamp.

---

### 2. `optiones` (User Preferences Table)
Stores persistent UI configurations for the frontend console interface.

```sql
CREATE TABLE IF NOT EXISTS optiones (
    nomen VARCHAR(255) PRIMARY KEY REFERENCES usores(nomen) ON DELETE CASCADE ON UPDATE CASCADE,
    optiones_json TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Fields Description:
* **`nomen`**: *Primary Key & Foreign Key*. References `usores(nomen)`. Enforces a strict 1-to-1 relationship.
* **`optiones_json`**: Validated JSON payload storing interface configurations (e.g. `{"theme": 0, "glitches": [23, 24], "volume": 120, "sfxVolume": 80, "musicVolume": 150}`). Includes separate master, sfx, and music levels (supporting up to 200%).
* **`updated_at`**: Timestamp indicating the last options update.

---

### 3. `fabulatio` (Chat Message History Table)
Stores the full conversation logs between users and the Oracle, separated by nickname and room name.

```sql
CREATE TABLE IF NOT EXISTS fabulatio (
    id SERIAL PRIMARY KEY,
    nomen VARCHAR(255) NOT NULL REFERENCES usores(nomen) ON DELETE CASCADE ON UPDATE CASCADE,
    cubiculum VARCHAR(255) NOT NULL DEFAULT 'default',
    nuntius TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Fields Description:
* **`id`**: *Primary Key*. Auto-incrementing message sequence.
* **`nomen`**: *Foreign Key*. References `usores(nomen)`. Identifies the sender/owner of the chat message.
* **`cubiculum`**: Room/Chat identifier (e.g., `'default'`, `'SalvePhilosophia'`).
* **`nuntius`**: Text body of the message (prefixed by `Tute:` for user inputs or `Oraculum:` for AI responses).
* **`created_at`**: Timestamp indicating when the message was sent.

---

### 4. `llm_key_status` (LLM Key State Table)
Stores the current health state of every synchronized provider key used by the load-balancing path.

```sql
CREATE TABLE IF NOT EXISTS llm_key_status (
    provider VARCHAR(128) NOT NULL,
    key_hash VARCHAR(64) NOT NULL,
    key_hint VARCHAR(32) NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'active',
    quarantine_until TIMESTAMP NULL,
    disabled_reason TEXT,
    last_http_code INTEGER,
    last_error_kind VARCHAR(128),
    success_count INTEGER NOT NULL DEFAULT 0,
    failure_count INTEGER NOT NULL DEFAULT 0,
    last_success_at TIMESTAMP NULL,
    last_failure_at TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (provider, key_hash)
);
```

#### Fields Description:
* **`provider`**: Provider name such as `gemini`, `groq`, or `cerebras`.
* **`key_hash`**: Salted SHA-1 hash of the real key. The plaintext key is not stored in PostgreSQL.
* **`key_hint`**: Short masked hint used for logs and debugging.
* **`status`**: Current lifecycle state, such as `active`, `resting`, or `disabled`.
* **`quarantine_until`**: TTL timestamp used for temporary cooldowns after rate limits or regional restrictions.
* **`disabled_reason`**: Why the key was permanently disabled.
* **`last_http_code`**: Most recent relevant provider HTTP status.
* **`last_error_kind`**: Normalized failure kind such as `rate_limit`, `auth_or_billing`, or `transient`.
* **`success_count` / `failure_count`**: Running counters of observed outcomes.
* **`last_success_at` / `last_failure_at`**: Timestamps of the latest success or failure.
* **`updated_at`**: Last state update timestamp.

---

### 5. `llm_key_events` (LLM Key Event Log)
Stores the event history used to audit key health decisions and TTL-based quarantine logic.

```sql
CREATE TABLE IF NOT EXISTS llm_key_events (
    id SERIAL PRIMARY KEY,
    provider VARCHAR(128) NOT NULL,
    key_hash VARCHAR(64) NOT NULL,
    key_hint VARCHAR(32) NOT NULL,
    model VARCHAR(255),
    event_type VARCHAR(64) NOT NULL,
    http_code INTEGER,
    error_kind VARCHAR(128),
    detail TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

#### Fields Description:
* **`provider` / `key_hash` / `key_hint`**: Identify the affected key without storing the plaintext value.
* **`model`**: Model used when the event occurred.
* **`event_type`**: Normalized event such as `SUCCESS`, `RATE_LIMIT`, `DISABLE`, or `TRANSIENT`.
* **`http_code`**: Observed provider HTTP status.
* **`error_kind`**: Classified failure category.
* **`detail`**: Sanitized diagnostic payload.
* **`created_at`**: Event creation timestamp.

---

## ⚡ Performance Optimization: Indexes

To achieve instantaneous page load speeds during room rendering and message history checks, the following multi-column index has been deployed:

```sql
CREATE INDEX IF NOT EXISTS idx_fabulatio_nomen_cubiculum ON fabulatio(nomen, cubiculum);
```

For key-state lookups and event history queries, the following event index is also used:

```sql
CREATE INDEX IF NOT EXISTS idx_llm_key_events_lookup ON llm_key_events(provider, key_hash, created_at);
```

### Purpose:
In `daemonium.pas`, when loading historical contexts using `LegendeNuntios` or verifying room lists via `IndexFabulationum`, the SQL parser executes queries filtering on both `nomen` and `cubiculum`:
```sql
SELECT nuntius FROM fabulatio WHERE nomen = :nomen AND cubiculum = :cubiculum ORDER BY id ASC;
```
Without this composite index, PostgreSQL would be forced to perform a **Sequential Scan** ($O(N)$ operations), reading every message in the system under heavy load. The composite index structure allows a rapid **Index Scan** ($O(\log N)$ complexity), keeping search operations lightning-fast regardless of chat size.

---

## 🔄 Cascading Relational Integrity

The legacy flat-file flat database had huge concurrency bottlenecks. When renaming a user or deleting an account, the backend had to block the thread, scan the directory, create temporary `.tmp` files, copy unchanged records line-by-line, delete the old file, and rename.

With PostgreSQL, we utilize native foreign key constraints with cascade options:
* **`ON DELETE CASCADE`**: When a record in `usores` is removed, PostgreSQL's relational engine automatically and atomically deletes the corresponding settings in `optiones` and the entire message logs in `fabulatio` in a single transaction.
* **`ON UPDATE CASCADE`**: When a user's nickname (`nomen`) is changed via the profiles API, the change automatically propagates through all corresponding rows in the `optiones` and `fabulatio` tables, maintaining perfect consistency without write conflicts.
