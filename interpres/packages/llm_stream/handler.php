<?php

function llm_stream_handle_send($usor, $cubiculum, $user_fp)
{
    $nuntius = trim($_POST['nuntius'] ?? '');
    $nuntius = sani_nuntius($nuntius);
    if (empty($nuntius)) {
        exit();
    }

    $user_timezone = sani($_POST['timezone'] ?? 'UTC');
    $user_local_time = sani($_POST['local_time'] ?? '');
    $time_context = llm_stream_build_time_context($user_local_time, $user_timezone);

    $aequilibrium_activum = env_ad_boolean("AEQUILIBRIUM_ENABLED", true);
    $destinatio_llm = eligere_destinationem_llm($cubiculum, $aequilibrium_activum);
    $apikey = $destinatio_llm["apikey"];
    $api_url = $destinatio_llm["api_url"];
    $model = $destinatio_llm["model"];

    $is_first = llm_stream_is_first_message($usor, $cubiculum, $user_fp);
    $renamed_to = llm_stream_try_auto_name_room($is_first, $apikey, $api_url, $model, $nuntius, $usor, $cubiculum, $user_fp);

    loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|" . $user_fp . "|Tute: " . $nuntius);

    llm_stream_init_sse_headers();

    $lingua_mode = $_POST['lingua'] ?? 'latin';
    $search_mode = $_POST['search'] ?? 'off';
    llm_stream_emit_room_rename($renamed_to);

    if (!$apikey) {
        $msg = "Clavis API deest. " . $destinatio_llm["error"];
        echo "data: " . json_encode(["choices" => [["delta" => ["content" => $msg]]]]) . "\n\n";
        loqui_cum_daemonio("ADDERE_NUNTIUM|" . $usor . "|" . $cubiculum . "|" . $user_fp . "|Oraculum: " . $msg);
        exit();
    }

    $llm_config = llm_stream_load_config();
    $system_role = llm_stream_build_system_role($lingua_mode, $time_context, $llm_config['max_tokens']);
    $messages = llm_stream_build_history_messages($system_role, $usor, $cubiculum, $user_fp, $nuntius);
    $tools = llm_stream_build_tools();

    $result = llm_stream_run_react_loop(
        $messages,
        $lingua_mode,
        $search_mode,
        $llm_config,
        $tools,
        $cubiculum,
        $aequilibrium_activum,
        $destinatio_llm
    );

    llm_stream_save_final_response(
        $usor,
        $cubiculum,
        $user_fp,
        $result['final_response_content'],
        $result['total_reasoning']
    );

    exit();
}
