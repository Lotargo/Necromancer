<?php

function tool_manager_load_dynamic_tools()
{
    $tools_dir = dirname(__DIR__) . '/tools';
    $dynamic_tools = [];

    if (!is_dir($tools_dir)) {
        return $dynamic_tools;
    }

    $folders = scandir($tools_dir);
    foreach ($folders as $folder) {
        if ($folder === '.' || $folder === '..') {
            continue;
        }

        $schema_path = $tools_dir . '/' . $folder . '/schema.json';
        if (file_exists($schema_path)) {
            $schema_content = file_get_contents($schema_path);
            $schema = json_decode($schema_content, true);
            if (is_array($schema)) {
                $dynamic_tools[] = $schema;
            }
        }
    }

    return $dynamic_tools;
}

function tool_manager_execute_tool($tool_name, $args)
{
    $tools_dir = dirname(__DIR__) . '/tools';
    $handler_path = $tools_dir . '/' . $tool_name . '/handler.php';

    if (file_exists($handler_path)) {
        // Enforce scoped execution of the handler
        return (function() use ($handler_path, $args) {
            return include $handler_path;
        })();
    }

    return null;
}
