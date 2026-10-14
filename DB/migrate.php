<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../dbconnect.php';
$conn->multi_query(file_get_contents(__DIR__ . '/02-roles-pos.sql'));
do { if ($result = $conn->store_result()) { $result->free(); } }
while ($conn->more_results() && $conn->next_result());
echo "Roles/POS migration complete.\n";
