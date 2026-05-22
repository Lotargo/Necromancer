<?php
// Test search queries and output snippets
require_once __DIR__ . '/../interpres/api.php';

$queries = [
    "current time in Tokyo",
    "Tokyo time",
    "weather in Tokyo",
    "weather Tokyo now"
];

foreach ($queries as $q) {
    echo "========================================\n";
    echo "QUERY: $q\n";
    echo "========================================\n";
    $res = investigare_in_tela($q);
    echo $res . "\n\n";
}
