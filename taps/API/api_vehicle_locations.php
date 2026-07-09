<?php
require_once "../DB/dbcon.php";

header("Content-Type: application/json; charset=UTF-8");

$API_KEY = "mQ7xR9pT2kV8nZ3bL5cD1aS6yH0wE4jF";

$headers = getallheaders();
$client_key = $headers['API-KEY'] ?? ($_GET['api_key'] ?? null);

if ($client_key !== $API_KEY) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized"
    ]);
    exit;
}

$date_from = $_GET['from'] ?? null;
$date_to   = $_GET['to'] ?? null;

$sql = "
SELECT 
      id,
      protocol,
      deviceid,
      servertime,
      devicetime,
      fixtime,
      valid,
      latitude,
      longitude,
      altitude,
      speed,
      course,
      address,
      attributes,
      accuracy
FROM tc_positions
WHERE 1=1
";

$params = [];

if (!empty($date_from)) {
    $sql .= " AND fixtime >= :from";
    // Start from beginning of the day (00:00:00)
    $params[':from'] = $date_from . ' 00:00:00';
}

if (!empty($date_to)) {
    $sql .= " AND fixtime <= :to";
    // End at 23:59:59 of the day
    $params[':to'] = $date_to . ' 23:59:59';
}

$sql .= " ORDER BY fixtime ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$data = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $attributes = [];

    if (!empty($row['attributes'])) {
        $decoded = json_decode($row['attributes'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $attributes = $decoded;
        }
    }

    $data[] = [
        "id" => $row["id"],
        "deviceid" => $row["deviceid"],
        "fixtime" => $row["fixtime"],
        "latitude" => $row["latitude"],
        "longitude" => $row["longitude"],
        "speed" => $row["speed"],
        "course" => $row["course"],
        "valid" => $row["valid"],
        "accuracy" => $row["accuracy"],
        "address" => $row["address"],
        "attributes" => $attributes
    ];
}

echo json_encode([
    "status" => "success",
    "count" => count($data),
    "from" => $date_from,
    "to" => $date_to,
    "data" => $data
], JSON_PRETTY_PRINT);
?>