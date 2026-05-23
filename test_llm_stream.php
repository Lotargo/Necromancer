<?php
include 'interpres/packages/llm_stream/react_loop.php';
$destinatio_llm = [
    "apikey" => "test_key",
    "api_url" => "http://test.url",
    "model" => "gemini-1.5-pro",
    "provisor_nomen" => "Gemini"
];
llm_stream_run_react_loop([], 'auto', 'on', ['max_tokens' => 100, 'temperature' => 0.5, 'top_p' => 0.9], [], 'room1', false, $destinatio_llm);
echo "OK\n";
