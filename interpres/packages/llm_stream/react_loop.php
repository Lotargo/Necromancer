<?php

$autoloader = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

if (!class_exists('ThoughtSignatureStreamDecorator')) {
    class ThoughtSignatureStreamDecorator implements \Psr\Http\Message\StreamInterface {
        private $stream;
        private $buffer = '';
        public static $signatures = [];

        public function __construct(\Psr\Http\Message\StreamInterface $stream) {
            $this->stream = $stream;
        }

        public function read(int $length): string {
            $data = $this->stream->read($length);
            $this->buffer .= $data;
            
            $lines = explode("\n", $this->buffer);
            $this->buffer = array_pop($lines);

            foreach ($lines as $line) {
                if (str_starts_with($line, 'data: {')) {
                    $json = json_decode(substr($line, 6), true);
                    if ($json && isset($json['choices'][0]['delta']['tool_calls'])) {
                        foreach ($json['choices'][0]['delta']['tool_calls'] as $tc) {
                            if (isset($tc['id']) && isset($tc['extra_content'])) {
                                self::$signatures[$tc['id']] = $tc['extra_content'];
                            }
                        }
                    }
                }
            }
            return $data;
        }

        public function __toString(): string { return $this->stream->__toString(); }
        public function close(): void { $this->stream->close(); }
        public function detach() { return $this->stream->detach(); }
        public function getSize(): ?int { return $this->stream->getSize(); }
        public function tell(): int { return $this->stream->tell(); }
        public function eof(): bool { return $this->stream->eof(); }
        public function isSeekable(): bool { return $this->stream->isSeekable(); }
        public function seek(int $offset, int $whence = SEEK_SET): void { $this->stream->seek($offset, $whence); }
        public function rewind(): void { $this->stream->rewind(); }
        public function isWritable(): bool { return $this->stream->isWritable(); }
        public function write(string $string): int { return $this->stream->write($string); }
        public function isReadable(): bool { return $this->stream->isReadable(); }
        public function getContents(): string { return $this->stream->getContents(); }
        public function getMetadata(?string $key = null) { return $this->stream->getMetadata($key); }
    }
}

function llm_stream_append_react_reminder($messages_to_send, $loop_count, $lingua_mode)
{
    if ($loop_count <= 1) {
        return $messages_to_send;
    }

    if (!isset($messages_to_send[0]) || ($messages_to_send[0]['role'] ?? '') !== 'system') {
        return $messages_to_send;
    }

    if ($lingua_mode === 'auto') {
        $messages_to_send[0]['content'] .= "\n\n[SYSTEM REMINDER: This is ReAct step $loop_count. You have received the results of the previous tool execution (Observation). You MUST start your response with a `<thought>` block to:
1. Reflect on the tool's output. If there were errors, compilation failures, or unexpected results, diagnose the issue and plan the fix.
2. If Pascal code was executed, perform a strict mental code review (check for logical bugs, syntax, boundary conditions, correct types).
3. Decide if you need to call another tool or if you have enough information for the final answer.
Do NOT repeat greetings or duplicate introductory thoughts.
Пример перехода после блока </thought>: 'Согласно найденным сведениям...' или 'Изучив свитки, я обнаружил...']";
    } else {
        $messages_to_send[0]['content'] .= "\n\n[SYSTEM REMINDER: Hic est gradus $loop_count ReAct. Eventum instrumenti accepisti. Cogitationem tuam in `<thought>` block statim incipe:
1. Perpende eventum instrumenti. Si error accidit, cogita cur et quomodo corrigas.
2. Si codicem (e.g. Pascal) scripsisti, diligentissime eum recense (code review).
3. Constitue utrum alio instrumento egeas an responsum finale parare possis.
NOLI salutationem vel cogitationes priores repetere.]";
    }

    return $messages_to_send;
}

function llm_stream_build_request_data($model, $messages_to_send, $llm_config, $search_mode, $tools, $provisor_nomen = "")
{
    $data = [
        "model" => $model,
        "messages" => $messages_to_send,
        "max_tokens" => $llm_config['max_tokens'],
        "temperature" => (float)$llm_config['temperature'],
        "top_p" => (float)$llm_config['top_p'],
        "stream" => true,
    ];

    if ($search_mode === 'on') {
        $data["tools"] = $tools;
        $data["tool_choice"] = "auto";

        if (strtolower(trim($provisor_nomen ?? '')) !== 'gemini') {
            $data["parallel_tool_calls"] = false;
        }
    }

    return $data;
}

function llm_stream_stream_completion($api_url, $apikey, $data, &$tool_calls_buffer, &$current_content, &$final_response_content, &$error_buffer, &$total_reasoning)
{
    if (!class_exists('\OpenAI')) {
        $autoloader = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (file_exists($autoloader)) {
            require_once $autoloader;
        } else {
            return [500, "Composer autoloader not found."];
        }
    }

    $data['stream'] = true; // Ensure boolean
    
    // Log outgoing request
    $log_json_data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    file_put_contents(dirname(__DIR__) . "/tmp_payload.txt", print_r($data, true) . "\n---\n" . $log_json_data . "\n========================\n", FILE_APPEND);

    try {
        $base_uri = preg_replace('#/chat/completions/?$#', '', $api_url);
        
        $handlerStack = \GuzzleHttp\HandlerStack::create();
        
        $handlerStack->push(\GuzzleHttp\Middleware::mapRequest(function (\Psr\Http\Message\RequestInterface $request) {
            if ($request->getMethod() === 'POST') {
                $body = $request->getBody()->getContents();
                $request->getBody()->rewind();
                
                if (!empty($body)) {
                    $json = json_decode($body, true);
                    if ($json && isset($json['messages'])) {
                        $modified = false;
                        foreach ($json['messages'] as &$msg) {
                            if (isset($msg['role']) && $msg['role'] === 'assistant' && isset($msg['tool_calls'])) {
                                foreach ($msg['tool_calls'] as &$tc) {
                                    if (isset($tc['id']) && isset(ThoughtSignatureStreamDecorator::$signatures[$tc['id']])) {
                                        $tc['extra_content'] = ThoughtSignatureStreamDecorator::$signatures[$tc['id']];
                                        $modified = true;
                                    }
                                }
                            }
                        }
                        if ($modified) {
                            $newBody = json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            return $request->withBody(\GuzzleHttp\Psr7\Utils::streamFor($newBody));
                        }
                    }
                }
            }
            return $request;
        }));

        $handlerStack->push(\GuzzleHttp\Middleware::mapResponse(function (\Psr\Http\Message\ResponseInterface $response) {
            return $response->withBody(new ThoughtSignatureStreamDecorator($response->getBody()));
        }));

        $client = \OpenAI::factory()
            ->withApiKey($apikey)
            ->withBaseUri($base_uri)
            ->withHttpClient(new \GuzzleHttp\Client(['stream' => true, 'verify' => false, 'handler' => $handlerStack]))
            ->make();

        $stream = $client->chat()->createStreamed($data);

        foreach ($stream as $response) {
            $raw_response = $response->toArray();
            if (!isset($raw_response['choices'][0]['delta'])) {
                continue;
            }

            $delta = $raw_response['choices'][0]['delta'];

            if (isset($delta['content']) && $delta['content'] !== null) {
                $current_content .= $delta['content'];
                $final_response_content .= $delta['content'];
                echo "data: " . json_encode(["choices" => [["delta" => ["content" => $delta['content']]]]]) . "\n\n";
                flush();
            }

            if (isset($delta['reasoning_content']) && $delta['reasoning_content'] !== null) {
                $total_reasoning .= $delta['reasoning_content'];
                echo "data: " . json_encode(["choices" => [["delta" => ["reasoning_content" => $delta['reasoning_content']]]]]) . "\n\n";
                flush();
            }

            if (isset($delta['tool_calls'])) {
                foreach ($delta['tool_calls'] as $tc) {
                    $idx = $tc['index'] ?? 0;
                    if (!isset($tool_calls_buffer[$idx])) {
                        $tool_calls_buffer[$idx] = [
                            "id" => $tc['id'] ?? "",
                            "type" => "function",
                            "function" => [
                                "name" => $tc['function']['name'] ?? "",
                                "arguments" => $tc['function']['arguments'] ?? "",
                            ],
                        ];

                        if (!empty($tc['function']['name'])) {
                            echo "data: " . json_encode(["event" => "tool_call", "name" => $tc['function']['name']]) . "\n\n";
                            flush();
                        }
                    } elseif (isset($tc['function']['arguments'])) {
                        $tool_calls_buffer[$idx]['function']['arguments'] .= $tc['function']['arguments'];
                    }

                    if (isset($tc['extra_content'])) {
                        $tool_calls_buffer[$idx]['extra_content'] = $tc['extra_content'];
                    }
                }
            }
        }
        return [200, ""];
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $response = $e->getResponse();
        $error_buffer = $response ? $response->getBody()->getContents() : $e->getMessage();
        return [$e->getCode(), $error_buffer];
    } catch (\Exception $e) {
        $error_buffer = $e->getMessage();
        $http_code = $e->getCode();
        if ($http_code < 100 || $http_code >= 600) {
            $http_code = 500;
        }
        return [$http_code, $error_buffer];
    }
}

function llm_stream_rotate_key_within_pinned_model($cubiculum, $aequilibrium_activum, $pinned_provider, $pinned_model)
{
    if (!$aequilibrium_activum) {
        return null;
    }

    for ($attempt = 0; $attempt < 20; $attempt++) {
        purgare_sessionem_aequilibrio($cubiculum);
        $dest = eligere_destinationem_llm($cubiculum, $aequilibrium_activum);
        if (($dest["provisor_nomen"] ?? '') === $pinned_provider && ($dest["model"] ?? '') === $pinned_model && !empty($dest["apikey"])) {
            return $dest;
        }
    }

    return null;
}

function llm_stream_maybe_execute_fallback_tool(&$assistant_message, &$messages, &$current_content, &$final_response_content)
{
    if (empty($current_content)) {
        return false;
    }

    $trimmed = trim($current_content);
    $parsed_tc = null;
    $json_block = '';

    // 1. Попытка распарсить строку целиком как JSON
    $maybe_json = json_decode($trimmed, true);
    if ($maybe_json && isset($maybe_json['name'])) {
        $parsed_tc = $maybe_json;
        $json_block = $trimmed;
    }

    // 2. Если целиком не получилось, ищем регулярным выражением валидный JSON-объект, содержащий "name": "название_инструмента"
    if (!$parsed_tc) {
        $tools_pattern = 'search_web|search_knowledge_base|check_weather|check_time|solve_discrete_math|run_streaming_simulation';
        if (preg_match('/\{[^{}]*"name"\s*:\s*"(' . $tools_pattern . ')"[^{}]*\}/s', $trimmed, $json_match)) {
            $maybe_json = json_decode($json_match[0], true);
            if ($maybe_json && isset($maybe_json['name'])) {
                $parsed_tc = $maybe_json;
                $json_block = $json_match[0];
            }
        }
    }

    if (!$parsed_tc) {
        return false;
    }

    $fb_tool_name = $parsed_tc['name'];
    $fb_args = [];

    // 3. Восстанавливаем аргументы с учетом всех возможных вариаций ключей (arguments, parameters, params, root keys)
    if (isset($parsed_tc['arguments'])) {
        $fb_args = $parsed_tc['arguments'];
    } elseif (isset($parsed_tc['parameters'])) {
        $fb_args = $parsed_tc['parameters'];
    } elseif (isset($parsed_tc['params'])) {
        $fb_args = $parsed_tc['params'];
    } else {
        // Аргументы лежат прямо в корне объекта!
        foreach ($parsed_tc as $k => $v) {
            if (!in_array($k, ['name', 'type'])) {
                $fb_args[$k] = $v;
            }
        }
    }

    // 4. Если аргументы представлены в виде обычной строки, оборачиваем в соответствующий названию инструмента ключ
    if (is_string($fb_args)) {
        $param_name = in_array($fb_tool_name, ['solve_discrete_math', 'run_streaming_simulation']) 
            ? 'code' 
            : (in_array($fb_tool_name, ['search_web', 'search_knowledge_base']) ? 'query' : 'location');
        $fb_args = [$param_name => $fb_args];
    } elseif (!is_array($fb_args)) {
        $fb_args = [];
    }

    $fb_tool_id = 'fallback_' . uniqid();

    echo "data: " . json_encode(["event" => "clear_fallback"]) . "\n\n";
    flush();
    echo "data: " . json_encode(["event" => "tool_call", "name" => $fb_tool_name]) . "\n\n";
    flush();

    // Extract any text content (like the <thought> block) by removing the JSON block from trimmed content
    $text_content = trim(str_replace($json_block, '', $trimmed));

    // Remove only the raw JSON block from final_response_content to keep the thoughts visible but hide raw JSON
    $final_response_content = str_replace($json_block, '', $final_response_content);

    // Keep the thought block / non-JSON text in the assistant message content so it remains in context history
    $assistant_message["content"] = $text_content;
    $assistant_message["tool_calls"] = [[
        "id" => $fb_tool_id,
        "type" => "function",
        "function" => [
            "name" => $fb_tool_name,
            "arguments" => json_encode($fb_args, JSON_UNESCAPED_UNICODE),
        ],
    ]];
    $messages[] = $assistant_message;

    $fb_result = llm_stream_execute_tool($fb_tool_name, $fb_args);
    $messages[] = [
        "tool_call_id" => $fb_tool_id,
        "role" => "tool",
        "name" => $fb_tool_name,
        "content" => $fb_result,
    ];

    return true;
}

function llm_stream_run_react_loop($messages, $lingua_mode, $search_mode, $llm_config, $tools, $cubiculum, $aequilibrium_activum, $destinatio_llm)
{
    $apikey = $destinatio_llm["apikey"];
    $api_url = $destinatio_llm["api_url"];
    $model = $destinatio_llm["model"];
    $provisor_nomen = $destinatio_llm["provisor_nomen"];

    $pinned_provider = $provisor_nomen;
    $pinned_model = $model;
    $max_loops = 8;
    $loop_count = 0;
    $api_error_retries = 0;
    $max_api_error_retries = 3;
    $final_response_content = "";
    $provisor_nuntiatus = "";
    $total_reasoning = "";
    $executed_codes = [];
    $assistant_texts = [];
    $last_math_result = null;

    while ($loop_count < $max_loops) {
        $loop_count++;

        if (!$apikey) {
            $err_msg = "Clavis API deest. " . ($destinatio_llm["error"] ?? "Nulla clavis provisa.");
            echo "data: " . json_encode(["choices" => [["delta" => ["content" => $err_msg]]]]) . "\n\n";
            $final_response_content .= $err_msg;
            break;
        }

        if ($provisor_nomen !== "Ignotus") {
            $provisor_hash = $provisor_nomen . "|" . $model;
            if ($provisor_hash !== $provisor_nuntiatus) {
                echo "data: " . json_encode(["event" => "provisor", "nomen" => $provisor_nomen, "model" => $model]) . "\n\n";
                flush();
                $provisor_nuntiatus = $provisor_hash;
            }
        }

        $messages_to_send = llm_stream_append_react_reminder($messages, $loop_count, $lingua_mode);
        $data = llm_stream_build_request_data($model, $messages_to_send, $llm_config, $search_mode, $tools, $provisor_nomen);
        $tool_calls_buffer = [];
        $current_content = "";
        $error_buffer = "";
        $total_reasoning = "";

        [$http_code, $err] = llm_stream_stream_completion(
            $api_url,
            $apikey,
            $data,
            $tool_calls_buffer,
            $current_content,
            $final_response_content,
            $error_buffer,
            $total_reasoning
        );

        if ($err || $http_code >= 400 || !empty($error_buffer)) {
            $error_json = json_decode($error_buffer, true);
            $parsed_msg = "";
            if ($error_json) {
                if (isset($error_json['error']['message'])) {
                    $parsed_msg = $error_json['error']['message'];
                } elseif (isset($error_json['message'])) {
                    $parsed_msg = $error_json['message'];
                }
            }

            $err_str = $err ?: ($parsed_msg ?: trim($error_buffer));
            $err_msg = "Error Oraculi [Provider: " . $provisor_nomen . ", Model: " . $model . "] (HTTP " . $http_code . "): " . $err_str;
            $eventus = classificare_eventum_llm((int)$http_code, $err_str);

            if ($aequilibrium_activum && $provisor_nomen !== "Ignotus") {
                notare_eventum_clavis_llm($provisor_nomen, $apikey, $model, $eventus["event_type"], (int)$http_code, $eventus["error_kind"], $err_str);
                purgare_sessionem_aequilibrio($cubiculum);
            }

            if ($aequilibrium_activum && $loop_count < $max_loops && $api_error_retries < $max_api_error_retries) {
                // We only increment if failover actually happened (inside the blocks below),
                // but for safety we can just increment here since this block is inside if ($err || $http_code >= 400 || !empty($error_buffer))
                $api_error_retries++;

                if ($loop_count === 1 && empty($final_response_content) && empty($tool_calls_buffer) && $current_content === "") {
                    $destinatio_llm = eligere_destinationem_llm($cubiculum, $aequilibrium_activum);
                    $apikey = $destinatio_llm["apikey"];
                    $api_url = $destinatio_llm["api_url"];
                    $model = $destinatio_llm["model"];
                    $provisor_nomen = $destinatio_llm["provisor_nomen"];
                    $pinned_provider = $provisor_nomen;
                    $pinned_model = $model;

                    echo "data: " . json_encode([
                        "event" => "failover",
                        "message" => "Provider " . $provisor_nomen . " failed. Switching to a new provider/model. Retry " . $api_error_retries . "/" . $max_api_error_retries,
                    ]) . "\n\n";
                    flush();
                    $loop_count--;
                    continue;
                }

                $rotated_dest = llm_stream_rotate_key_within_pinned_model($cubiculum, $aequilibrium_activum, $pinned_provider, $pinned_model);
                if ($rotated_dest) {
                    $apikey = $rotated_dest["apikey"];
                    $api_url = $rotated_dest["api_url"];
                    $provisor_nomen = $rotated_dest["provisor_nomen"];
                    $model = $rotated_dest["model"];

                    echo "data: " . json_encode([
                        "event" => "failover",
                        "message" => "Key for " . $pinned_provider . " [" . $pinned_model . "] rotated successfully due to API error. Retry " . $api_error_retries . "/" . $max_api_error_retries,
                    ]) . "\n\n";
                    flush();
                    $loop_count--;
                    continue;
                }
            }

            if ($loop_count === 1) {
                echo "data: " . json_encode(["choices" => [["delta" => ["content" => $err_msg]]]]) . "\n\n";
                $final_response_content .= $err_msg;
            } else {
                $final_response_content .= "\n" . $err_msg;
            }
            break;
        }

        if ($aequilibrium_activum && $provisor_nomen !== "Ignotus") {
            notare_eventum_clavis_llm($provisor_nomen, $apikey, $model, "SUCCESS", (int)$http_code, "success", "");
        }

        $assistant_message = ["role" => "assistant", "content" => $current_content ?: ""];
        if (trim($current_content) !== '') {
            $assistant_texts[] = $current_content;
        }
        if (!empty($tool_calls_buffer)) {
            $normalized_tool_calls = normalizare_tool_calls_ad_executionem(array_values($tool_calls_buffer));
            $assistant_message["tool_calls"] = $normalized_tool_calls;
            $messages[] = $assistant_message;

            foreach ($normalized_tool_calls as $tc) {
                $tool_name = $tc['function']['name'];
                $args = json_decode($tc['function']['arguments'], true) ?: [];

                // Автоматически фиксируем сгенерированный Pascal-код и отправляем на фронтенд
                if (in_array($tool_name, ['solve_discrete_math', 'run_streaming_simulation']) && isset($args['code'])) {
                    $executed_codes[] = [
                        "name" => $tool_name,
                        "code" => $args['code']
                    ];
                    
                    // Стримим событие выполнения с полным исходным кодом на фронтенд
                    echo "data: " . json_encode([
                        "event" => "tool_execute",
                        "name" => $tool_name,
                        "code" => $args['code']
                    ]) . "\n\n";
                    flush();
                }

                $tool_result = llm_stream_execute_tool($tool_name, $args);

                // AUTO-RAG: Automatically inject knowledge base hints for Pascal compilation errors
                if (strpos($tool_result, 'Pascal Execution Compilation:') !== false) {
                    // Extract a clean version of the error message to search the knowledge base
                    // Remove the compiler banner which can pollute the search
                    $clean_error = preg_replace('/Pascal Execution Compilation:.*?Error:/s', 'Error:', $tool_result);
                    // Remove filenames and line numbers
                    $clean_error = preg_replace('/temp_[a-zA-Z0-9_]+\.pas\([0-9,]+\)\s*/', '', $clean_error);

                    // Take up to 150 characters, ensuring it captures the actual error string
                    $clean_error = substr($clean_error, 0, 150);

                    // Sanitize the string to prevent IPC protocol breakage
                    $clean_error = str_replace(["\r", "\n", "|"], " ", trim($clean_error));

                    $rag_resp = loqui_cum_daemonio("INVESTIGARE|" . $clean_error);
                    $partes_rag = explode("|", $rag_resp);

                    if (($partes_rag[0] ?? '') === '200') {
                        $hint = $partes_rag[2] ?? '';
                        if (!empty($hint)) {
                            $tool_result .= "\n\n[System Note: The local knowledge base suggests: '" . $hint . "']";
                        }
                    }
                }

                if ($tool_name === 'solve_discrete_math') {
                    $last_math_result = $tool_result;
                }
                $messages[] = [
                    "tool_call_id" => $tc['id'],
                    "role" => "tool",
                    "name" => $tool_name,
                    "content" => $tool_result,
                ];
            }
        } else {
            $fallback_executed = llm_stream_maybe_execute_fallback_tool($assistant_message, $messages, $current_content, $final_response_content);
            if (!$fallback_executed) {
                // LLM output is pure text (could be a final answer or a plan without tools).
                // We must break the ReAct cycle to prevent infinite loops of tool-less thoughts
                break;
            } else {
                // Inform frontend that fallback tool was captured
                echo "data: " . json_encode(["choices" => [["delta" => ["content" => "

[Fallback Tool Recovered...]
"]]]]) . "\n\n";
                flush();
            }
        }
    }

    // AUTO-FALLBACK ДЛЯ ЛЕНИВЫХ МОДЕЛЕЙ:
    // Если выполнялся математический инструмент, но модель не вывела результат в финальном ответе
    if ($last_math_result !== null && !empty(trim($last_math_result))) {
        $math_snippet = substr(trim($last_math_result), 0, 50);
        if (empty($final_response_content) || strpos($final_response_content, $math_snippet) === false) {
            $prefix = "\n\n**Calculus Oraculi:**\n";
            echo "data: " . json_encode(["choices" => [["delta" => ["content" => $prefix . $last_math_result]]]]) . "\n\n";
            flush();
            $final_response_content .= $prefix . $last_math_result;
            $assistant_texts[] = $prefix . $last_math_result;
        }
    }

    echo "data: [DONE]\n\n";
    flush();

    if ($aequilibrium_activum) {
        purgare_sessionem_aequilibrio($cubiculum);
    }

    return [
        'final_response_content' => $final_response_content,
        'assistant_texts' => $assistant_texts,
        'total_reasoning' => $total_reasoning,
        'executed_codes' => $executed_codes
    ];
}
