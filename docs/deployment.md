# 🔮 Deployment & Summoning Guide - Necromancer

This guide details the procedures to summon (deploy) the **Necromancer** stack, configure environment variables, and manage operations.

---

## 🐋 1. Rapid Summoning (Docker Compose Deployment)

Docker Compose is the recommended way to stand up the entire architecture in seconds. This automatically compiles the Pascal source, installs the required PostgreSQL client libraries inside the daemon, provisions the DB, and sets up networking.

### Step 1: Clone and Enter the Vault
```bash
git clone https://github.com/Lotargo/Necromancer.git
cd Necromancer
```

### Step 2: Establish the Mystical Environment
Copy the example environment files, choose the LLM mode in `config.env`, and configure your custom OpenAI-compatible provider in `.env`:
```bash
cp .env.example .env
cp config.env.example config.env
```

#### Runtime Mode Blueprint (`config.env`):
```ini
AEQUILIBRIUM_ENABLED=true
```

* `true`: use the Lua balancer and ignore the OpenAI values from `.env`.
* `false`: use only the `.env` OpenAI-compatible provider values.

#### Custom Provider Blueprint (`.env`):
```ini
# --- Custom OpenAI-Compatible Provider ---
OPENAI_API_URL=https://api.openai.com/v1/chat/completions
OPENAI_API_MODEL=gpt-4o-mini
OPENAI_API_KEY=your_secret_openai_key_here

# --- Relational Database Secrets ---
DB_HOST=db
DB_PORT=5432
DB_NAME=necromancer
DB_USER=necromancer
DB_PASS=necromancer_secret
```

### Step 3: Evoke the Containers
Run Docker Compose in detached mode to compile, construct, and boot all services:
```bash
docker-compose up --build -d
```

### Step 4: Validate the Summoning
Check the running state of the microservices:
```bash
docker-compose ps
```

Verify that `chat_daemonium` has successfully initialized connection to PostgreSQL and auto-created the schemas:
```bash
docker-compose logs chat_daemonium
```

*Expected output should confirm database schema validation/creation with no errors.*

---

## 🛠️ 2. Manual Summoning (Native Compilation)

For developers wishing to run the services bare-metal without containerization:

### Prerequisites:
* **Operating Systems**: Linux (Debian/Ubuntu), macOS, or Windows.
* **Compiler**: FreePascal Compiler (`fpc` version 3.2.0 or higher).
* **Database Driver**: A running local **PostgreSQL 15** server with client libraries (`libpq`) installed.
* **Runtime**: PHP 8.1 or higher.

### Step 1: Database Initialization
Connect to your local Postgres server and initialize the database:
```sql
CREATE DATABASE necromancer;
CREATE USER necromancer WITH PASSWORD 'necromancer_secret';
GRANT ALL PRIVILEGES ON DATABASE necromancer TO necromancer;
```

### Step 2: Set Environment Variables
Set the database connection variables in your terminal shell:
```bash
export DB_HOST="localhost"
export DB_PORT="5432"
export DB_NAME="necromancer"
export DB_USER="necromancer"
export DB_PASS="necromancer_secret"
```

### Step 3: Compile and Launch `Daemonium`
Navigate to the daemon source directory and run the FreePascal compiler:
```bash
cd daemonium
fpc -O2 daemonium.pas
./daemonium
```
*Note: Make sure that `libpq` is on your system path so the FPC connection library can bind dynamically.*

### Step 4: Launch `Interpres` (PHP Web Server)
Navigate to the web gateway directory and launch PHP's built-in development server:
```bash
cd ../interpres
php -S 127.0.0.1:666
```

### Step 5: Enter the Portal
Open your web browser and navigate to: **[http://localhost:666](http://localhost:666)**

---

## 🛑 3. Controlling the Ritual (Shutdown & Cleanup)

To gracefully stop and shut down the containers while retaining the persisted PostgreSQL database data:
```bash
docker-compose down
```

To wipe the database volume and reset all data for a clean fresh boot:
```bash
docker-compose down -v
```
