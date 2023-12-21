<?php
require 'vendor/autoload.php';
use GuzzleHttp\Client;

header("Content-Type: application/json");
// Set the necessary headers for cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");
    
if (!isset($_POST['ammount'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ammount']);
    exit();
}
if(!is_numeric($_POST['ammount'])){
    http_response_code(400);
    echo json_encode(['error' => 'Fuck You, put a number']);
    exit();
}
if ($_POST['ammount'] >= 11) {
    http_response_code(400);
    echo json_encode(['error' => 'Too Much ammount, go fuck yourself. max is 10']);
    exit();
}

const PROMOTION_ID = '1180231712274387115';
const DISCORD_BASE_URL = 'https://discord.com/billing/partner-promotions';
const DISCORD_API_URL = 'https://api.discord.gx.games/v1/direct-fulfillment';

function requestToken($partnerUserId)
{
    $client = new Client();
    $headers = [
        'accept' => '*/*',
        'accept-language' => 'en-US,en;q=0.9',
        'content-type' => 'application/json',
        'sec-ch-ua' => '"Opera GX";v="105", "Chromium";v="119", "Not?A_Brand";v="24"',
        'sec-ch-ua-mobile' => '?0',
        'sec-ch-ua-platform' => '"Windows"',
        'sec-fetch-dest' => 'empty',
        'sec-fetch-mode' => 'cors',
        'sec-fetch-site' => 'cross-site',
        'Referer' => 'https://www.opera.com/',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

    $response = $client->request('POST', DISCORD_API_URL, [
        'headers' => $headers,
        'json' => ['partnerUserId' => $partnerUserId],
    ]);

    return json_decode($response->getBody(), true);
}

function generateUUID()
{
    return preg_replace_callback('/[xy]/u', function ($matches) {
        $c = $matches[0];
        $r = random_int(0, 15);
        $v = $c === 'x' ? $r : ($r & 0x3 | 0x8);
        $u = dechex($v);

        return $u;
    }, 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx');
}

function generateHASHEDUUID()
{
    $uuid = generateUUID();
    $hashedUUID = hash('sha256', $uuid);

    return $hashedUUID;
}

$return = [
    'status' => false,
    'data' => []    
];
try {
    $amount = $_POST['ammount'];
    for ($i = 0; $i < $amount; $i++) {
        $partnerUserId = generateHASHEDUUID();
        $getToken = requestToken($partnerUserId);
        if (!isset($getToken['token'])) {
            echo "No token received, please try again\n";
            exit(0);
        }
        $token = $getToken['token'];
        $combineURL = sprintf('%s/%s/%s', DISCORD_BASE_URL, PROMOTION_ID, $token);
        array_push($return['data'], $combineURL);
    }
    $return['status'] = true;
    $return['all'] = implode("\n", $return['data']);
    echo json_encode($return);
} catch (Exception $error) {
    $return['error'] = $error->getMessage() . PHP_EOL;
}
