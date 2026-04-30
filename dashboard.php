<?php
require_once 'inc/load.php';

// בדיקת הרשאות
if (strlen($cdr_user_name) > 0) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$dbh = dbConnect();

// קבלת טווח תאריכים מהבקשה או ברירת מחדל לחודש האחרון
$dateRange = isset($_GET['range']) ? $_GET['range'] : 'month';
$endDate = date('Y-m-d H:i:s');
$startDate = date('Y-m-d H:i:s', strtotime('-1 month'));

switch ($dateRange) {
    case 'today':
        $startDate = date('Y-m-d 00:00:00');
        break;
    case 'week':
        $startDate = date('Y-m-d H:i:s', strtotime('-1 week'));
        break;
    case '3months':
        $startDate = date('Y-m-d H:i:s', strtotime('-3 months'));
        break;
    case 'year':
        $startDate = date('Y-m-d H:i:s', strtotime('-1 year'));
        break;
}

// שליפת סטטיסטיקות כלליות
$statsQuery = "
    SELECT 
        COUNT(*) as total_calls,
        COUNT(CASE WHEN disposition = 'ANSWERED' THEN 1 END) as answered_calls,
        COUNT(CASE WHEN disposition = 'NO ANSWER' THEN 1 END) as missed_calls,
        COUNT(CASE WHEN disposition = 'BUSY' THEN 1 END) as busy_calls,
        COUNT(CASE WHEN disposition = 'FAILED' THEN 1 END) as failed_calls,
        AVG(CASE WHEN disposition = 'ANSWERED' THEN billsec END) as avg_duration,
        MAX(billsec) as max_duration,
        SUM(billsec) as total_duration
    FROM " . Config::get('db.table') . "
    WHERE calldate BETWEEN :startDate AND :endDate
";

$stmt = $dbh->prepare($statsQuery);
$stmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// חישוב אחוז מענה
$answerRate = $stats['total_calls'] > 0 ? 
    round(($stats['answered_calls'] / $stats['total_calls']) * 100, 1) : 0;

// שליפת סטטיסטיקות לתקופה הקודמת להשוואה
$prevEndDate = $startDate;
$prevStartDate = date('Y-m-d H:i:s', strtotime($startDate . ' -' . (strtotime($endDate) - strtotime($startDate)) . ' seconds'));

$prevStatsQuery = str_replace(
    'WHERE calldate BETWEEN :startDate AND :endDate',
    'WHERE calldate BETWEEN :prevStartDate AND :prevEndDate',
    $statsQuery
);

$prevStmt = $dbh->prepare($prevStatsQuery);
$prevStmt->execute(['prevStartDate' => $prevStartDate, 'prevEndDate' => $prevEndDate]);
$prevStats = $prevStmt->fetch(PDO::FETCH_ASSOC);

// חישוב אחוזי שינוי
$callsChange = $prevStats['total_calls'] > 0 ? 
    round((($stats['total_calls'] - $prevStats['total_calls']) / $prevStats['total_calls']) * 100, 1) : 0;
$durationChange = $prevStats['avg_duration'] > 0 ? 
    round((($stats['avg_duration'] - $prevStats['avg_duration']) / $prevStats['avg_duration']) * 100, 1) : 0;
$answerRateChange = $prevStats['answered_calls'] > 0 ? 
    round(($answerRate - (($prevStats['answered_calls'] / $prevStats['total_calls']) * 100)), 1) : 0;

// שליפת שיחות פעילות (שיחות מהדקה האחרונה)
$activeCallsQuery = "
    SELECT COUNT(*) as active_calls 
    FROM " . Config::get('db.table') . "
    WHERE calldate >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    AND disposition = 'ANSWERED'
";
$activeStmt = $dbh->query($activeCallsQuery);
$activeCalls = $activeStmt->fetchColumn();

// שליפת מקסימום שיחות במקביל להיום
$maxConcurrentQuery = "
    SELECT MAX(concurrent_calls) as max_concurrent
    FROM (
        SELECT 
            calldate,
            (SELECT COUNT(*) 
             FROM " . Config::get('db.table') . " t2 
             WHERE DATE(t2.calldate) = DATE(t1.calldate)
             AND t2.calldate <= t1.calldate 
             AND DATE_ADD(t2.calldate, INTERVAL t2.duration SECOND) >= t1.calldate
            ) as concurrent_calls
        FROM " . Config::get('db.table') . " t1
        WHERE DATE(calldate) = CURDATE()
    ) as concurrency_table
";
$maxStmt = $dbh->query($maxConcurrentQuery);
$maxConcurrent = $maxStmt->fetchColumn() ?: 0;

// שליפת נתונים לגרף שיחות לפי שעה
$hourlyQuery = "
    SELECT 
        HOUR(calldate) as hour,
        COUNT(CASE WHEN LOCATE('/', channel) > 0 AND SUBSTRING_INDEX(channel, '/', 1) = 'SIP' THEN 1 END) as incoming_calls,
        COUNT(CASE WHEN LOCATE('/', channel) > 0 AND SUBSTRING_INDEX(channel, '/', 1) != 'SIP' THEN 1 END) as outgoing_calls
    FROM " . Config::get('db.table') . "
    WHERE calldate BETWEEN :startDate AND :endDate
    GROUP BY HOUR(calldate)
    ORDER BY hour
";

$hourlyStmt = $dbh->prepare($hourlyQuery);
$hourlyStmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
$hourlyData = $hourlyStmt->fetchAll(PDO::FETCH_ASSOC);

// שליפת נתונים למפת החום
$heatmapQuery = "
    SELECT 
        DAYOFWEEK(calldate) - 1 as day_of_week,
        HOUR(calldate) as hour,
        COUNT(*) as call_count
    FROM " . Config::get('db.table') . "
    WHERE calldate BETWEEN :startDate AND :endDate
    GROUP BY DAYOFWEEK(calldate), HOUR(calldate)
";

$heatmapStmt = $dbh->prepare($heatmapQuery);
$heatmapStmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
$heatmapData = $heatmapStmt->fetchAll(PDO::FETCH_ASSOC);

// חישוב ערכי מקסימום למפת החום
$maxHeatmapValue = 0;
foreach ($heatmapData as $cell) {
    if ($cell['call_count'] > $maxHeatmapValue) {
        $maxHeatmapValue = $cell['call_count'];
    }
}

// שליפת שיחות אחרונות
$recentCallsQuery = "
    SELECT 
        calldate,
        src,
        dst,
        disposition,
        billsec,
        uniqueid,
        " . Config::get('system.column_name') . " as recordingfile,
        CASE 
            WHEN LOCATE('/', channel) > 0 AND SUBSTRING_INDEX(channel, '/', 1) = 'SIP' THEN 'incoming'
            ELSE 'outgoing'
        END as direction
    FROM " . Config::get('db.table') . "
    ORDER BY calldate DESC
    LIMIT 20
";

$recentStmt = $dbh->query($recentCallsQuery);
$recentCalls = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// שליפת נתונים להתפלגות משך שיחות
$durationDistQuery = "
    SELECT 
        CASE 
            WHEN billsec BETWEEN 0 AND 60 THEN '0-1'
            WHEN billsec BETWEEN 61 AND 120 THEN '1-2'
            WHEN billsec BETWEEN 121 AND 180 THEN '2-3'
            WHEN billsec BETWEEN 181 AND 300 THEN '3-5'
            WHEN billsec BETWEEN 301 AND 600 THEN '5-10'
            ELSE '10+'
        END as duration_range,
        COUNT(*) as count
    FROM " . Config::get('db.table') . "
    WHERE calldate BETWEEN :startDate AND :endDate
    AND disposition = 'ANSWERED'
    GROUP BY duration_range
    ORDER BY 
        CASE duration_range
            WHEN '0-1' THEN 1
            WHEN '1-2' THEN 2
            WHEN '2-3' THEN 3
            WHEN '3-5' THEN 4
            WHEN '5-10' THEN 5
            WHEN '10+' THEN 6
        END
";

$durationStmt = $dbh->prepare($durationDistQuery);
$durationStmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
$durationDist = $durationStmt->fetchAll(PDO::FETCH_ASSOC);

// חישוב זמן המתנה ממוצע (זמן עד מענה)
$waitTimeQuery = "
    SELECT AVG(duration - billsec) as avg_wait_time
    FROM " . Config::get('db.table') . "
    WHERE calldate BETWEEN :startDate AND :endDate
    AND disposition = 'ANSWERED'
    AND duration > billsec
";

$waitStmt = $dbh->prepare($waitTimeQuery);
$waitStmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
$avgWaitTime = round($waitStmt->fetchColumn() ?: 0);

// חישוב SLA - אחוז שיחות שנענו תוך 20 שניות
$slaQuery = "
    SELECT 
        COUNT(CASE WHEN (duration - billsec) <= 20 THEN 1 END) as within_sla,
        COUNT(*) as total_answered
    FROM " . Config::get('db.table') . "
    WHERE calldate BETWEEN :startDate AND :endDate
    AND disposition = 'ANSWERED'
    AND duration > billsec
";

$slaStmt = $dbh->prepare($slaQuery);
$slaStmt->execute(['startDate' => $startDate, 'endDate' => $endDate]);
$slaData = $slaStmt->fetch(PDO::FETCH_ASSOC);
$slaPercentage = $slaData['total_answered'] > 0 ? 
    round(($slaData['within_sla'] / $slaData['total_answered']) * 100) : 0;

// סגירת חיבור
$dbh = null;

// כלילת תבנית הדשבורד
require_once 'templates/dashboard_template.php';
?>