# LLM Providers & Load Balancing Guide - Necromancer

This guide explains how the LLM load-balancing architecture works, how to configure API providers, add models, rotate keys, and secure configurations.

> [!IMPORTANT]
> The load balancer is used only when `AEQUILIBRIUM_ENABLED=true` in `config.env`.
> If `AEQUILIBRIUM_ENABLED=false`, `Interpres` skips the Lua balancer entirely and uses the single OpenAI-compatible provider defined in `.env`.
> The currently maintained balancer providers in this repository are `gemini`, `groq`, and `cerebras`.

---

## Load Balancer Architecture (`Aequilibrium`)

The **Aequilibrium** balancer (`aequilibrium/aequilibrium.lua`) is a lightweight LuaJIT socket microservice running on port `8081`.

It currently provides:

1. **Round-Robin Provider Rotation**: cycles across active providers.
2. **Round-Robin Key Rotation**: rotates keys within each provider.
3. **Session Anchoring**: pins a conversation session to one provider/key/model during a multi-step interaction.
4. **Early Failure Failover**: if a provider/model fails before streaming starts, `Interpres` unpins the session and requests the next candidate.
5. **PostgreSQL-backed Key State**: key health is tracked in PostgreSQL.
6. **Automatic Key Sync**: PostgreSQL key-state records are synchronized from `tabularium/provisores/*/claves.txt`, so newly added keys appear automatically and removed keys do not remain as zombie records.

---

## Active Provider Rules

The balancer reads provider configuration from `tabularium/provisores/`.

A provider is considered active only if:

1. `claves.txt` exists and contains at least one key.
2. `modela.txt` exists and contains at least one model.
3. `url.txt` exists and contains a valid endpoint.

If any of these are missing or empty, that provider is skipped automatically.
If no providers are active, `Interpres` receives a clean `500` response indicating that no providers are available.

---

## Directory Layout

```text
tabularium/provisores/
|-- gemini/
|   |-- claves.txt
|   |-- claves.txt.example
|   |-- modela.txt
|   `-- url.txt
|-- groq/
|   |-- claves.txt
|   |-- claves.txt.example
|   |-- modela.txt
|   `-- url.txt
`-- cerebras/
    |-- claves.txt
    |-- claves.txt.example
    |-- modela.txt
    `-- url.txt
```

---

## Recommended Models

These are the current repository defaults for inexpensive usage and relatively friendly free-tier limits:

* `gemini-2.5-flash-lite`
* `gemini-2.0-flash-lite`
* `llama-3.1-8b-instant` (Groq)
* `openai/gpt-oss-20b` (Groq)
* `llama3.1-8b` (Cerebras)

At the time of this update, `sambanova` is excluded from the active Lua balancer rotation because its credit model no longer fits the project's free-tier rotation strategy. However, its keys are still synchronized to PostgreSQL by Daemonium for future availability if needed.

---

## Key State And Quarantine

Key state is tracked in PostgreSQL through `llm_key_status` and `llm_key_events`.

The current runtime behavior is:

* `429` or repeated rate-limit behavior: the key is placed into a 30-minute rest period.
* Region or location restrictions: treated as temporary rest, not permanent disable.
* `401`, `402`, or non-region `403`: treated as invalid/auth/billing failures and the key is disabled.
* `400` / `404`: treated as request/model errors, not as permanent key failures.
* `5xx` and transport failures: treated as transient provider-side issues.

Because the key registry is synced from the on-disk `claves.txt` files, adding or removing keys from those files automatically updates the PostgreSQL view of the pool.

---

## Step-by-Step Configuration

### Step 1: Populate keys

Copy the template for your provider and create a real `claves.txt`:

```bash
cp tabularium/provisores/gemini/claves.txt.example tabularium/provisores/gemini/claves.txt
```

Put one real API key per line.

### Step 2: Configure models

Write one model per line into `modela.txt`.

Example `gemini/modela.txt`:

```text
gemini-2.5-flash-lite
gemini-2.0-flash-lite
```

### Step 3: Verify endpoint

Check `url.txt` and make sure it points to the provider's OpenAI-compatible endpoint.

Example `gemini/url.txt`:

```text
https://generativelanguage.googleapis.com/v1beta/openai/chat/completions
```

---

## Security And Git Hygiene

Real key files are ignored by Git:

```ini
tabularium/provisores/*/claves.txt
```

Rules:

* Commit only `.example` templates.
* Do not commit real keys.
* If a real key file was ever staged, remove it from the index with:

```bash
git rm --cached tabularium/provisores/<provider>/claves.txt
```

---

## Live Reload

You do not need to rebuild containers when changing provider files.

* `Aequilibrium` re-reads provider configuration on its refresh cycle.
* `Daemonium` synchronizes PostgreSQL key-state records from the same files during runtime.

This keeps:

* active keys
* disabled keys
* resting keys
* newly added keys
* removed keys

consistent between disk and database.
