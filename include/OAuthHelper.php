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

	public function isCreatableName($name){
		if( User::isCreatableName( $name ) )
			return true;
		else 
			return false;
	}

	/**
	 * automaticly generate user 
	 * I am not sure QQ or Weibo's username is valid in mw
	 */
	public function generateNewUser($name){
		if($this->isCreatableName($name))
			return User::newFromName( $name );
		else
			return false;
	}

	public function createRandomString($len)
	{
		$str = '';
		for ($i = 0; $i < $len; $i++)
		{
			$str .= chr(mt_rand(33, 126));
		}
		return $str;
	}

}