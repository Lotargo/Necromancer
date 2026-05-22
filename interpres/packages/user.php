<?php
// Require the router utility context before executing package code.
if (!function_exists('sani')) {
    exit("Direct access not allowed");
}

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
