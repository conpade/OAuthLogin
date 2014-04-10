<?php
/**
 * SpecialTwitterLogin.php
 * Written by David Raison, based on the guideline published by Dave Challis 
 * at http://blogs.ecs.soton.ac.uk/webteam/2010/04/13/254/
 * @license: LGPL (GNU Lesser General Public License) http://www.gnu.org/licenses/lgpl.html
 *
 * @file SpecialTwitterLogin.php
 * @ingroup TwitterLogin
 *
 * @author David Raison
 *
 * Uses the twitter oauth library by Abraham Williams from https://github.com/abraham/twitteroauth
 *
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This is a MediaWiki extension, and must be run from within MediaWiki.' );
}

$wgExtensionCredits['specialpage'][] = array(
	'path' => __FILE__,
	'name' => 'OAuthLogin',
	'version' => '0.02',
	'author' => array( 'Francis Lee', '' ), 
	// 'url' => 'https://www.mediawiki.org/wiki/Extension:TwitterLogin',
	'descriptionmsg' => 'oauth login'
);

$wgOauthSourceList = array('weibo','qq');

// Create a twiter group
$wgGroupPermissions['oauth'] = $wgGroupPermissions['user'];

$wgAutoloadClasses['Net'] = dirname(__FILE__) . '/include/Net.php';
$wgAutoloadClasses['OAuthBase'] = dirname(__FILE__) . '/include/OAuthBase.php';
$wgAutoloadClasses['WeiboLogin'] = dirname(__FILE__) . '/include/WeiboLogin.php';
$wgAutoloadClasses['OAuthUserModel'] = dirname(__FILE__) . '/include/OAuthUserModel.php';

$wgAutoloadClasses['OAuthHelper'] = dirname(__FILE__) . '/include/OAuthHelper.php';
$wgAutoloadClasses['RegisterForm'] = dirname(__FILE__) . '/include/RegisterForm.php';
$wgAutoloadClasses['SpecialOAuthLogin'] = dirname(__FILE__) . '/SpecialOAuthLogin.php';
$wgAutoloadClasses['OAuthLoginUI'] = dirname(__FILE__) . '/OAuthLogin.body.php';

$wgExtensionMessagesFiles['OAuthLogin'] = dirname(__FILE__) .'/OAuthLogin.i18n.php';
$wgExtensionAliasFiles['OAuthLogin'] = dirname(__FILE__) .'/OAuthLogin.alias.php';

// speacial page
$wgSpecialPages['OAuthLogin'] = 'SpecialOAuthLogin';
$wgSpecialPageGroups['OAuthLogin'] = 'login';

define('OAUTH_USER_TABLE', 'oauth_user');
// update schema
$wgHooks['LoadExtensionSchemaUpdates'][] = 'efSetupOAuthLoginSchema';

function efSetupOAuthLoginSchema( $updater ) {
	$updater->addExtensionUpdate( array( 'addTable', OAUTH_USER_TABLE,
		dirname(__FILE__) . '/schema/OAuthUser.sql', true ) );
	return true;
}

// hooks
$olb = new OAuthLoginUI;
$wgHooks['BeforePageDisplay'][] = array( $olb, 'efAddLoginButton' );

// $sol = new SpecialOAuthLogin;
// $wgHooks['UserLogoutComplete'][] = array($sol,'efOAuthLogout');