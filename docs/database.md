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

## ⚡ Performance Optimization: Indexes

To achieve instantaneous page load speeds during room rendering and message history checks, the following multi-column index has been deployed:

```sql
CREATE INDEX IF NOT EXISTS idx_fabulatio_nomen_cubiculum ON fabulatio(nomen, cubiculum);
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
