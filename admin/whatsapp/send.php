<?php
require_once __DIR__ . '/../../app/core/bootstrap.php';
require_admin();

if (!function_exists('sendWhatsApp')) {
    function sendWhatsApp($phone, $message) {

    $token = "META_TOKEN";
    $phone_id = "PHONE_NUMBER_ID";

    $url = "https://graph.facebook.com/v18.0/$phone_id/messages";

    $data = [
        "messaging_product" => "whatsapp",
        "to" => $phone,
        "type" => "text",
        "text" => ["body" => $message]
    ];

    $headers = [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
    }
}
