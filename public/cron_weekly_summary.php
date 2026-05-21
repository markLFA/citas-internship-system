<?php
require 'db.php';

$sql = "
INSERT INTO weekly_hours_summary (intern_id, week_start, total_hours, avg_hours)
SELECT 
    intern_id,
    DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AS week_start,
    SUM(hours),
    AVG(hours)
FROM time_logs
WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY intern_id
";

$pdo->exec($sql);

echo "Weekly summary generated successfully";