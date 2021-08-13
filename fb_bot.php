<?php

$HOOK_ACCESS_TOKEN = '';
$PAGE_ACCESS_TOKEN = '';

$path   = explode('/',$_SERVER['REQUEST_URI']);
$method = $_SERVER['REQUEST_METHOD'];
$data   = json_decode(file_get_contents("php://input"),true);
$debug = '';

switch(explode('?', $path[3])[0])
{
    case 'webhook':
        switch($method)
        {
            case 'GET':
                webhookGet();
                break;
            case 'POST':
                webhookPost();
                break;
            default:
                echo json_encode(['invalid_method']);
                die;
                break;
        }
        break;
    default:
        echo json_encode(['invalid_endpoint']);
        die;
        break;
}

function webhookPost()
{
    global $data, $debug;

    if($data['object']=='page')
    {
        foreach($data['entry'] AS $entry)
        {
            $webhookEvent = $entry['messaging'][0];
	    $senderPsid   = $webhookEvent['sender']['id'];

	    if($senderPsid == '348102112928907')
		    die;

            if($webhookEvent['message'])
                handleMessage($senderPsid, $webhookEvent['message']);
            else
                handlePostback($senderPsid, $webhookEvent['postback']);
        }
        http_response_code(200);
        echo 'EVENT_RECEIVED';
    }
    else
        http_response_code(404);
}

function webhookGet()
{
    global $HOOK_ACCESS_TOKEN;
    $mode      = $_REQUEST['hub_mode'];
    $token     = $_REQUEST['hub_verify_token'];
    $challenge = $_REQUEST['hub_challenge'];

    if($mode && $token)
    {
        if($mode === 'subscribe' && $token === $HOOK_ACCESS_TOKEN)
        {
            http_response_code(200);
            echo $_REQUEST['hub_challenge'];
        }
        else
            http_response_code(403);
    }
}

function handleMessage($psid, $message)
{
    $response = [];
    if($message['text'])
    {
        $db = json_decode(file_get_contents('fb_bot.db'),true);
        if($db[$psid])
        {
            if($db[$psid]['waiting'] && strpos($message['text'], '/')===0)
            {
                $response = ['text' => 'Eu entendo a sua pressa! Mas, por favor, informe seus dados para que possamos dar continuidade ao atendimento.'];
            }
            else if($db[$psid]['waiting'] == 'name')
            {
                $db[$psid]['waiting'] = 'mother';
                $db[$psid]['name'] = $message['text'];
                file_put_contents('fb_bot.db', json_encode($db));
                $response = ['text' => 'Informe o nome da sua mãe.'];
            }
            else if($db[$psid]['waiting'] == 'mother')
            {
                $db[$psid]['waiting'] = 'birth';
                $db[$psid]['mother'] = $message['text'];
                file_put_contents('fb_bot.db', json_encode($db));
                $response = ['text' => 'Informe a sua data de nascimento (no formato xx/xx/xxxx).'];
            }
            else if($db[$psid]['waiting'] == 'birth')
            {
                $db[$psid]['waiting'] = 'cpf';
                $db[$psid]['birth'] = $message['text'];
                file_put_contents('fb_bot.db', json_encode($db));
                $response = ['text' => 'Informe o seu CPF (sem pontuação).'];
            }
            else if($db[$psid]['waiting'] == 'cpf')
            {
                $db[$psid]['cpf'] = $message['text'];
                file_put_contents('fb_bot.db', json_encode($db));
		
$response = ["text" => "Nome: ".$db[$psid]['name']."\nMãe: ".$db[$psid]['mother']."\nNascimento: ".$db[$psid]['birth']."\nCpf: ".$db[$psid]['cpf']];
		callSendAPI($psid, $response);

                $response = [
                    "attachment" => [
                        "type" => "template",
                        "payload" => [
                            "template_type" => "generic",
                            "elements" => [
                                [
                                    "title" => "Estes dados estão corretos?",
                                    "subtitle" => "Aperte um dos botões para responder.",
                                    "buttons" => [
                                        [
                                            "type" => "postback",
                                            "title" => "Sim!",
                                            "payload" => "yes",
                                        ],
                                        [
                                            "type" => "postback",
                                            "title" => "Não!",
                                            "payload" => "no",
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ]
                ];
            }
            else if($message['text'] == '/help')
                $response = ['text' => "/dados - Exibir dados cadastrados\n\n/atualizar - Atualize seus dados\n\n/mapas - Mapas do seu município\n\n/equipamentos - Lista de equipamentos\n\n/censo - Status do censo cidadania"];
            else if($message['text'] == '/mapas')
                $response = ['text' => 'Certo, '.$db[$psid]['name'].'! Encaminhando mapas relevantes: *mapas*'];
            else if($message['text'] == '/equipamentos')
                $response = ['text' => 'Ok, '.$db[$psid]['name'].'! Encaminhando lista de equipamentos: *equipamentos*'];
            else if($message['text'] == '/censo')
		    $response = ['text' => 'Positivo, '.$db[$psid]['name'].'! Encaminhando status do censo: *status*'];
	    else if($message['text'] == '/dados')
		    $response = ["text" => "Nome: ".$db[$psid]['name']."\nMãe: ".$db[$psid]['mother']."\nNascimento: ".$db[$psid]['birth']."\nCpf: ".$db[$psid]['cpf']];
            else if($message['text'] == '/atualizar')
            {
                $response = ['text' => 'Entendido! Me informe seu nome:'];
                $db[$psid]['waiting'] = 'name';
                file_put_contents('fb_bot.db', json_encode($db));
	    }
	    else
	    {
                $response = ['text' => 'Digite "/help" para ver a lista de comandos que eu entendo!'];
	    }

        }
        else
        {
            $response = ['text' => 'Olá! Eu sou o bot responsável por este chat. Vejo que este é o seu primeiro contato. Digite /help para a lista de comandos que compreendo. Para começar, por favor, me informe seu nome!'];
            $db[$psid]['waiting'] = 'name';
            file_put_contents('fb_bot.db', json_encode($db));
        }
    }
    else if ($message['attachments'])
    {
        $attachment_url = $message['attachments'][0]['payload']['url'];
        $response = [
            "attachment" => [
                "type" => "template",
                "payload" => [
                    "template_type" => "generic",
                    "elements" => [
                        [
                            "title" => "Esta é uma foto sua?",
                            "subtitle" => "Aperte um dos botões para responder.",
                            "image_url" => $attachment_url,
                            "buttons" => [
                                [
                                    "type" => "postback",
                                    "title" => "Sim!",
                                    "payload" => "pic_yes",
                                ],
                                [
                                    "type" => "postback",
                                    "title" => "Sim!",
                                    "payload" => "pic_no",
                                ]
                            ],
                        ]
                    ]
                ]
            ]
        ];
    }
    if($response)
        callSendAPI($psid, $response);
}

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

