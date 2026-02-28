<?php
/*
PHP Firebase Bridge

Handles:
- Realtime Database writes
- Firebase Cloud Messaging alerts
*/

require __DIR__ . '/vendor/autoload.php';
use Google\Client;

$serviceAccountPath = __DIR__ . "/serviceAccountKey.json";
$client = new Client();
$client->setAuthConfig($serviceAccountPath);
$client->addScope('https://www.googleapis.com/auth/firebase.database');
$client->addScope('https://www.googleapis.com/auth/firebase.messaging');

$accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
$projectId = "smartfireapp1";
$databaseUrl = "https://smartfireapp1-default-rtdb.firebaseio.com";

//hardcoded value
$temperature = 100.0;
$smoke = 150;
$status = ($temperature >= 40 && $smoke > 0) ? "emergency" : "normal";

// Helper: GET wrapper
function firebase_get($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}

// Function to send one event + FCM
function send_event($databaseUrl, $accessToken, $projectId, $temperature, $smoke, $status) {
    $event = [
        "timestamp"   => round(microtime(true) * 1000),
        "temperature" => $temperature,
        "smoke"       => $smoke,
        "status"      => $status
    ];

    // Push to history
    $historyUrl = "$databaseUrl/fire_events.json?auth=$accessToken";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $historyUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $dbResponse = curl_exec($ch);
    curl_close($ch);

    // Update latest
    $putLatestUrl = "$databaseUrl/fire_events_latest.json?auth=$accessToken";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $putLatestUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($event));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    $putResp = curl_exec($ch);
    curl_close($ch);

    // Send FCM
    $fcmUrl = "https://fcm.googleapis.com/v1/projects/$projectId/messages:send";
    $message = [
        "message" => [
            "topic" => "fire_alert",
            "data" => [
                "temperature" => strval($event["temperature"]),
                "smoke"       => strval($event["smoke"]),
                "status"      => $event["status"]
            ],
            "android" => [
                "priority" => "high"
            ],
            "notification" => [
                "title" => $status === "emergency" ? "🔥 FIRE ALERT!" : " SAFE AGAIN",
                "body"  => $status === "emergency"
                    ? "Temp: {$event['temperature']}°C | Smoke: {$event['smoke']}"
                    : "Environment back to normal."
            ]
        ]
    ];

    $headers = [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fcmUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    $fcmResponse = curl_exec($ch);
    curl_close($ch);

    echo "Event sent: DB=$dbResponse | Latest=$putResp | FCM=$fcmResponse\n";
}

// Emergency = spam 10 times
if ($status === "emergency") {
    for ($i = 1; $i <= 10; $i++) {
        echo "Sending EMERGENCY notification #$i ...\n";
        send_event($databaseUrl, $accessToken, $projectId, $temperature, $smoke, $status);
        usleep(300000); // 0.5 sec delay
    }
}
//  Normal = single safe update (only if flipping back)
else {
    $latestUrl = "$databaseUrl/fire_events_latest.json?auth=$accessToken";
    $latestRaw = firebase_get($latestUrl);
    $latestObj = json_decode($latestRaw, true);
    $latestStatus = isset($latestObj['status']) ? $latestObj['status'] : null;

    if ($latestStatus !== "normal") {
        echo "Sending SAFE notification (flip back)...\n";
        send_event($databaseUrl, $accessToken, $projectId, $temperature, $smoke, $status);
    } else {
        echo "Skipped: duplicate 'normal' (no DB write / no FCM)\n";
    }
}
?>