# Necromancer Repair Status

## Completed Fixes

### P0 (Critical Fixes)
1. **HashLib4Pascal Missing Dependency:** Cloned v4.2 and updated `Dockerfile` and `README.md` to compile correctly.
2. **SQL Injection:** Parameterized dynamic `IN` clauses in `daemonium/ClavesLlm.pas`.
3. **Information Leak (Buffer):** Zero-filled receive buffers in `daemonium.pas` and `mechanica.pas` using `FillChar`.
4. **Compiler Errors Lost:** Appended `poStderrToOutput` flag to `mechanica.pas` to capture process error streams.
5. **Null Byte Injection:** Stripped `\0` in `interpres/packages/sanitas.php` to prevent pipe-delimited payload injection.
6. **API Key & SSL Security:** Redacted raw `Authorization` headers from `react_loop.php` logs and enabled SSL verification in the Guzzle client.
7. **Hardcoded DB Password Fallback:** Removed the fallback in `Database.pas`; the `DB_PASS` environment variable is now strictly required.
8. **DB Password in Compose:** Changed hardcoded passwords in `docker-compose.yml` to use environment interpolation (`${DB_PASS}`).

### P1 (High Severity Fixes)
1. **Lost LLM Reasoning:** Removed the errant `$total_reasoning = ""` reset from the ReAct loop in `react_loop.php`.
2. **Path Traversal in Mechanica:** Sanitized the `Id` parameter by removing `/`, `\`, and `.` to prevent writing files outside the sandbox.
3. **Session Hijacking:** Secured cookies in `auth.php` by applying the `HttpOnly` flag.
4. **API Key Leaks:** Masked the API key in the `error_log` mechanism located within `daemonium.php` and ignored the debug `tmp_payload.txt` via `.gitignore`.
5. **Missing Provider:** Added `sambanova` back to the synchronization list in `ClavesLlm.pas`.
6. **Aequilibrium Session Management:** Implemented automatic TTL eviction (1 hour) for sessions in `aequilibrium.lua` to prevent memory leaks.
7. **Aequilibrium Recv Timeout:** Configured a 3-second socket receive timeout on connections in `aequilibrium.lua` to prevent Slow-Loris attacks.

### P2 & P3 (Edge-Cases & Maintainability)
1. **PRNG Weakening:** Relocated `Randomize` from `Usores.pas` (which ran on every invocation) to the main daemon startup logic in `daemonium.pas`.
2. **Double-Escaping:** Eliminated redundant backslash escaping in `interpres/packages/llm_stream/context.php`.
3. **Buffer Overflows via PHP Payload:** Limited inbound Pascal code strings to 64KB in `solve_discrete_math` and `run_streaming_simulation` handlers.
4. **Rename Sanitization Inconsistency:** Forced `sani()` to execute over `novum_nomen` in `user.php` prior to regex filtering.
5. **Loose Type Casting:** Forced explicit `(int)` cast for `$http_code` within `oraculum.php`.
6. **Hardcoded Paths:** Converted `SPIRITUS_MAIL_LOG` and `TABULARIUM_PROVISORES` to pull from environment variables with fallback defaults.
7. **Hash Length Failures:** Changed the Argon2id check from a strict `Length(hash) = 64` to a scalable fallback branch.
8. **Network Attack Surface:** Removed the exposed host mapping for PostgreSQL (`5432:5432`) in `docker-compose.yml`.
9. **Per-User Password Salts:** Upgraded Argon2id hashing in `Usores.pas` to generate and use per-user random salts with a transparent migration flow on next login.
10. **Interpres Input Constraints:** Added strict size caps on room name/ID, user message payload, account settings options, usernames, and passwords to prevent buffer overflows or DDoS.
11. **Subresource Integrity (SRI):** Pinned CDN versions and added `integrity` and `crossorigin` hashes for Marked, DOMPurify, and KaTeX script/link tags.
12. **Mechanica Safe Mode Restrictions:** Integrated a `StripComments` parser in `mechanica.pas` and added dynamic loading/assembly blocklist terms (`external`, `asm`, include directives, etc.) to harden the Pascal compilation sandbox.

## Pending Items / Work Requiring Rework

*All identified security, load-balancing, and validation issues have been successfully resolved and completed.*

