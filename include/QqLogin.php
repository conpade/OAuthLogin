<?php
class QqLogin extends OAuthBase
{
	function __construct()
	{
		global $wgQqOAuthKey, $wgQqOAuthSecret, $wgServer;
		parent::__construct($wgQqOAuthKey, $wgQqOAuthSecret);

		$redirUrl = strpos($wgServer, '//') === 0 ? 'http:' . $wgServer : $wgServer;
		$redirUrl .= SpecialPage::getTitleFor( 'OAuthLogin', 'callback' )->getLinkUrl( array('source'=>'qq'));
		
		$this->setConfig('redirUrl', $redirUrl);
		$this->setConfig('serverName', 'graph.qq.com');
		
		$this->setApiDirs('getCode', '/oauth2.0/authorize');
		$this->setApiDirs('getToken', '/oauth2.0/token');
		$this->setApiDirs('getTokenInfo', '/oauth2.0/me');
		$this->setApiDirs('getUserInfo', '/user/get_user_info');
	}

	public function getRedirectUrl()
	{
		$params = array('client_id' => $this->getConfig('appId'),'response_type'=>'code','redirect_uri'=>$this->getConfig('redirUrl'),'state'=>$this->getState());
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
			$msgArr = explode('&',$ret['msg']);
			$msg = array();
			foreach($msgArr as $value)
			{
				$data = explode('=',$value);
				$msg[$data[0]] = $data[1];
			}
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
			$msg = array();
			if(!preg_match('/(?<=callback\().+(?=\);)/', $ret['msg'], $msg))
			{
				throw new OauthException(__METHOD__.' invalid data');
			}
			$msg = json_decode($msg[0], true);
			if (isset($msg['openid']))
			{
				$this->setUser('openId', $msg['openid']);
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
		$params = array('access_token' => $this->getData('token'), 'oauth_consumer_key' => $this->getConfig('appId'), 'openid' => $this->getUser('openId'));
		$ret = Net::makeRequest($this->getApiUri('getUserInfo'), $params, array(), 'get', 'https');
		if (true === $ret['result'])
		{
			$msg = json_decode($ret['msg'], true);
			if (isset($msg['ret']) && $msg['ret']===0)
			{
				$nickname = $msg['nickname'];
				$this->setUser('name', $nickname);
				return true;
			}
			else
			{
				$this->setErrMsg($msg['msg']);
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
