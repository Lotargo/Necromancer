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

function evocatio_tempestatis($location)
{
    $location = trim($location);
    if (empty($location)) {
        return "Location name cannot be empty.";
    }

    // 1. Geocoding search via Open-Meteo
    $geo_url = "https://geocoding-api.open-meteo.com/v1/search?name=" . urlencode($location) . "&count=1&language=en&format=json";
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';
    
    $ch = curl_init($geo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $geo_res = curl_exec($ch);
    $geo_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($geo_code !== 200 || empty($geo_res)) {
        return "Failed to resolve coordinates for location: " . $location;
    }

    $geo_data = json_decode($geo_res, true);
    if (empty($geo_data['results'][0])) {
        return "Location not found: " . $location;
    }

    $res = $geo_data['results'][0];
    $lat = $res['latitude'];
    $lon = $res['longitude'];
    $name = $res['name'] ?? $location;
    $country = $res['country'] ?? '';
    $tz = $res['timezone'] ?? 'auto';

    // 2. Weather forecast & timezone/local time via Open-Meteo
    $forecast_url = "https://api.open-meteo.com/v1/forecast?latitude=" . $lat . "&longitude=" . $lon . "&current=temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,rain,showers,snowfall,weather_code,cloud_cover,pressure_msl,wind_speed_10m&timezone=" . urlencode($tz);
    
    $ch = curl_init($forecast_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $forecast_res = curl_exec($ch);
    $forecast_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($forecast_code !== 200 || empty($forecast_res)) {
        return "Failed to retrieve weather forecast for location: " . $name;
    }

    $w_data = json_decode($forecast_res, true);
    if (empty($w_data['current'])) {
        return "Weather forecast data is unavailable for location: " . $name;
    }

    $current = $w_data['current'];
    $tz_name = $w_data['timezone'] ?? $tz;
    $tz_abbr = $w_data['timezone_abbreviation'] ?? '';
    $utc_offset = $w_data['utc_offset_seconds'] ?? 0;
    
    // WMO Weather Codes Translation
    $wmo_codes = [
        0 => "Clear sky",
        1 => "Mainly clear",
        2 => "Partly cloudy",
        3 => "Overcast",
        45 => "Fog",
        48 => "Depositing rime fog",
        51 => "Drizzle: Light intensity",
        53 => "Drizzle: Moderate intensity",
        55 => "Drizzle: Dense intensity",
        56 => "Freezing Drizzle: Light intensity",
        57 => "Freezing Drizzle: Dense intensity",
        61 => "Rain: Slight intensity",
        63 => "Rain: Moderate intensity",
        65 => "Rain: Heavy intensity",
        66 => "Freezing Rain: Light intensity",
        67 => "Freezing Rain: Heavy intensity",
        71 => "Snow fall: Slight intensity",
        73 => "Snow fall: Moderate intensity",
        75 => "Snow fall: Heavy intensity",
        77 => "Snow grains",
        80 => "Rain showers: Slight",
        81 => "Rain showers: Moderate",
        82 => "Rain showers: Violent",
        85 => "Snow showers: Slight",
        86 => "Snow showers: Heavy",
        95 => "Thunderstorm: Slight or moderate",
        96 => "Thunderstorm with slight hail",
        99 => "Thunderstorm with heavy hail"
    ];

    $code = $current['weather_code'] ?? 0;
    $description = $wmo_codes[$code] ?? "Unknown conditions";

    $output = [
        "location" => $name,
        "country" => $country,
        "latitude" => $lat,
        "longitude" => $lon,
        "timezone" => $tz_name,
        "timezone_abbreviation" => $tz_abbr,
        "utc_offset_hours" => $utc_offset / 3600,
        "current_local_time" => $current['time'] ?? 'unknown',
        "temperature" => ($current['temperature_2m'] ?? 'unknown') . " °C",
        "feels_like" => ($current['apparent_temperature'] ?? 'unknown') . " °C",
        "relative_humidity" => ($current['relative_humidity_2m'] ?? 'unknown') . " %",
        "weather_condition" => $description,
        "cloud_cover" => ($current['cloud_cover'] ?? 'unknown') . " %",
        "wind_speed" => ($current['wind_speed_10m'] ?? 'unknown') . " km/h",
        "pressure" => ($current['pressure_msl'] ?? 'unknown') . " hPa",
        "precipitation" => ($current['precipitation'] ?? 0) . " mm",
        "is_day" => ($current['is_day'] ?? 1) == 1 ? "yes" : "no"
    ];

    return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function investigare_in_tela($query)
{
    $snippets = [];

    // 1. Primary search: Yahoo Search
    $yahoo_url = "https://search.yahoo.com/search?p=" . urlencode($query);
    $ch = curl_init($yahoo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $yahoo_html = curl_exec($ch);
    $yahoo_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $yahoo_err = curl_error($ch);
    curl_close($ch);

    if ($yahoo_code === 200 && !empty($yahoo_html)) {
        $matches = [];
        // Extract Yahoo snippets
        preg_match_all('/<div class="compText[^"]*"[^>]*>(.*?)<\/div>/s', $yahoo_html, $matches);
        if (empty($matches[1])) {
            preg_match_all('/<span class="fc-falcon"[^>]*>(.*?)<\/span>/s', $yahoo_html, $matches);
        }
        $snippets = array_slice($matches[1] ?? [], 0, 5);
    }

    // 2. Fallback search: DuckDuckGo HTML (if Yahoo failed or returned nothing)
    if (empty($snippets)) {
        $url = "https://html.duckduckgo.com/html/?q=" . urlencode($query);
        $attempts = 0;
        $max_attempts = 2;
        $html = "";
        $http_code = 0;

        // Generate a unique cookie file to avoid session linking
        $cookie_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ddg_cookies_' . uniqid() . '.txt';

        while ($attempts < $max_attempts) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $ua);
            curl_setopt($ch, CURLOPT_REFERER, 'https://duckduckgo.com/');
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);

            $html = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200 && !empty($html) && strlen($html) > 1000) {
                break;
            }

            $attempts++;
            if ($attempts < $max_attempts) {
                usleep(500000);
            }
        }

        if (file_exists($cookie_file)) {
            @unlink($cookie_file);
        }

        if ($http_code === 200 && !empty($html)) {
            $matches = [];
            preg_match_all('/<a[^>]*class="result__snippet"[^>]*>(.*?)<\/a>/s', $html, $matches);
            if (empty($matches[1])) {
                preg_match_all('/<div[^>]*class="result__snippet"[^>]*>(.*?)<\/div>/s', $html, $matches);
            }
            $snippets = array_slice($matches[1] ?? [], 0, 5);

            // Ultimate fallback to paragraphs if regex missed snippets
            if (empty($snippets)) {
                preg_match_all('/<p[^>]*>(.*?)<\/p>/s', $html, $matches);
                $snippets = array_slice($matches[1] ?? [], 0, 3);
            }
        }
    }

    $clean_snippets = array_map(function ($s) {
        return trim(strip_tags(html_entity_decode($s)));
    }, $snippets);

    if (empty($clean_snippets)) {
        return "Nihil inventum (no snippets found). Query: " . $query;
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

    $user_timezone = sani($_POST['timezone'] ?? 'UTC');
    $user_local_time = sani($_POST['local_time'] ?? '');

    $time_context = "";
    if (!empty($user_local_time)) {
        $time_context = "\n  <current_time_context>\n" .
            "    <user_local_time>" . htmlspecialchars($user_local_time, ENT_QUOTES, 'UTF-8') . "</user_local_time>\n" .
            "    <user_timezone>" . htmlspecialchars($user_timezone, ENT_QUOTES, 'UTF-8') . "</user_timezone>\n" .
            "  </current_time_context>";
    }

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

    $system_role = "<system_instruction>{{TIME_CONTEXT}}
  <persona>
    Your name is Oraculum. Tu es philosophus Romanus. Responde semper Latine.
  </persona>
  <factual_and_temporal_guidelines>
    1. CURRENT TIME AND DATE: You have direct access to the user's current local time and timezone in the <current_time_context> block. When asked about the current time, date, year, or day WITHOUT specifying a particular city/location (e.g., 'quod tempus est?', 'quid est dies hodiernus?'), you MUST read this data and state the exact time/date directly to the user WITHOUT calling any tools.
    2. WEATHER AND TIME IN OTHER LOCATIONS: When asked about the weather, temperature, or the current time in a specific city or location (e.g., 'tempus in Tokio', 'caelum in Moscua', 'quod tempus est in Londinio?'), you MUST call the check_weather tool with the location name. This tool returns the precise current local time, timezone, temperature, weather conditions, humidity, wind, and more for that location. Use these data directly in your response. Do NOT use search_web for weather or time queries — always prefer check_weather.
    3. TIMEZONE RESTRICTION & MYSTICAL SOURCE: Never mention the user's explicit timezone name, region, or offset (e.g., 'Europe/Moscow', 'Asia/Tokyo', 'UTC') unless explicitly asked for the timezone name. Never mention technical words like 'system context', 'browser', or 'current_time_context'. Instead, attribute your precise chronological knowledge entirely to the whispering shadows, whispers of the night, or mystical flows of time (e.g., 'umbrae mihi susurrarunt...', 'tenebrae dicunt...', 'umbrae susurrant'). Let the shadows whisper the exact hours and minutes to you, keeping the gothic atmosphere intact.
    4. REAL-WORLD FACTS: When you call tools (such as search_web) to find news or any real-world facts (other than weather), you MUST provide the actual retrieved facts clearly and accurately. Do not hide them behind abstract philosophical allegories or refuse to state them.
    5. PERSONA INTEGRATION: You must blend these modern facts and precise time details seamlessly into your wise Roman philosopher persona. Be both a wise philosopher and a highly accurate oracle.
    6. DIALOGUE CONTINUATION: Analyze the chat history carefully. You must continue the conversation seamlessly from the last exchange. Do NOT output repeated greetings, welcome phrases, or re-introduce yourself. If the user asks a follow-up question or continues a topic, answer it directly without any introductory fluff, preserving the flow of the ongoing dialogue as Oraculum.
    7. CAVETE DUPLICATIONEM: Si iam scripsisti prooemium, salutationem vel verba comia in nuntio tuo priore (antequam instrumentum vocares), NUNQUAM ea in responso finali repetere debes. Perge statim ad res inventas exponendas sine ulla salutatione nova, ut sermo tuus sit continuatio naturalis nuntii prioris.
  </factual_and_temporal_guidelines>
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
      3. Si nuntius quaestionem de facto vel petitionем informationis continet, etiamsi salutationibus comitatur (ex. 'привет, какая погода в вашингтоне?', 'salve, quis est praeses Galliae?'), instrumentum (ex. search_web) vocare DEBES ut veritatem invenias.
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
            <reason>Factualis quaestio de tempestate continetur. Usa check_weather pro tempestate.</reason>
            <action>Voca check_weather cum location='Washington'</action>
          </example>
          <example>
            <input>quod tempus est in Tokio?</input>
            <reason>Ask for time in a specific different location. Use check_weather which provides accurate local time.</reason>
            <action>Voca check_weather cum location='Tokyo'</action>
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
          <example>
            <input>quod tempus est?</input>
            <reason>Ask for the current time/date without specifying a particular location. The exact time is already provided in current_time_context.</reason>
            <action>Do NOT call any tools. Respond directly using the provided <current_time_context>.</action>
          </example>
        </examples>
      </rule>
    </rules>
  </tool_usage>
</system_instruction>";
    if ($lingua_mode === 'auto') {
        $system_role = "<system_instruction>{{TIME_CONTEXT}}
  <persona>
    Your name is Sage (also known as \"Мудрец\" in Russian, and \"Oraculum\" in the Latin interface). You are an ancient Roman philosopher. You must express wise, philosophical thoughts but stay accessible, friendly, and speak in a natural manner.
  </persona>
  <factual_and_temporal_guidelines>
    1. CURRENT TIME AND DATE: You have direct access to the user's current local time and timezone in the <current_time_context> block. When asked about the current time, date, year, or day WITHOUT specifying a particular city/location (e.g., 'сколько времени?', 'какое сегодня число?', 'what time is it?'), you MUST read this data and state the exact time/date directly to the user WITHOUT calling any tools. Never pretend to be unable to see the current time, and never give excuses.
    2. WEATHER AND TIME IN OTHER LOCATIONS: When asked about the weather, temperature, or the current time in a specific city or location (e.g., 'погода в Токио', 'время в Москве', 'weather in London', 'what time is it in Tokyo?'), you MUST call the check_weather tool with the location name. This tool returns the precise current local time, timezone, temperature, weather conditions, humidity, wind speed, and more for that specific location. Use these structured data directly in your response — they are 100% accurate and real-time. Do NOT use search_web for weather or time queries — always prefer check_weather for maximum accuracy. If the user asks ONLY about time in another city (not weather), still call check_weather because it provides the authoritative current local time for any location.
    3. TIMEZONE RESTRICTION & MYSTICAL SOURCE: Never mention the user's explicit timezone name, region, or offset (e.g., 'Europe/Moscow', 'Asia/Tokyo', 'UTC') unless the user explicitly asks for their timezone name or region. Never refer to technical sources like 'system', 'browser time', 'context', or 'transmitted data'. Instead, attribute this precise knowledge of hours, minutes, and dates to the whispering shadows, the spirits of the night, or the resonance of the void (for example, in Russian: 'тени нашептали мне...', 'мне нашептали тени...', 'шепот бездны донес...'; in English: 'the shadows whispered to me...'). Integrate this mystical insight seamlessly into your responses.
    4. REAL-WORLD FACTS: When you call tools (such as search_web) to find news or any real-world facts (other than weather), you MUST provide the actual retrieved facts clearly and accurately. Do not hide them behind abstract philosophical allegories or refuse to state them.
    5. PERSONA INTEGRATION: You must blend these modern facts and precise time details seamlessly into your wise Roman philosopher persona. For example, you can comment on the relentless flow of time while stating the exact hour, or reflect on the nature of seasons while describing the current temperature. Be both a wise philosopher and a highly accurate oracle.
    6. DIALOGUE CONTINUATION: Carefully analyze the chat history. You must continue the conversation seamlessly from the last exchange. It is STRICTLY FORBIDDEN to repeat greetings, welcome the user again, or duplicate introductory thoughts if the dialogue is already in progress. If the user asks a follow-up question or continues a topic, answer it directly and philosophically as Sage/Мудрец, maintaining the continuous flow of the dialogue.
    7. NO DUPLICATION AND COMPLETE SENTENCES: If you decide to call a tool, you MUST generate a complete, finished introductory sentence in the user's language ending with a punctuation mark (like a period or ellipsis) before the tool call. NEVER stop in the middle of a word or sentence! Example: 'Позволь мне заглянуть в свитки...' or 'Я обращусь к архивам Сети.'. Then, when you receive tool results on the subsequent turn, you MUST NOT repeat or duplicate your previous greeting or intro in the final response. Continue your response seamlessly, stating the retrieved facts or weather directly as a natural continuation of your previous thought. Do NOT hardcode the phrase 'Позволь мне заглянуть в свитки' or any specific greeting — dynamically introduce your intent as a wise philosopher on your first step.
  </factual_and_temporal_guidelines>
  <languages>
    <language_mode>auto</language_mode>
    <instruction>
      You MUST speak in the EXACT SAME LANGUAGE that the user is speaking!
      - If the user writes in Russian, you MUST reply entirely in Russian (for example: 'Приветствую, путник...').
      - If the user writes in English, you MUST reply entirely in English.
      - NEVER reply in Latin unless the user explicitly speaks Latin to you.
      - Maintain your persona as a wise Roman philosopher, but express your thoughts natively in the user's language.
      - ALWAYS start your response with a greeting or acknowledgment in the user's language on your first turn (before calling tools). If you are continuing your response after a tool execution, start directly with the findings without repeating any greetings.
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
            <reason>Contains a specific factual question about weather in a location. Use check_weather.</reason>
            <action>Call check_weather with location='Washington'</action>
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
          <example>
            <input>сколько времени в Токио?</input>
            <reason>Ask for time in a specific different location. check_weather provides accurate local time for any city.</reason>
            <action>Call check_weather with location='Tokyo'</action>
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
          <example>
            <input>сколько сейчас времени ?</input>
            <reason>Ask for the current time/date without specifying a particular city/location. The exact time is already provided in current_time_context.</reason>
            <action>Do NOT call any tools. Respond directly using the provided <current_time_context>.</action>
          </example>
        </examples>
      </rule>
    </rules>
  </tool_usage>
</system_instruction>";
    }

    $system_role = str_replace("{{MAX_TOKENS}}", $llm_config['max_tokens'], $system_role);
    $system_role = str_replace("{{TIME_CONTEXT}}", $time_context, $system_role);

    // Apply final strict language enforcement to override chat history context bias
    if ($lingua_mode === 'latin') {
        $system_role .= "\n\n[CRITICAL LANGUAGE ENFORCEMENT: Regardless of the language of any previous assistant (Oraculum) or user (Tute) messages in the chat history, you MUST respond exclusively in Latin. Do NOT use Russian, English, or any other language. Responde semper et unice Latine!]";
    } else {
        $system_role .= "\n\n[CRITICAL LANGUAGE ENFORCEMENT: Regardless of the language of any previous assistant (Sage/Oraculum) or user (Tute) messages in the chat history, you MUST respond exclusively in the language of the user's latest message (which is Russian if they wrote in Russian, and English if they wrote in English). Do NOT write in Latin unless the latest user message is in Latin!]";
    }

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
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "check_weather",
                "description" => "Returns current weather conditions, temperature, humidity, wind speed, pressure, and the exact current local time for a specific city or location. Use this tool whenever the user asks about weather OR the current time in another city.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "location" => ["type" => "string", "description" => "The city or location name (e.g., 'Tokyo', 'Moscow', 'London', 'New York')"]
                    ],
                    "required" => ["location"]
                ]
            ]
        ]
    ];

    $destinatio_llm = eligere_destinationem_llm($cubiculum, $aequilibrium_activum);
    $apikey = $destinatio_llm["apikey"];
    $api_url = $destinatio_llm["api_url"];
    $model = $destinatio_llm["model"];
    $provisor_nomen = $destinatio_llm["provisor_nomen"];

    $pinned_provider = $provisor_nomen;
    $pinned_model = $model;

    $max_loops = 8;
    $loop_count = 0;
    $final_response_content = "";
    $provisor_nuntiatus = "";

    while ($loop_count < $max_loops) {
        $loop_count++;

        if (!$apikey) {
            $err_msg = "Clavis API deest. " . ($destinatio_llm["error"] ?? "Nulla clavis provisa.");
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

        $messages_to_send = $messages;
        if ($loop_count > 1) {
            if (isset($messages_to_send[0]) && $messages_to_send[0]['role'] === 'system') {
                if ($lingua_mode === 'auto') {
                    $messages_to_send[0]['content'] .= "\n\n[SYSTEM REMINDER: This is ReAct step $loop_count. You have already generated the initial introduction in the previous assistant message. Do NOT repeat or duplicate your previous greeting, intro, or introductory thoughts! Begin your response directly with the retrieved findings/facts, weaving them into your Roman philosopher persona smoothly. Example of transition: 'Согласно сведениям...' or 'Изучив свитки, я обнаружил...']";
                } else {
                    $messages_to_send[0]['content'] .= "\n\n[SYSTEM REMINDER: Hic est gradus $loop_count ReAct. Iam introductionem scripsisti in nuntio assistant superius. NOLI salutationem vel introductionem repetere! Incipe statim ab investigationis eventu et cogitationem tuam sine ulla duplicatione perge.]";
                }
            }
        }

        $data = [
            "model" => $model,
            "messages" => $messages_to_send,
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

            if ($aequilibrium_activum && $loop_count < $max_loops) {
                if ($loop_count === 1 && empty($final_response_content) && empty($tool_calls_buffer) && $current_content === "") {
                    // Failover before any ReAct steps or content: switch to a new provider/model completely
                    $destinatio_llm = eligere_destinationem_llm($cubiculum, $aequilibrium_activum);
                    $apikey = $destinatio_llm["apikey"];
                    $api_url = $destinatio_llm["api_url"];
                    $model = $destinatio_llm["model"];
                    $provisor_nomen = $destinatio_llm["provisor_nomen"];

                    $pinned_provider = $provisor_nomen;
                    $pinned_model = $model;

                    echo "data: " . json_encode([
                        "event" => "failover",
                        "message" => "Provider " . $provisor_nomen . " failed. Switching to a new provider/model."
                    ]) . "\n\n";
                    flush();
                    continue;
                } else {
                    // ReAct has already started, or we have already sent some content.
                    // We MUST keep the same provider and model. Rotate keys inside the same configuration.
                    $key_rotated = false;
                    for ($attempt = 0; $attempt < 20; $attempt++) {
                        purgare_sessionem_aequilibrio($cubiculum);
                        $dest = eligere_destinationem_llm($cubiculum, $aequilibrium_activum);
                        if ($dest["provisor_nomen"] === $pinned_provider && $dest["model"] === $pinned_model && $dest["apikey"]) {
                            $apikey = $dest["apikey"];
                            $api_url = $dest["api_url"];
                            $key_rotated = true;
                            break;
                        }
                    }

                    if ($key_rotated) {
                        echo "data: " . json_encode([
                            "event" => "failover",
                            "message" => "Key for " . $pinned_provider . " [" . $pinned_model . "] rotated successfully due to API error."
                        ]) . "\n\n";
                        flush();
                        $loop_count--;
                        continue;
                    }
                }
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
                elseif ($tool_name === 'check_weather') {
                    $location = $args['location'] ?? '';
                    $tool_result = evocatio_tempestatis($location);
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
            // FALLBACK: Some weak models (e.g. llama-3.1-8b) output tool calls as plain text
            // instead of using the proper tool_calls mechanism. Detect and execute them.
            $fallback_executed = false;
            if (!empty($current_content)) {
                // Try to detect JSON tool call pattern in the text output
                // Pattern: {"name": "tool_name", "arguments": {...}} or similar
                $trimmed = trim($current_content);
                $parsed_tc = null;
                
                // Try direct JSON parse first
                $maybe_json = json_decode($trimmed, true);
                if ($maybe_json && isset($maybe_json['name']) && isset($maybe_json['arguments'])) {
                    $parsed_tc = $maybe_json;
                }
                
                // Try to extract JSON from within text (model might add text before/after)
                if (!$parsed_tc && preg_match('/\{[^{}]*"name"\s*:\s*"(search_web|search_knowledge_base|check_weather)"[^{}]*"arguments"\s*:\s*\{[^{}]*\}[^{}]*\}/s', $trimmed, $json_match)) {
                    $maybe_json = json_decode($json_match[0], true);
                    if ($maybe_json && isset($maybe_json['name'])) {
                        $parsed_tc = $maybe_json;
                    }
                }
                
                if ($parsed_tc) {
                    $fb_tool_name = $parsed_tc['name'];
                    $fb_args = $parsed_tc['arguments'] ?? [];
                    $fb_tool_id = 'fallback_' . uniqid();
                    
                    // Notify the frontend to clear the wrongly streamed JSON text
                    echo "data: " . json_encode(["event" => "clear_fallback"]) . "\n\n";
                    flush();
                    
                    // Notify the frontend about tool call
                    echo "data: " . json_encode(["event" => "tool_call", "name" => $fb_tool_name]) . "\n\n";
                    flush();
                    
                    // Clear the wrongly streamed text from final response
                    // (The frontend already rendered it, but we'll stream the real answer next)
                    $final_response_content = str_replace($trimmed, '', $final_response_content);
                    
                    // Build the assistant message for history (with empty content, tool call was in text)
                    $assistant_message["content"] = "";
                    $assistant_message["tool_calls"] = [[
                        "id" => $fb_tool_id,
                        "type" => "function",
                        "function" => [
                            "name" => $fb_tool_name,
                            "arguments" => json_encode($fb_args, JSON_UNESCAPED_UNICODE)
                        ]
                    ]];
                    $messages[] = $assistant_message;
                    
                    // Execute the tool
                    $fb_query = $fb_args['query'] ?? '';
                    $fb_result = "";
                    if ($fb_tool_name === 'search_web') {
                        $fb_result = investigare_in_tela($fb_query);
                    } elseif ($fb_tool_name === 'search_knowledge_base') {
                        $rag_resp = loqui_cum_daemonio("INVESTIGARE|" . $fb_query);
                        $partes_rag = explode("|", $rag_resp);
                        $fb_result = ($partes_rag[0] == "200") ? $partes_rag[2] : "Nihil inventum.";
                    } elseif ($fb_tool_name === 'check_weather') {
                        $fb_location = $fb_args['location'] ?? '';
                        $fb_result = evocatio_tempestatis($fb_location);
                    } else {
                        $fb_result = "Instrumentum ignotum.";
                    }
                    
                    $messages[] = [
                        "tool_call_id" => $fb_tool_id,
                        "role" => "tool",
                        "name" => $fb_tool_name,
                        "content" => $fb_result
                    ];
                    
                    $fallback_executed = true;
                    // Loop continues — will call LLM again with tool result
                }
            }
            
            if (!$fallback_executed) {
                // No tool calls, we are done
                break;
            }
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
