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
	const OAUTH_SESSION_KEY='OAuthSession';
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

	public function setSessionValue($key,$value){
		$_SESSION[self::OAUTH_SESSION_KEY][$key]=$value;
	}

	public function getSessionValue($key){
		if(isset($_SESSION[self::OAUTH_SESSION_KEY][$key]))
			return $_SESSION[self::OAUTH_SESSION_KEY][$key];
		return null;
	}

	public function clearSessionValue($key){
		if(isset($_SESSION[self::OAUTH_SESSION_KEY][$key])){
			unset($_SESSION[self::OAUTH_SESSION_KEY][$key]);
			return true;
		}else{
			return false;
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
		$this->cleanOAuthSession();
		$errMsg = $exception->getMessage();
		$redirectJs = $this->makeCloseChildWindowJS();
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

	public function makeCloseChildWindowJS()
	{
		$content = <<<CONTENT
<div id="timer"></div>
<script type="text/javascript">
/*<![CDATA[*/
var time = 3;

function redirect(){ 
	window.close();
} 
var countTime = 1; 
function countDown(){ 
	document.all.timer.innerHTML = (time - countTime) + "秒后跳转"; 
	countTime++;
} 
timer=setInterval('countDown()', 1000);
timer=setTimeout('redirect()',time * 1000);

/*]]>*/
</script>
CONTENT;
		return $content;
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

	public function cleanOAuthSession(){
		unset($_SESSION['returnTo']);
		unset($_SESSION['oauthUser']);
		unset($_SESSION['qqLoginState']);
		unset($_SESSION['oauthLoginFirstTime']);
	}

	public function checkPassword($user,$password,$password2){
		if($password2 !== $password)
			return '2次输入的密码不一致';
		if ($user->getPasswordValidity($password) !== true) {
			$errorMsg = $user->getPasswordValidity($password);
			
			if(empty($errorMsg)){
				$errorMsg = '密码不符合要求';
			}
			return $errorMsg;
		} else {
			return true;
		}
	}

	public function checkEmail($email){
		global $wgEmailAuthentication;
		if ( $wgEmailAuthentication && Sanitizer::validateEmail( $email ) ) {
			return true;
		}
		return '邮件格式不正确';
		// $error = new RawMessage( '' );
		// return $abortError->text();
	}

	public function checkFirstTimeOAuthLogin($user,$password,$password2,$email){
		if($user instanceof Status){
			$errorMsg = '用户名已存在或者含有非法字符';
			return $errorMsg;
		} else {
			$msg = $this->checkPassword($user,$password,$password2);
			if($msg !== true){
				return $msg;
			}
		}

		$msg = $this->checkEmail($email);
		if($msg !== true){
			return $msg;
		}

		$msg = $this->checkCaptcha($user);
		if($msg !== true){
			return $msg;
		}

		return 'success';
	}

	public function checkNotFirstTimeOAuthLogin($user,$password,$password2,$email){
		$msg = $this->checkPassword($user,$password,$password2);
		if($msg !== true){
			$errorMsg = $msg;
			return $errorMsg;
		}

		$msg = $this->checkEmail($email);
		if($msg !== true){
			return $msg;
		}

		$msg = $this->checkCaptcha($user);
		if($msg !== true){
			return $msg;
		}

		return 'success';
	}

	public function checkCaptcha($u){
		$abortError = '';
		if ( !wfRunHooks( 'AbortNewAccount', array( $u, &$abortError ) ) ) {
			// Hook point to add extra creation throttles and blocks
			wfDebug( "LoginForm::addNewAccountInternal: a hook blocked creation\n" );
			$abortError = new RawMessage( $abortError );
			return $abortError->text();
		}
		return true;
	}

}