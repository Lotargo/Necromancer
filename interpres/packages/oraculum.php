<?php

function statum_clavis_llm($provider, $key)
{
    $resp = loqui_cum_daemonio(
        "STATUM_CLAVIS_LLM|" . sanitizare_internam($provider) . "|" . sanitizare_internam($key)
    );
    $partes = explode("|", $resp, 3);
    if (($partes[0] ?? '') === "200") {
        return trim($partes[2] ?? 'active');
    }
    return 'active';
}

function notare_eventum_clavis_llm($provider, $key, $model, $event_type, $http_code, $error_kind, $detail = '')
{
    $mandatum = implode("|", [
        "NOTARE_EVENTUM_CLAVIS_LLM",
        sanitizare_internam($provider),
        sanitizare_internam($key),
        sanitizare_internam($model),
        sanitizare_internam($event_type),
        sanitizare_internam((string)$http_code),
        sanitizare_internam($error_kind),
        sanitizare_internam($detail),
    ]);
    return loqui_cum_daemonio($mandatum);
}

function classificare_eventum_llm($http_code, $err_str)
{
    $text = strtolower((string)$err_str);

    if ($http_code === 429 || strpos($text, 'rate limit') !== false || strpos($text, 'too many requests') !== false) {
        return ["event_type" => "RATE_LIMIT", "error_kind" => "rate_limit"];
    }

    $is_region = (
        strpos($text, 'region') !== false ||
        strpos($text, 'country') !== false ||
        strpos($text, 'location') !== false ||
        strpos($text, 'unsupported_location') !== false
    );
    if ($is_region) {
        return ["event_type" => "REGION_BLOCKED", "error_kind" => "region_blocked"];
    }

    $is_auth = (
        strpos($text, 'invalid api key') !== false ||
        strpos($text, 'unauthorized') !== false ||
        strpos($text, 'authentication') !== false ||
        strpos($text, 'payment method is required') !== false ||
        strpos($text, 'billing') !== false ||
        strpos($text, 'account') !== false
    );

    if ($http_code === 401 || $http_code === 402 || ($http_code === 403 && !$is_region) || $is_auth) {
        return ["event_type" => "DISABLE", "error_kind" => "auth_or_billing"];
    }

    if ($http_code === 400 || $http_code === 404) {
        return ["event_type" => "MODEL_ERROR", "error_kind" => "model_or_request"];
    }

    if ($http_code >= 500 || $http_code === 0 || strpos($text, 'timed out') !== false || strpos($text, 'could not resolve') !== false) {
        return ["event_type" => "TRANSIENT", "error_kind" => "transient"];
    }

    return ["event_type" => "TRANSIENT", "error_kind" => "unknown"];
}

function eligere_destinationem_llm($id_sessionis, $aequilibrium_activum)
{
    if ($aequilibrium_activum) {
        $ultimus_error = "Aequilibrium activum est, sed nullum responsum validum cum provisore, clave et modelo accepimus.";
        for ($temptatio = 0; $temptatio < 12; $temptatio++) {
            $aequilibrium_resp = loqui_cum_aequilibrio($id_sessionis);
            if ($aequilibrium_resp) {
                $partes_aeq = explode("|", $aequilibrium_resp);
                if (count($partes_aeq) >= 6 && $partes_aeq[0] === "200") {
                    $provider = $partes_aeq[2];
                    $key = $partes_aeq[3];
                    $status = statum_clavis_llm($provider, $key);
                    if ($status === 'active') {
                        return [
                            "apikey" => $key,
                            "api_url" => $partes_aeq[4],
                            "model" => $partes_aeq[5],
                            "provisor_nomen" => $provider,
                            "error" => null
                        ];
                    }

                    $ultimus_error = "Omnes claves huius rotationis sunt in statu '" . $status . "'.";
                    purgare_sessionem_aequilibrio($id_sessionis);
                    continue;
                }
            }
        }

        return [
            "apikey" => null,
            "api_url" => null,
            "model" => null,
            "provisor_nomen" => "Ignotus",
            "error" => $ultimus_error
        ];
    }

    $apikey = getenv("OPENAI_API_KEY") ?: null;
    $api_url = getenv("OPENAI_API_URL") ?: "https://api.openai.com/v1/chat/completions";
    $model = getenv("OPENAI_API_MODEL") ?: "gpt-4o-mini";

    return [
        "apikey" => $apikey,
        "api_url" => $api_url,
        "model" => $model,
        "provisor_nomen" => $apikey ? "Custom" : "Ignotus",
        "error" => $apikey ? null : "Aequilibrium inactivum est, ergo valores ex .env requiruntur."
    ];
}
