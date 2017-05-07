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
$responseArr = json_decode($responseBody, true);
$accesToken = $responseArr['access_token'];

$request = $http->post('/api/1664/eggs-collect');
$request->addHeader('Authorization', 'Bearer '.$accesToken);
$response = $request->send();

echo $response->getBody();

echo "\n\n";
