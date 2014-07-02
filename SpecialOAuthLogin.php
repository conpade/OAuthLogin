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

	// default method called by a specialpage
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
			$this->helper->handlerException($e);
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

	// redirect to third-party login page
	private function _redirect(){
		// do not allow login when user is already logged in
		if($this->helper->isUserLoggedIn())
			$this->helper->defaultRedirect();

		// set return to
		$returnTo = !empty($_GET['returnto']) ? $_GET['returnto'] : '';
		$this->helper->setSessionData('returnTo',$returnTo);

		$source = $this->helper->getSource();
		$oauth = $this->helper->getOAuthObj($source);
		if($source == 'qq'){
			$state = md5(microtime(true) . $this->helper->createRandomString(8));
			$this->helper->setSessionData('qqLoginState',$state);
			$oauth->setState($state);
		}
		$redirectUrl = $oauth->getRedirectUrl();
		
		header("Location: $redirectUrl",true ,302);
	}

	// handler callback
	private function _handleCallback(){
		// do not allow login when user is already logged in
		if($this->helper->isUserLoggedIn())
			$this->helper->defaultRedirect();

		$source = $this->helper->getSource();

		// anti-csrf (only qq)
		if($source == 'qq'){
			$state = !empty($_GET['state']) ? $_GET['state'] : '';
			$ourState = $this->helper->getSessionData('qqLoginState');

			if((string)$ourState !== (string)$state){
				var_dump($ourState);
				var_dump($state)
				throw new OAuthException('Csrf attack');
			}
			$this->helper->clearSessionData('qqLoginState');
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
			if(!$oauthUser->isInitialized()){
				$_SESSION['oauthUser'] = array(
					'openId' => $oauthUser->openId,
					'sourceUserName' => $oauthUserData['name'],
				);
				$_SESSION['oauthLoginFirstTime'] = true;
				$url = SpecialPage::getTitleFor( 'OAuthLogin', 'register' )->getLinkUrl();
				header("Location: $url", true, 302);
				return true;
			} else {
				// login
				$oauthUser->sourceUserName=$oauthUserData['name'];
				$oauthUser->save();
				$this->_login($user);
				$this->_loginSuccess();
			}
		} else {
			$_SESSION['oauthUser'] = array(
				'openId' => $oauthUser->openId,
				'source' => $oauthUser->source,
				'sourceUserName' => $oauthUser->sourceUserName,
			);
			$url = SpecialPage::getTitleFor( 'OAuthLogin', 'register' )->getLinkUrl( array('userName'=>$oauthUser->sourceUserName) );
			header("Location: $url", true, 302);
			return true;
		}
	}

	private function _processRegister($password, $email, $user, $oauthUser){
		try {
			// need to add transaction?
			$user->addToDatabase();
			$user->setPassword($password);

			// I do not know group's function
			// $user->addGroup('oauth');
			//$user->confirmEmail();
			$user->setToken();
			$user->saveSettings();

			// email
			$user->setEmail($email);
			$user->saveSettings();
			$user->sendConfirmationMail();

			// add user count
			DeferredUpdates::addUpdate( new SiteStatsUpdate( 0, 0, 0, 0, 1 ) );

			$oauthUser->userId = $user->getId();
			$oauthUser->initialized = '1';
			$oauthUser->save();
			return $user;

		} catch(OAuthException $e) {
			// need to roll back?
			throw new OAuthException('location:' . __method__ . '\n' . $e->getMessage()); 
		}
	}

	/**
	 * may have some problem
	 * from SpecialUserlogin.php function processLogin()
	 */
	private function _login($user){
		global $wgSecureLogin,$wgCookieSecure,$wgMemc;

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

		// Reset the throttle
		$request = $this->getRequest();
		$key = wfMemcKey( 'password-throttle', $request->getIP(), md5( $user->mName ) );
		$wgMemc->delete( $key );

		/* Replace the language object to provide user interface in
		 * correct language immediately on this first page load.
		 */
		$code = $request->getVal( 'uselang', $user->getOption( 'language' ) );
		$userLang = Language::factory( $code );
		$wgLang = $userLang;
		$this->getContext()->setLanguage( $userLang );

		// $this->renewSessionId();
		if ( $wgSecureLogin && !$this->mStickHTTPS ) {
			$wgCookieSecure = false;
		}
		wfResetSessionID();
		
		// $this->successfulLogin();
		$injected_html = '';
		wfRunHooks( 'UserLoginComplete', array( &$user, &$injected_html ) );
		
	}

	private function _loginSuccess(){
		global $wgRedirectOnLogin, $wgSecureLogin;
		$loginForm = $this->loginForm;

		$returnTo = $this->helper->getSessionData('returnTo');
		if( empty($returnTo) ){  // if return to is empty , reload
			$returnScript = 'window.opener.location.reload();';
		}
		else{
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

			$redirectUrl = $returnToTitle->getLinkURL( array(), false, $proto );
			$returnScript = 'window.opener.location="'.$redirectUrl.'";';
		}
		$closeScript = 'window.close();';
		$html = '<script type="text/javascript">' . $returnScript . $closeScript . '</script>';
		// header("Content-type: text/html; charset=utf-8"); 
		$this->helper->cleanOAuthSession();
		echo $html;
	}

	private function _register(){
		if(empty($_SESSION['oauthUser'])){
			$returnToTitle = Title::newMainPage();
			$redirectUrl = $returnToTitle->getLinkURL( array(), false );
			header("Location: $redirectUrl",true ,302);
		}

		$userName = (!empty($_GET['userName']) ? $_GET['userName'] : '');
		$password = (!empty($_POST['password']) ? $_POST['password'] : '');
		$password2 = (!empty($_POST['password2']) ? $_POST['password2'] : '');
		$email = (!empty($_POST['email']) ? $_POST['email'] : '');

		$errorMsg = '';
		if(!empty($_POST['submit'])){
			if(empty($_SESSION['oauthLoginFirstTime'])){
				$userName = (!empty($_POST['userName']) ? $_POST['userName'] : '');
				$oauthUserData = $_SESSION['oauthUser'];

				$oauthUserData['name'] = $userName;
				$oauthUser = new OAuthUserModel($oauthUserData);
				$user = $this->helper->generateNewUser($oauthUser->sourceUserName);

				$errorMsg = $this->helper->checkFirstTimeOAuthLogin($user,$password,$password2,$email);

				if($errorMsg==='success'){
					// register
					$this->_processRegister($password, $email, $user, $oauthUser);
					// login
					$this->_login($user);
					$this->_loginSuccess();
					return true;
				}
			} else { // not first time
				$oauthUserData = $_SESSION['oauthUser'];
				$oauthUser = new OAuthUserModel($oauthUserData['openId']);
				$oauthUser->loadByOpenId();
				$user = User::newFromId($oauthUser->userId);
				$user->loadFromId();

				$errorMsg = $this->helper->checkNotFirstTimeOAuthLogin($user,$password,$password2,$email);

				if($errorMsg==='success'){
					// update user
					$user->setPassword($password);
					$user->saveSettings();

					// email
					$user->setEmail($email);
					$user->saveSettings();
					$user->sendConfirmationMail();

					$oauthUser->sourceUserName=$oauthUserData['sourceUserName'];
					$oauthUser->initialized=1;
					$oauthUser->save();
					// login
					$this->_login($user);
					$this->_loginSuccess();
					return true;
				}
			}
		}

		global $wgOut;
		require('OAuthUserRegisterTemplate.php');
		$wgOut->setPagetitle("OAuthUser Register");
		$template = new OAuthUserRegisterTemplate;
		$template->set( 'userName', $userName );
		$template->set( 'password', $password );
		$template->set( 'email', $email );
		$template->set( 'errorMsg', $errorMsg );
		$template->set( 'header', '' );

		wfRunHooks( 'UserCreateForm', array( &$template ) );

		$wgOut->addTemplate( $template );
	}
}
