<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('user');
$dos = array('display', 'post', 'del');
$do = !empty($_GPC['do']) ? $_GPC['do'] : 'display';

if ($do == 'display') {
	$_W['page']['title'] = '副创始人组列表 - 副创始人组 - 副创始人管理';
	$pageindex = max(1, intval($_GPC['page']));
	$pagesize = 10;

	$condition = '' ;
	$params = array();
	$name = safe_gpc_string($_GPC['name']);
	if (!empty($name)) {
		$condition .= "WHERE name LIKE :name";
		$params[':name'] = "%{$name}%";
	}
	$lists = pdo_fetchall("SELECT * FROM " . tablename('users_founder_group') . $condition . " LIMIT " . ($pageindex - 1) * $pagesize . "," . $pagesize, $params);
	$lists = user_group_format($lists);
	$total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('users_founder_group') . $condition, $params);
	$pager = pagination($total, $pageindex, $pagesize);
	template('founder/group');
}

if ($do == 'post') {
	$id = intval($_GPC['id']);
	if (!empty($id)) {
		$group_info = pdo_get('users_founder_group', array('id' => $id));
		$group_info['package'] = iunserializer($group_info['package']);
		if (!empty($group_info['package']) && in_array(-1, $group_info['package'])) {
			$group_info['check_all'] = true;
		}
	}
	$packages = uni_groups();
	if (!empty($packages)) {
		foreach ($packages as $uni_group_id => &$package_info) {
			if (!empty($group_info['package']) && in_array($uni_group_id, $group_info['package'])) {
				$package_info['checked'] = true;
			} else {
				$package_info['checked'] = false;
			}
		}
		unset($package_info);
	}

	if (checksubmit('submit')) {
		$founder_user_group = array(
			'id' => intval($_GPC['id']),
			'name' => safe_gpc_string($_GPC['name']),
			'package' => safe_gpc_array($_GPC['package']),
			'maxaccount' => intval($_GPC['maxaccount']),
			'maxwxapp' => intval($_GPC['maxwxapp']),
			'maxwebapp' => intval($_GPC['maxwebapp']),
			'maxphoneapp' => intval($_GPC['maxphoneapp']),
			'maxxzapp' => intval($_GPC['maxxzapp']),
			'maxaliapp' => intval($_GPC['maxaliapp']),
			'timelimit' => intval($_GPC['timelimit'])
		);
		$user_group = user_save_founder_group($founder_user_group);
		if (is_error($user_group)) {
			itoast($user_group['message'], '', '');
		}
		cache_clean(cache_system_key('user_modules'));
		itoast('用户组更新成功！', url('founder/group'), 'success');
	}
	template('founder/group-post');
}

if ($do == 'del') {
	$id = intval($_GPC['id']);
	$result = pdo_delete('users_founder_group', array('id' => $id));
	if(!empty($result)){
		itoast('删除成功！', url('founder/group/'), 'success');
	}else {
		itoast('删除失败！请稍候重试！', url('founder/group'), 'error');
	}
	exit;
}