<?php
require_once('utils.php');
require_once('response.php');

$conn = db_connect();
validate_api_key($conn);

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
$query = "SELECT word, embedding, embedding <-> $1 AS distance FROM wordembeddings ORDER BY embedding <-> $1 LIMIT 5";
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
