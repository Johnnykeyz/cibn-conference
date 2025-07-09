<?php

// Add this debugging section at the very top, right after the opening PHP tag
error_log("=== DEBUG INFO ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));
error_log("POST data: " . print_r($_POST, true));
error_log("Raw input: " . file_get_contents('php://input'));
error_log("All headers: " . print_r(getallheaders(), true));
error_log("=== END DEBUG ===");

header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$host = "localhost";
$user = "root";
$password = "";
$dbname = "tetris_db";

try {
    // Validate input
    if (!isset($_POST['unique_id'])) {
        throw new Exception("Missing Unique ID");
    }
    if (!isset($_POST['score'])) {
        throw new Exception("Missing score");
    }
    
    $unique_id = trim($_POST['unique_id']);
    $score = intval($_POST['score']);
    
    // Validate unique_id
    if (empty($unique_id)) {
        throw new Exception("Unique ID cannot be empty");
    }
    
    if (strlen($unique_id) > 20) {
        throw new Exception("Unique ID must be 20 characters or less");
    }
    
    // Validate score
    if ($score < 0) {
        throw new Exception("Invalid score");
    }
    
    // Connect to database
    $conn = new mysqli($host, $user, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    
    $conn->set_charset("utf8");
    
    // Check if user exists in database
    $checkSql = "SELECT score FROM leaderboard WHERE unique_id = ? ORDER BY score DESC LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $unique_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    // If user doesn't exist, throw error
    if ($checkResult->num_rows == 0) {
        throw new Exception("Unique ID not found in database. Please register first.");
    }
    
    // User exists, check if new score is higher
    $existingScore = $checkResult->fetch_assoc()['score'];
    
    if ($score <= $existingScore) {
        echo "Score not submitted - you already have a higher score of " . number_format($existingScore);
    } else {
        // New score is higher, update the record
        $updateSql = "UPDATE leaderboard SET score = ?, created_at = NOW() WHERE unique_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("is", $score, $unique_id);
        
        if ($updateStmt->execute()) {
            echo "Score updated successfully! New high score: " . number_format($score);
        } else {
            throw new Exception("Failed to update score in database");
        }
        
        $updateStmt->close();
    }
    
    $checkStmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo "Error: " . $e->getMessage();
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo "Database error occurred";
    error_log("Database error: " . $e->getMessage());
}

/*
header('Content-Type: text/plain; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Simple debugging - just echo what we receive
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "POST data received:\n";
print_r($_POST);
echo "\nRaw input:\n";
echo file_get_contents('php://input');
echo "\nEnd of debug info\n";

// Basic validation
if (!isset($_POST['unique_id'])) {
    http_response_code(400);
    echo "Error: Missing Unique ID\n";
    echo "Available POST keys: " . implode(', ', array_keys($_POST));
    exit;
}

if (!isset($_POST['score'])) {
    http_response_code(400);
    echo "Error: Missing score\n";
    echo "Available POST keys: " . implode(', ', array_keys($_POST));
    exit;
}

echo "Success: Received unique_id=" . $_POST['unique_id'] . " and score=" . $_POST['score'];
*/
?>
