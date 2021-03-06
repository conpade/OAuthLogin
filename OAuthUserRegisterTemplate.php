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
	<section>
		请完善您的账户信息
	</section>

	<section class="mw-form-header">
		<?php $this->html( 'header' ); /* extensions such as ConfirmEdit add form HTML here */ ?>
	</section>

	<?php if(empty($_SESSION['oauthLoginFirstTime'])): ?>
	<tr>
		<td>用户名：</td>
		<td><input name="userName" value="<?php echo $this->data['userName']; ?>" /></td>
	</tr>
	<?php endif; ?>

	<tr>
		<td>密码：</td>
		<td><input type="password" name="password" value="" /></td>
	</tr>
	<tr>
		<td>再次输入密码：</td>
		<td><input type="password" name="password2" value="" /></td>
	</tr>
	<tr>
		<td>邮箱：</td>
		<td><input type="text" name="email" value="<?php echo $this->data['email']; ?>" /></td>
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
