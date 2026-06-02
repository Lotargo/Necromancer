<?php

function loqui_cum_daemonio($mandatum)
{
    $daemonium_host = getenv("DAEMONIUM_HOST") ?: "127.0.0.1";
    $fp = @fsockopen($daemonium_host, 8080, $errno, $errstr, 10);
    if (!$fp) {
        error_log("[ERR] Daemonium connection failed: $errstr ($errno)");
        return "500|Error|Daemonium non respondet";
    }
    fwrite($fp, $mandatum . "\n");
    $responsum = fgets($fp, 8192);
    fclose($fp);
    $safe_mandatum = $mandatum;
    if (str_starts_with($mandatum, 'STATUM_CLAVIS_LLM') || str_starts_with($mandatum, 'NOTARE_EVENTUM_CLAVIS_LLM')) {
        $parts = explode('|', $mandatum);
        if (count($parts) >= 3) {
            $parts[2] = 'REDACTED';
            $safe_mandatum = implode('|', $parts);
        }
    }
    error_log("[DAEMON REQ] " . $safe_mandatum);
    error_log("[DAEMON RESP] " . trim($responsum));
    return trim($responsum);
}
