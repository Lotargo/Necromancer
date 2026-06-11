<?php

$code = $args['code'] ?? '';
if (empty($code)) {
    return "Error: No Pascal code provided.";
}

if (strlen($code) > 65536) {
    return "Error: Pascal code exceeds the maximum allowed size of 64KB.";
}

$mechanica_host = getenv("MECHANICA_HOST") ?: "127.0.0.1";
$mechanica_port = getenv("MECHANICA_PORT") ?: 8082;
$unique_id = uniqid('math_');

$payload = "SOLVE_DISCRETE|" . $unique_id . "|" . $code . "\n";

$fp = @fsockopen($mechanica_host, $mechanica_port, $errno, $errstr, 10);
if (!$fp) {
    return "Error: Computational core (Mechanica) is not responding.";
}

fwrite($fp, $payload);
fflush($fp);
stream_socket_shutdown($fp, STREAM_SHUT_WR);

$response = '';
while (!feof($fp)) {
    $response .= fgets($fp, 1024);
}
fclose($fp);

$parts = explode('|', $response, 3);
if ($parts[0] === '200') {
    return $parts[2] ?? 'Success.';
} else {
    $err_kind = $parts[1] ?? 'Error';
    $err_msg = $parts[2] ?? 'Unknown error';
    return "Pascal Execution " . $err_kind . ": " . $err_msg;
}
