<?php

$app = require __DIR__.'/../bootstrap.php';
use Guzzle\Http\Client;

// create our http client (Guzzle)
$http = new Client('http://coop.apps.knpuniversity.com', array(
  'request.options' => array(
    'exceptions' => false,
  )
));

// refresh all tokens expiring today or earlier
/** @var \OAuth2Demo\Client\Storage\Connection $conn */
$conn = $app['connection'];

$expiringTokens = $conn->getExpiringTokens(new DateTime('+1 month'));

foreach($expiringTokens as $userInfo){
  $request = $http->post('/token', null, array(
    'client_id'     => 'Peter Top Cluck',
    'client_secret' => 'feb19d51c9211f0b93e11ee445f10c76',
    'grant_type'    => 'refresh_token',
    'refresh_token' => $userInfo['coopRefreshToken']
  ));
  $response = $request->send();
  $responseBody = $response->getBody(true);
  var_dump($responseBody);
  $responseArr = json_decode($responseBody, true);
}
