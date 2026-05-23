<?php

require_once __DIR__ . '/../app/core/bootstrap.php';
require_admin();

if (!function_exists('sendWhatsApp')) {
    function sendWhatsApp($phone, $message) {
        $token = "SEU_TOKEN_META";
        $phone_number_id = "SEU_PHONE_ID";

        $url = "https://graph.facebook.com/v18.0/$phone_number_id/messages";

        $data = [
            "messaging_product" => "whatsapp",
            "to" => $phone,
            "type" => "text",
            "text" => ["body" => $message]
        ];

        $options = [
            "http" => [
                "header"  => "Content-type: application/json\r\nAuthorization: Bearer $token",
                "method"  => "POST",
                "content" => json_encode($data)
            ]
        ];

        $context = stream_context_create($options);
        file_get_contents($url, false, $context);
    }
}
