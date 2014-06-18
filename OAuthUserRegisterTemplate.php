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
<table>
	<tr>
		<td>用户名：</td>
		<td><input name="userName" value="<?php echo $this->data['userName']; ?>" /></td>
	</tr>
	<tr>
		<td>密码：</td>
		<td><input type="password" name="password" value="<?php echo $this->data['password']; ?>" /></td>
	</tr>
	<tr>
		<td>
			<input type="hidden" name="submit" value="1" />
			<button>注册</button>
		</td>
		<td><span id="info"><?php echo $this->data['errorMsg'] ; ?></span></td>
	</tr>
</table>
</form>

<?php

	}
}
