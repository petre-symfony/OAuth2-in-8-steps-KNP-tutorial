<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Facebook\Facebook;
use Facebook\FacebookApp;
use Facebook\FacebookRequest;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class FacebookOAuthController extends BaseController {
  public static function addRoutes($routing){
    $routing->get('/facebook/oauth/start', array(new self(), 'redirectToAuthorization'))->bind('facebook_authorize_start');
    $routing->get('/facebook/oauth/handle', array(new self(), 'receiveAuthorizationCode'))->bind('facebook_authorize_redirect');

    $routing->get('/coop/facebook/share', array(new self(), 'shareProgressOnFacebook'))->bind('facebook_share_place');
  }

  /**
   * This page actually redirects to the Facebook authorize page and begins
   * the typical, "auth code" OAuth grant type flow.
   *
   * @return RedirectResponse
   */
  public function redirectToAuthorization(){
    $facebook = $this->createFacebook();
    $helper = $facebook->getRedirectLoginHelper();
    
    $redirectUrl = $this->generateUrl('facebook_authorize_redirect', array(), true);
    $url = $helper->getLoginUrl(
      $redirectUrl,
      array('email', 'publish_actions')      
    );
    
    return $this->redirect($url);
  }

  /**
   * This is the URL that Facebook will redirect back to after the user approves/denies access
   *
   * Here, we will get the authorization code from the request, exchange
   * it for an access token, and maybe do some other setup things.
   *
   * @param  Application             $app
   * @param  Request                 $request
   * @return string|RedirectResponse
   */
  public function receiveAuthorizationCode(Application $app, Request $request){
    $facebook = $this->createFacebook();
    $helper = $facebook->getRedirectLoginHelper();
    
    try{
      $accesToken = $helper->getAccessToken();
    } catch (FacebookResponseException $e) {
      return $this->render('failed_token_request.twig', array(
        'response'      => $e->getMessage()
      ));
    } catch (FacebookSDKException $e){
      return $this->render('failed_token_request.twig', array(
        'response'      => $e->getMessage()
      ));
    }
    
    if (!isset($accesToken)){
      if ($helper->getError()){
        $error_body = 'Error: ' . $helper->getError() . '<br>';
        $eror_body .= 'Error Code: ' . $helper->getErrorCode() . '<br>';
        $eror_body .= 'Error Reason: ' . $helper->getErrorReason() . '<br>';
        $eror_body .= 'Error Description: ' . $helper->getErrorDescription();
        return $this->render('failed_token_request.twig', array(
          'response'      => $eror_body
        ));
      } else {
        $eror_body = 'Bad Request';
        return $this->render('failed_token_request.twig', array(
          'response'      => $eror_body 
        ));
      }
    }
    
    try{
      $response = $facebook->get('/me?fields=id,name', $accesToken);
      $facebookUserId = $response->getGraphUser()['id'];
      
      $user = $this->getLoggedInUser();
      $user->facebookUserId = $facebookUserId;
      //not a real example for this app - just an example idea
      // $user->facebookAccessToken = $facebook->getAccessToken();
      $this->saveUser($user);
      
      return $this->redirect($this->generateUrl('home'));
    } catch (FacebookResponseException $e){
      return $this->render('failed_authorization.twig', array(
        'response'      => $e->getMessage()
      ));
    } catch (FacebookSDKException $e){
      return $this->render('failed_authorization.twig', array(
        'response'      => $e->getMessage()
      ));
    };
  
  }

  /**
   * Posts your current status to your Facebook wall then redirects to
   * the homepage.
   *
   * @return RedirectResponse
   */
  public function shareProgressOnFacebook(){
    $facebook = $this->createFacebook();
    $eggCount = $this->getTodaysEggCountForUser($this->getLoggedInUser());
    
    $fbApp = new FacebookApp(
      getenv('FACEBOOK_APP_ID'),
      getenv('FACEBOOK_APP_SECRET')      
    );
    $accessToken = $fbApp->getAccessToken();
    
    $facebookRequest = new FacebookRequest(
      $fbApp, 
      $accessToken, 
      'POST',
      '/'.$this->getLoggedInUser()->facebookUserId . '/feed',
      array(
        'message' => sprintf('Woh my chickens have laid %s eggs today!', $eggCount)
      )
    );

    try {
      $response = $facebook->getClient()->sendRequest($facebookRequest);
    } catch (FacebookResponseException $e) {
      // https://developers.facebook.com/docs/graph-api/using-graph-api/#errors
      if ($e->getErrorType() == 'OAuthException' || in_array($e->getCode(), array(190, 102))){
        // our token is bad - reauthorize to get a new token
        return $this->redirect($this->generateUrl('facebook_authorize_start'));
      }
      $errorBody = 'Graph returned an error ' . $e->getMessage();
      return $this->render('failed_authorization.twig', array(
        'response'      => $errorBody
      ));
    } catch (FacebookSDKException $e){
      $errorBody = 'Facebook SDK returned an error ' . $e->getMessage();
      return $this->render('failed_authorization.twig', array(
        'response'      => $errorBody
      ));
    }

    return $this->redirect($this->generateUrl('home'));
  }
  
  private function createFacebook(){
    $config = array(
      'app_id'                  => getenv('FACEBOOK_APP_ID'),
      'app_secret'              => getenv('FACEBOOK_APP_SECRET'),
      'default_graph_version'   => 'v2.2'
    );
    
    return new Facebook($config);  
  }
}
