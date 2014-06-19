<?php
class OAuthUserModel
{
	private $_tableName;

	public $openId;
	public $userId;
	public $source;
	public $createdTime;
	public $lastUpdatedTime;

	public $sourceUserName;
	public $initialized;

	public function __construct($userData = null)
	{
		$this->_tableName = OAUTH_USER_TABLE;
		if(is_array($userData)){
			$this->openId = $userData['openId'];
			$this->source = $userData['source'];
			$this->sourceUserName = $userData['name'];
		} elseif(is_numeric($userData)) {
			$this->openId = $userData;
		}
	}

	public function load()
	{
		if(!empty($this->userId))
		{
			$res = $this->getDbr()->select(
				$this->_tableName, 
				array('open_id','source','created_time','source_user_name','initialized'),
				array( 'user_id' => $this->userId ),
				__METHOD__
			);
			if ( $row = $this->getDbr()->fetchObject( $res ) ) 
			{
				$this->openId = $row->open_id;
				$this->source = $row->source;
				$this->createdTime = $row->created_time;

				$this->sourceUserName = $row->source_user_name;
				$this->initialized = $row->initialized;
			}
		}
	}

	public function loadByOpenId()
	{
		if(!empty($this->openId))
		{
			$res = $this->getDbr()->select(
				$this->_tableName, 
				array('user_id','source','created_time','source_user_name','initialized'),
				array( 'open_id' => $this->openId ),
				__METHOD__
			);
			if ( $row = $this->getDbr()->fetchObject( $res ) ) 
			{
				$this->userId = $row->user_id;
				$this->source = $row->source;
				$this->createdTime = $row->created_time;

				$this->sourceUserName = $row->source_user_name;
				$this->initialized = $row->initialized;
			}
		}
	}

	public function getDbr()
	{
		return wfGetDB(DB_SLAVE);
	}

	public function getDbw()
	{
		return wfGetDB(DB_MASTER);
	}

	public function isExist()
	{
		$res = $this->getDbr()->select(
			$this->_tableName, 
			'user_id',
			array( 'open_id' => $this->openId ),
			__METHOD__
		);
		if ( $row = $this->getDbr()->fetchObject( $res ) ) 
		{
			return $row->user_id;
		}
		return false;
	}

	public function isInitialized()
	{
		$res = $this->getDbr()->select(
			$this->_tableName, 
			'initialized',
			array( 'open_id' => $this->openId ),
			__METHOD__
		);
		if ( $row = $this->getDbr()->fetchObject( $res ) ) 
		{
			$this->initialized = $row->initialized;
			return $this->initialized;
		}
		return false;
	}

	public function save()
	{
		if($this->isExist()){
			$this->getDbw()->update( $this->_tableName,
				array(
					'source_user_name' => $this->sourceUserName, 
					'last_updated_time' => date('Y-m-d H:i:s'),
					'initialized' => $this->initialized,
				), 
				array( /* WHERE */
					'open_id' => $this->openId
				), 
				__METHOD__
			);
		} else {
			return $this->getDbw()->insert(
				$this->_tableName,
				array(
					'user_id' => $this->userId, 
					'open_id' => $this->openId, 
					'source' => $this->source, 
					'created_time' => date('Y-m-d H:i:s'),
					'last_updated_time' => date('Y-m-d H:i:s'),
					'source_user_name' => $this->sourceUserName, 
					'initialized' => $this->initialized
				),
				__METHOD__,
				array()
			);
		}
		
	}
}