<?php
// api/dashboard-live.php
require_once '../inc/load.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache');

// בדיקת הרשאות
if (strlen($cdr_user_name) > 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$dbh = dbConnect();

// שליפת שיחות פעילות
$activeCallsQuery = "
    SELECT COUNT(*) as active_calls 
    FROM " . Config::get('db.table') . "
    WHERE calldate >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    AND disposition = 'ANSWERED'
";

try {
    $stmt = $dbh->query($activeCallsQuery);
    $activeCalls = $stmt->fetchColumn();
    
    // שליפת נתונים נוספים לעדכון בזמן אמת
    $realtimeQuery = "
        SELECT 
            COUNT(*) as calls_last_5min,
            AVG(billsec) as avg_duration_5min,
            COUNT(CASE WHEN disposition = 'ANSWERED' THEN 1 END) as answered_5min
        FROM " . Config::get('db.table') . "
        WHERE calldate >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ";
    
    $realtimeStmt = $dbh->query($realtimeQuery);
    $realtimeData = $realtimeStmt->fetch(PDO::FETCH_ASSOC);
    
    // שליפת שיחה אחרונה
    $lastCallQuery = "
        SELECT 
            calldate,
            src,
            dst,
            disposition,
            billsec
        FROM " . Config::get('db.table') . "
        ORDER BY calldate DESC
        LIMIT 1
    ";
    
    $lastCallStmt = $dbh->query($lastCallQuery);
    $lastCall = $lastCallStmt->fetch(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'activeCalls' => (int)$activeCalls,
        'callsLast5Min' => (int)$realtimeData['calls_last_5min'],
        'avgDuration5Min' => round($realtimeData['avg_duration_5min']),
        'answered5Min' => (int)$realtimeData['answered_5min'],
        'lastCall' => $lastCall ? [
            'time' => $lastCall['calldate'],
            'from' => $lastCall['src'],
            'to' => $lastCall['dst'],
            'status' => $lastCall['disposition'],
            'duration' => $lastCall['billsec']
        ] : null
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

$dbh = null;
?>