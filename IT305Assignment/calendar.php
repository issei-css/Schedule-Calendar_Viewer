<?php
// calendar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: calendar.php');
    exit;
}

// Get current date
$current_date = new DateTime();
$year = $_GET['year'] ?? $current_date->format('Y');
$month = $_GET['month'] ?? $current_date->format('m');
$week = $_GET['week'] ?? null;
$day = $_GET['day'] ?? null;
$view = $_GET['view'] ?? 'month'; // month, week, day

// Fetch all attendance records
$url = FIREBASE_DB_URL . '/attendances.json' . (FIREBASE_DB_AUTH ? '?auth='.FIREBASE_DB_AUTH : '');
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$records = json_decode($response, true) ?? [];

// Organize records by date
$events_by_date = [];
foreach ($records as $id => $record) {
    if (!empty($record['attendance_date'])) {
        $date = $record['attendance_date'];
        if (!isset($events_by_date[$date])) {
            $events_by_date[$date] = [];
        }
        $events_by_date[$date][] = $record;
    }
}

// Helper function to get attendance color
function getAttendanceColor($attendance) {
    if ($attendance === 'Present') return '#4caf50';
    if ($attendance === 'Late') return '#ff9800';
    if ($attendance === 'Absent') return '#f44336';
    return '#999';
}

// Helper function to get attendance text color
function getAttendanceTextColor($attendance) {
    if ($attendance === 'Present') return '#2e7d32';
    if ($attendance === 'Late') return '#e65100';
    if ($attendance === 'Absent') return '#c62828';
    return '#333';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance Calendar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #5a9bd4, #7ac8e3);
            min-height: 100vh;
            padding: 20px;
        }

        .calendar-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            padding: 30px;
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        h1 {
            color: #333;
            font-size: 28px;
        }

        .view-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .view-btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            background: #f0f0f0;
            color: #333;
        }

        .view-btn.active {
            background: #5a9bd4;
            color: white;
        }

        .view-btn:hover {
            background: #468cc1;
            color: white;
        }

        .nav-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .nav-btn {
            padding: 8px 14px;
            background: #5a9bd4;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .nav-btn:hover {
            background: #468cc1;
        }

        .date-display {
            color: #333;
            font-weight: 600;
            min-width: 200px;
            text-align: center;
        }

        .top-links {
            display: flex;
            gap: 10px;
        }

        .back-btn {
            padding: 8px 14px;
            background: #888;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.3s ease;
        }

        .back-btn:hover {
            background: #777;
        }

        /* Month View */
        .month-view {
            display: none;
        }

        .month-view.active {
            display: block;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #ddd;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .day-header {
            background: #5a9bd4;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 600;
        }

        .calendar-day {
            background: white;
            min-height: 120px;
            padding: 10px;
            position: relative;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        .calendar-day.other-month {
            background: #f9f9f9;
            color: #999;
        }

        .calendar-day.today {
            background: #e3f2fd;
        }

        .day-number {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .day-events {
            font-size: 11px;
            max-height: 100px;
            overflow-y: auto;
        }

        .day-event {
            background: #f0f0f0;
            padding: 4px;
            margin: 2px 0;
            border-radius: 3px;
            border-left: 3px solid #5a9bd4;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .day-event:hover {
            background: #e0e0e0;
            transform: translateX(2px);
        }

        .event-faculty {
            font-weight: 600;
            color: #333;
        }

        .event-status {
            font-size: 10px;
            font-weight: 600;
        }

        /* Week View */
        .week-view {
            display: none;
        }

        .week-view.active {
            display: block;
        }

        .week-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #ddd;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .week-day-header {
            background: #5a9bd4;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 600;
        }

        .week-day-content {
            background: white;
            min-height: 200px;
            padding: 15px;
            border-right: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }

        .week-day-content.other-month {
            background: #f9f9f9;
        }

        .week-day-content.today {
            background: #e3f2fd;
        }

        .week-event {
            background: #f0f0f0;
            padding: 8px;
            margin: 5px 0;
            border-radius: 4px;
            border-left: 4px solid #5a9bd4;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .week-event:hover {
            background: #e0e0e0;
        }

        /* Day View */
        .day-view {
            display: none;
        }

        .day-view.active {
            display: block;
        }

        .day-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .day-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .day-events-list {
            display: grid;
            gap: 15px;
        }

        .event-card {
            background: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #5a9bd4;
            transition: all 0.3s ease;
        }

        .event-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .event-card.present {
            border-left-color: #4caf50;
        }

        .event-card.late {
            border-left-color: #ff9800;
        }

        .event-card.absent {
            border-left-color: #f44336;
        }

        .event-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
        }

        .event-label {
            font-weight: 600;
            color: #666;
            min-width: 120px;
        }

        .event-value {
            color: #333;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            margin-top: 5px;
        }

        .status-present {
            background: #c8e6c9;
            color: #2e7d32;
        }

        .status-late {
            background: #ffe0b2;
            color: #e65100;
        }

        .status-absent {
            background: #ffcdd2;
            color: #c62828;
        }

        .no-events {
            text-align: center;
            color: #999;
            padding: 40px 20px;
            font-size: 16px;
        }

        .legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
        }

        @media (max-width: 768px) {
            .calendar-container {
                padding: 15px;
            }

            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
            }

            .day-header {
                padding: 8px;
                font-size: 12px;
            }

            .calendar-day {
                min-height: 80px;
                padding: 5px;
            }

            .day-number {
                font-size: 12px;
            }

            .day-event {
                font-size: 9px;
            }
        }
    </style>
</head>
<body>
    <div class="calendar-container">
        <div class="calendar-header">
            <h1>📅 Attendance Calendar</h1>
            <div class="view-controls">
                <a href="calendar.php?view=month&year=<?= $year ?>&month=<?= $month ?>" class="view-btn <?= $view === 'month' ? 'active' : '' ?>">Month</a>
                <a href="calendar.php?view=week&year=<?= $year ?>&month=<?= $month ?>&week=<?= $week ?>" class="view-btn <?= $view === 'week' ? 'active' : '' ?>">Week</a>
                <a href="calendar.php?view=day&year=<?= $year ?>&month=<?= $month ?>&day=<?= $day ?>" class="view-btn <?= $view === 'day' ? 'active' : '' ?>">Day</a>
            </div>
            <div class="top-links">
                <a href="list.php" class="back-btn">📚 Back to Records</a>
            </div>
        </div>

        <!-- MONTH VIEW -->
        <div class="month-view <?= $view === 'month' ? 'active' : '' ?>">
            <div class="nav-controls" style="margin-bottom: 20px;">
                <a href="calendar.php?view=month&year=<?= ($month == 1 ? $year - 1 : $year) ?>&month=<?= ($month == 1 ? 12 : $month - 1) ?>" class="nav-btn">← Prev</a>
                <span class="date-display"><?= date('F Y', strtotime("$year-$month-01")) ?></span>
                <a href="calendar.php?view=month&year=<?= ($month == 12 ? $year + 1 : $year) ?>&month=<?= ($month == 12 ? 1 : $month + 1) ?>" class="nav-btn">Next →</a>
            </div>

            <div class="calendar-grid">
                <?php
                $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach ($days as $d) {
                    echo "<div class='day-header'>$d</div>";
                }

                $first_day = new DateTime("$year-$month-01");
                $last_day = new DateTime("$year-$month-" . date('t', strtotime("$year-$month-01")));
                $start_date = clone $first_day;
                $start_date->modify('-' . ($first_day->format('w')) . ' days');

                $current = clone $start_date;
                for ($i = 0; $i < 42; $i++) {
                    $date_str = $current->format('Y-m-d');
                    $is_other_month = $current->format('m') != $month;
                    $is_today = $date_str === date('Y-m-d');

                    $day_class = 'calendar-day';
                    if ($is_other_month) $day_class .= ' other-month';
                    if ($is_today) $day_class .= ' today';

                    echo "<div class='$day_class'>";
                    echo "<div class='day-number'>" . $current->format('d') . "</div>";
                    echo "<div class='day-events'>";

                    if (isset($events_by_date[$date_str])) {
                        foreach ($events_by_date[$date_str] as $event) {
                            $status = $event['faculty_attendance'] ?? 'Unknown';
                            $color = getAttendanceColor($status);
                            $text_color = getAttendanceTextColor($status);
                            echo "<div class='day-event' style='border-left-color: $color; background: rgba(" . hexToRgb($color) . ", 0.1);'>";
                            echo "<div class='event-faculty'>" . htmlspecialchars($event['faculty_name'] ?? 'N/A') . "</div>";
                            echo "<div class='event-status' style='color: $text_color;'>$status</div>";
                            echo "</div>";
                        }
                    }
                    echo "</div>";
                    echo "</div>";

                    $current->modify('+1 day');
                }
                ?>
            </div>

            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color" style="background: #4caf50;"></div>
                    <span>Present</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #ff9800;"></div>
                    <span>Late</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: #f44336;"></div>
                    <span>Absent</span>
                </div>
            </div>
        </div>

        <!-- WEEK VIEW -->
        <div class="week-view <?= $view === 'week' ? 'active' : '' ?>">
            <?php
            $week = (int)($week ?? 1);
            $first_day = new DateTime("$year-$month-01");
            $week_start = clone $first_day;
            $week_start->modify('-' . (int)$first_day->format('w') . ' days');
            $week_start->modify('+' . (($week - 1) * 7) . ' days');

            $prev_week = max(1, $week - 1);
            $next_week = $week + 1;
            ?>
            <div class="nav-controls" style="margin-bottom: 20px;">
                <a href="calendar.php?view=week&year=<?= $year ?>&month=<?= $month ?>&week=<?= $prev_week ?>" class="nav-btn">← Prev Week</a>
                <span class="date-display"><?= $week_start->format('M d, Y') ?> - <?= (clone $week_start)->modify('+6 days')->format('M d, Y') ?></span>
                <a href="calendar.php?view=week&year=<?= $year ?>&month=<?= $month ?>&week=<?= $next_week ?>" class="nav-btn">Next Week →</a>
            </div>

            <div class="week-grid">
                <?php
                $week_start = clone $first_day;
                $week_start->modify('-' . $first_day->format('w') . ' days');
                $week_start->modify('+' . (($week - 1) * 7) . ' days');

                $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                foreach ($days as $d) {
                    echo "<div class='week-day-header'>$d</div>";
                }

                $current = clone $week_start;
                for ($i = 0; $i < 7; $i++) {
                    $date_str = $current->format('Y-m-d');
                    $is_today = $date_str === date('Y-m-d');
                    $day_class = 'week-day-content';
                    if ($current->format('m') != $month) $day_class .= ' other-month';
                    if ($is_today) $day_class .= ' today';

                    echo "<div class='$day_class'>";
                    echo "<strong>" . $current->format('d') . "</strong><br>";

                    if (isset($events_by_date[$date_str])) {
                        foreach ($events_by_date[$date_str] as $event) {
                            $status = $event['faculty_attendance'] ?? 'Unknown';
                            echo "<div class='week-event'>";
                            echo "<strong>" . htmlspecialchars($event['faculty_name'] ?? 'N/A') . "</strong><br>";
                            echo htmlspecialchars($event['subject_code'] ?? 'N/A') . " - " . $status;
                            echo "</div>";
                        }
                    }

                    echo "</div>";
                    $current->modify('+1 day');
                }
                ?>
            </div>
        </div>

        <!-- DAY VIEW -->
        <div class="day-view <?= $view === 'day' ? 'active' : '' ?>">
            <?php
            $selected_day = (int)($day ?? date('d'));
            if ($selected_day < 1) $selected_day = 1;
            if ($selected_day > 31) $selected_day = 31;
            
            $date_str = sprintf("%04d-%02d-%02d", (int)$year, (int)$month, $selected_day);
            try {
                $selected_date = new DateTime($date_str);
            } catch (Exception $e) {
                $selected_date = new DateTime();
                $date_str = $selected_date->format('Y-m-d');
            }
            ?>
            <div class="nav-controls" style="margin-bottom: 20px;">
                <?php
                $prev_date = clone $selected_date;
                $prev_date->modify('-1 day');
                $next_date = clone $selected_date;
                $next_date->modify('+1 day');
                ?>
                <a href="calendar.php?view=day&year=<?= $prev_date->format('Y') ?>&month=<?= $prev_date->format('m') ?>&day=<?= $prev_date->format('d') ?>" class="nav-btn">← Prev Day</a>
                <span class="date-display"><?= $selected_date->format('l, F d, Y') ?></span>
                <a href="calendar.php?view=day&year=<?= $next_date->format('Y') ?>&month=<?= $next_date->format('m') ?>&day=<?= $next_date->format('d') ?>" class="nav-btn">Next Day →</a>
            </div>

            <div class="day-container">
                <div class="day-title"><?= $selected_date->format('l, F d, Y') ?></div>

                <?php
                if (isset($events_by_date[$date_str]) && count($events_by_date[$date_str]) > 0) {
                    echo "<div class='day-events-list'>";
                    foreach ($events_by_date[$date_str] as $event) {
                        $status = $event['faculty_attendance'] ?? 'Unknown';
                        $status_class = strtolower($status);
                        echo "<div class='event-card $status_class'>";
                        echo "<div class='event-row'>";
                        echo "<span class='event-label'>Faculty:</span>";
                        echo "<span class='event-value'>" . htmlspecialchars($event['faculty_name'] ?? 'N/A') . "</span>";
                        echo "</div>";
                        echo "<div class='event-row'>";
                        echo "<span class='event-label'>Subject:</span>";
                        echo "<span class='event-value'>" . htmlspecialchars($event['subject_code'] ?? 'N/A') . "</span>";
                        echo "</div>";
                        echo "<div class='event-row'>";
                        echo "<span class='event-label'>Section:</span>";
                        echo "<span class='event-value'>" . htmlspecialchars($event['section'] ?? 'N/A') . "</span>";
                        echo "</div>";
                        echo "<div class='event-row'>";
                        echo "<span class='event-label'>Time:</span>";
                        echo "<span class='event-value'>" . htmlspecialchars($event['time_from'] ?? 'N/A') . " - " . htmlspecialchars($event['time_to'] ?? 'N/A') . "</span>";
                        echo "</div>";
                        echo "<div class='event-row'>";
                        echo "<span class='event-label'>Class Mode:</span>";
                        echo "<span class='event-value'>" . htmlspecialchars($event['room_or_mode'] ?? 'N/A') . "</span>";
                        echo "</div>";
                        echo "<div class='event-row'>";
                        echo "<span class='event-label'>Modality:</span>";
                        echo "<span class='event-value'>" . htmlspecialchars($event['learning_modality'] ?? 'N/A') . "</span>";
                        echo "</div>";
                        if (!empty($event['online_link'])) {
                            echo "<div class='event-row'>";
                            echo "<span class='event-label'>Online Link:</span>";
                            echo "<span class='event-value'><a href='" . htmlspecialchars($event['online_link']) . "' target='_blank'>Join Class</a></span>";
                            echo "</div>";
                        }
                        echo "<div class='event-row'>";
                        echo "<span class='event-label'>Dress Code:</span>";
                        echo "<span class='event-value'>" . htmlspecialchars($event['dress_code'] ?? 'N/A') . "</span>";
                        echo "</div>";
                        echo "<div>";
                        echo "<span class='event-label'>Attendance:</span>";
                        $status_class_map = [
                            'Present' => 'status-present',
                            'Late' => 'status-late',
                            'Absent' => 'status-absent'
                        ];
                        $badge_class = $status_class_map[$status] ?? 'status-present';
                        echo "<span class='status-badge $badge_class'>$status</span>";
                        echo "</div>";
                        if (!empty($event['remark'])) {
                            echo "<div class='event-row'>";
                            echo "<span class='event-label'>Remark:</span>";
                            echo "<span class='event-value'>" . htmlspecialchars($event['remark']) . "</span>";
                            echo "</div>";
                        }
                        echo "</div>";
                    }
                    echo "</div>";
                } else {
                    echo "<div class='no-events'>No attendance records for this day</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        // Helper function to convert hex to RGB (for styling)
    </script>
</body>
</html>

<?php

function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "$r, $g, $b";
}
?>