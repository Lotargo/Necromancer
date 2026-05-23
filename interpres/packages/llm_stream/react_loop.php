<?php

function llm_stream_append_react_reminder($messages_to_send, $loop_count, $lingua_mode)
{
    if ($loop_count <= 1) {
        return $messages_to_send;
    }

    if (!isset($messages_to_send[0]) || ($messages_to_send[0]['role'] ?? '') !== 'system') {
        return $messages_to_send;
    }

    if ($lingua_mode === 'auto') {
        $messages_to_send[0]['content'] .= "\n\n[SYSTEM REMINDER: This is ReAct step $loop_count. You have already generated the initial introduction in the previous assistant message. Do NOT repeat or duplicate your previous greeting, intro, or introductory thoughts. Begin directly with the retrieved findings and continue naturally. Example transitions: 'Согласно найденным сведениям...' or 'Изучив свитки, я обнаружил...']";
    } else {
        $messages_to_send[0]['content'] .= "\n\n[SYSTEM REMINDER: Hic est gradus $loop_count ReAct. Iam introductionem scripsisti in nuntio assistant superius. NOLI salutationem vel introductionem repetere. Incipe statim ab investigationis eventu et cogitationem tuam sine ulla duplicatione perge.]";
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
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    $json_encoded_data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    file_put_contents(dirname(__DIR__) . "/tmp_payload.txt", print_r($data, true) . "\n---\n" . $json_encoded_data . "\n========================\n", FILE_APPEND);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_encoded_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apikey,
    ]);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$tool_calls_buffer, &$current_content, &$final_response_content, &$error_buffer, &$total_reasoning) {
        $lines = explode("\n", $chunk);
        foreach ($lines as $line) {
            if (!empty(trim($line)) && strpos($line, 'data: ') !== 0 && strpos(trim($line), '{') === 0 && empty($current_content) && empty($tool_calls_buffer)) {
                $error_buffer .= $chunk;
                return strlen($chunk);
            }

            if (strpos($line, 'data: ') !== 0) {
                continue;
            }

            $jsonStr = substr($line, 6);
            if (trim($jsonStr) === '[DONE]') {
                continue;
            }

            $json = json_decode($jsonStr, true);

            if ($json && isset($json['error'])) {
                $error_buffer .= json_encode($json);
                continue;
            }

            if (!$json || !isset($json['choices'][0]['delta'])) {
                continue;
            }

            $delta = $json['choices'][0]['delta'];

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

            if (!isset($delta['tool_calls'])) {
                continue;
            }

            foreach ($delta['tool_calls'] as $tc) {
                $idx = $tc['index'];
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
            }
        }

        return strlen($chunk);
    });

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    return [$http_code, $err];
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

    // 1. Попытка распарсить строку целиком как JSON
    $maybe_json = json_decode($trimmed, true);
    if ($maybe_json && isset($maybe_json['name'])) {
        $parsed_tc = $maybe_json;
    }

    // 2. Если целиком не получилось, ищем регулярным выражением валидный JSON-объект, содержащий "name": "название_инструмента"
    if (!$parsed_tc) {
        $tools_pattern = 'search_web|search_knowledge_base|check_weather|check_time|solve_discrete_math|run_streaming_simulation';
        if (preg_match('/\{[^{}]*"name"\s*:\s*"(' . $tools_pattern . ')"[^{}]*\}/s', $trimmed, $json_match)) {
            $maybe_json = json_decode($json_match[0], true);
            if ($maybe_json && isset($maybe_json['name'])) {
                $parsed_tc = $maybe_json;
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

    $final_response_content = str_replace($trimmed, '', $final_response_content);

    $assistant_message["content"] = "";
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
                break;
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
