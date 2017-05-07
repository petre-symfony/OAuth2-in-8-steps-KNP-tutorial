<?php

include __DIR__.'/vendor/autoload.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$http = new Client('http://coop.apps.knpuniversity.com', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

$request = $http->post('/api/166/eggs-collect');
$request->addHeader('Authorization', 'Bearer 44901de9f0598a4967e1c941e760a9ea75524f6d');
$response = $request->send();

echo $response->getBody();

echo "\n\n";
