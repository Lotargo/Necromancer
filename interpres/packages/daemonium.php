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
    error_log("[DAEMON REQ] " . $mandatum);
    error_log("[DAEMON RESP] " . trim($responsum));
    return trim($responsum);
}
