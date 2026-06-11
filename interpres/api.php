<?php
session_start();
set_time_limit(0);

require_once __DIR__ . '/packages/sanitas.php';
require_once __DIR__ . '/packages/daemonium.php';
require_once __DIR__ . '/packages/aequilibrium.php';
require_once __DIR__ . '/packages/auxilia.php';
require_once __DIR__ . '/packages/oraculum.php';
require_once __DIR__ . '/packages/tempestas.php';
require_once __DIR__ . '/packages/tela.php';

$action = sani($_GET['action'] ?? $_POST['action'] ?? '');
$cubiculum = sani($_GET['room'] ?? $_POST['room'] ?? 'default');
if (strlen($cubiculum) > 100) {
    http_response_code(400);
    exit("Bad Request: Room ID too long");
}

// 1. Public Actions (No Session Required)
if ($action === 'login_anima' || $action === 'register_anima' || $action === 'forgot_anima') {
    require_once __DIR__ . '/packages/auth.php';
    exit();
}

// 2. Session Check
if (!isset($_SESSION["usor"]) || !isset($_SESSION["fp"])) {
    http_response_code(401);
    exit("Unauthorized (No Session or FP)");
}
$usor = $_SESSION["usor"];
$user_fp = $_SESSION["fp"];

// 3. Dispatch to packages based on action
if (in_array($action, ['get_user_state', 'save_options', 'renominare_usorem', 'mutare_tessaram', 'delere_rationem'])) {
    require_once __DIR__ . '/packages/user.php';
    exit();
}

if (in_array($action, ['list', 'load', 'delete', 'delere_omnes_fabulationes', 'rename'])) {
    require_once __DIR__ . '/packages/chat_manager.php';
    exit();
}

if ($action === 'send') {
    require_once __DIR__ . '/packages/llm_stream.php';
    exit();
}

// Unknown action
http_response_code(400);
exit("Bad Request: Unknown Action");

