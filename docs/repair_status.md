# Necromancer Repair Status

## Completed Fixes

### P0 (Critical Fixes)
1. **HashLib4Pascal Missing Dependency:** Cloned v4.2 and updated `Dockerfile` and `README.md` to compile correctly.
2. **SQL Injection:** Parameterized dynamic `IN` clauses in `daemonium/ClavesLlm.pas`.
3. **Information Leak (Buffer):** Zero-filled receive buffers in `daemonium.pas` and `mechanica.pas` using `FillChar`.
4. **Compiler Errors Lost:** Appended `poStderrToOutput` flag to `mechanica.pas` to capture process error streams.
5. **Null Byte Injection:** Stripped `\0` in `interpres/packages/sanitas.php` to prevent pipe-delimited payload injection.
6. **API Key & SSL Security:** Redacted raw `Authorization` headers from `react_loop.php` logs and enabled SSL verification in the Guzzle client.

### P1 (High Severity Fixes)
1. **Lost LLM Reasoning:** Removed the errant `$total_reasoning = ""` reset from the ReAct loop in `react_loop.php`.
2. **Path Traversal in Mechanica:** Sanitized the `Id` parameter by removing `/`, `\`, and `.` to prevent writing files outside the sandbox.
3. **Session Hijacking:** Secured cookies in `auth.php` by applying the `HttpOnly` flag.
4. **API Key Leaks:** Masked the API key in the `error_log` mechanism located within `daemonium.php` and ignored the debug `tmp_payload.txt` via `.gitignore`.
5. **Missing Provider:** Added `sambanova` back to the synchronization list in `ClavesLlm.pas`.

### P2 & P3 (Edge-Cases & Maintainability)
1. **PRNG Weakening:** Relocated `Randomize` from `Usores.pas` (which ran on every invocation) to the main daemon startup logic in `daemonium.pas`.
2. **Double-Escaping:** Eliminated redundant backslash escaping in `interpres/packages/llm_stream/context.php`.
3. **Buffer Overflows via PHP Payload:** Limited inbound Pascal code strings to 64KB in `solve_discrete_math` and `run_streaming_simulation` handlers.
4. **Rename Sanitization Inconsistency:** Forced `sani()` to execute over `novum_nomen` in `user.php` prior to regex filtering.
5. **Loose Type Casting:** Forced explicit `(int)` cast for `$http_code` within `oraculum.php`.
6. **Hardcoded Paths:** Converted `SPIRITUS_MAIL_LOG` and `TABULARIUM_PROVISORES` to pull from environment variables with fallback defaults.
7. **Hash Length Failures:** Changed the Argon2id check from a strict `Length(hash) = 64` to a scalable fallback branch.
8. **Network Attack Surface:** Removed the exposed host mapping for PostgreSQL (`5432:5432`) in `docker-compose.yml`.

## Pending Items / Work Requiring Rework

1. **Aequilibrium Session Management:** `Sessiones` still grows unbounded leading to potential memory leaks. `aequilibrium.lua` requires TTL evictions.
2. **Aequilibrium Recv Timeout:** Slow-loris attack still possible on the Lua load balancer since no socket timeouts exist.
3. **Interpres Content Length:** Generic text payload inputs (e.g., chat messages) still lack explicit size caps.
4. **Subresource Integrity (SRI):** Third-party CDN libraries in `fabulatio.php` remain loaded without integrity hashes.
5. **Mechanica Safe Mode Restrictions:** The Pascal keyword blocklist is still relatively primitive and can be bypassed by string manipulation; requires robust AST inspection or container isolation.
