<?php

defined('IN_IA') or exit('Access Denied');

load()->model('phoneapp');

if (!empty($_W['uniacid'])) {
	$version = intval($_GPC['v']);
	$version_info = phoneapp_version($version);
	if (!empty($version_info['modules'])) {
		foreach ($version_info['modules'] as $module) {
			if (!empty($module['account']) && intval($module['account']['uniacid']) > 0) {
				$_W['uniacid'] = $module['account']['uniacid'];
			}
		}
	}
}
$site = WeUtility::createModulePhoneapp($entry['module']);
$method = 'doPage' . ucfirst($entry['do']);
if(!is_error($site)) {
	$site->version = $version;
	exit($site->$method());
}
message('模块不存在或是 '.$method.' 方法不存在', '', 'error');