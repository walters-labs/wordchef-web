<?php
require_once('utils.php');
require_once('response.php');

header('Content-Type: application/json');

$conn = db_connect();

// Validate admin API key (must have admin = TRUE)
function validate_admin_api_key($conn) {
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $api_key = $_SERVER['HTTP_X_API_KEY'];
    } else if (isset($_GET['api_key'])) {
        $api_key = $_GET['api_key'];
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Missing API key']);
        exit;
    }

    $query = "SELECT admin FROM api_keys WHERE api_key = $1 AND active = TRUE";
    $result = pg_query_params($conn, $query, [$api_key]);
    if (!$result || pg_num_rows($result) === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid API key']);
        exit;
    }

    $row = pg_fetch_assoc($result);
    if (!$row['admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin privileges required']);
        exit;
    }
}

validate_admin_api_key($conn);

// Helper: parse JSON input body
function get_json_input() {
    $raw = file_get_contents("php://input");
    return json_decode($raw, true);
}

$method = $_SERVER['REQUEST_METHOD'];

// --- Handle HTTP methods ---
switch ($method) {
    case 'GET':
        // List all keys (hide full api_key, show only prefix)
        $result = pg_query($conn, "SELECT id, LEFT(api_key, 12) || '…' AS key_prefix, description, active, admin, created_at FROM api_keys ORDER BY created_at DESC");
        $keys = [];
        while ($row = pg_fetch_assoc($result)) {
            $keys[] = $row;
        }
        echo json_encode($keys);
        break;

    case 'POST':
        $data = get_json_input();
        $desc = $data['description'] ?? '';
        $is_admin = !empty($data['admin']) ? 1 : 0;

        $key = bin2hex(random_bytes(32));

        $query = "INSERT INTO api_keys (api_key, description, active, admin) VALUES ($1, $2, TRUE, $3) RETURNING id, api_key, description, active, admin, created_at";
        $result = pg_query_params($conn, $query, [$key, $desc, $is_admin]);
        if (!$result) {
            $error = pg_last_error($conn);
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create API key', 'details' => $error]);
            exit;
        }

        $new_key = pg_fetch_assoc($result);
        echo json_encode($new_key);
        break;

    case 'PATCH':
        $data = get_json_input();
        $id = $data['id'] ?? null;
        $active = isset($data['active']) ? ($data['active'] ? 1 : 0) : null;

        if (!$id || !is_int($id) || !is_int($active)) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing or invalid parameters']);
            exit;
        }

        $query = "UPDATE api_keys SET active = $1 WHERE id = $2 RETURNING id, description, active, admin, created_at";
        $result = pg_query_params($conn, $query, [$active, $id]);
        if (!$result || pg_num_rows($result) === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'API key not found']);
            exit;
        }

        $updated_key = pg_fetch_assoc($result);
        echo json_encode($updated_key);
        break;

    case 'DELETE':
        // Delete key by id (from JSON body)
        $data = get_json_input();
        $id = $data['id'] ?? null;
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing id parameter']);
            exit;
        }

        $query = "DELETE FROM api_keys WHERE id = $1 RETURNING id";
        $result = pg_query_params($conn, $query, [$id]);
        if (!$result || pg_num_rows($result) === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'API key not found']);
            exit;
        }

        echo json_encode(['deleted_id' => $id]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
