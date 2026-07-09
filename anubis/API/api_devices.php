<?php
require_once "../DB/dbcon.php";

header("Content-Type: application/json; charset=UTF-8");

/* =========================
   SIMPLE API KEY SECURITY
========================= */
$API_KEY = "C4dE9fG2hJ7kL1mN8pQ3rS6tU0vWxY5z"; // <-- change this

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

/* =========================
   QUERY DATA
========================= */
$sql = "
SELECT 
      id,
      name,
      uniqueid,
      lastupdate,
      positionid,
      groupid,
      attributes,
      phone,
      model,
      contact,
      category,
      disabled,
      status,
      expirationtime,
      motionstate,
      motiontime,
      motiondistance,
      overspeedstate,
      overspeedtime,
      overspeedgeofenceid,
      motionstreak,
      calendarid,
      motionpositionid,
      motionlatitude,
      motionlongitude,
      company,
      site,
      vehicle_type,
      fuel_consumption_liter_per_km,
      sim,
      data,
      amount,
      last_load,
      balance
FROM tc_devices
ORDER BY id DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$data = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    // decode JSON attributes safely
    $attributes = [];
    if (!empty($row['attributes'])) {
        $decoded = json_decode($row['attributes'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $attributes = $decoded;
        }
    }

    $data[] = [
        "id" => $row["id"],
        "name" => $row["name"],
        "uniqueid" => $row["uniqueid"],
        "lastupdate" => $row["lastupdate"],
        "positionid" => $row["positionid"],
        "groupid" => $row["groupid"],
        "phone" => $row["phone"],
        "model" => $row["model"],
        "contact" => $row["contact"],
        "category" => $row["category"],
        "disabled" => $row["disabled"],
        "status" => $row["status"],
        "expirationtime" => $row["expirationtime"],
        "motionstate" => $row["motionstate"],
        "motiontime" => $row["motiontime"],
        "motiondistance" => $row["motiondistance"],
        "overspeedstate" => $row["overspeedstate"],
        "overspeedtime" => $row["overspeedtime"],
        "overspeedgeofenceid" => $row["overspeedgeofenceid"],
        "motionstreak" => $row["motionstreak"],
        "calendarid" => $row["calendarid"],
        "motionpositionid" => $row["motionpositionid"],
        "motionlatitude" => $row["motionlatitude"],
        "motionlongitude" => $row["motionlongitude"],
        "company" => $row["company"],
        "site" => $row["site"],
        "vehicle_type" => $row["vehicle_type"],
        "fuel_consumption_liter_per_km" => $row["fuel_consumption_liter_per_km"],
        "sim" => $row["sim"],
        "data" => $row["data"],
        "amount" => $row["amount"],
        "last_load" => $row["last_load"],
        "balance" => $row["balance"],
        "attributes" => $attributes
    ];
}

/* =========================
   OUTPUT JSON
========================= */
echo json_encode([
    "status" => "success",
    "count" => count($data),
    "data" => $data
], JSON_PRETTY_PRINT);
?>