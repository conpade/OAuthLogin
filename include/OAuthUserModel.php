<?php
class OAuthUserModel
{
	private $_tableName;

	public $openId;
	public $userId;
	public $userName;
	public $source;
	public $createdTime;

	public $sourceUserName;
	public $initialized;

	public function __construct($userData)
	{
		$this->_tableName = OAUTH_USER_TABLE;

		$this->openId = $userData['openId'];
		$this->source = $userData['source'];
		$this->userName = $userData['name'];
	}

	public function load()
	{
		if(!empty($this->userId))
		{
			$res = $this->getDbr()->select(
				$this->_tableName, 
				array('open_id','source','created_time'),
				array( 'user_id' => $this->userId ),
				__METHOD__
			);
			if ( $row = $this->getDbr()->fetchObject( $res ) ) 
			{
				$this->openId = $row['open_id'];
				$this->source = $row['source'];
				$this->createdTime = $row['created_time'];

				$this->sourceUserName = $row['source_user_name'];
				$this->initialized = $row['initialized'];
			}
		}
	}

	public function loadByOpenId()
	{
		if(!empty($this->openId))
		{
			$res = $this->getDbr()->select(
				$this->_tableName, 
				array('user_id','source','created_time'),
				array( 'open_id' => $this->openId ),
				__METHOD__
			);
			if ( $row = $this->getDbr()->fetchObject( $res ) ) 
			{
				$this->userId = $row->user_id;
				$this->source = $row->source;
				$this->createdTime = $row->created_time;
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
			return true;
		}
		return false;
	}

	public function save()
	{
		if($this->isExist()){
			$this->getDbw()->update( $this->_tableName,
				array(
					'source' => $this->source, 
					'source_user_name' => $this->sourceUserName, 
					'initialized' => $this->initialized
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
					'source_user_name' => $this->sourceUserName, 
					'initialized' => $this->initialized
				),
				__METHOD__,
				array()
			);
		}
		
	}
}