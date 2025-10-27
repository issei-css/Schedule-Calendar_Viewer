<?php
// list.php
require 'config.php';

// Build the Firebase URL
$url = FIREBASE_DB_URL . '/attendances.json' . (defined('FIREBASE_DB_AUTH') && FIREBASE_DB_AUTH ? '?auth=' . FIREBASE_DB_AUTH : '');

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$records = json_decode($response, true); // associative array keyed by pushId
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Attendance Records</title>
  <style>
    body {
      font-family: "Poppins", sans-serif;
      background: linear-gradient(135deg, #5a9bd4, #7ac8e3);
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }

    .records-container {
      background: #fff;
      margin-top: 60px;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.2);
      width: 90%;
      max-width: 900px;
      animation: fadeIn 0.8s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #333;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th, td {
      padding: 12px 10px;
      text-align: center;
    }

    th {
      background-color: #5a9bd4;
      color: white;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tr:hover {
      background-color: #eef6ff;
    }

    .no-records {
      text-align: center;
      color: #666;
      font-size: 16px;
      padding: 20px 0;
    }

    .actions a {
      text-decoration: none;
      font-weight: 500;
      padding: 6px 12px;
      border-radius: 6px;
      transition: all 0.2s ease-in-out;
      color: white;
    }

    .edit-btn {
      background-color: #4caf50;
    }

    .edit-btn:hover {
      background-color: #449d48;
    }

    .delete-btn {
      background-color: #f44336;
    }

    .delete-btn:hover {
      background-color: #d7372c;
    }

    .top-links {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
      gap: 10px;
    }

    .left-section {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .right-section {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .add-btn, .calendar-btn, .back-btn {
      color: white;
      text-decoration: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s ease;
      display: inline-block;
    }

    .add-btn {
      background-color: #5a9bd4;
    }

    .add-btn:hover {
      background-color: #468cc1;
    }

    .calendar-btn {
      background-color: #9c27b0;
    }

    .calendar-btn:hover {
      background-color: #7b1fa2;
    }

    .back-btn {
      background-color: #888;
    }

    .back-btn:hover {
      background-color: #777;
    }

    @media (max-width: 768px) {
      .top-links {
        flex-direction: column;
      }

      .left-section, .right-section {
        width: 100%;
        justify-content: center;
      }

      .add-btn, .calendar-btn, .back-btn {
        width: 100%;
        text-align: center;
      }
    }

  </style>
</head>
<body>
  <div class="records-container">
    <div class="top-links">
      <div class="left-section">
        <a href="submission.php" class="add-btn">➕ Add New Record</a>
        <a href="calendar.php" class="calendar-btn">📅 View Calendar</a>
      </div>
      <div class="right-section">
        <a href="logout.php" class="back-btn">🚪 Logout</a>
      </div>
    </div>

    <h2>📋 Attendance Records</h2>

    <?php if (!$records) { ?>
      <p class="no-records">No records found</p>
    <?php } else { ?>
      <table>
        <tr>
          <th>Faculty</th>
          <th>Section</th>
          <th>Subject</th>
          <th>Day</th>
          <th>Date</th>
          <th>Attendance</th>
          <th>Actions</th>
        </tr>
        <?php foreach ($records as $id => $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['faculty_name'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['section'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['subject_code'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['day_of_week'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['attendance_date'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['faculty_attendance'] ?? '') ?></td>
            <td class="actions">
              <a href="edit.php?id=<?= $id ?>" class="edit-btn">Edit</a>
              <a href="delete.php?id=<?= $id ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php } ?>
  </div>
</body>
</html>