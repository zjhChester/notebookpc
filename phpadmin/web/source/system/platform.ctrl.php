<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

load()->classs('weixin.platform');

setting_load('platform');

$founders = explode(',', $_W['config']['setting']['founder']);
$_W['page']['title'] = '开放平台设置';

if($_W['isajax'] && $_W['ispost']) {
	$data = array();
	$token = trim($_GPC['token']);
	$encodingaeskey =trim($_GPC['encodingaeskey']);
	$appid = trim($_GPC['appid']);
	$appsecret = trim($_GPC['appsecret']);

	$authstate = isset($_GPC['authstate']) ? intval($_GPC['authstate']) : $_W['setting']['platform']['authstate'];
	$data['token'] = !empty($token) ? $token : $_W['setting']['platform']['token'];
	$data['encodingaeskey'] = !empty($encodingaeskey) ? $encodingaeskey : $_W['setting']['platform']['encodingaeskey'];
	$data['appid'] = !empty($appid) ? $appid : $_W['setting']['platform']['appid'];
	$data['appsecret'] = !empty($appsecret) ? $appsecret : $_W['setting']['platform']['appsecret'];
	$data['authstate'] = !empty($authstate) ? 1 : 0;

	$result = setting_save($data,'platform');
	if($result) {
		iajax(0, '修改成功！', '');
	}else {
		iajax(1, '修改失败！', '');
	}
}

if(empty($_W['setting']['platform'])) {
	$_W['setting']['platform'] = array(
		'token' => random(32),
		'encodingaeskey' => random(43),
		'appsecret' => '',
		'appid' => '',
		'authstate' => 1
	);
	setting_save($_W['setting']['platform'],'platform');
}
$siteroot_parse_array = parse_url($_W['siteroot']);
if (!function_exists('mcrypt_module_open')) {
	itoast('抱歉，您的系统不支持加解密 mcrypt 模块，无法进行平台接入', '', '');
}
$account_platform = new WeixinPlatform();
$authurl = $account_platform->getAuthLoginUrl();
template('system/platform');