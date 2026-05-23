<?php
// Direct ReAct loop test runner
session_start();

// Mock variables for session context
$_SESSION["usor"] = "test_user";
$_SESSION["fp"] = "test_fp";
$usor = "test_user";
$user_fp = "test_fp";
$cubiculum = "room_test";
$action = "test";

require_once __DIR__ . '/packages/sanitas.php';
require_once __DIR__ . '/packages/daemonium.php';
require_once __DIR__ . '/packages/aequilibrium.php';
require_once __DIR__ . '/packages/auxilia.php';
require_once __DIR__ . '/packages/oraculum.php';
require_once __DIR__ . '/packages/tempestas.php';
require_once __DIR__ . '/packages/tela.php';

require_once __DIR__ . '/packages/llm_stream/tool_calls.php';
require_once __DIR__ . '/packages/llm_stream/context.php';
require_once __DIR__ . '/packages/llm_stream/prompts.php';
require_once __DIR__ . '/packages/llm_stream/react_loop.php';

// Parse arguments
$apikey = getenv('GEMINI_API_KEY') ?: ($argv[1] ?? '');
if (empty($apikey)) {
    echo "Error: GEMINI_API_KEY environment variable or argument is missing.\n";
    echo "Usage: php test_react_direct.php <API_KEY> [question] [model]\n";
    exit(1);
}

$question = $argv[2] ?? "Сколько сейчас времени в Токио и какая там погода?";
$model = $argv[3] ?? "gemini-2.5-flash";

$time_context = "\n  <current_time_context>\n" .
    "    <user_local_time>Saturday, May 23, 2026, 9:00 PM</user_local_time>\n" .
    "    <user_timezone>Europe/Moscow</user_timezone>\n" .
    "  </current_time_context>";

// Build system prompt using our new optimized instructions
$system_role = llm_stream_build_system_role('auto', $time_context, 4000);
$messages = [
    ["role" => "system", "content" => $system_role],
    ["role" => "user", "content" => $question]
];

$tools = llm_stream_build_tools();
$llm_config = ["max_tokens" => 4000, "temperature" => 0.4, "top_p" => 0.95];

$destinatio_llm = [
    "apikey" => $apikey,
    "api_url" => "https://generativelanguage.googleapis.com/v1beta/openai/chat/completions",
    "model" => $model,
    "provisor_nomen" => "Gemini"
];

echo "===============================================\n";
echo "🚀 STARTING DIRECT GEMINI REACT LOOP TEST\n";
echo "===============================================\n";
echo "Model:    $model\n";
echo "Question: $question\n";
echo "Tools:    " . implode(', ', array_map(fn($t) => $t['function']['name'], $tools)) . "\n";
echo "-----------------------------------------------\n";

// Execute loop
$result = llm_stream_run_react_loop(
    $messages,
    'auto',
    'on',
    $llm_config,
    $tools,
    $cubiculum,
    false, // false to prevent failover/equilibrium key rotations
    $destinatio_llm
);

echo "\n-----------------------------------------------\n";
echo "📝 FINAL RESPONSE CONTENT:\n";
echo "-----------------------------------------------\n";
echo $result['final_response_content'] . "\n";
echo "===============================================\n";
echo "✅ REACT LOOP TEST COMPLETED SUCCESSFULLY\n";
echo "===============================================\n";
