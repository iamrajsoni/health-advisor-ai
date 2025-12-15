<?php
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=TEST_KEY';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Intentionally not sending post data to just test connection/SSL
// We expect a 400 or 403, but not a curl error (0)
$response = curl_exec($ch);
$error = curl_error($ch);
$errno = curl_errno($ch);
curl_close($ch);

if ($error) {
    echo "CURL Error ($errno): $error";
} else {
    echo "Connection successful (Response received)";
}
?>