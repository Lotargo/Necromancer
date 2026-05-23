<?php

$rule = $args['rule'] ?? '';

if (empty(trim($rule))) {
    return "Error: No rule provided to save.";
}

// Enforce single-line to avoid breaking the text-based RAG parsing
$clean_rule = str_replace(["\r", "\n", "|"], " ", trim($rule));

$resp = loqui_cum_daemonio("SALVARE_SCIENTIAM|" . $clean_rule);
$parts = explode("|", $resp, 3);

if ($parts[0] === '200') {
    return "Rule successfully saved to knowledge base: " . $clean_rule;
} else {
    $err_msg = $parts[2] ?? 'Unknown error';
    return "Failed to save rule: " . $err_msg;
}
