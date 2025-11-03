<?php

// Load DB config and open connection
function db_connect() {
    $config = include(__DIR__ . '/../include/db_config.php');
    $conn_string = sprintf(
        "host=%s port=%d dbname=%s user=%s password=%s",
        $config['host'],
        $config['port'],
        $config['dbname'],
        $config['user'],
        $config['password']
    );
    $conn = pg_connect($conn_string);
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
    return $conn;
}

// Fetch embedding vector by word
function fetch_embedding($conn, $word) {
    $query = "SELECT embedding FROM wordembeddings WHERE word = $1";
    $result = pg_query_params($conn, $query, [$word]);
    if ($row = pg_fetch_assoc($result)) {
        $embedding_str = str_replace(['[', ']'], '', $row['embedding']);
        return array_map('floatval', explode(',', $embedding_str));
    }
    return array_fill(0, 300, 0); // zero vector if not found
}

// Add two vectors elementwise
function add_arrays($a, $b) {
    $n = count($a);
    $sum = [];
    for ($i = 0; $i < $n; $i++) {
        $sum[$i] = $a[$i] + $b[$i];
    }
    return $sum;
}

// Scale vector by scalar
function scale_array($scalar, $arr) {
    return array_map(fn($x) => $x * $scalar, $arr);
}

// Compute average of multiple embeddings
function average_embeddings($embeddings) {
    $count = count($embeddings);
    if ($count === 0) return array_fill(0, 300, 0);
    $sum = $embeddings[0];
    for ($i = 1; $i < $count; $i++) {
        $sum = add_arrays($sum, $embeddings[$i]);
    }
    return scale_array(1 / $count, $sum);
}

function generate_vector_image_base64(array $vector, string $label): ?string {
    $temp_vector = tempnam(sys_get_temp_dir(), 'vec');
    file_put_contents($temp_vector, implode(',', $vector));

    $cmd = escapeshellcmd("python3 /var/www/wordchef.app/html/scripts/generate_image.py " . escapeshellarg($temp_vector) . " " . escapeshellarg($label));
    $output = shell_exec($cmd);

    // Expect Python to print a base64 string directly (stdout)
    $base64 = trim($output);

    unlink($temp_vector);

    return $base64 !== '' ? $base64 : null;
}

function validate_api_key($conn) {
    // Try to get API key from 'X-API-Key' header (via $_SERVER), or fallback to GET param
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $api_key = $_SERVER['HTTP_X_API_KEY'];
    } else if (isset($_GET['api_key'])) {
        $api_key = $_GET['api_key'];
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Missing API key']);
        exit;
    }

    // Now validate against database
    $query = "SELECT 1 FROM api_keys WHERE api_key = $1 AND active = TRUE";
    $result = pg_query_params($conn, $query, [$api_key]);

    if (!$result || pg_num_rows($result) === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }
}


?>