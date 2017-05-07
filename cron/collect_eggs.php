<?php

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$http = new Client('http://coop.apps.knpuniversity.com', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

$request = $http->post('/token', null, array(
  'client_id'     => 'Peter\'s Lazy CRON job',
  'client_secret' => '4a368f96fb7bf29f35a862eb6a500a28',
  'grant_type'    => 'client_credentials'   
));
$response = $request->send();
$responseBody = $response->getBody(true);
var_dump($responseBody); die;

$request = $http->post('/api/1664/eggs-collect');
$request->addHeader('Authorization', 'Bearer 44901de9f0598a4967e1c941e760a9ea75524f6d');
$response = $request->send();

echo $response->getBody();

echo "\n\n";
