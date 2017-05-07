<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Client;

class CoopOAuthController extends BaseController{
  public static function addRoutes($routing){
    $routing->get('/coop/oauth/start', array(new self(), 'redirectToAuthorization'))->bind('coop_authorize_start');
    $routing->get('/coop/oauth/handle', array(new self(), 'receiveAuthorizationCode'))->bind('coop_authorize_redirect');
  }

  /**
   * This page actually redirects to the COOP authorize page and begins
   * the typical, "auth code" OAuth grant type flow.
   *
   * @param Request $request
   * @return RedirectResponse
   */
  public function redirectToAuthorization(Request $request){
    $redirectUrl = $this->generateUrl(
      'coop_authorize_redirect', 
      array(), 
      true
    );
    
    $url = 'http://coop.apps.knpuniversity.com/authorize?'.http_build_query(array(
      'response_type' => 'code',
      'client_id'     => 'Peter Top Cluck',
      'redirect_uri'  => $redirectUrl,
      'scope'         => 'eggs-count profile'
    ));
    
    return $this->redirect($url);
  }

  /**
   * This is the URL that COOP will redirect back to after the user approves/denies access
   *
   * Here, we will get the authorization code from the request, exchange
   * it for an access token, and maybe do some other setup things.
   *
   * @param  Application             $app
   * @param  Request                 $request
   * @return string|RedirectResponse
   */
  public function receiveAuthorizationCode(Application $app, Request $request){
    // equivalent to $_GET['code']
    $code = $request->get('code');

    if(!$code){
      $error = $request->get('error');
      $errorDescription = $request->get('error_description');
      
      return $this->render('failed_authorization.twig', array(
        'response' => array(
          'error'             => $error,
          'errorDescription'  => $errorDescription
        )
      ));
    }
    
    // create our http client (Guzzle)
    $http = new Client('http://coop.apps.knpuniversity.com', array(
        'request.options' => array(
            'exceptions' => false,
        )
    ));
    $redirectUrl = $this->generateUrl(
      'coop_authorize_redirect', 
      array(), 
      true
    );
    
    $request = $http->post('/token', null, array(
      'client_id'     => 'Peter Top Cluck',
      'client_secret' => 'feb19d51c9211f0b93e11ee445f10c76',
      'grant_type'    => 'authorization_code',
      'code'          => $code,
      'redirect_uri'  => $redirectUrl  
    ));
    $response = $request->send();
    $responseBody = $response->getBody(true);
    $responseArr = json_decode($responseBody, true);
    if (!isset($responseArr['access_token'])){
      return $this->render('failed_token_request.twig', array(
        'response' => $responseArr ? $responseArr : $response
      ));
    }
    
    $accesToken = $responseArr['access_token'];
    $expiresIn = $responseArr['expires_in'];
    $expiresAt = new \DateTime('+' . $expiresIn . ' seconds');
    
    $request = $http->get('/api/me');
    $request->addHeader('Authorization', 'Bearer '.$accesToken);
    $response = $request->send();
    $json = json_decode($response->getBody(), true);
    
    if($this->isUserLoggedIn()){
      $user = $this->getLoggedInUser();
    } else {
      $user = $this->findOrCreateUser($json);
      
      $this->loginUser($user);
    }
    
    $user = $this->getLoggedInUser();
    $user->coopAccessToken = $accesToken;
    $user->coopUserId = $json['id'];
    $user->coopAccessExpiresAt = $expiresAt;
    $this->saveUser($user);
    
    //redirects to the homepage
    return $this->redirect($this->generateUrl('home'));
  }
  
  private function findOrCreateUser(array $myData){
    if ($user = $this->findUserByCOOPId($myData['id'])){
      return $user;  
    } else {
      return $this->createUser($myData['email'],
        '',
        $myData['firstName'],
        $myData['lastName']
      ); 
    }
  }
}
