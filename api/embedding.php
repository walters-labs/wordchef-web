<?php
require_once('utils.php');
require_once('response.php');

$conn = db_connect();
validate_api_key($conn);

$word = $_GET['word'] ?? '';

if ($word === '') {
    http_response_code(400);
    echo json_encode(['error' => 'No word parameter provided']);
    exit;
}

// Sanitize input, if you want to be strict
if (!preg_match('/^[a-zA-Z0-9_]{1,30}$/', $word)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid word format']);
    exit;
}

$query = "SELECT embedding FROM wordembeddings WHERE word = $1 LIMIT 1";
$result = pg_query_params($conn, $query, [$word]);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed']);
    exit;
}

$row = pg_fetch_assoc($result);
if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Word not found']);
    exit;
}

// Assuming embedding is stored as string like '{0.123,0.456,...}'
$embedding_str = trim($row['embedding'], '{}');
$embedding_array = array_map('floatval', explode(',', $embedding_str));

echo json_encode([
    'word' => $word,
    'embedding' => $embedding_array,
]);

pg_free_result($result);
pg_close($conn);
