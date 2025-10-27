<?php
// edit.php
require 'config.php';

$id = $_GET['id'] ?? '';
if (!$id) {
  die('Error: No record ID provided.');
}

// Fetch existing record from Firebase
$url = FIREBASE_DB_URL . "/attendances/{$id}.json" . (defined('FIREBASE_DB_AUTH') && FIREBASE_DB_AUTH ? '?auth=' . FIREBASE_DB_AUTH : '');
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$record = json_decode($response, true);

if (!$record) {
  die('Record not found or invalid response.');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit Attendance Record</title>
  <style>
    body {
      font-family: "Poppins", sans-serif;
      background: linear-gradient(135deg, #5a9bd4, #7ac8e3);
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .edit-form-container {
      background-color: #ffffff;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
      width: 420px;
      max-width: 90%;
      text-align: center;
      animation: fadeIn 0.8s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
      letter-spacing: 1px;
    }

    form label {
      display: block;
      text-align: left;
      font-weight: 500;
      color: #333;
      margin-bottom: 6px;
      margin-top: 14px;
    }

    input, select {
      width: 100%;
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      outline: none;
      transition: border 0.3s ease, box-shadow 0.3s ease;
    }

    input:focus, select:focus {
      border-color: #5a9bd4;
      box-shadow: 0 0 6px rgba(90, 155, 212, 0.5);
    }

    button {
      margin-top: 20px;
      width: 100%;
      background-color: #4caf50;
      border: none;
      color: white;
      font-weight: 600;
      padding: 12px;
      border-radius: 8px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background-color: #449d48;
    }

    .cancel-btn {
      display: inline-block;
      text-decoration: none;
      background-color: #f44336;
      color: white;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 600;
      margin-top: 12px;
      transition: background 0.3s ease;
    }

    .cancel-btn:hover {
      background-color: #d7372c;
    }

  </style>
</head>
<body>
  <div class="edit-form-container">
    <h2>✏️ Edit Attendance Record</h2>
    <form action="update.php" method="post">
      <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

      <label>Faculty Name:</label>
      <input name="faculty_name" value="<?= htmlspecialchars($record['faculty_name'] ?? '') ?>" required>

      <label>Section:</label>
      <input name="section" value="<?= htmlspecialchars($record['section'] ?? '') ?>" required>

      <label>Subject Code:</label>
      <input name="subject_code" value="<?= htmlspecialchars($record['subject_code'] ?? '') ?>" required>

      <label>Room / Class Mode:</label>
      <select name="room_or_mode" required>
        <option <?= ($record['room_or_mode'] ?? '') === 'Lecture' ? 'selected' : '' ?>>Lecture</option>
        <option <?= ($record['room_or_mode'] ?? '') === 'Lab' ? 'selected' : '' ?>>Lab</option>
      </select>

      <label>Day of the Week:</label>
      <select name="day_of_week" required>
        <?php
        $days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        foreach ($days as $day) {
          $sel = ($record['day_of_week'] ?? '') === $day ? 'selected' : '';
          echo "<option $sel>$day</option>";
        }
        ?>
      </select>

      <label>Time From:</label>
      <input type="time" name="time_from" value="<?= htmlspecialchars($record['time_from'] ?? '') ?>" required>

      <label>Time To:</label>
      <input type="time" name="time_to" value="<?= htmlspecialchars($record['time_to'] ?? '') ?>" required>

      <label>Attendance Date:</label>
      <input type="date" name="attendance_date" value="<?= htmlspecialchars($record['attendance_date'] ?? '') ?>" required>

      <label>Learning Modality:</label>
      <select name="learning_modality" required>
        <option <?= ($record['learning_modality'] ?? '') === 'F2F Class' ? 'selected' : '' ?>>F2F Class</option>
        <option <?= ($record['learning_modality'] ?? '') === 'Online Class' ? 'selected' : '' ?>>Online Class</option>
      </select>

      <label>Online Class Link:</label>
      <input name="online_link" value="<?= htmlspecialchars($record['online_link'] ?? '') ?>" placeholder="https://... (optional)">

      <label>Faculty Attendance:</label>
      <select name="faculty_attendance" required>
        <option <?= ($record['faculty_attendance'] ?? '') === 'Present' ? 'selected' : '' ?>>Present</option>
        <option <?= ($record['faculty_attendance'] ?? '') === 'Absent' ? 'selected' : '' ?>>Absent</option>
        <option <?= ($record['faculty_attendance'] ?? '') === 'Late' ? 'selected' : '' ?>>Late</option>
      </select>

      <label>Dress Code:</label>
      <select name="dress_code" required>
        <option <?= ($record['dress_code'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
        <option <?= ($record['dress_code'] ?? '') === 'No' ? 'selected' : '' ?>>No</option>
      </select>

      <label>Remark:</label>
      <input name="remark" value="<?= htmlspecialchars($record['remark'] ?? '') ?>">

      <button type="submit">💾 Update Record</button>
    </form>
    <a href="list.php" class="cancel-btn">❌ Cancel</a>
  </div>
</body>
</html>
