<?php
// Require the router utility context before executing package code.
if (!function_exists('sani')) {
    exit("Direct access not allowed");
}

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
    if ($historia !== "") {
        if (!function_exists('llm_stream_decode_db_message')) {
            function llm_stream_decode_db_message($text)
            {
                $text = str_replace('\\\\', '\\', $text);
                $text = str_replace('\n', "\n", $text);
                return $text;
            }
        }
        $messages = explode('[NUNTIUS_SEP]', $historia);
        $decoded_messages = [];
        foreach ($messages as $msg) {
            $decoded_messages[] = llm_stream_decode_db_message($msg);
        }
        echo implode("\n", $decoded_messages);
    }
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
