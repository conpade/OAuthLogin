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
	 * automaticly generate user 
	 */
	public function generateNewUser($name){
		# Now create a dummy user ($u) and check if it is valid
		$name = trim( $name );

		$u = User::newFromName( $name, 'creatable' );

		if ( $u === false ) {
			return Status::newFatal( 'noname' );
		} elseif ( 0 != $u->idForName() ) {
			return Status::newFatal( 'userexists' );
		}

		return $u;
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

	public function defaultRedirect()
	{
		$returnToTitle = Title::newMainPage();
		$redirectUrl = $returnToTitle->getLinkURL( array(), false );
		header("Location: $redirectUrl",true ,302);
	}

	public function handlerException($exception)
	{
		$errMsg = $exception->getMessage();
		$redirectJs = $this->makeRedirectToReferJS();
		$content = <<<CONTENT
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<title>登陆出错</title>
</head>
<body>
	<div>$errMsg</div>
	$redirectJs
</body>
</html>
CONTENT;
		echo $content;
	}

	public function makeRedirectToReferJS()
	{
		$returnTo = null;
		if(!empty($_SESSION['returnTo'])){
			$returnTo = $_SESSION['returnTo'];
		}
		$returnToUrl = $this->makeReturnToUrl($returnTo);
		$content = <<<CONTENT
<div id="timer"></div>
<script type="text/javascript">
/*<![CDATA[*/
var time = 3;

function redirect(){ 
	window.opener.location = "$returnToUrl";
	window.close();
} 
var leftTime = 0; 
function countDown(){ 
	document.all.timer.innerHTML = (time - leftTime) + "秒后跳转"; 
	leftTime++;
} 
timer=setInterval('countDown()', 1000);
timer=setTimeout('redirect()',time * 1000);

/*]]>*/
</script>
CONTENT;
		return $content;
	}

	public function makeReturnToUrl($returnTo = null)
	{
		global $wgSecureLogin;
		if ( $wgSecureLogin && !$loginForm->mStickHTTPS ) {
			$options = array( 'http' );
			$proto = PROTO_HTTP;
		} elseif ( $wgSecureLogin ) {
			$options = array( 'https' );
			$proto = PROTO_HTTPS;
		} else {
			$options = array();
			$proto = PROTO_RELATIVE;
		}
		if( empty($returnTo) ){
			$returnToTitle = Title::newMainPage();
		} else {
			$returnToTitle = Title::newFromText( $returnTo );
			if ( !$returnToTitle ) {
				$returnToTitle = Title::newMainPage();
			}
		}
		$redirectUrl = $returnToTitle->getLinkURL( array(), false, $proto );
		return $redirectUrl;
	}

}