<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once('utils.php');
require_once('response.php');

$conn = db_connect();
$api_key = validate_api_key($conn);
$api_rate_limit = fetch_rate_limit($conn, $api_key);
check_rate_limit($conn, $api_key, $api_rate_limit);

$words_str = $_POST['words'] ?? ($_GET['words'] ?? '');
$label = $_POST['label'] ?? ($_GET['label'] ?? 'vector');

if ($words_str === '') {
    json_response(['error' => 'Missing parameter: words'], 400);
}

// Split input words by comma or space if multiple words supported
$words = preg_split('/[\s,]+/', trim($words_str));

// Lookup embedding(s) for words from DB
$embeddings = [];
foreach ($words as $word) {
    $embedding = lookup_embedding($conn, $word);
    if ($embedding === null) {
        json_response(['error' => "Embedding not found for word: $word"], 404);
    }
    $embeddings[] = $embedding;
}

// For simplicity, let's assume we only handle one word for now:
$vector = $embeddings[0];

$base64_img = generate_vector_image_base64($vector, $label);

if (!$base64_img) {
    error_log("Image generation failed for label: $label, vector length: " . count($vector));
    json_response(['error' => 'Image generation failed'], 500);
}

json_response([
    'label' => $label,
    'image_base64' => $base64_img
]);

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

?>
