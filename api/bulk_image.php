<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once('utils.php');
require_once('response.php');

$conn = db_connect();
$api_key = validate_api_key($conn);
$api_rate_limit = fetch_rate_limit($conn, $api_key);
check_rate_limit($conn, $api_key, $api_rate_limit);

// Read raw POST body
$input = file_get_contents('php://input');
$words = json_decode($input, true);

if (!is_array($words) || empty($words)) {
    json_response(['error' => 'Invalid or missing words JSON array'], 400);
}

$results = [];

foreach ($words as $word) {
    // Simple validation to avoid bad input
    if (!is_string($word) || !preg_match('/^[a-zA-Z0-9_]{1,30}$/', $word)) {
        $results[$word] = ['error' => 'Invalid word format'];
        continue;
    }

    $embedding = lookup_embedding($conn, $word);
    if ($embedding === null) {
        $results[$word] = ['error' => 'Embedding not found'];
        continue;
    }

    $base64_img = generate_vector_image_base64($embedding, $word);
    if (!$base64_img) {
        $results[$word] = ['error' => 'Image generation failed'];
        continue;
    }

    $results[$word] = $base64_img;
}

json_response($results);

function lookup_embedding($conn, $word) {
    $query = 'SELECT embedding FROM wordembeddings WHERE word = $1 LIMIT 1';
    $result = pg_query_params($conn, $query, [$word]);
    if (!$result || pg_num_rows($result) === 0) {
        return null;
    }
    $row = pg_fetch_assoc($result);
    $embedding_str = str_replace(['{', '}'], '', $row['embedding']);
    return array_map('floatval', explode(',', $embedding_str));
}
