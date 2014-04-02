<?php

class OAuthLoginUI {
	/**
	 * Add a sign in with Twitter button but only when a user is not logged in
	 */
	public function efAddLoginButton( &$out, &$skin ) {
		global $wgUser, $wgExtensionAssetsPath, $wgScriptPath, $wgEnabledOAuthLoginList, $wgOauthSourceList;
	
		if ( !$wgUser->isLoggedIn() ) {
			if(!empty($wgEnabledOAuthLoginList)){
				$link = '';
				foreach($wgEnabledOAuthLoginList as $ol){
					if(!in_array($ol, $wgOauthSourceList) ){
						continue;
					}
					$url = SpecialPage::getTitleFor( 'OauthLogin', 'redirect' )->getLinkUrl( array('source'=>strtolower($ol)) );
					$imgUrl = $wgExtensionAssetsPath . '/OAuthLogin/images/' . $ol . '-login.png';
					$link .= '<a href="'.$url.'"><img src="'.$imgUrl.'" /></a>';
				}

				$script = <<<SCRIPT
jQuery(function(){
	jQuery("#pt-anonlogin, #pt-login").after('<li id="oauth_login">$link</li>');

	jQuery('#oauth_login').on('click', 'a', function(e){
		e.preventDefault();
		var link = jQuery(this).attr('href');
		var width = 680;
		var w = window.open(link, 'a', "width=" + width + ",height=500,menubar=0,scrollbars=1,resizable=1,status=1,titlebar=0,toolbar=0,location=1");
		w.focus();
	});
});
SCRIPT;
				$out->addInlineScript($script);
			}
		}
		return true;
	}
}
