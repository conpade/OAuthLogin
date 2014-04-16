<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is a MediaWiki extension, and must be run from within MediaWiki.' );
}

class SpecialOAuthLogin extends SpecialPage {
	public $helper;

	public function __construct(){
		parent::__construct('OAuthLogin');
		$this->helper = new OAuthHelper($this); 
	}

	// default method being called by a specialpage
	public function execute( $parameter ){
		$this->helper->setupSession();
		try{
			switch($parameter){
				case 'redirect':
					$this->_redirect();
					break;
				case 'callback':
					$this->_handleCallback();
					break;
				case 'register':
					$this->_register();
					break;
				default:
					$this->_default();
			}
		} catch(OAuthException $e) {
			echo $e->getMessage();
			die();
		}
		
	}

	// info page
	private function _default(){
		global $wgOut;
		$this->setHeaders();

		$wgOut->setPagetitle("OAuthLogin");

		if ( $this->getUser()->isLoggedIn() ) {
			$wgOut->addWikiMsg( 'You are already logged in.' );
		} else {
			$wgOut->addWikiMsg( 'You are not logged in.' );
		}
		return true;
	}

	// redirect to source platform's login page
	private function _redirect(){
		// do not allow login when user is already logged in
		if($this->helper->isUserLoggedIn())
			return false;

		// set return to
		$_SESSION['returnTo'] = $_GET['returnto'];

		$source = $this->helper->getSource();
		$oauth = $this->helper->getOAuthObj($source);
		if($source == 'qq'){
			$state = md5(microtime(true) . $this->helper->createRandomString(8));
			$_SESSION['qqLoginState'] = $state;
			$oauth->setState($state);
		}
		$redirectUrl = $oauth->getRedirectUrl();
		
		header("Location: $redirectUrl",true ,302);
	}

	// handler callback
	private function _handleCallback(){
		// do not allow login when user is already logged in
		if($this->helper->isUserLoggedIn())
			return false;

		$source = $this->helper->getSource();

		// anti-csrf (only qq)
		if($source == 'qq'){
			$state = !empty($_GET['state']) ? $_GET['state'] : '';
			$ourState = !empty($_SESSION['qqLoginState']) ? $_SESSION['qqLoginState'] : '';
			unset($_SESSION['qqLoginState']);
			if($ourState !== $state){
				throw new OAuthException('Csrf attack');
			}
		}

		$authCode = $_GET['code'];
		if (!$authCode){
			throw new OAuthException('Empty code');
		}

		// do oauth
		$oauth = $this->helper->getOAuthObj($source);
		$oauth->handlerCallBack($authCode);

		// get third-party-user's data
		$oauthUserData = $oauth->getUserData();
		
		$oauthUser = new OAuthUserModel($oauthUserData);

		// is user Existed
		if ($oauthUser->isExist()){
			$oauthUser->loadByOpenId();
			$user = User::newFromId($oauthUser->userId);
		} else {
			// create new user
			$user = $this->helper->generateNewUser($oauthUser->userName);

			if($user === false){
				$_SESSION['oauthUser'] = array(
					'openId' => $oauthUser->openId,
					'source' => $oauthUser->source,
				);
				$url = SpecialPage::getTitleFor( 'OAuthLogin', 'register' )->getLinkUrl( array('userName'=>$oauthUser->userName) );
				header("Location: $url",true ,302);
			} 
			// register
			$this->_processRegister($user, $oauthUser);
		}
		// login
		$this->_login($user);
		$this->_loginSuccess();
	}

	private function _processRegister($user, $oauthUser){
		try {
			// need to add transaction?
			$user->addToDatabase();
			$user->setPassword(User::randomPassword());
			// I do not know group's function
			// $user->addGroup('oauth');
			//$user->confirmEmail();
			$user->setToken();
			$user->saveSettings();

			// add user count
			DeferredUpdates::addUpdate( new SiteStatsUpdate( 0, 0, 0, 0, 1 ) );

			$oauthUser->userId = $user->getId();
			$oauthUser->save();
			return $user;

		} catch(OAuthException $e) {
			// need to roll back?
			throw new OAuthException('location:' . __method__ . '\n' . $e->getMessage()); 
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

		if( empty($_SESSION['returnTo']) ){  // if return to is empty , reload
			$returnScript = 'window.opener.location.reload();';
		}
		else{
			$returnTo = $_SESSION['returnTo'];
			$returnToTitle = Title::newFromText( $returnTo );
			if ( !$returnToTitle ) {
				$returnToTitle = Title::newMainPage();
			}

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

			$redirectUrl = $returnToTitle->getLinkURL( array(), false );
			$returnScript = 'window.opener.location="'.$redirectUrl.'";';
		}
		$closeScript = 'window.close();';
		$html = '<script type="text/javascript">' . $returnScript . $closeScript . '</script>';
		// header("Content-type: text/html; charset=utf-8"); 
		echo $html;
	}

	private function _register(){
		if(empty($_SESSION['oauthUser'])){
			$returnToTitle = Title::newMainPage();
			$redirectUrl = $returnToTitle->getLinkURL( array(), false );
			header("Location: $redirectUrl",true ,302);
		}

		$userName = (!empty($_REQUEST['userName']) ? $_REQUEST['userName'] : '');
		if(!empty($userName)){
			$oauthUserData = $_SESSION['oauthUser'];
			$oauthUserData['name'] = $userName;
			$oauthUser = new OAuthUserModel($oauthUserData);
			$user = $this->helper->generateNewUser($oauthUser->userName);
			if($user != false){
				unset($_SESSION['oauthUser']);
				// register
				$this->_processRegister($user, $oauthUser);
				// login
				$this->_login($user);
				$this->_loginSuccess();
				return true;
			}
		}

		global $wgOut;
		require('OAuthUserRegisterTemplate.php');
		$wgOut->setPagetitle("OAuthUser Register");
		$template = new OAuthUserRegisterTemplate;
		$template->set( 'userName', $oauthUser->userName );
		$wgOut->addTemplate( $template );
	}
}
