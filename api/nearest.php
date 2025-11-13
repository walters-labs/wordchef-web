<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once('utils.php');
require_once('response.php');

$conn = db_connect();
$api_key = validate_api_key($conn);
$api_rate_limit = fetch_rate_limit($conn, $api_key);
check_rate_limit($conn, $api_key, $api_rate_limit);

$limit = intval($_GET['limit'] ?? ($_POST['limit'] ?? 5));
if ($limit < 1) {
    $limit = 5;
}
if ($limit > 20) {  // enforce max limit
    $limit = 20;
}

// Parse input: either GET or POST
$input = $_GET['words'] ?? ($_POST['words'] ?? '');
if ($input === '') {
    json_response(['error' => 'Missing parameter: words'], 400);
}
$words = array_filter(array_map('trim', explode(',', $input)));

if (count($words) === 0) {
    json_response(['error' => 'No valid words provided'], 400);
}

// Fetch embeddings for each input word
$input_embeddings = [];
foreach ($words as $w) {
    $embedding = fetch_embedding($conn, $w);
    $input_embeddings[] = $embedding;
}

// Compute average embedding vector
$average = average_embeddings($input_embeddings);
$average_str = '[' . implode(',', $average) . ']';

// Query nearest neighbors (words + distances + embeddings)
$query = "SELECT word, embedding, embedding <-> $1 AS distance FROM wordembeddings ORDER BY embedding <-> $1 LIMIT $limit";
$result = pg_query_params($conn, $query, [$average_str]);

$neighbors = [];
while ($row = pg_fetch_assoc($result)) {
    // Parse embedding string to float array (assuming pgvector stores as {v1,v2,...})
    $embedding_str = str_replace(['{', '}'], '', $row['embedding']);
    $embedding_arr = array_map('floatval', explode(',', $embedding_str));

    $neighbors[] = [
        'word' => $row['word'],
        'distance' => (float)$row['distance'],
        'embedding' => $embedding_arr
    ];
}
pg_free_result($result);
pg_close($conn);

// Return JSON including input words, their embeddings, average embedding, and neighbors with embeddings
json_response([
    'input' => [
        'words' => $words,
        'embeddings' => $input_embeddings,
        'average_embedding' => $average
    ],
    'nearest' => $neighbors
]);
