<?php
class WeiboLogin extends OAuthBase
{
	function __construct()
	{
		global $wgWeiboOAuthKey, $wgWeiboOAuthSecret, $wgServer;
		parent::__construct($wgWeiboOAuthKey, $wgWeiboOAuthSecret);
		
		$this->setConfig('redirUrl', $wgServer . SpecialPage::getTitleFor( 'OauthLogin', 'callback' )->getLinkUrl( array('source'=>'weibo')) );
		$this->setConfig('serverName', 'api.weibo.com');
		
		$this->setApiDirs('getCode', '/oauth2/authorize');
		$this->setApiDirs('getToken', '/oauth2/access_token');
		$this->setApiDirs('getTokenInfo', '/oauth2/get_token_info');
		$this->setApiDirs('getUserInfo', '/2/users/show.json');
	}

	public function getRedirectUrl()
	{
		$params = array('client_id' => $this->getConfig('appId'),'response_type'=>'code','redirect_uri'=>$this->getConfig('redirUrl'));
		$url = Net::makeUrl($params, $this->getConfig('serverName'), $this->getApiDirs('getCode'), 'https');
		return $url;
	}

	public function getAccessToken()
	{
		$params = array('grant_type' => 'authorization_code', 'client_id' => $this->getConfig('appId'), 'client_secret' => $this->getConfig('appKey'),
			'code' => $this->getData('code'), 'redirect_uri' => $this->getConfig('redirUrl'));
		$ret = Net::makeRequest($this->getApiUri('getToken'), $params, array(), 'post', 'https');
		if (true === $ret['result'])
		{
			$msg = json_decode($ret['msg'], true);
			if (isset($msg['access_token']))
			{
				$this->setData('token', $msg['access_token']);
				return true;
			}
			else
			{
				$this->setErrMsg($msg);
				throw new OauthException(__METHOD__.' invalid data');
			}
		}
		else
		{
			$this->setErrMsg(array('err' => 1, 'error_msg' => 'Connect error!'));
			throw new OauthException(__METHOD__.' connect err');
		}
	}
	
	public function getTokenInfo()
	{
		$params = array('access_token' => $this->getData('token'));
		$ret = Net::makeRequest($this->getApiUri('getTokenInfo'), $params, array(), 'post', 'https');
		if (true === $ret['result'])
		{
			$msg = json_decode($ret['msg'], true);
			if (isset($msg['uid']))
			{
				$this->setUser('openId', $msg['uid']);
				return true;
			}
			else
			{
				$this->setErrMsg($msg);
				throw new OauthException(__METHOD__.' invalid data');
			}
		}
		else
		{
			$this->setErrMsg(array('err' => 1, 'error_msg' => 'Connect error!'));
			throw new OauthException(__METHOD__.' connect err');
		}
	}

	public function getUserInfo()
	{
		$params = array('access_token' => $this->getData('token'), 'uid' => $this->getUser('openId'));
		$ret = Net::makeRequest($this->getApiUri('getUserInfo'), $params, array(), 'get', 'https');
		if (true === $ret['result'])
		{
			$msg = json_decode($ret['msg'], true);
			if (isset($msg['name']))
			{
				$this->setUser('name', $msg['name']);
				return true;
			}
			else
			{
				$this->setErrMsg($msg);
				throw new OauthException(__METHOD__.' invalid data');
			}
		}
		else
		{
			throw new OauthException(__METHOD__.' connect err');
		}
	}

	public function handlerCallBack($code)
	{
		$this->setData('code', $code);
		$this->getAccessToken();
		$this->getTokenInfo();
		$this->getUserInfo();
	}

	public function getUserData()
	{
		return array(
			'openId' => $this->getUser('openId'), 
			'name' => $this->getUser('name'), 
			'source' => 'weibo'
		);
	}

}