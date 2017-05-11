<?php

namespace OAuth2Demo\Client\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Facebook\Facebook;
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
    
    die('Todo: Handle after Facebook redirects to us');
  }

  /**
   * Posts your current status to your Facebook wall then redirects to
   * the homepage.
   *
   * @return RedirectResponse
   */
  public function shareProgressOnFacebook(){
    die('Todo: Use Facebook\'s API to post to someone\'s feed');

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
