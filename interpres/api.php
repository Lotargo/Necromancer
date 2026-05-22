<?php
session_start();
set_time_limit(0);

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
    return str_replace(['|', "\r", "\n"], [' ', '', ' '], $val);
}

$action = sani($_GET['action'] ?? $_POST['action'] ?? '');

function loqui_cum_daemonio($mandatum)
{
    $daemonium_host = getenv("DAEMONIUM_HOST") ?: "127.0.0.1";
    $fp = @fsockopen($daemonium_host, 8080, $errno, $errstr, 10);
    if (!$fp) {
        error_log("[ERR] Daemonium connection failed: $errstr ($errno)");
        return "500|Error|Daemonium non respondet";
    }
    fwrite($fp, $mandatum . "\n");
    $responsum = fgets($fp, 8192);
    fclose($fp);
    error_log("[DAEMON REQ] " . $mandatum);
    error_log("[DAEMON RESP] " . trim($responsum));
    return trim($responsum);
}

function purgare_sessionem_aequilibrio($id_sessionis)
{
    $aequilibrium_host = getenv("AEQUILIBRIUM_HOST") ?: "127.0.0.1";
    $fp = @fsockopen($aequilibrium_host, 8081, $errno, $errstr, 2);
    if (!$fp)
        return false;
    fwrite($fp, "PURGARE_SESSIONEM|" . $id_sessionis . "\n");
    $responsum = fgets($fp, 8192);
    fclose($fp);
    return trim($responsum);
}

function loqui_cum_aequilibrio($id_sessionis)
{
    $aequilibrium_host = getenv("AEQUILIBRIUM_HOST") ?: "127.0.0.1";
    $fp = @fsockopen($aequilibrium_host, 8081, $errno, $errstr, 2);
    if (!$fp)
        return false;
    fwrite($fp, "PETERE_CLAVEM|" . $id_sessionis . "\n");
    $responsum = fgets($fp, 8192);
    fclose($fp);
    return trim($responsum);
}

function env_ad_boolean($nomen, $default = false)
{
    $valor = getenv($nomen);
    if ($valor === false || $valor === '') {
        return $default;
    }

    $parsed = filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($parsed === null) {
        return $default;
    }

    return $parsed;
}

function sanitizare_internam($value)
{
    return str_replace(["|", "\r", "\n"], [" ", "", " "], (string)$value);
}

function statum_clavis_llm($provider, $key)
{
    $resp = loqui_cum_daemonio(
        "STATUM_CLAVIS_LLM|" . sanitizare_internam($provider) . "|" . sanitizare_internam($key)
    );
    $partes = explode("|", $resp, 3);
    if (($partes[0] ?? '') === "200") {
        return trim($partes[2] ?? 'active');
    }
    return 'active';
}

function notare_eventum_clavis_llm($provider, $key, $model, $event_type, $http_code, $error_kind, $detail = '')
{
    $mandatum = implode("|", [
        "NOTARE_EVENTUM_CLAVIS_LLM",
        sanitizare_internam($provider),
        sanitizare_internam($key),
        sanitizare_internam($model),
        sanitizare_internam($event_type),
        sanitizare_internam((string)$http_code),
        sanitizare_internam($error_kind),
        sanitizare_internam($detail),
    ]);
    return loqui_cum_daemonio($mandatum);
}

function classificare_eventum_llm($http_code, $err_str)
{
    $text = strtolower((string)$err_str);

    if ($http_code === 429 || strpos($text, 'rate limit') !== false || strpos($text, 'too many requests') !== false) {
        return ["event_type" => "RATE_LIMIT", "error_kind" => "rate_limit"];
    }

    $is_region = (
        strpos($text, 'region') !== false ||
        strpos($text, 'country') !== false ||
        strpos($text, 'location') !== false ||
        strpos($text, 'unsupported_location') !== false
    );
    if ($is_region) {
        return ["event_type" => "REGION_BLOCKED", "error_kind" => "region_blocked"];
    }

    $is_auth = (
        strpos($text, 'invalid api key') !== false ||
        strpos($text, 'unauthorized') !== false ||
        strpos($text, 'authentication') !== false ||
        strpos($text, 'payment method is required') !== false ||
        strpos($text, 'billing') !== false ||
        strpos($text, 'account') !== false
    );

    if ($http_code === 401 || $http_code === 402 || ($http_code === 403 && !$is_region) || $is_auth) {
        return ["event_type" => "DISABLE", "error_kind" => "auth_or_billing"];
    }

    if ($http_code === 400 || $http_code === 404) {
        return ["event_type" => "MODEL_ERROR", "error_kind" => "model_or_request"];
    }

    if ($http_code >= 500 || $http_code === 0 || strpos($text, 'timed out') !== false || strpos($text, 'could not resolve') !== false) {
        return ["event_type" => "TRANSIENT", "error_kind" => "transient"];
    }

    return ["event_type" => "TRANSIENT", "error_kind" => "unknown"];
}

function eligere_destinationem_llm($id_sessionis, $aequilibrium_activum)
{
    if ($aequilibrium_activum) {
        $ultimus_error = "Aequilibrium activum est, sed nullum responsum validum cum provisore, clave et modelo accepimus.";
        for ($temptatio = 0; $temptatio < 12; $temptatio++) {
            $aequilibrium_resp = loqui_cum_aequilibrio($id_sessionis);
            if ($aequilibrium_resp) {
                $partes_aeq = explode("|", $aequilibrium_resp);
                if (count($partes_aeq) >= 6 && $partes_aeq[0] === "200") {
                    $provider = $partes_aeq[2];
                    $key = $partes_aeq[3];
                    $status = statum_clavis_llm($provider, $key);
                    if ($status === 'active') {
                        return [
                            "apikey" => $key,
                            "api_url" => $partes_aeq[4],
                            "model" => $partes_aeq[5],
                            "provisor_nomen" => $provider,
                            "error" => null
                        ];
                    }

                    $ultimus_error = "Omnes claves huius rotationis sunt in statu '" . $status . "'.";
                    purgare_sessionem_aequilibrio($id_sessionis);
                    continue;
                }
            }
        }

        return [
            "apikey" => null,
            "api_url" => null,
            "model" => null,
            "provisor_nomen" => "Ignotus",
            "error" => $ultimus_error
        ];
    }

    $apikey = getenv("OPENAI_API_KEY") ?: null;
    $api_url = getenv("OPENAI_API_URL") ?: "https://api.openai.com/v1/chat/completions";
    $model = getenv("OPENAI_API_MODEL") ?: "gpt-4o-mini";

    return [
        "apikey" => $apikey,
        "api_url" => $api_url,
        "model" => $model,
        "provisor_nomen" => $apikey ? "Custom" : "Ignotus",
        "error" => $apikey ? null : "Aequilibrium inactivum est, ergo valores ex .env requiruntur."
    ];
}

// Public Actions (No Session Required)
if ($action === 'login_anima' || $action === 'register_anima' || $action === 'forgot_anima') {
    $fp_client = sani($_POST['fp'] ?? '');
    if ($action === 'login_anima') {
        $email = sani($_POST['email'] ?? '');
        $pass = sani($_POST['pass'] ?? '');
        $resp = loqui_cum_daemonio("INTRARE_PLENUM|$email|$pass|$fp_client");
        $partes = explode("|", $resp);
        if ($partes[0] == "200") {
            $_SESSION["usor"] = sani($partes[2]);
            $_SESSION["fp"] = $fp_client;
            if (isset($_POST['remember'])) {
                setcookie(session_name(), session_id(), time() + 86400 * 30, "/");
            }
            echo json_encode(["status" => "ok", "usor" => sani($partes[2])]);
        }
        else {
            echo json_encode(["status" => "error", "message" => sani($partes[2])]);
        }
    }
    else if ($action === 'register_anima') {
        $nomen = sani($_POST['nomen'] ?? '');
        $email = sani($_POST['email'] ?? '');
        $pass = sani($_POST['pass'] ?? '');
        $resp = loqui_cum_daemonio("CREARE_USOREM_PLENUM|$nomen|$email|$pass|$fp_client");
        $partes = explode("|", $resp);
        if ($partes[0] == "200") {
            $_SESSION["usor"] = $nomen;
            $_SESSION["fp"] = $fp_client;
            echo json_encode(["status" => "ok"]);
        }
        else {
            echo json_encode(["status" => "error", "message" => sani($partes[2])]);
        }
    }
    else if ($action === 'forgot_anima') {
        $email = sani($_POST['email'] ?? '');
        $resp = loqui_cum_daemonio("PETERE_RECUPERATIONEM|$email");
        $partes = explode("|", $resp);
        echo json_encode(["status" => ($partes[0] == "200" ? "ok" : "error"), "message" => sani($partes[2])]);
    }
    exit();
}

// Move session check above `get_user_state`
if (!isset($_SESSION["usor"]) || !isset($_SESSION["fp"])) {
    http_response_code(401);
    exit("Unauthorized (No Session or FP)");
}
$usor = $_SESSION["usor"];
$user_fp = $_SESSION["fp"];

if ($action === 'get_user_state') {
    // Get messages count
    $resp_nuntios = loqui_cum_daemonio("NUMERARE_NUNTIOS|" . $usor . "|" . $user_fp);
    $partes_nuntios = explode("|", $resp_nuntios);
    $messages = 0;
    if ($partes_nuntios[0] == "200") {
        $messages = (int)$partes_nuntios[2];
    }

    // Calculate level (1 msg -> level 1, 2 msgs -> level 2, etc up to 13)
    // 2 messages = level 2 as per user request (so level = min(13, 1 + floor(messages / 2)))
    $level = min(13, 1 + floor($messages / 2));

    // Get options
    $resp_opt = loqui_cum_daemonio("LEGERE_OPTIONES|" . $usor . "|" . $user_fp);
    $partes_opt = explode("|", $resp_opt);
    $options_json = "{}";
    if ($partes_opt[0] == "200") {
        $options_json = $partes_opt[2];
    }

    header('Content-Type: application/json');
    echo json_encode([
        "status" => "ok",
        "messages" => $messages,
        "level" => $level,
        "options" => json_decode($options_json, true) ?: new stdClass()
    ]);
    exit();
}

if ($action === 'save_options') {
    $options_str = $_POST['options'] ?? '{}';
    $safe_options = json_encode(json_decode($options_str)); // Validate JSON
    if ($safe_options) {
        $safe_options = str_replace(['|', "\r", "\n"], ['\\u007c', '', ''], $safe_options);
        $resp = loqui_cum_daemonio("SERVARE_OPTIONES|" . $usor . "|" . $safe_options . "|" . $user_fp);
        $partes = explode("|", $resp);
        if ($partes[0] == "200") {
            echo json_encode(["status" => "ok"]);
            exit();
        }
    }
    echo json_encode(["status" => "error", "message" => "Could not save options"]);
    exit();
}

function investigare_in_tela($query)
{
    $url = "https://html.duckduckgo.com/html/?q=" . urlencode($query);
    $attempts = 0;
    $max_attempts = 3;
    $html = "";
    $http_code = 0;

    while ($attempts < $max_attempts) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        curl_setopt($ch, CURLOPT_REFERER, 'https://duckduckgo.com/');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        // Use a cookie file to persist session if DDG requests it
        $cookie_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ddg_cookies.txt';
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);

        $html = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($http_code === 200 && !empty($html) && strlen($html) > 500) {
            break;
        }

        $attempts++;
        if ($attempts < $max_attempts) {
            usleep(700000); // Wait 0.7s before retry
        }
    }

    if ($http_code !== 200 || empty($html)) {
        return "Error in tela (HTTP $http_code): " . ($error ?: "Vacuum responsum.");
    }

    $matches = [];
    // Pattern 1: a.result__snippet
    preg_match_all('/<a[^>]*class="result__snippet"[^>]*>(.*?)<\/a>/s', $html, $matches);

    if (empty($matches[1])) {
        // Pattern 2: div.result__snippet
        preg_match_all('/<div[^>]*class="result__snippet"[^>]*>(.*?)<\/div>/s', $html, $matches);
    }

    $snippets = array_slice($matches[1] ?? [], 0, 5);
    $clean_snippets = array_map(function ($s) {
        return trim(strip_tags(html_entity_decode($s)));
    }, $snippets);

    if (empty($clean_snippets)) {
        // Fallback: any text paragraphs
        preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $html, $matches);
        $snippets = array_slice($matches[1] ?? [], 0, 3);
        $clean_snippets = array_map(function ($s) {
            return trim(strip_tags(html_entity_decode($s)));
        }, $snippets);
    }

    if (empty($clean_snippets)) {
        return "Nihil inventum (regex mismatch). Raw length: " . strlen($html) . " bytes. Query: " . $query;
    }

    return implode("\n---\n", $clean_snippets);
}

$action = sani($_GET['action'] ?? $_POST['action'] ?? '');
$cubiculum = sani($_GET['room'] ?? $_POST['room'] ?? 'default');

if ($action === 'list') {
    $resp = loqui_cum_daemonio("INDEX_FABULATIONUM|" . $usor . "|" . $user_fp);
    $partes = explode("|", $resp, 3);
    $rooms = [];
    if ($partes[0] == "200") {
        $rooms = explode(",", $partes[2]);
    }
    header('Content-Type: application/json');
    echo json_encode(["rooms" => $rooms]);
    exit();
}

if ($action === 'load') {
    $resp = loqui_cum_daemonio("LEGENDE_NUNTIOS|" . $usor . "|" . $cubiculum . "|" . $user_fp);
    $partes = explode("|", $resp, 3);
    $historia = ($partes[0] == "200") ? $partes[2] : "";
    header('Content-Type: text/plain');
    echo str_replace('\n', "\n", $historia);
    exit();
}

if ($action === 'delete') {
    loqui_cum_daemonio("DELE_FABULATIONEM|" . $usor . "|" . $cubiculum . "|" . $user_fp);
    
    // Clean up room from chat_names in optiones_json
    $resp_opt = loqui_cum_daemonio("LEGERE_OPTIONES|" . $usor . "|" . $user_fp);
    $partes_opt = explode("|", $resp_opt);
    if ($partes_opt[0] == "200") {
        $options = json_decode($partes_opt[2], true) ?: [];
        if (isset($options['chat_names'][$cubiculum])) {
            unset($options['chat_names'][$cubiculum]);
            $safe_options = json_encode($options, JSON_UNESCAPED_UNICODE);
            $safe_options = str_replace(['|', "\r", "\n"], ['\\u007c', '', ''], $safe_options);
            loqui_cum_daemonio("SERVARE_OPTIONES|" . $usor . "|" . $safe_options . "|" . $user_fp);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(["status" => "ok"]);
    exit();
}

if ($action === 'renominare_usorem') {
    $novum_nomen = trim($_POST['novum_nomen'] ?? '');
    if (empty($novum_nomen)) {
        echo json_encode(["status" => "error", "message" => "Nomen vacuum est"]);
        exit();
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $novum_nomen);
    if ($safeName) {
        $resp = loqui_cum_daemonio("RENOMINARE_USOREM|" . $usor . "|" . $safeName . "|" . $user_fp);
        $partes = explode("|", $resp);
        if ($partes[0] == "200") {
            $_SESSION["usor"] = $safeName;
            echo json_encode(["status" => "ok", "novum_nomen" => $safeName]);
        }
        else {
            echo json_encode(["status" => "error", "message" => $partes[2] ?? 'Error']);
        }
    }
    else {
        echo json_encode(["status" => "error", "message" => "Nomen non validum"]);
    }
    exit();
}

if ($action === 'mutare_tessaram') {
    $vetus_pass = sani($_POST['vetus_pass'] ?? '');
    $nova_pass = sani($_POST['nova_pass'] ?? '');

    if (empty($vetus_pass) || empty($nova_pass)) {
        echo json_encode(["status" => "error", "message" => "Tessera vacua est"]);
        exit();
    }

    $resp = loqui_cum_daemonio("MUTARE_TESSARAM|" . $usor . "|" . $vetus_pass . "|" . $nova_pass . "|" . $user_fp);
    $partes = explode("|", $resp);

    if ($partes[0] == "200") {
        echo json_encode(["status" => "ok"]);
    }
    else {
        echo json_encode(["status" => "error", "message" => $partes[2] ?? 'Error']);
    }
    exit();
}

if ($action === 'delere_rationem') {
    $resp = loqui_cum_daemonio("DELERE_RATIONEM|" . $usor . "|" . $user_fp);
    $partes = explode("|", $resp);

    if ($partes[0] == "200") {
        session_destroy();
        echo json_encode(["status" => "ok"]);
    }
    else {
        echo json_encode(["status" => "error", "message" => $partes[2] ?? 'Error']);
    }
    exit();
}

if ($action === 'delere_omnes_fabulationes') {
    $resp = loqui_cum_daemonio("DELERE_OMNES_FABULATIONES|" . $usor . "|" . $user_fp);
    $partes = explode("|", $resp);

    if ($partes[0] == "200") {
        echo json_encode(["status" => "ok"]);
    }
    else {
        echo json_encode(["status" => "error", "message" => $partes[2] ?? 'Error']);
    }
    exit();
}

if ($action === 'rename') {
    $new_room = trim($_POST['new_room'] ?? '');
    if (!empty($new_room)) {
        $resp_opt = loqui_cum_daemonio("LEGERE_OPTIONES|" . $usor . "|" . $user_fp);
        $partes_opt = explode("|", $resp_opt);
        $options = [];
        if ($partes_opt[0] == "200") {
            $options = json_decode($partes_opt[2], true) ?: [];
        }
        if (!isset($options['chat_names'])) {
            $options['chat_names'] = [];
        }
        $options['chat_names'][$cubiculum] = $new_room;
        
        $safe_options = json_encode($options, JSON_UNESCAPED_UNICODE);
        $safe_options = str_replace(['|', "\r", "\n"], ['\\u007c', '', ''], $safe_options);
        
        $resp = loqui_cum_daemonio("SERVARE_OPTIONES|" . $usor . "|" . $safe_options . "|" . $user_fp);
        $partes = explode("|", $resp);
        if ($partes[0] == "200") {
            header('Content-Type: application/json');
            echo json_encode(["status" => "ok"]);
            exit();
        }
    }
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Could not rename chat"]);
    exit();
}

if ($action === 'send') {
    $nuntius = trim($_POST['nuntius'] ?? '');
    $nuntius = sani_nuntius($nuntius);
    if (empty($nuntius))
        exit();

    // 0. Check if it's the first message in this room
    $is_first = false;
    $resp_hist = loqui_cum_daemonio("LEGENDE_NUNTIOS|" . $usor . "|" . $cubiculum . "|" . $user_fp);
    $partes_hist = explode("|", $resp_hist, 3);
    if ($partes_hist[0] !== "200" || trim($partes_hist[2]) === "") {
        $is_first = true;
    }

    $aequilibrium_activum = env_ad_boolean("AEQUILIBRIUM_ENABLED", true);
    $destinatio_llm = eligere_destinationem_llm($cubiculum, $aequilibrium_activum);
    $apikey = $destinatio_llm["apikey"];
    $api_url = $destinatio_llm["api_url"];
    $model = $destinatio_llm["model"];
    $provisor_nomen = $destinatio_llm["provisor_nomen"];

    // 0.5 Auto-name if first message
    $renamed_to = "";
    if ($is_first && $apikey) {
        $title_prompt = "Provide a very short 1-3 word title in Latin for this message: '" . $nuntius . "'. Keep it very brief, only the Latin words.";
        $data_title = [
            "model" => $model,
            "messages" => [["role" => "user", "content" => $title_prompt]],
            "max_tokens" => 10
        ];
        $ch_t = curl_init($api_url);
        curl_setopt($ch_t, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_t, CURLOPT_POST, true);
        curl_setopt($ch_t, CURLOPT_POSTFIELDS, json_encode($data_title, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        curl_setopt($ch_t, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer " . $apikey]);
        $res_t = curl_exec($ch_t);
        curl_close($ch_t);

        $json_t = json_decode($res_t, true);
        if (isset($json_t['choices'][0]['message']['content'])) {
            $new_title = trim($json_t['choices'][0]['message']['content']);
            $new_title = trim($new_title, " \t\n\r\0\x0B.\"'`“”");
            if (!empty($new_title)) {
                $renamed_to = substr($new_title, 0, 50);
                
                // Read and update options
                $resp_opt = loqui_cum_daemonio("LEGERE_OPTIONES|" . $usor . "|" . $user_fp);
                $partes_opt = explode("|", $resp_opt);
                $options = [];
                if ($partes_opt[0] == "200") {
                    $options = json_decode($partes_opt[2], true) ?: [];
                }
                if (!isset($options['chat_names'])) {
                    $options['chat_names'] = [];
                }
                $options['chat_names'][$cubiculum] = $renamed_to;
                
                $safe_options = json_encode($options, JSON_UNESCAPED_UNICODE);
                $safe_options = str_replace(['|', "\r", "\n"], ['\\u007c', '', ''], $safe_options);
                loqui_cum_daemonio("SERVARE_OPTIONES|" . $usor . "|" . $safe_options . "|" . $user_fp);
            }
        }
    }

    // 1. Save user message
    loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|" . $user_fp . "|Tute: " . $nuntius);

    // 2. Prepare LLM Stream Context
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    while (ob_get_level() > 0)
        ob_end_flush();

    $lingua_mode = $_POST['lingua'] ?? 'latin';
    $search_mode = $_POST['search'] ?? 'off';

    // If we renamed the room, tell the client before streaming the message
    if ($renamed_to) {
        echo "data: " . json_encode(["event" => "renamed", "new_name" => $renamed_to]) . "\n\n";
        flush();
    }

    if (!$apikey) {
        $msg = "Clavis API deest. " . $destinatio_llm["error"];
        echo "data: " . json_encode(["choices" => [["delta" => ["content" => $msg]]]]) . "\n\n";
        loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|" . $user_fp . "|Oraculum: " . $msg);
        exit();
    }

    // Read LLM config
    $llm_config_path = __DIR__ . '/../tabularium/llm_config.json';
    $llm_config = ["max_tokens" => 4096, "temperature" => 1.0, "top_p" => 0.95];
    if (file_exists($llm_config_path)) {
        $json_config = json_decode(file_get_contents($llm_config_path), true);
        if (is_array($json_config)) {
            $llm_config = array_merge($llm_config, $json_config);
        }
    }

    $system_role = "<system_instruction>
  <persona>
    Tu es philosophus Romanus. Responde semper Latine.
  </persona>
  <constraints>
    <max_tokens>{{MAX_TOKENS}}</max_tokens>
    <instruction>
      Te finibus strictis debes circumscribere: ad summum {{MAX_TOKENS}} indicia (tokens) tibi permittuntur.
    </instruction>
  </constraints>
  <tool_usage>
    <priority_instructions>
      1. REGULA CRITICA: Diligenter inspice usoris nuntium. Utrum salutatio vel colloquium simplex sit, an quaestio de facto investigando.
      2. Si nuntius SOLUM salutationes (ex. 'salve', 'ave', 'привет'), vel inquisitiones de statu tuo (ex. 'quomodo te habes?', 'как дела?'), vel colloquia casualia continet, NUNQUAM instrumenta voca. Responde statim ex sapientia tua philosophica.
      3. Si nuntius quaestionem de facto vel petitionem informationis continet, etiamsi salutationibus comitatur (ex. 'привет, какая погода в вашингтоне?', 'salve, quis est praeses Galliae?'), instrumentum (ex. search_web) vocare DEBES ut veritatem invenias.
    </priority_instructions>
    <rules>
      <rule type=\"allow\">
        <scenario>Usor rem de facto vel investigationem quaerit, sive cum salutatione sive sine ea.</scenario>
        <examples>
          <example>
            <input>quis est praeses Galliae?</input>
            <reason>Quaestio de facto de duce civitatis.</reason>
            <action>Voca search_web</action>
          </example>
          <example>
            <input>привет, какая погода в вашингтоне?</input>
            <reason>Factualis quaestio de tempestate continetur.</reason>
            <action>Voca search_web</action>
          </example>
        </examples>
      </rule>
      <rule type=\"forbid\">
        <scenario>Usoris input solum ex salutatione, colloquio simplici vel inquisitione polita sine ulla petitione facti constat.</scenario>
        <examples>
          <example>
            <input>salve</input>
            <reason>Salutatio simplex.</reason>
            <action>NOLITE instrumenta vocare. Responde directe philosophice.</action>
          </example>
          <example>
            <input>quomodo te habes?</input>
            <reason>Inquisitio polita de statu tuo.</reason>
            <action>NOLITE instrumenta vocare. Responde directe philosophice.</action>
          </example>
          <example>
            <input>привет, как дела?</input>
            <reason>Salutatio et colloquium casuale sine investigatione facti.</reason>
            <action>NOLITE instrumenta vocare. Responde directe.</action>
          </example>
        </examples>
      </rule>
    </rules>
  </tool_usage>
</system_instruction>";
    if ($lingua_mode === 'auto') {
        $system_role = "<system_instruction>
  <persona>
    You are an ancient Roman philosopher. You must express wise, philosophical thoughts but stay accessible, friendly, and speak in a natural manner.
  </persona>
  <languages>
    <language_mode>auto</language_mode>
    <instruction>
      You MUST speak in the EXACT SAME LANGUAGE that the user is speaking!
      - If the user writes in Russian, you MUST reply entirely in Russian (for example: 'Приветствую, путник...').
      - If the user writes in English, you MUST reply entirely in English.
      - NEVER reply in Latin unless the user explicitly speaks Latin to you.
      - Maintain your persona as a wise Roman philosopher, but express your thoughts natively in the user's language.
      - ALWAYS start your response with a greeting or acknowledgment in the user's language.
    </instruction>
  </languages>
  <constraints>
    <max_tokens>{{MAX_TOKENS}}</max_tokens>
    <instruction>
      You have a strict response length limit of {{MAX_TOKENS}} tokens. You MUST complete your thought and finish your narrative within this limit. Plan the length of your response accordingly.
    </instruction>
  </constraints>
  <tool_usage>
    <priority_instructions>
      1. CRITICAL RULE: Analyze the user's message to determine if it is pure conversation/greeting or if it requests real-world facts/searches.
      2. If the user's message contains ONLY generic greetings (like 'привет', 'hello'), casual questions about you ('как дела', 'how are you'), or basic pleasantries, you MUST NOT call any tools. You must reply immediately using your own philosophical wisdom.
      3. If the user's message contains a factual request or query (like 'какая погода в Вашингтоне', 'кто президент Франции'), EVEN IF it starts with a greeting (like 'привет', 'hi', 'здравствуй'), you MUST call the search tool to find the accurate answer.
    </priority_instructions>
    <rules>
      <rule type=\"allow\">
        <scenario>User asks a factual or information-seeking question, with or without greetings.</scenario>
        <examples>
          <example>
            <input>привет, какая погода в вашингтоне?</input>
            <reason>Contains a specific factual question about weather.</reason>
            <action>Call search_web</action>
          </example>
          <example>
            <input>кто президент Франции?</input>
            <reason>Contains a factual query about the current president.</reason>
            <action>Call search_web</action>
          </example>
          <example>
            <input>Привет! Расскажи о новостях в Риме сегодня.</input>
            <reason>Requests current real-world news requiring search.</reason>
            <action>Call search_web</action>
          </example>
        </examples>
      </rule>
      <rule type=\"forbid\">
        <scenario>User's input consists purely of greeting, casual talk, salutation, or polite inquiry without any factual request.</scenario>
        <examples>
          <example>
            <input>привет</input>
            <reason>Pure simple greeting.</reason>
            <action>Do NOT call any tools. Respond directly in a philosophical manner.</action>
          </example>
          <example>
            <input>как дела?</input>
            <reason>Pure casual question about state/wellbeing.</reason>
            <action>Do NOT call any tools. Respond directly in a philosophical manner.</action>
          </example>
          <example>
            <input>Привет! Как твои дела?</input>
            <reason>Combination of simple greeting and casual talk with no factual question.</reason>
            <action>Do NOT call any tools. Respond directly.</action>
          </example>
        </examples>
      </rule>
    </rules>
  </tool_usage>
</system_instruction>";
    }

    $system_role = str_replace("{{MAX_TOKENS}}", $llm_config['max_tokens'], $system_role);

    // Reconstruct history to give LLM context (up to last 10 messages)
    $chat_history = [];
    $resp_hist2 = loqui_cum_daemonio("LEGENDE_NUNTIOS|" . $usor . "|" . $cubiculum . "|" . $user_fp);
    $partes_hist2 = explode("|", $resp_hist2, 3);
    if ($partes_hist2[0] === "200" && trim($partes_hist2[2]) !== "") {
        $lines = explode('\n', $partes_hist2[2]);
        $lines = array_slice($lines, -20); // Last 20 lines
        foreach ($lines as $line) {
            if (strpos($line, 'Tute: ') === 0) {
                $chat_history[] = ["role" => "user", "content" => substr($line, 6)];
            }
            elseif (strpos($line, 'Oraculum: ') === 0) {
                $chat_history[] = ["role" => "assistant", "content" => substr($line, 10)];
            }
        }
    }

    $messages = [["role" => "system", "content" => $system_role]];
    $messages = array_merge($messages, $chat_history);

    // If they just said something, but it's not in the history fetch due to sync, add it
    if (empty($chat_history) || end($chat_history)['content'] !== $nuntius) {
        $messages[] = ["role" => "user", "content" => $nuntius];
    }

    $tools = [
        [
            "type" => "function",
            "function" => [
                "name" => "search_web",
                "description" => "Searches the internet for up-to-date real world information and news.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "query" => ["type" => "string", "description" => "The search query"]
                    ],
                    "required" => ["query"]
                ]
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "search_knowledge_base",
                "description" => "Searches the local Necronomicon daemonium database for esoteric, local, or platform specific knowledge.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "query" => ["type" => "string", "description" => "The exact Latin or English keywords to search"]
                    ],
                    "required" => ["query"]
                ]
            ]
        ]
    ];

    $max_loops = 8;
    $loop_count = 0;
    $final_response_content = "";
    $provisor_nuntiatus = "";

    while ($loop_count < $max_loops) {
        $loop_count++;

        $destinatio_llm = eligere_destinationem_llm($cubiculum, $aequilibrium_activum);
        $apikey = $destinatio_llm["apikey"];
        $api_url = $destinatio_llm["api_url"];
        $model = $destinatio_llm["model"];
        $provisor_nomen = $destinatio_llm["provisor_nomen"];

        if (!$apikey) {
            $err_msg = "Clavis API deest. " . $destinatio_llm["error"];
            echo "data: " . json_encode(["choices" => [["delta" => ["content" => $err_msg]]]]) . "\n\n";
            $final_response_content .= $err_msg;
            break;
        }

        if ($provisor_nomen !== "Ignotus") {
            $provisor_hash = $provisor_nomen . "|" . $model;
            if ($provisor_hash !== $provisor_nuntiatus) {
                echo "data: " . json_encode(["event" => "provisor", "nomen" => $provisor_nomen, "model" => $model]) . "\n\n";
                flush();
                $provisor_nuntiatus = $provisor_hash;
            }
        }

        $data = [
            "model" => $model,
            "messages" => $messages,
            "max_tokens" => $llm_config['max_tokens'],
            "temperature" => (float)$llm_config['temperature'],
            "top_p" => (float)$llm_config['top_p'],
            "stream" => true
        ];

        // Only include tools if search mode is ON
        if ($search_mode === 'on') {
            $data["tools"] = $tools;
        }

        $tool_calls_buffer = [];
        $current_content = "";
        $total_reasoning = "";
        $error_buffer = "";

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        $json_encoded_data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        file_put_contents(__DIR__ . "/tmp_payload.txt", print_r($data, true) . "
---
" . $json_encoded_data . "
========================
", FILE_APPEND);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_encoded_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apikey
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$tool_calls_buffer, &$current_content, &$final_response_content, &$error_buffer, &$total_reasoning) {
            $lines = explode("\n", $chunk);
            foreach ($lines as $line) {
                // If the API returns a JSON error, it usually doesn't start with "data: "
                if (!empty(trim($line)) && strpos($line, 'data: ') !== 0 && strpos(trim($line), '{') === 0 && empty($current_content) && empty($tool_calls_buffer)) {
                     $error_buffer .= $chunk;
                     return strlen($chunk);
                }

                if (strpos($line, 'data: ') === 0) {
                    $jsonStr = substr($line, 6);
                    if (trim($jsonStr) == '[DONE]') {
                        // Forward DONE only if we aren't handling tools right now
                        continue;
                    }
                    $json = json_decode($jsonStr, true);
                    if ($json && isset($json['choices'][0]['delta'])) {
                        $delta = $json['choices'][0]['delta'];

                        // Pass along regular content and reasoning to the client
                        if (isset($delta['content']) && $delta['content'] !== null) {
                            $current_content .= $delta['content'];
                            $final_response_content .= $delta['content'];
                            // Stream this chunk directly to client
                            echo "data: " . json_encode(["choices" => [["delta" => ["content" => $delta['content']]]]]) . "\n\n";
                            flush();
                        }

                        if (isset($delta['reasoning_content']) && $delta['reasoning_content'] !== null) {
                            $total_reasoning .= $delta['reasoning_content'];
                            echo "data: " . json_encode(["choices" => [["delta" => ["reasoning_content" => $delta['reasoning_content']]]]]) . "\n\n";
                            flush();
                        }

                        // Collect tool calls
                        if (isset($delta['tool_calls'])) {
                            foreach ($delta['tool_calls'] as $tc) {
                                $idx = $tc['index'];
                                if (!isset($tool_calls_buffer[$idx])) {
                                    $tool_calls_buffer[$idx] = [
                                        "id" => $tc['id'] ?? "",
                                        "type" => "function",
                                        "function" => [
                                            "name" => $tc['function']['name'] ?? "",
                                            "arguments" => $tc['function']['arguments'] ?? ""
                                        ]
                                    ];

                                    // Let the frontend know we are using a tool!
                                    if (!empty($tc['function']['name'])) {
                                        echo "data: " . json_encode(["event" => "tool_call", "name" => $tc['function']['name']]) . "\n\n";
                                        flush();
                                    }
                                }
                                else {
                                    if (isset($tc['function']['arguments'])) {
                                        $tool_calls_buffer[$idx]['function']['arguments'] .= $tc['function']['arguments'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return strlen($chunk);
        });

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || $http_code >= 400 || !empty($error_buffer)) {
            // Check if error_buffer contains actual JSON error content
            $error_json = json_decode($error_buffer, true);
            $parsed_msg = "";
            if ($error_json) {
                if (isset($error_json['error']['message'])) {
                    $parsed_msg = $error_json['error']['message'];
                } else if (isset($error_json['message'])) {
                    $parsed_msg = $error_json['message'];
                }
            }

            $err_str = $err ?: ($parsed_msg ?: trim($error_buffer));
            $err_msg = "Error Oraculi (HTTP $http_code): " . $err_str;
            $eventus = classificare_eventum_llm((int)$http_code, $err_str);

            if ($aequilibrium_activum && $provisor_nomen !== "Ignotus") {
                notare_eventum_clavis_llm($provisor_nomen, $apikey, $model, $eventus["event_type"], (int)$http_code, $eventus["error_kind"], $err_str);
            }

            // Unpin the provider from the load balancer if it fails
            if ($aequilibrium_activum) {
                purgare_sessionem_aequilibrio($cubiculum);
            }

            $potest_fallere = $aequilibrium_activum
                && $loop_count < $max_loops
                && $current_content === ""
                && empty($tool_calls_buffer)
                && empty($final_response_content);

            if ($potest_fallere) {
                echo "data: " . json_encode([
                    "event" => "failover",
                    "message" => "Provider " . $provisor_nomen . " [" . $model . "] failed, trying the next provider/model."
                ]) . "\n\n";
                flush();
                continue;
            }

            if ($loop_count == 1) {
                echo "data: " . json_encode(["choices" => [["delta" => ["content" => $err_msg]]]]) . "\n\n";
                $final_response_content .= $err_msg;
            } else {
                $final_response_content .= "\n" . $err_msg;
            }
            break;
        }

        if ($aequilibrium_activum && $provisor_nomen !== "Ignotus") {
            notare_eventum_clavis_llm($provisor_nomen, $apikey, $model, "SUCCESS", (int)$http_code, "success", "");
        }

        // Add the assistant's message to history
        $assistant_message = ["role" => "assistant", "content" => $current_content ?: ""];

        // Prepare tool calls for history
        if (!empty($tool_calls_buffer)) {
            $assistant_message["tool_calls"] = array_values($tool_calls_buffer);
            $messages[] = $assistant_message;

            // Execute the tools
            foreach ($tool_calls_buffer as $tc) {
                $tool_name = $tc['function']['name'];
                $args = json_decode($tc['function']['arguments'], true) ?: [];
                $query = $args['query'] ?? '';

                $tool_result = "";
                if ($tool_name === 'search_web') {
                    $tool_result = investigare_in_tela($query);
                }
                elseif ($tool_name === 'search_knowledge_base') {
                    $rag_resp = loqui_cum_daemonio("INVESTIGARE|" . $query);
                    $partes_rag = explode("|", $rag_resp);
                    $tool_result = ($partes_rag[0] == "200") ? $partes_rag[2] : "Nihil inventum.";
                }
                else {
                    $tool_result = "Instrumentum ignotum.";
                }

                $messages[] = [
                    "tool_call_id" => $tc['id'],
                    "role" => "tool",
                    "name" => $tool_name,
                    "content" => $tool_result
                ];
            }
        // Loop continues because we appended tool results
        }
        else {
            // No tool calls, we are done
            break;
        }
    }

    // Send final DONE
    echo "data: [DONE]\n\n";
    flush();

    // Save bot response
    $clean_resp = str_replace(["\r", "\n"], " ", trim($final_response_content));
    $clean_reasoning = str_replace(["\r", "\n"], " ", trim($total_reasoning));
    if (!empty($clean_reasoning)) {
        $clean_resp = "<thought>" . $clean_reasoning . "</thought> " . $clean_resp;
    }
    
    if (empty($clean_resp))
        $clean_resp = "Oraculum mutum est.";
    loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|" . $user_fp . "|Oraculum: " . $clean_resp);

    // Unpin session from load balancer after ReAct loop finishes
    if ($aequilibrium_activum) {
        purgare_sessionem_aequilibrio($cubiculum);
    }

    exit();
}
