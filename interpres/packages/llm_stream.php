<?php
// Require the router utility context before executing package code.
if (!function_exists('sani')) {
    exit("Direct access not allowed");
}

require_once __DIR__ . '/llm_stream/tool_calls.php';
require_once __DIR__ . '/llm_stream/context.php';
require_once __DIR__ . '/llm_stream/prompts.php';
require_once __DIR__ . '/llm_stream/react_loop.php';
require_once __DIR__ . '/llm_stream/handler.php';

if ($action === 'send') {
    llm_stream_handle_send($usor, $cubiculum, $user_fp);
}
