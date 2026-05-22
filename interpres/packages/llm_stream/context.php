<?php

function llm_stream_build_time_context($user_local_time, $user_timezone)
{
    if ($user_local_time === '') {
        return "";
    }

    return "\n  <current_time_context>\n" .
        "    <user_local_time>" . htmlspecialchars($user_local_time, ENT_QUOTES, 'UTF-8') . "</user_local_time>\n" .
        "    <user_timezone>" . htmlspecialchars($user_timezone, ENT_QUOTES, 'UTF-8') . "</user_timezone>\n" .
        "  </current_time_context>";
}

function llm_stream_is_first_message($usor, $cubiculum, $user_fp)
{
    $resp_hist = loqui_cum_daemonio("LEGENDE_NUNTIOS|" . $usor . "|" . $cubiculum . "|" . $user_fp);
    $partes_hist = explode("|", $resp_hist, 3);
    return $partes_hist[0] !== "200" || trim($partes_hist[2] ?? "") === "";
}

function llm_stream_try_auto_name_room($is_first, $apikey, $api_url, $model, $nuntius, $usor, $cubiculum, $user_fp)
{
    if (!$is_first || !$apikey) {
        return "";
    }

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
    if (!isset($json_t['choices'][0]['message']['content'])) {
        return "";
    }

    $new_title = trim($json_t['choices'][0]['message']['content']);
    $new_title = trim($new_title, " \t\n\r\0\x0B.\"'`“”");
    if ($new_title === '') {
        return "";
    }

    $renamed_to = substr($new_title, 0, 50);
    $resp_opt = loqui_cum_daemonio("LEGERE_OPTIONES|" . $usor . "|" . $user_fp);
    $partes_opt = explode("|", $resp_opt);
    $options = [];
    if (($partes_opt[0] ?? '') == "200") {
        $options = json_decode($partes_opt[2], true) ?: [];
    }
    if (!isset($options['chat_names'])) {
        $options['chat_names'] = [];
    }
    $options['chat_names'][$cubiculum] = $renamed_to;

    $safe_options = json_encode($options, JSON_UNESCAPED_UNICODE);
    $safe_options = str_replace(['|', "\r", "\n"], ['\\u007c', '', ''], $safe_options);
    loqui_cum_daemonio("SERVARE_OPTIONES|" . $usor . "|" . $safe_options . "|" . $user_fp);

    return $renamed_to;
}

function llm_stream_init_sse_headers()
{
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
}

function llm_stream_emit_room_rename($renamed_to)
{
    if ($renamed_to === '') {
        return;
    }

    echo "data: " . json_encode(["event" => "renamed", "new_name" => $renamed_to]) . "\n\n";
    flush();
}

function llm_stream_load_config()
{
    $llm_config_path = __DIR__ . '/../../../tabularium/llm_config.json';
    $llm_config = ["max_tokens" => 4096, "temperature" => 1.0, "top_p" => 0.95];
    if (file_exists($llm_config_path)) {
        $json_config = json_decode(file_get_contents($llm_config_path), true);
        if (is_array($json_config)) {
            $llm_config = array_merge($llm_config, $json_config);
        }
    }
    return $llm_config;
}

function llm_stream_build_history_messages($system_role, $usor, $cubiculum, $user_fp, $nuntius)
{
    $chat_history = [];
    $resp_hist2 = loqui_cum_daemonio("LEGENDE_NUNTIOS|" . $usor . "|" . $cubiculum . "|" . $user_fp);
    $partes_hist2 = explode("|", $resp_hist2, 3);
    if (($partes_hist2[0] ?? '') === "200" && trim($partes_hist2[2] ?? "") !== "") {
        $lines = explode('\n', $partes_hist2[2]);
        $lines = array_slice($lines, -20);
        foreach ($lines as $line) {
            if (strpos($line, 'Tute: ') === 0) {
                $chat_history[] = ["role" => "user", "content" => substr($line, 6)];
            } elseif (strpos($line, 'Oraculum: ') === 0) {
                $chat_history[] = ["role" => "assistant", "content" => substr($line, 10)];
            }
        }
    }

    $messages = [["role" => "system", "content" => $system_role]];
    $messages = array_merge($messages, $chat_history);

    if (empty($chat_history) || end($chat_history)['content'] !== $nuntius) {
        $messages[] = ["role" => "user", "content" => $nuntius];
    }

    return $messages;
}

function llm_stream_save_final_response($usor, $cubiculum, $user_fp, $final_response_content, $total_reasoning)
{
    $clean_resp = str_replace(["\r", "\n"], " ", trim($final_response_content));
    $clean_reasoning = str_replace(["\r", "\n"], " ", trim($total_reasoning));
    if ($clean_reasoning !== '') {
        $clean_resp = "<thought>" . $clean_reasoning . "</thought> " . $clean_resp;
    }

    if ($clean_resp === '') {
        $clean_resp = "Oraculum mutum est.";
    }

    loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|" . $user_fp . "|Oraculum: " . $clean_resp);
}
