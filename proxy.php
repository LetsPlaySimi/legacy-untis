<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['server']) || !isset($data['payload'])) {
    http_response_code(400);
    echo json_encode(array("error" => "Invalid proxy payload"));
    exit();
}

// Format Server URL (ensure https://)
$server = rtrim($data['server'], '/');
if (!preg_match("~^https?://~i", $server)) {
    $server = "https://" . $server;
}

// Build WebUntis JSON-RPC URL
$url = $server . "/WebUntis/jsonrpc.do?school=" . urlencode($data['school']);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data['payload']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'User-Agent: iOS8-Legacy-WebUntis'
));

// Forward WebUntis Session ID
if (isset($data['sessionId']) && !empty($data['sessionId'])) {
    curl_setopt($ch, CURLOPT_COOKIE, "JSESSIONID=" . $data['sessionId']);
}

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(array("error" => array("message" => curl_error($ch))));
} else {
    echo $response;
}

curl_close($ch);
?>