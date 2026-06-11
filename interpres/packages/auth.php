<?php
// Require the router utility context before executing package code.
if (!function_exists('sani')) {
    exit("Direct access not allowed");
}

$fp_client = sani($_POST['fp'] ?? '');
if (strlen($fp_client) > 256) {
    echo json_encode(["status" => "error", "message" => "Fingerprint is too long (max 256 chars)"]);
    exit();
}

if ($action === 'login_anima') {
    $email = sani($_POST['email'] ?? '');
    $pass = sani($_POST['pass'] ?? '');
    if (strlen($email) > 100 || strlen($pass) > 256) {
        echo json_encode(["status" => "error", "message" => "Input limits exceeded (email max 100, password max 256 chars)"]);
        exit();
    }
    $resp = loqui_cum_daemonio("INTRARE_PLENUM|$email|$pass|$fp_client");
    $partes = explode("|", $resp);
    if ($partes[0] == "200") {
        $_SESSION["usor"] = sani($partes[2]);
        $_SESSION["fp"] = $fp_client;
        if (isset($_POST['remember'])) {
            setcookie(session_name(), session_id(), time() + 86400 * 30, "/", "", false, true);
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
    if (strlen($nomen) > 100 || strlen($email) > 100 || strlen($pass) > 256) {
        echo json_encode(["status" => "error", "message" => "Input limits exceeded (nomen/email max 100, password max 256 chars)"]);
        exit();
    }
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
    if (strlen($email) > 100) {
        echo json_encode(["status" => "error", "message" => "Email is too long (max 100 chars)"]);
        exit();
    }
    $resp = loqui_cum_daemonio("PETERE_RECUPERATIONEM|$email");
    $partes = explode("|", $resp);
    echo json_encode(["status" => ($partes[0] == "200" ? "ok" : "error"), "message" => sani($partes[2])]);
}
exit();
