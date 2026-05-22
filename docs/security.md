# 🛡️ Security & Cryptography - Necromancer

This document details the security posture, cryptographic designs, and sanitization mechanisms implemented in the **Necromancer** chat environment to safeguard user profiles and prevent system vulnerabilities.

---

## 🔐 1. Password Cryptography & Hashing (SHA-1 to Argon2id)

Legacy versions of the core backend saved user credentials in plaintext within flat scroll files, creating a significant security risk.

Initially, we modernized the stack by implementing cryptographically secure, salted hashing using FreePascal’s built-in `sha1` unit with a static secret salt (`'NecromancerSalt1337'`).

### 🚀 The Ultimate Standard: Argon2id Hashing

To meet modern security standards (ASVS 4.0), we upgraded the password hashing algorithm to **Argon2id** (via the native `HashLib4Pascal` library). This represents the highest industry standard for password protection against both GPU/ASIC-accelerated brute-force attacks and side-channel analysis.

#### Hashing Properties & Parameters:
* **Algorithm**: Argon2id (combines memory-hard Argon2d and time-hard Argon2i properties).
* **Work Factors**:
  * **Time Cost (Iterations)**: `2` iterations (passes).
  * **Memory Cost**: `65536 KB` (64 MB) RAM, enforcing memory-hardness.
  * **Parallelism**: `2` independent execution threads.
  * **Output Length**: `32` bytes (represented as a 64-character lowercase hexadecimal string).
* **Dynamic Salting**: Instead of a static system-wide salt, Argon2id hashes are generated using the user's unique nickname (`nomen`) as the dynamic salt. This ensures that even if two users choose identical passwords, their hashes in the database will be completely different.
* **LLM Key Protection**: In addition to user passwords, API provider credentials stored in `llm_key_status` are protected using a lightweight, fast variant of Argon2id (`Iterations = 1`, `Memory = 4 MB`, `Parallelism = 1`).

### 🔄 Seamless Legacy SHA-1 Migration Blueprint

To prevent user disruption (forcing password resets), we built a transparent migration mechanism into the authentication lifecycle inside the `Usores` module:

1. **Hash Length Discrimination**: 
   When a user attempts to log in using the `INTRARE_PLENUM` socket command, the system retrieves their stored `password_hash` from the database and checks its length:
   - **40 Characters**: Indicates a legacy **SHA-1** hash.
   - **64 Characters**: Indicates a modern **Argon2id** hash.

2. **Automated Re-hashing**:
   If the stored hash is 40 characters long:
   - The system verifies the submitted password against the legacy SHA-1 hash (`LegacySHA1(Password)` using the static `'NecromancerSalt1337'` salt).
   - If verification succeeds, the user is authorized.
   - Immediately in the same execution cycle, the system generates a new **Argon2id** hash of the verified password using the user's nickname as the salt:
     ```pascal
     NovusPassHash := HashPassword(Password, RegNomen);
     ```
   - The new hash is committed back to PostgreSQL in a secure transaction.
   - The system outputs a `[MIGRATION]` notice to the daemon logs for auditing purposes.

3. **Subsequent Logins**:
   For all future authentication cycles, the stored hash length is 64 characters, and the system bypasses legacy paths, executing Argon2id verification directly.

---

## 🛡️ 2. Parameterized SQL Injection Guard

FreePascal's `TSQLQuery` unit has been carefully configured to handle all dynamic database operations using **Parametric Placeholder Bindings** instead of dynamic string concatenation.

### Vulnerable Design (Avoided):
```pascal
// HIGHLY VULNERABLE TO SQL INJECTION
Query.SQL.Text := 'SELECT * FROM usores WHERE nomen = ''' + InputName + ''';';
```

### Secure Parametric Design (Implemented):
```pascal
Query.SQL.Text := 'SELECT * FROM usores WHERE nomen = :nomen AND fingerprint = :fp;';
Query.ParamByName('nomen').AsString := InputName;
Query.ParamByName('fp').AsString := InputFingerprint;
Query.Open;
```

### Protective Function:
When parameter placeholders (`:nomen`, `:fp`) are used, the PostgreSQL driver (`libpq`) separates the SQL instruction compilation from the execution parameters. 
The database engine compiles the query structure first. When the parameters are passed later, they are strictly evaluated as raw values (literals), completely neutralising any malicious SQL commands injected inside input fields.

---

## 🧼 3. Socket Protocol Pipe Injection Sanitization

Since `Interpres` (PHP) communicates with `Daemonium` (Pascal) over a raw TCP socket using a pipe-separated (`|`) wire protocol, any user-submitted pipe character in a nickname, email, or message could hijack the command parser.

### The Attack Vector:
A user registering with the name `Hacker|CREARE_USOREM|Admin|` could inject a second command into the socket, fooling the backend daemon into executing arbitrary commands.

### The Shield (`interpres/api.php`):
We have introduced strict global sanitizers to remove pipes and control characters before payload assembly:

```php
function sani($val) {
    if (is_array($val)) {
        return array_map('sani', $val);
    }
    return str_replace(['|', "\r", "\n"], '', $val);
}

function sani_nuntius($val) {
    if (is_array($val)) {
        return array_map('sani_nuntius', $val);
    }
    // Replace newlines and pipes in messages with spaces to maintain formatting
    return str_replace(['|', "\r", "\n"], [' ', '', ' '], $val);
}
```

### Applied Contexts:
* **All Authentication Fields** (emails, passwords, names, fingerprints) are filtered using `sani()`.
* **Chat Messages** are filtered using `sani_nuntius()` to ensure the message body cannot inject command separators.
* **Persistent Settings** have their JSON payloads sanitized, replacing pipes with Unicode escapes (`\\u007c`) to guarantee secure JSON integrity in the database.

---

## 🗄️ 4. Path Traversal & File System Safeguards

By completely migrating the user state, settings, and conversation logs to PostgreSQL, we have eliminated local file-system writes driven by user input.

* **Legacy Risks**: In flat-file systems, users named `../../../etc/passwd` could attempt directory traversal attacks, writing or reading system files.
* **Modern Protection**: Database keys are typed strings mapped as variables. The filesystem is no longer accessed to save or load chat rooms, removing path traversal vulnerabilities at the architectural level.
* **System Config Isolation**: OCCULT knowledge files (`scientia/`) and balancer key settings are accessed using static, hardcoded backend paths completely unreachable via client requests.
