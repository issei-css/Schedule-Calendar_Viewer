<?php
// create.php
require 'config.php';

// Basic sanitization (improve as needed)
$data = [
  'faculty_name' => $_POST['faculty_name'] ?? '',
  'section' => $_POST['section'] ?? '',
  'subject_code' => $_POST['subject_code'] ?? '',
  'room_or_mode' => $_POST['room_or_mode'] ?? '',
  'day_of_week' => $_POST['day_of_week'] ?? '',
  'time_from' => $_POST['time_from'] ?? '',
  'time_to' => $_POST['time_to'] ?? '',
  'attendance_date' => $_POST['attendance_date'] ?? '',
  'learning_modality' => $_POST['learning_modality'] ?? '',
  'online_link' => $_POST['online_link'] ?: null,
  'faculty_attendance' => $_POST['faculty_attendance'] ?? '',
  'dress_code' => $_POST['dress_code'] ?? '',
  'remark' => $_POST['remark'] ?? ''
];

// push to /attendances.json via POST
$url = FIREBASE_DB_URL . '/attendances.json' . (FIREBASE_DB_AUTH ? '?auth='.FIREBASE_DB_AUTH : '');

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
  die("cURL Error: $err");
}

$result = json_decode($response, true);
// Firebase returns something like { "name": "-MpushKey..." }
if (isset($result['name'])) {
  header('Location: list.php?msg=created');
  exit;
} else {
  echo "Error saving: " . htmlentities($response);
}
