<?php

function extrahere_objecta_json_concatenata($raw_arguments)
{
    $raw = trim((string)$raw_arguments);
    if ($raw === '') {
        return [];
    }

    $parts = [];
    $depth = 0;
    $start = null;
    $in_string = false;
    $escape = false;
    $len = strlen($raw);

    for ($i = 0; $i < $len; $i++) {
        $char = $raw[$i];

        if ($in_string) {
            if ($escape) {
                $escape = false;
                continue;
            }
            if ($char === '\\') {
                $escape = true;
                continue;
            }
            if ($char === '"') {
                $in_string = false;
            }
            continue;
        }

        if ($char === '"') {
            $in_string = true;
            continue;
        }

        if ($char === '{') {
            if ($depth === 0) {
                $start = $i;
            }
            $depth++;
            continue;
        }

        if ($char === '}') {
            if ($depth > 0) {
                $depth--;
                if ($depth === 0 && $start !== null) {
                    $parts[] = substr($raw, $start, $i - $start + 1);
                    $start = null;
                }
            }
        }
    }

    return $parts;
}

function normalizare_tool_calls_ad_executionem($tool_calls_buffer)
{
    $normalized = [];

    foreach ($tool_calls_buffer as $tc) {
        $tool_name = $tc['function']['name'] ?? '';

        // Ensure every tool call has a unique ID, falling back if missing from buffer
        if (empty($tc['id'])) {
            $tc['id'] = 'tool_' . uniqid();
        }

        $raw_arguments = (string)($tc['function']['arguments'] ?? '');
        $decoded = json_decode($raw_arguments, true);

        if (is_array($decoded)) {
            $tc['function']['arguments'] = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            $normalized[] = $tc;
            continue;
        }

        $split_objects = extrahere_objecta_json_concatenata($raw_arguments);
        $decoded_objects = [];
        foreach ($split_objects as $object_json) {
            $parsed = json_decode($object_json, true);
            if (is_array($parsed)) {
                $decoded_objects[] = $parsed;
            }
        }

        if (!empty($decoded_objects)) {
            $multiple = count($decoded_objects) > 1;
            foreach ($decoded_objects as $idx => $parsed_args) {
                $variant = $tc;
                $base_id = $tc['id'] ?? ('tool_' . uniqid());
                $variant['id'] = $multiple ? ($base_id . '_part' . ($idx + 1)) : $base_id;
                $variant['function']['arguments'] = json_encode($parsed_args, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                $normalized[] = $variant;
            }
            continue;
        }

        $fallback_args = match ($tool_name) {
            'check_weather', 'check_time' => ['location' => ''],
            'search_web', 'search_knowledge_base' => ['query' => ''],
            'solve_discrete_math', 'run_streaming_simulation' => ['code' => ''],
            default => [],
        };

        $tc['function']['arguments'] = json_encode($fallback_args, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        $normalized[] = $tc;
    }

    return $normalized;
}

function llm_stream_build_tools()
{
    require_once __DIR__ . '/tool_manager.php';
    $hardcoded = [
        [
            "type" => "function",
            "function" => [
                "name" => "search_web",
                "description" => "Searches the internet for up-to-date real world information and news.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "query" => ["type" => "string", "description" => "The search query"]
                    ],
                    "required" => ["query"]
                ]
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "search_knowledge_base",
                "description" => "Searches the local Necronomicon daemonium database for esoteric, local, or platform specific knowledge.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "query" => ["type" => "string", "description" => "The exact Latin or English keywords to search"]
                    ],
                    "required" => ["query"]
                ]
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "check_time",
                "description" => "Returns the exact current local time, timezone name, and GMT offset for a specific city or location. Use this for time-only questions about another city.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "location" => ["type" => "string", "description" => "The city or location name (e.g., 'Berlin', 'Tokyo', 'London', 'New York')"]
                    ],
                    "required" => ["location"]
                ]
            ]
        ],
        [
            "type" => "function",
            "function" => [
                "name" => "check_weather",
                "description" => "Returns current weather conditions, temperature, humidity, wind speed, pressure, and the exact current local time for a specific city or location. Use this tool for weather questions.",
                "parameters" => [
                    "type" => "object",
                    "properties" => [
                        "location" => ["type" => "string", "description" => "The city or location name (e.g., 'Tokyo', 'Moscow', 'London', 'New York')"]
                    ],
                    "required" => ["location"]
                ]
            ]
        ]
    ];

    $dynamic = tool_manager_load_dynamic_tools();
    return array_merge($hardcoded, $dynamic);
}

function llm_stream_execute_tool($tool_name, $args)
{
    require_once __DIR__ . '/tool_manager.php';
    $dyn_res = tool_manager_execute_tool($tool_name, $args);
    if ($dyn_res !== null) {
        return $dyn_res;
    }

    $query = $args['query'] ?? '';

    return match ($tool_name) {
        'search_web' => investigare_in_tela($query),
        'search_knowledge_base' => (function () use ($query) {
            $rag_resp = loqui_cum_daemonio("INVESTIGARE|" . $query);
            $partes_rag = explode("|", $rag_resp);
            return ($partes_rag[0] == "200") ? $partes_rag[2] : "Nihil inventum.";
        })(),
        'check_time' => evocatio_temporis($args['location'] ?? ''),
        'check_weather' => evocatio_tempestatis($args['location'] ?? ''),
        default => "Instrumentum ignotum.",
    };
}
