<?php

// Set webhook
// https://api.telegram.org/$bot/setWebhook?url=https://singlehorizon.com/testes/bot.php

// Query info
// https://api.telegram.org/$bot/getWebhookInfo

// Webhook set script with self signed certificate
// curl -F "url=https://singlehorizon.com/testes/bot.php" -F "certificate=@/etc/ssl/singlehorizon.com.crt" https://api.telegram.org/$bot/setWebhook

// Declare main variables

$token = "";
$bot = "https://api.telegram.org/bot".$token;

// Get request contents and relevant data

$content    = file_get_contents("php://input");
$update     = json_decode($content, true);
$update_id  = $update['update_id'];
$text       = $update['message']['text'];
$chat_date  = $update['message']['date'];
$message_id = $update['message']['message_id'];
$chat_id    = $update['message']['chat']['id'];
$first_name = $update['message']['chat']['first_name'];
$chat_type  = $update['message']['chat']['type'];

// Main - Treats the input and returns answer plus keyboard layout

$id = 42286522;
$message = 'Olá, Murilo!';

$response = file_get_contents($bot."/sendmessage?chat_id=$id&text=$message");
file_put_contents('sagi_bot.txt', "[-] ".json_encode($response)."\n\n", FILE_APPEND);
