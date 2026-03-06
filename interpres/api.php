<?php
session_start();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

function loqui_cum_daemonio($mandatum)
{
    $daemonium_host = getenv("DAEMONIUM_HOST") ?: "127.0.0.1";
    $fp = @fsockopen($daemonium_host, 8080, $errno, $errstr, 10);
    if (!$fp)
        return "500|Error|Daemonium non respondet";
    fwrite($fp, $mandatum . "\n");
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

// Public Actions (No Session Required)
if ($action === 'login_anima' || $action === 'register_anima' || $action === 'forgot_anima') {
    $fp_client = $_POST['fp'] ?? '';
    if ($action === 'login_anima') {
        $email = $_POST['email'] ?? '';
        $pass = $_POST['pass'] ?? '';
        $resp = loqui_cum_daemonio("INTRARE_PLENUM|$email|$pass|$fp_client");
        $partes = explode("|", $resp);
        if ($partes[0] == "200") {
            $_SESSION["usor"] = $partes[2];
            $_SESSION["fp"] = $fp_client;
            if (isset($_POST['remember'])) {
                setcookie(session_name(), session_id(), time() + 86400 * 30, "/");
            }
            echo json_encode(["status" => "ok", "usor" => $partes[2]]);
        }
        else {
            echo json_encode(["status" => "error", "message" => $partes[2]]);
        }
    }
    else if ($action === 'register_anima') {
        $nomen = $_POST['nomen'] ?? '';
        $email = $_POST['email'] ?? '';
        $pass = $_POST['pass'] ?? '';
        $resp = loqui_cum_daemonio("CREARE_USOREM_PLENUM|$nomen|$email|$pass|$fp_client");
        $partes = explode("|", $resp);
        if ($partes[0] == "200") {
            $_SESSION["usor"] = $nomen;
            $_SESSION["fp"] = $fp_client;
            echo json_encode(["status" => "ok"]);
        }
        else {
            echo json_encode(["status" => "error", "message" => $partes[2]]);
        }
    }
    else if ($action === 'forgot_anima') {
        $email = $_POST['email'] ?? '';
        $resp = loqui_cum_daemonio("PETERE_RECUPERATIONEM|$email");
        $partes = explode("|", $resp);
        echo json_encode(["status" => ($partes[0] == "200" ? "ok" : "error"), "message" => $partes[2]]);
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
        $cookie_file = '/tmp/ddg_cookies.txt';
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

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$cubiculum = $_GET['room'] ?? $_POST['room'] ?? 'default';

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
    $vetus_pass = $_POST['vetus_pass'] ?? '';
    $nova_pass = $_POST['nova_pass'] ?? '';

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
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $new_room);
        if ($safeName) {
            loqui_cum_daemonio("RENOMINARE_FABULATIONEM|" . $usor . "|" . $cubiculum . "|" . $safeName . "|" . $user_fp);
        }
    }
    header('Content-Type: application/json');
    echo json_encode(["status" => "ok"]);
    exit();
}

if ($action === 'send') {
    $nuntius = trim($_POST['nuntius'] ?? '');
    if (empty($nuntius))
        exit();

    // 0. Check if it's the first message in this room
    $is_first = false;
    $resp_hist = loqui_cum_daemonio("LEGENDE_NUNTIOS|" . $usor . "|" . $cubiculum . "|" . $user_fp);
    $partes_hist = explode("|", $resp_hist, 3);
    if ($partes_hist[0] !== "200" || trim($partes_hist[2]) === "") {
        $is_first = true;
    }

    // Aequilibrium: Petere Clavem et Provisorem
    $aequilibrium_resp = loqui_cum_aequilibrio($cubiculum);
    $apikey = getenv("OPENAI_API_KEY"); // Fallback
    $api_url = getenv("OPENAI_API_URL") ?: "https://api.openai.com/v1/chat/completions";
    $model = getenv("OPENAI_API_MODEL") ?: "gpt-4o-mini";
    $provisor_nomen = "Ignotus";

    if ($aequilibrium_resp) {
        $partes_aeq = explode("|", $aequilibrium_resp);
        if ($partes_aeq[0] === "200") {
            $provisor_nomen = $partes_aeq[2];
            $apikey = $partes_aeq[3];
            $api_url = $partes_aeq[4];
            $model = $partes_aeq[5];
        }
    }

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
        curl_setopt($ch_t, CURLOPT_POSTFIELDS, json_encode($data_title));
        curl_setopt($ch_t, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer " . $apikey]);
        $res_t = curl_exec($ch_t);
        curl_close($ch_t);

        $json_t = json_decode($res_t, true);
        if (isset($json_t['choices'][0]['message']['content'])) {
            $new_title = trim($json_t['choices'][0]['message']['content']);
            $new_title = preg_replace('/[^a-zA-Z0-9]/', '', ucwords($new_title));
            if (!empty($new_title)) {
                $renamed_to = substr($new_title, 0, 30);
                loqui_cum_daemonio("RENOMINARE_FABULATIONEM|" . $usor . "|" . $cubiculum . "|" . $renamed_to . "|" . $user_fp);
                $cubiculum = $renamed_to;
            }
        }
    }

    // 1. Save user message
    loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|" . $user_fp . "|Tute: " . $nuntius);

    // 2. Prepare LLM Stream Context
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    while (ob_get_level() > 0)
        ob_end_flush();

    $lingua_mode = $_POST['lingua'] ?? 'latin';
    $search_mode = $_POST['search'] ?? 'off';

    // If we renamed the room, tell the client before streaming the message
    if ($renamed_to) {
        echo "data: " . json_encode(["event" => "renamed", "new_room" => $renamed_to]) . "\n\n";
        flush();
    }

    if (!$apikey) {
        $msg = "Clavis API deest. Aequilibrium non respondet, et nulla clavis in .env exstat.";
        echo "data: " . json_encode(["choices" => [["delta" => ["content" => $msg]]]]) . "\n\n";
        loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|" . $user_fp . "|Oraculum: " . $msg);
        exit();
    }

    // Nuntiare provisorem ad clientem (Optional info)
    if ($provisor_nomen !== "Ignotus") {
        echo "data: " . json_encode(["event" => "provisor", "nomen" => $provisor_nomen, "model" => $model]) . "\n\n";
        flush();
    }

    $system_role = "Tu es philosophus Romanus. Responde semper Latine.";
    if ($lingua_mode === 'auto') {
        $system_role = "You are an ancient Roman philosopher. 
        CRITICAL INSTRUCTION: You MUST speak in the EXACT SAME LANGUAGE that the user is speaking!
        - If the user writes in Russian, you MUST reply entirely in Russian (for example: 'Приветствую, путник...').
        - If the user writes in English, you MUST reply entirely in English.
        - NEVER reply in Latin unless the user explicitly speaks Latin to you.
        - Maintain your persona as a wise Roman philosopher, but express your thoughts natively in the user's language.
        - ALWAYS start your response with a greeting or acknowledgment in the user's language.
        - Use the provided tools (search_web, search_knowledge_base) to find facts if needed.";
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
        ]
    ];

    $max_loops = 5;
    $loop_count = 0;
    $final_response_content = "";

    while ($loop_count < $max_loops) {
        $loop_count++;

        $data = [
            "model" => $model,
            "messages" => $messages,
            "max_tokens" => 800,
            "stream" => true
        ];

        // Only include tools if search mode is ON
        if ($search_mode === 'on') {
            $data["tools"] = $tools;
        }

        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apikey
        ]);

        $tool_calls_buffer = [];
        $current_content = "";

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$tool_calls_buffer, &$current_content, &$final_response_content) {
            $lines = explode("\n", $chunk);
            foreach ($lines as $line) {
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
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $err_msg = "Error Oraculi: " . $err;
            echo "data: " . json_encode(["choices" => [["delta" => ["content" => $err_msg]]]]) . "\n\n";
            $final_response_content .= $err_msg;
            break;
        }

        // Add the assistant's message to history
        $assistant_message = ["role" => "assistant"];
        if (!empty($current_content)) {
            $assistant_message["content"] = $current_content;
        }

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
    if (empty($clean_resp))
        $clean_resp = "Oraculum mutum est.";
    loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|" . $user_fp . "|Oraculum: " . $clean_resp);
    exit();
}
