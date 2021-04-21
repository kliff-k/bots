<?php

$HOOK_ACCESS_TOKEN = '';
$PAGE_ACCESS_TOKEN = '';

$psid = '';
$response = ['text' => 'Bom dia!'];
callSendAPI($psid, $response);

function handlePostback($psid, $postback)
{
    $response = [];
    $payload  = $postback['payload'];
    $db = json_decode(file_get_contents('fb_bot.db'),true);

    if($payload === 'yes')
    {
        $response = ['text' => 'Perfeito! Dados cadastrados. Será um prazer te ajudar, '.$db[$psid]['name'].'!'];
        $db[$psid]['waiting'] = '';
    }
    else if ($payload === 'no')
    {
        $response = ['text' => 'Opa, quais seriam os dados certos? Informe novamente seu nome.'];
        $db[$psid]['waiting'] = 'name';
    }
    else if ($payload === 'pic_yes')
        $response = ['text' => 'Vou guardar com carinho.'];
    else if ($payload === 'pic_no')
        $response = ['text' => 'Melhor esquecer, então!'];


    file_put_contents('fb_bot.db', json_encode($db));

    if($response)
        callSendAPI($psid, $response);
}

function callSendAPI($psid, $response)
{
    global $PAGE_ACCESS_TOKEN, $debug;
    $url = 'https://graph.facebook.com/v2.6/me/messages?access_token='.$PAGE_ACCESS_TOKEN;
    $body = json_encode(['recipient' => ['id' => $psid], 'message' => $response]);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $headers = array();
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    $debug .= $result;
}

file_put_contents('fb_bot.txt', json_encode($data)."\nREQUEST_METHOD: $_SERVER[REQUEST_METHOD]\n----- Request Date: ".date("d.m.Y H:i:s")." IP: $_SERVER[REMOTE_ADDR] -----\n".json_encode($debug)."\n\n", FILE_APPEND);

