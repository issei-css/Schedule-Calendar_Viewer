<?php
// delete.php
require 'config.php';
$id = $_GET['id'] ?? '';
if (!$id) exit('No id');

$url = FIREBASE_DB_URL . "/attendances/{$id}.json" . (FIREBASE_DB_AUTH ? '?auth='.FIREBASE_DB_AUTH : '');

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

header('Location: list.php?msg=deleted');
?>