<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is a MediaWiki extension, and must be run from within MediaWiki.' );
}

class SpecialOAuthLogin extends SpecialPage {
	public $helper;

	public function __construct(){
		parent::__construct('OAuthLogin');
		$this->helper = new OAuthHelper; 
	}

	// default method being called by a specialpage
	public function execute( $parameter ){

		$this->setHeaders();

		switch($parameter){
			case 'redirect':
				$this->_redirect();
			break;
			case 'callback':
				$this->_handleCallback();
			break;
			default:
				$this->_default();
			break;
		}
	}

	private function _default(){
		global $wgOut;

		$wgOut->setPagetitle("OAuth Login");

		if ( $this->getUser()->isLoggedIn() ) {
			$wgOut->addWikiMsg( 'OAuth-notloggedin' );
		} else {
			$wgOut->addWikiMsg( 'OAuth-alreadyloggedin' );
		}
		return true;
	}

	private function _redirect(){
		if($this->getUser()->isLoggedIn())
			return ;
		// set return to
		$source = $this->helper->getSource();
		$oauth = $this->helper->getOAuthObj($source);
		$redirectUrl = $oauth->getRedirectUrl();
		header("Location: $redirectUrl",true ,302);
	}


	private function _handleCallback(){
		if($this->getUser()->isLoggedIn())
			return ;
		$source = $this->helper->getSource();
		// todo anti-csrf (only qq)
		if($source == 'qq'){
			$state = $_GET['state'];
			if($_SESSION['state'] != $state){
				throw new OAuthException('Csrf attack');
			}
		}

		$authCode = $_GET['code'];
		if (!$authCode){
			throw new OAuthException('Empty code');
		}

		$oauth = $this->helper->getOAuthObj($source);
		$oauth->handlerCallBack($authCode);
		$oauthUserData = $oauth->getUserData();
		$oauthUser = new OAuthUserModel($oauthUserData);

		// Is user Existed
		if ($oauthUser->isExist()){
			$oauthUser->loadByOpenId();
			$user = User::newFromId($oauthUser->userId);
		}
		else {
			// check user name
			$user = $this->_generateNewUser($oauthUser->userName);
			// register
			$this->_register($user, $oauthUser);
		}
		// login
		$this->_login($user);
		// close & refresh
		$this->_loginSuccess();
	}

	private function _isUserNameExisted($name){
    	$user = User::newFromName( $name );
		if( $user->getId() == 0 )
			return false;
		else 
			return true;
    }

    private function _generateNewUser($name, $first = true){
    	if($first){
    		$suffix = '';
    	}
    	else{
    		$suffix = '_' . rand(1000,9999);
    	}
    	$newName = $name . $suffix;
    	if($this->_isUserNameExisted($newName))
			return $this->_generateNewUser($name, false);
		else
			return User::newFromName( $newName );
    }

	private function _register(&$user, &$oauthUser){
		// todo add transaction
		global $wgAuth;

		try {
			$user->addToDatabase();
			// $user->setRealName($name);

			if ( $wgAuth->allowPasswordChange() )
				$user->setPassword(User::randomPassword());

			$user->addGroup('oauth');
			//$user->confirmEmail();
			$user->setToken();
			$user->saveSettings();

			// add user count
			DeferredUpdates::addUpdate( new SiteStatsUpdate( 0, 0, 0, 0, 1 ) );

			$oauthUser->userId = $user->getId();
			$oauthUser->save();
			
			return true;

		} catch( Exception $e ) {
			print( $e->getTraceAsString() );
			return false;
		}
	}

	private function _login($user){
		global $wgSecureLogin;

		$this->loginForm = new LoginForm;
		$loginForm = $this->loginForm;
		$loginForm->load();
		$user->load();

		if ( $user->requiresHTTPS() ) {
			$loginForm->mStickHTTPS = true;
		}

		if ( $wgSecureLogin && !$this->mStickHTTPS ) {
			$user->setCookies( null, false );
		} else {
			$user->setCookies();
		}
		$injected_html = '';
		wfRunHooks( 'UserLoginComplete', array( &$user, &$injected_html ) );
	}

	private function _loginSuccess(){
		global $wgRedirectOnLogin, $wgSecureLogin;
		$loginForm = $this->loginForm;

		if(1){  // todo if current page is logout , redir to main
			$returnScript = 'window.opener.location.reload();';
		}
		else{
			$returnToTitle = Title::newMainPage();

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

			$redirectUrl = $returnToTitle->getFullURL( array(), false, $proto );
			$returnScript = 'window.opener.location='.$redirectUrl.';';
		}
		
		$closeScript = 'window.close();';
		$html = '<script type="text/javascript">' . $returnScript . $closeScript . '</script>';
		echo $html;
	}

	public function efOAuthLogout(){
		if (session_id() == '') {
			session_start();
		}
		session_destroy();
		return true;
	}
	
}