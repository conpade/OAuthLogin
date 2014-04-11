<?php
class OAuthUserRegisterTemplate extends BaseTemplate {

	public function execute() {
?>

<style>
#info{
	background-color: #FAE3E3;
    border-color: #FAC5C5;
    color: #CC0000;
}
</style>

<form action="<?php SpecialPage::getTitleFor( 'OAuthLogin', 'register' )->getLinkUrl();?>" method="post">
	<p>
		用户名：<input name="userName" value="<?php echo $this->data['userName']; ?>" /> 
		<span id="info">用户名无效，或已存在</span>
	</p>
	<p><button>注册</button></p>
</form>

<?php

	}
}
