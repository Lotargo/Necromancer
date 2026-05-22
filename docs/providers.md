# ⚙️ LLM Providers & Load Balancing Guide - Necromancer

This guide explains how the LLM load-balancing architecture works, how to configure API providers, add models, rotate keys, and secure configurations.

---

## 🏛️ Load Balancer Architecture (`Aequilibrium`)

The **Aequilibrium** balancer (`aequilibrium/aequilibrium.lua`) is a high-performance socket microservice written in LuaJIT. It runs natively on port `8081` and performs two key tasks:
1. **Round-Robin Provider Rotation**: It cycles through the list of active API providers to balance traffic.
2. **Round-Robin Key Rotation**: For each active provider, it automatically rotates through their list of configured API keys (avoiding rate limits).
3. **Session Anchoring**: For conversations utilizing multi-step reasoning (ReAct/CoT), it anchors the session ID to the selected provider and key to prevent context-switching errors.

---

## 🔌 Zero-Config Active Provider Filtering

The balancer implements smart, zero-config active provider filtering. It dynamically scans the subdirectories inside `tabularium/provisores/` on startup and every 60 seconds (hot-reload).

### How it works:
A provider is considered **active** and added to the balancing pool only if:
1. `claves.txt` exists and contains **at least one** API key.
2. `modela.txt` exists and contains **at least one** model identifier.
3. `url.txt` exists and contains the valid API endpoint.

> [!IMPORTANT]  
> If any of these files are empty, missing, or contain no valid lines, the load balancer **automatically skips the provider** without throwing any errors. The balancer will seamlessly distribute requests among the remaining active providers.
> 
> If **no** providers have keys configured, the balancer will return a clean error code (`500`) to `Interpres` indicating that no active providers are available.

---

## 📂 Directory Structure for Providers

All provider configurations are stored under `tabularium/provisores/`. Each provider must have its own subdirectory:

```
tabularium/provisores/
├── gemini/
│   ├── claves.txt         <-- Real API keys (one per line, Git-ignored)
│   ├── claves.txt.example <-- Example template
│   ├── modela.txt         <-- Model names to rotate (one per line)
│   └── url.txt            <-- Provider API base URL
├── groq/
│   ├── claves.txt
│   ├── claves.txt.example
│   ├── modela.txt
│   └── url.txt
...
```

---

## 🛠️ Step-by-Step Configuration Guide

To add or update keys and models for a provider, follow these simple steps:

### Step 1: Copy and populate keys
Locate the provider's folder under `tabularium/provisores/<name>/`. Copy the example file to create your active keys file:
```bash
cp tabularium/provisores/gemini/claves.txt.example tabularium/provisores/gemini/claves.txt
```
Open `claves.txt` and paste your real API keys, **one key per line**.

### Step 2: Configure models to rotate
Open `modela.txt` in the same directory and write the models you want to use, **one model per line**. The balancer will rotate requests among these models:
* Example `gemini/modela.txt`:
  ```text
  gemini-1.5-flash
  gemini-1.5-pro
  ```

### Step 3: Verify the API URL
Check `url.txt` to ensure it points to the correct endpoint.
* Example `gemini/url.txt`:
  ```text
  https://generativelanguage.googleapis.com/v1beta/openai/chat/completions
  ```

---

## 🛡️ Security & Git Safeguards

All real API keys (**`claves.txt`**) are strictly ignored in `.gitignore` to prevent any credentials leak:
```ini
# Ignored secrets inside .gitignore
tabularium/provisores/*/claves.txt
```
* **Always commit `.example` templates** instead of the real `claves.txt` files.
* **If a key was tracked by Git previously**, untrack it immediately from the Git index while keeping it on your local disk:
  ```bash
  git rm --cached tabularium/provisores/<provider>/claves.txt
  ```

---

## 🔄 Live Configuration Reloading (Hot-Reload)

You do **not** need to restart or rebuild your Docker containers when adding, removing, or modifying API keys or models!
The balancer (`Aequilibrium`) automatically checks for file modifications **every 60 seconds** and re-builds its internal active provider cache. You can modify keys live on your production server.
