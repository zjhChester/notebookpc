<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

class Mobile extends OAuth2Client {
	public function __construct($ak, $sk) {
		parent::__construct($ak, $sk);
		$this->stateParam['from'] = 'mobile';
	}

	public function showLoginUrl($calback_url = '') {

	}

	public function user() {
		global $_GPC, $_W;
		$mobile = trim($_GPC['username']);
		$member['password'] = $_GPC['password'];
		pdo_delete('users_failed_login', array('lastupdate <' => TIMESTAMP-3600));
		$failed = pdo_get('users_failed_login', array('username' => $mobile, 'ip' => CLIENT_IP));
		if ($failed['count'] >= 5) {
			return error('-1', '输入密码错误次数超过5次，请在1小时后再登录');
		}
		if (!empty($_W['setting']['copyright']['verifycode'])) {
			$verify = trim($_GPC['verify']);
			if (empty($verify)) {
				return error('-1', '请输入验证码');
			}
			$result = checkcaptcha($verify);
			if (empty($result)) {
				return error('-1', '输入验证码错误');
			}
		}
		if (empty($mobile)) {
			return error('-1', '请输入要登录的手机号');
		}
		if (!preg_match(REGULAR_MOBILE, $mobile)) {
			return error(-1, '手机号格式不正确');
		}
		if (empty($member['password'])) {
			return error('-1', '请输入密码');
		}

		$user_table = table('users');
		$user_profile = $user_table->userProfileMobile($mobile);

		if (empty($user_profile)) {
			return error(-1, '手机号未注册');
		}
		$member['uid'] = $user_profile['uid'];
		return $member;
	}

	public function validateMobile() {
		global $_GPC;
		$mobile = $_GPC['mobile'];
		if (empty($mobile)) {
			return error(-1, '手机号不能为空');
		}
		if (!preg_match(REGULAR_MOBILE, $mobile)) {
			return error(-1, '手机号格式不正确');
		}
		$user_table = table('users');
		$mobile_exists = $user_table->userProfileMobile($mobile);
		if (!empty($mobile_exists)) {
			return error(-1, '手机号已存在');
		}
		return true;
	}

	public function register() {
		global $_GPC;
		load()->model('user');
		$member = array();
		$profile = array();
		$smscode = trim($_GPC['smscode']);
		$mobile = trim($_GPC['mobile']);
		$member['password'] = $_GPC['password'];

		if (empty($smscode)) {
			return error(-1, '短信验证码不能为空');
		}

		$user_table = table('users');
		$code_info = $user_table->userVerifyCode($mobile, $smscode);
		if (empty($code_info)) {
			return error(-1, '短信验证码不正确');
		}
		if ($code_info['createtime'] + 120 < TIMESTAMP) {
			return error(-1, '短信验证码已过期，请重新获取');
		}

		if(istrlen($member['password']) < 8) {
			return error(-1, '必须输入密码，且密码长度不得低于8位。');
		}

		$member['username'] = $mobile;
		$member['openid'] = $mobile;
		$member['register_type'] = USER_REGISTER_TYPE_MOBILE;
		$member['owner_uid'] = intval($_GPC['owner_uid']);


		$profile['mobile'] = $mobile;

		$register =  array(
			'member' => $member,
			'profile' => $profile
		);
		return parent::user_register($register);
	}

	public function login() {
		return $this->user();
	}

	public function bind() {
		global $_GPC, $_W;
		$user_table = table('users');
		$password = $_GPC['password'];
		$mobile = trim($_GPC['mobile']);

		$user = $user_table->usersInfo($_W['uid']);
		$user_profile = $user_table->userProfile($_W['uid']);

		$param_validate = $this->paramValidate();

		if (is_error($param_validate)) {
			return $param_validate;
		}

		pdo_update('users', array('password' => user_hash($password, $user['salt'])), array('uid' => $_W['uid']));
		if (empty($user_profile)) {
			pdo_insert('users_profile', array('uid' => $_W['uid'], 'mobile' => $mobile));
		} else {
			pdo_update('users_profile', array('mobile' => $mobile), array('id' => $user_profile['id']));
		}
		pdo_insert('users_bind', array('uid' => $_W['uid'], 'bind_sign' => $mobile, 'third_type' => USER_REGISTER_TYPE_MOBILE, 'third_nickname' => $mobile));

		return error(0, '绑定成功');
	}

	public function unbind() {
		global $_GPC, $_W;
		$user_table = table('users');
		$mobile = trim($_GPC['mobile']);

		$user_profile = $user_table->userProfile($_W['uid']);

		$param_validate = $this->paramValidate();

		if (is_error($param_validate)) {
			return $param_validate;
		}

		pdo_update('users', array('openid' => ''), array('uid' => $_W['uid']));
		pdo_update('users_profile', array('mobile' => ''), array('id' => $user_profile['id']));
		pdo_delete('users_bind', array('uid' => $_W['uid'], 'bind_sign' => $mobile, 'third_type' => USER_REGISTER_TYPE_MOBILE));

		return error(0, '解除绑定成功');
	}

	public function paramValidate($type = false) {
		global $_GPC;
		$password = $_GPC['password'];
		$repassword = $_GPC['repassword'];
		$mobile = trim($_GPC['mobile']);
		$image_code =trim($_GPC['imagecode']);
		$sms_code = trim($_GPC['smscode']);

		$user_table = table('users');
		if (empty($sms_code)) {
			return error(-1, '短信验证码不能为空');
		}

		if (empty($image_code)) {
			return error(-1, '图形验证码不能为空');
		}

		$captcha = checkcaptcha($image_code);
		if (empty($captcha)) {
			return error(-1, '图形验证码错误,请重新获取');
		}

		if (!empty($type)) {
			if ((empty($password) || empty($repassword))) {
				return error(-1, '密码不能为空');
			}

			if ($password != $repassword) {
				return error(-1, '两次密码不一致');
			}
		}

		$code_info = $user_table->userVerifyCode($mobile, $sms_code);
		if (empty($code_info)) {
			return error(-1, '短信验证码不正确');
		}
		if ($code_info['createtime'] + 120 < TIMESTAMP) {
			return error(-1, '短信验证码已过期，请重新获取');
		}
	}
}