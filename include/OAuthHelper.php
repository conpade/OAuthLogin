<?php
class OAuthException extends Exception{

}

/**
 * OAuthHelper
 * 
 * Helper for oauth login controller
 * Keep the struct of controller
 * Write details here
 *
 * @author Francis Conpade
 */
class OAuthHelper{
	public $controller;

	public function __construct($controller){
		$this->controller = $controller;
	}

	public function getSource(){
		global $wgOauthSourceList;
		if(!isset($_GET['source'])){
			throw new OAuthException('Empty source');
		}
		if(!in_array($_GET['source'], $wgOauthSourceList) ){
			throw new OAuthException('Invalid source');
		}
		return $_GET['source'];
	}

	public function getOAuthObj($source){
		$class = ucfirst($source) . 'Login';
		return new $class;
	}

	public function isUserLoggedIn(){
		return $this->controller->getUser()->isLoggedIn();
	}

	public function setupSession(){
		if (session_id() == '') {
			wfSetupSession();
		}
	}

	/**
	 * get state for csrf
	 */
	public function getState(){

	}



}