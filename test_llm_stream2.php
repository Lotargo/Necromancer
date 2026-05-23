<?php
include 'interpres/packages/llm_stream/react_loop.php';
$destinatio_llm = [
    "apikey" => "test_key",
    "api_url" => "http://test.url",
    "model" => "gemini-1.5-pro",
    "provisor_nomen" => "Gemini"
];

$tools = [['name' => 'test_tool']];
$req_data = llm_stream_build_request_data("gemini-1.5-pro", [], ['max_tokens' => 100, 'temperature' => 0.5, 'top_p' => 0.9], 'on', $tools, "Gemini");
var_dump(isset($req_data['parallel_tool_calls']));

$req_data2 = llm_stream_build_request_data("groq-model", [], ['max_tokens' => 100, 'temperature' => 0.5, 'top_p' => 0.9], 'on', $tools, "Groq");
var_dump(isset($req_data2['parallel_tool_calls']));
