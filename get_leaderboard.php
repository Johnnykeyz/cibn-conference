<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    // Connect to database
    $conn = new mysqli($host, $user, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }
    
    $conn->set_charset("utf8");
    
    // Get top 10 scores
    $sql = "SELECT unique_id, score, created_at FROM leaderboard ORDER BY score DESC LIMIT 10";
    $result = $conn->query($sql);
    
    $scores = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $scores[] = [
                'unique_id' => $row['unique_id'],
                'score' => intval($row['score']),
                'date' => $row['created_at']
            ];
        }
    }
    
    $conn->close();
    
    echo json_encode($scores);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
    error_log("Database error: " . $e->getMessage());
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error occurred']);
    error_log("Database error: " . $e->getMessage());
}
?>