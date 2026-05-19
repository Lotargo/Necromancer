# 🛡️ Security & Cryptography - Necromancer

This document details the security posture, cryptographic designs, and sanitization mechanisms implemented in the **Necromancer** chat environment to safeguard user profiles and prevent system vulnerabilities.

---

## 🔐 1. Salted Password Cryptography

Legacy versions of the core backend saved user credentials in plaintext within flat scroll files, creating a significant security risk.

In the modernized stack, we have implemented cryptographically secure, salted hashing using FreePascal’s built-in `sha1` unit.

### Implementation Blueprint (`daemonium.pas`):
```pascal
function HashPassword(Pass: String): String;
begin
  // Cryptographically secure hashing with a static salt
  Result := SHA1Print(SHA1String(Pass + 'NecromancerSalt1337'));
end;
```

### Security Properties:
* **Preimage Resistance**: The one-way nature of SHA-1 makes it computationally infeasible to reconstruct the original plaintext password from the stored hash.
* **Salt Protection**: Appending a static secret salt (`'NecromancerSalt1337'`) to the password before hashing changes the target digest. This protects the credentials against **Rainbow Table** pre-computation attacks, where attackers use pre-calculated hashes of common words.
* **Database Breach Mitigations**: In the event that the PostgreSQL storage layer is compromised, the attacker only gains access to salted digests. They cannot authenticate without knowing the salt and brute-forcing the password.

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
