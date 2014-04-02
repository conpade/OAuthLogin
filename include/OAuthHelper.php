<?php
class OAuthException extends Exception{

}
class OAuthHelper{
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

}