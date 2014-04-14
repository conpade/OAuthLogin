OAuthLogin
==========

mediawiki-oauthlogin

Place the OAuthLogin directory within the main MediaWiki 'extensions' directory. Then, in the file LocalSettings.php in the main MediaWiki directory, add the following line:

require_once "$IP/extensions/OAuthLogin/OAuthLogin.php";
This extension requires an additional table in your MediaWiki database. To install it, use MediaWiki' update.php script:
/w$ php maintenance/update.php

Configuration
In order to use this extension, you will need to register your MediaWiki installation as a Weibo app. You can do so here.
http://open.weibo.com/wiki/%E8%BF%9E%E6%8E%A5%E5%BE%AE%E5%8D%9A

When you have registered your application, you will get a consumer key and secret. Add these to your LocalSettings.php file, just below the line where you required the extension:

$wgWeiboOAuthKey = 'your_key';
$wgWeiboOAuthSecret = 'your_secret';

Then in order to enable this source , just add these to your LocalSettings.php file .
$wgEnabledOAuthLoginList = array('weibo');

If you want to add QQ login. Add these to your LocalSettings.php file:
$wgQqOAuthKey = 'your_key';
$wgQqOAuthSecret = 'your_secret';

To enable qq login. Add 'qq' to $wgEnabledOAuthLoginList:
$wgEnabledOAuthLoginList = array('weibo','qq');