<?php
require_once('utils.php');
require_once('response.php');

$conn = db_connect();
validate_api_key($conn);

// Parse vector (as comma-separated values) and label
$vector_str = $_POST['vector'] ?? ($_GET['vector'] ?? '');
$label = $_POST['label'] ?? ($_GET['label'] ?? 'vector');

if ($vector_str === '') {
    json_response(['error' => 'Missing parameter: vector'], 400);
}

// Convert vector string to array of floats
$vector = array_map('floatval', explode(',', $vector_str));

$base64_img = generate_vector_image_base64($vector, $label);

if (!$base64_img) {
    json_response(['error' => 'Image generation failed'], 500);
}

json_response([
    'label' => $label,
    'image_base64' => $base64_img
]);
?>
