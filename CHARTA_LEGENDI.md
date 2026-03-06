# ChatDaemonium (Oraculum)

Hoc est repositorium in quo vivit chat antiquus et arcanus, scriptus in linguis classicis ut PHP et Pascal. Systema habet tres partes principales:

1. **Daemonium** (Pascal Backend):
   Daemonium est pars maxima et potissima. Scriptum in `FreePascal`, operatur in portu `8080`. Daemonium utitur `sockets` ad communicandum cum interpres. In `daemonium.pas` invenies logicam retis et systema RAG (Retrievium Augmentatum Generatium), quod in textibus quaerit et similia verba invenit. Omnis logica, commentaria et variabiles in lingua Latina scripta sunt.

2. **Interpres** (PHP BFF & Frontend):
   Scripta in PHP 8. Hic invenies duas paginas: `index.php` et `fabulatio.php`. Facies utitur HTML 3.2 / 4.01 stilo classico "console" (litterae virides, fondum nigrum). Communicat cum Daemonio per `fsockopen` et cum OpenAI API per `cURL`.

3. **Tabularium** (Database):
   Omnes notitiae in fasciculis textus (.txt) conservantur.
   - `usores.txt`: Nomina usorum.
   - `scientia/scientia.txt`: Basis scientiae pro RAG (Retrievium Augmentatum Generatium).
   - `fabulatio_*.txt`: Historia nuntiorum pro quoque usore.

## Magia Nigra (Docker Compose)

Ut incipias per "Docker", solum opus est uti hoc mandato:
```bash
export OPENAI_API_KEY="tua-clavis-hic"
docker-compose up --build -d
```
Tunc aperi navigatorem ad `http://localhost:8000`.

## Quomodo incipere sine Magia (Manual run)

1. Instrue `fpc` (FreePascal) et `php`.
2. Scribe `export OPENAI_API_KEY="tua-clavis"` in linea mandati.
3. In primo termino, curre Daemonium:
   ```bash
   cd daemonium
   fpc daemonium.pas
   ./daemonium
   ```
4. In secundo termino, curre PHP:
   ```bash
   cd interpres
   php -S 127.0.0.1:8000
   ```
5. Aperi navigatorem ad `http://127.0.0.1:8000`.

## Quomodo API configurare (How to configure API)

Si vis uti alio provisore (e.g. ad compatibilitatem cum OpenAI protocollo, sicut LocalAI vel vLLM), potes etiam URL mutare:
```bash
export OPENAI_API_URL="http://localhost:8080/v1/chat/completions"
export OPENAI_API_KEY="tua-clavis-hic"
docker-compose up --build -d
```

*Memento: Omnes viae Romam ducunt!*
