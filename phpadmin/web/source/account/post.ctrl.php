<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('cloud');
load()->model('cache');
load()->model('user');
load()->classs('weixin.platform');
load()->model('wxapp');
load()->model('utility');
load()->func('file');
$uniacid = intval($_GPC['uniacid']);
$acid = intval($_GPC['acid']);
if (empty($uniacid) || empty($acid)) {
	$url = url('account/manage', array('account_type' => ACCOUNT_TYPE));
	itoast('请选择要编辑的' . ACCOUNT_TYPE_NAME, $url, 'error');
}
$defaultaccount = uni_account_default($uniacid);
if (!$defaultaccount) {
	itoast('无效的acid', url('account/manage'), 'error');
}
$acid = $defaultaccount['acid']; 
$state = permission_account_user_role($_W['uid'], $uniacid);
$dos = array('base', 'sms', 'modules_tpl');

$role_permission = in_array($state, array(ACCOUNT_MANAGE_NAME_FOUNDER, ACCOUNT_MANAGE_NAME_OWNER, ACCOUNT_MANAGE_NAME_VICE_FOUNDER));
if ($role_permission) {
	$do = in_array($do, $dos) ? $do : 'base';
} elseif ($state == ACCOUNT_MANAGE_NAME_MANAGER) {
	if (ACCOUNT_TYPE == ACCOUNT_TYPE_APP_NORMAL || ACCOUNT_TYPE == ACCOUNT_TYPE_APP_AUTH) {
		header('Location: ' . url('wxapp/manage/display', array('uniacid' => $uniacid, 'acid' => $acid)));
		exit;
	} else {
		$do = in_array($do, $dos) ? $do : 'modules_tpl';
	}
} else {
	itoast('您是该公众号的操作员，无权限操作！', url('account/manage'), 'error');
}

$_W['page']['title'] = '管理设置 - ' . ACCOUNT_TYPE_NAME . '管理';
$headimgsrc = tomedia('headimg_'.$acid.'.jpg');
$qrcodeimgsrc = tomedia('qrcode_'.$acid.'.jpg');
$account = account_fetch($acid);

if($do == 'base') {
	if (!$role_permission) {
		itoast('无权限操作！', url('account/post/modules_tpl', array('uniacid' => $uniacid, 'acid' => $acid)), 'error');
	}
	if($_W['ispost'] && $_W['isajax']) {
		if(!empty($_GPC['type'])) {
			$type = trim($_GPC['type']);
		} else {
			iajax(40035, '参数错误！', '');
		}
		switch ($type) {
			case 'qrcodeimgsrc':
			case 'headimgsrc':
				$image_type = array(
					'qrcodeimgsrc' => ATTACHMENT_ROOT . 'qrcode_' . $acid . '.jpg',
					'headimgsrc' => ATTACHMENT_ROOT . 'headimg_' . $acid . '.jpg'
				);
				$imgsrc = $_GPC['imgsrc'];
				if(!file_is_image($imgsrc)){
					$result = '';
				}
				$result = utility_image_rename($imgsrc, $image_type[$type]);
				break;
			case 'name':
				$uni_account = pdo_update('uni_account', array('name' => trim($_GPC['request_data'])), array('uniacid' => $uniacid));
				$account_wechats = pdo_update(uni_account_tablename(ACCOUNT_TYPE), array('name' => trim($_GPC['request_data'])), array('acid' => $acid, 'uniacid' => $uniacid));
				$result = ($uni_account && $account_wechats) ? true : false;
				break;
			case 'account' :
				$data = array('account' => trim($_GPC['request_data']));break;
			case 'original':
				$data = array('original' => trim($_GPC['request_data']));break;
			case 'level':
				$data = array('level' => intval($_GPC['request_data']));break;
			case 'key':
				$data = array('key' => trim($_GPC['request_data']));break;
			case 'secret':
				$data = array('secret' => trim($_GPC['request_data']));break;
			case 'token':
				$oauth = (array)uni_setting_load(array('oauth'), $uniacid);
				if($oauth['oauth'] == $acid && $account['level'] != 4) {
					$acid = pdo_fetchcolumn("SELECT acid FROM " . tablename('account_wechats') . " WHERE uniacid = :uniacid AND level = 4 AND secret != '' AND `key` != ''", array(':uniacid' => $uniacid));
					pdo_update('uni_settings', array('oauth' => iserializer(array('account' => $acid, 'host' => $oauth['oauth']['host']))), array('uniacid' => $uniacid));
				}
				$data = array('token' => trim($_GPC['request_data']));
				break;
			case 'encodingaeskey':
				$oauth = (array)uni_setting_load(array('oauth'), $uniacid);
				if($oauth['oauth'] == $acid && $account['level'] != 4) {
					$acid = pdo_fetchcolumn("SELECT acid FROM " . tablename('account_wechats') . " WHERE uniacid = :uniacid AND level = 4 AND secret != '' AND `key` != ''", array(':uniacid' => $uniacid));
					pdo_update('uni_settings', array('oauth' => iserializer(array('account' => $acid, 'host' => $oauth['oauth']['host']))), array('uniacid' => $uniacid));
				}
				$data = array('encodingaeskey' => trim($_GPC['request_data']));
				break;
			case 'jointype':
				$original_type = pdo_get('account', array('uniacid' => $uniacid), 'type');
				if ($original_type['type'] == ACCOUNT_NORMAL_LOGIN) {
					$result = true;
				} else {
					$update_type = pdo_update('account', array('type' => ACCOUNT_NORMAL_LOGIN), array('uniacid' => $uniacid));
					$result = $update_type ? true : false;
				}
				break;
			case 'highest_visit':
				if (user_is_vice_founder() || empty($_W['isfounder'])) {
					iajax(1, '只有创始人可以修改！');
				}
				$statistics_setting = (array)uni_setting_load(array('statistics'), $uniacid);
				if (!empty($statistics_setting['statistics'])) {
					$highest_visit = $statistics_setting['statistics'];
					$highest_visit['founder'] = intval($_GPC['request_data']);
				} else {
					$highest_visit = array('founder' => intval($_GPC['request_data']));
				}
				$result = pdo_update('uni_settings', array('statistics' => iserializer($highest_visit)), array('uniacid' => $uniacid));
				break;
			case 'endtime':
				$endtime = strtotime($_GPC['endtime']);
				if ($endtime <= 0) {
					iajax(1, '参数错误！');
				}
				
					$store_create_account_info = table('store')->StoreCreateAccountInfo($uniacid);
				
				if (user_is_founder($_W['uid'], true)) {
					
						if (!empty($store_create_account_info)) {
							pdo_update('site_store_create_account', array('endtime' => $endtime), array('uniacid' => $uniacid));
						}
					
				} else {
					$owner_id = pdo_getcolumn('uni_account_users', array('uniacid' => $uniacid, 'role' => 'owner'), 'uid');
					$user_endtime = pdo_getcolumn('users', array('uid' => $owner_id), 'endtime');
					
						if (!empty($store_create_account_info)) {
							$user_endtime = max($user_endtime, $store_create_account_info['endtime']);
						}
					
					if ($user_endtime < $endtime && !empty($user_endtime)) {
						iajax(1, '设置到期日期不能超过' . date('Y-m-d', $user_endtime));
					}
				}
				$result = pdo_update('account', array('endtime' => $endtime), array('uniacid' => $uniacid));
				break;
			case 'attachment_limit':
				if (user_is_vice_founder() || empty($_W['isfounder'])) {
					iajax(1, '只有创始人可以修改！');
				}
				$has_uniacid = pdo_getcolumn('uni_settings', array('uniacid' => $uniacid), 'uniacid');
				if ($_GPC['request_data'] < 0) {
					$attachment_limit = -1;
				} else {
					$attachment_limit = intval($_GPC['request_data']);
				}
				if (empty($has_uniacid)) {
					$result = pdo_insert('uni_settings', array('attachment_limit' => $attachment_limit, 'uniacid' => $uniacid));
				} else {
					$result = pdo_update('uni_settings', array('attachment_limit' => $attachment_limit), array('uniacid' => $uniacid));
				}
				break;
		}
		if(!in_array($type, array('qrcodeimgsrc', 'headimgsrc', 'name', 'endtime', 'jointype', 'highest_visit', 'attachment_limit'))) {
			$result = pdo_update(uni_account_tablename(ACCOUNT_TYPE), $data, array('acid' => $acid, 'uniacid' => $uniacid));
		}
		if($result) {
			cache_delete(cache_system_key('uniaccount', array('uniacid' => $uniacid)));
			cache_delete(cache_system_key('accesstoken', array('acid' => $acid)));
			cache_delete(cache_system_key('statistics', array('uniacid' => $uniacid)));
			iajax(0, '修改成功！', '');
		} else {
			iajax(1, '修改失败！', '');
		}
	}

	if ($_W['setting']['platform']['authstate']) {
		$account_platform = new WeixinPlatform();
		$preauthcode = $account_platform->getPreauthCode();
		if (is_error($preauthcode)) {
			$authurl = array(
				'errno' => 1,
				'url' => "{$preauthcode['message']}"
			);
		} else {
			$authurl = array(
				'errno' => 0,
				'url' => sprintf(ACCOUNT_PLATFORM_API_LOGIN, $account_platform->appid, $preauthcode, urlencode($GLOBALS['_W']['siteroot'] . 'index.php?c=account&a=auth&do=forward'), ACCOUNT_PLATFORM_API_LOGIN_ACCOUNT)
			);
		}
	}

	$table_name = in_array(ACCOUNT_TYPE, array(ACCOUNT_TYPE_OFFCIAL_NORMAL, ACCOUNT_TYPE_OFFCIAL_AUTH)) ? 'account_wechats' : 'account_' . TYPE_SIGN;
	if (in_array(ACCOUNT_TYPE, array(ACCOUNT_TYPE_OFFCIAL_NORMAL, ACCOUNT_TYPE_OFFCIAL_AUTH, ACCOUNT_TYPE_APP_NORMAL, ACCOUNT_TYPE_APP_AUTH, ACCOUNT_TYPE_XZAPP_NORMAL))) {
		$account_other_info = pdo_get($table_name, array('uniacid' => $uniacid, 'acid' => $acid), array('key', 'secret', 'token', 'encodingaeskey'));
	}
	$account_other_info = (array)$account_other_info;
	$account = array_merge($account, $account_other_info);
	$account['start'] = date('Y-m-d', $account['starttime']);
	$account['end'] = $account['endtime'] == 0 ? '永久' : date('Y-m-d', $account['endtime']);
	$account['endtype'] = $account['endtime'] == 0 ? 1 : 2;
	$uni_setting = (array)uni_setting_load(array('statistics', 'attachment_limit', 'attachment_size'), $uniacid);
	$account['highest_visit'] = empty($uni_setting['statistics']['founder']) ? 0 : $uni_setting['statistics']['founder'];
	$account['attachment_size'] = round($uni_setting['attachment_size'] / 1024, 2);

	$attachment_limit = intval($uni_setting['attachment_limit']);
	if ($attachment_limit == 0) {
		$upload = setting_load('upload');
		$attachment_limit = empty($upload['upload']['attachment_limit']) ? 0 : intval($upload['upload']['attachment_limit']);
	}
	if ($attachment_limit <= 0) {
		$attachment_limit = -1;
	}
	$account['attachment_limit'] = intval($attachment_limit);

	$uniaccount = array();
	$uniaccount = pdo_get('uni_account', array('uniacid' => $uniacid));
	
		$account_api = uni_site_store_buy_goods($uniacid, STORE_TYPE_API);
	
	template('account/manage-base' . ACCOUNT_TYPE_TEMPLATE);
}

if($do == 'sms') {
	if (!$role_permission) {
		itoast('无权限操作！', url('account/post/modules_tpl', array('uniacid' => $uniacid, 'acid' => $acid)), 'error');
	}
	$settings = uni_setting($uniacid, array('notify'));
	$notify = $settings['notify'] ? $settings['notify'] : array();

	$sms_info = cloud_sms_info();
	$max_num = empty($sms_info['sms_count']) ? 0 : $sms_info['sms_count'];
	$signatures = $sms_info['sms_sign'];

	if ($_W['isajax'] && $_W['ispost'] && $_GPC['type'] == 'balance') {
		if ($max_num == 0) {
			iajax(-1, '您现有短信数量为0，请联系服务商购买短信！', '');
		}
		$balance = intval($_GPC['balance']);
		$notify['sms']['balance'] = $balance;
		$notify['sms']['balance'] = min(max(0, $notify['sms']['balance']), $max_num);
		$count_num = $max_num - $notify['sms']['balance'];
		$num = $notify['sms']['balance'];
		$notify = iserializer($notify);
		$updatedata['notify'] = $notify;
		$result = pdo_update('uni_settings', $updatedata , array('uniacid' => $uniacid));
		if($result){
			iajax(0, array('count' => $count_num, 'num' => $num), '');
		}else {
			iajax(1, '修改失败！', '');
		}
	}
	if($_W['isajax'] && $_W['ispost'] && $_GPC['type'] == 'signature') {
		if (!empty($_GPC['signature'])) {
			$signature = trim($_GPC['signature']);
			$setting = pdo_get('uni_settings', array('uniacid' => $uniacid));
			$notify = iunserializer($setting['notify']);
			$notify['sms']['signature'] = $signature;

			$notify = serialize($notify);
			$result = pdo_update('uni_settings', array('notify' => $notify), array('uniacid' => $uniacid));
			if($result) {
				iajax(0, '修改成功！', '');
			}else {
				iajax(1, '修改失败！', '');
			}
		}else {
			iajax(40035, '参数错误！', '');
		}
	}

	template('account/manage-sms' . ACCOUNT_TYPE_TEMPLATE);
}

if($do == 'modules_tpl') {
	$owner = account_owner($uniacid);
	if($_W['isajax'] && $_W['ispost'] && ($role_permission)) {
		if($_GPC['type'] == 'group') {
			$groups = $_GPC['groupdata'];
			if(!empty($groups)) {
								pdo_delete('uni_account_group', array('uniacid' => $uniacid));
				$group = pdo_get('users_group', array('id' => $owner['groupid']));
				$group['package'] = (array)iunserializer($group['package']);
				$group['package'] = array_unique($group['package']);
				foreach ($groups as $packageid) {
					if (!empty($packageid) && !in_array($packageid, $group['package'])) {
						pdo_insert('uni_account_group', array(
							'uniacid' => $uniacid,
							'groupid' => $packageid,
						));
					}
				}
				cache_build_account_modules($uniacid);
				cache_build_account($uniacid);
				iajax(0, '修改成功！', '');
			}else {
				pdo_delete('uni_account_group', array('uniacid' => $uniacid));
				cache_build_account_modules($uniacid);
				cache_build_account($uniacid);
				iajax(0, '修改成功！', '');
			}
		}

		if($_GPC['type'] == 'extend') {
						$module = $_GPC['module'];
			$tpl = $_GPC['tpl'];
			if (!empty($module) || !empty($tpl)) {
				$data = array(
					'modules' => array('modules' => array(), 'wxapp' => array(), 'webapp' => array(), 'xzapp' => array(), 'phoneapp' => array()),
					'templates' => empty($tpl) ? '' : iserializer($tpl),
					'uniacid' => $uniacid,
					'name' => '',
				);
				switch ($defaultaccount['type']) {
					case ACCOUNT_TYPE_OFFCIAL_NORMAL:
					case ACCOUNT_TYPE_OFFCIAL_AUTH:
						$data['modules']['modules'] = $module;
						break;
					case ACCOUNT_TYPE_APP_NORMAL:
					case ACCOUNT_TYPE_APP_AUTH:
					case ACCOUNT_TYPE_WXAPP_WORK:
						$data['modules']['wxapp'] = $module;
						break;
					case ACCOUNT_TYPE_WEBAPP_NORMAL:
						$data['modules']['webapp'] = $module;
						break;
					case ACCOUNT_TYPE_XZAPP_NORMAL:
					case ACCOUNT_TYPE_XZAPP_AUTH:
						$data['modules']['xzapp'] = $module;
						break;
					case ACCOUNT_TYPE_PHONEAPP_NORMAL:
						$data['modules']['phoneapp'] = $module;
						break;
					case ACCOUNT_TYPE_ALIAPP_NORMAL:
						$data['modules']['aliapp'] = $module;
						break;
				}
				$data['modules'] = iserializer($data['modules']);

				$id = pdo_fetchcolumn("SELECT id FROM ".tablename('uni_group')." WHERE uniacid = :uniacid", array(':uniacid' => $uniacid));
				if (empty($id)) {
					pdo_insert('uni_group', $data);
				} else {
					pdo_update('uni_group', $data, array('id' => $id));
				}
			} else {
				pdo_delete('uni_group', array('uniacid' => $uniacid));
			}
			cache_build_account_modules($uniacid);
			cache_build_account($uniacid);
			iajax(0, '修改成功！', '');
		}
		
			if ($_GPC['type'] == 'store_endtime' && user_is_founder($_W['uid']) && !user_is_vice_founder()) {
				$order_id = intval($_GPC['order_id']);
				$new_endtime = safe_gpc_string($_GPC['new_time']);
				if (empty($order_id)) {
					iajax(-1, '参数错误！');
				}
				$condition = array('uniacid' => $uniacid, 'type' => STORE_ORDER_FINISH,  'id' => $order_id);
				$order_exist = pdo_get('site_store_order', $condition);
				if (!empty($order_exist)) {
					pdo_update('site_store_order', array('endtime' => strtotime($new_endtime)), $condition);
				} else {
					iajax(-1, '您未购买该权限组！');
				}
				iajax(0, '修改成功！', referer());
			}
		

		iajax(40035, '参数错误！', '');
	}
	$modules_tpl = $extend = array();

	$founders = explode(',', $_W['config']['setting']['founder']);
	if (in_array($_W['uid'], $founders)) {
		$uni_groups = uni_groups();
	}
	if (in_array($owner['uid'], $founders)) {
		$modules_tpl[] = array(
			'id' => -1,
			'name' => '所有服务',
			'modules' => array(array('name' => 'all', 'title' => '所有模块')),
			'templates' => array(array('name' => 'all', 'title' => '所有模板')),
			'type' => 'default'
		);
	} else {
		if ($owner['founder_groupid'] == ACCOUNT_MANAGE_GROUP_VICE_FOUNDER) {
			$owner['group'] = pdo_get('users_founder_group', array('id' => $owner['groupid']), array('id', 'name', 'package'));
		} else {
			$owner['group'] = pdo_get('users_group', array('id' => $owner['groupid']), array('id', 'name', 'package'));
		}

		$owner['group']['package'] = (array)iunserializer($owner['group']['package']);
		if(!empty($owner['group']['package'])){
			foreach ($owner['group']['package'] as $package_value) {
				if($package_value == -1){
					$modules_tpl[] = array(
						'id' => -1,
						'name' => '所有服务',
						'modules' => array(array('name' => 'all', 'title' => '所有模块')),
						'templates' => array(array('name' => 'all', 'title' => '所有模板')),
						'type' => 'default'
					);
				}elseif ($package_value == 0) {

				}else {
					$defaultmodule = current(uni_groups(array($package_value)));
					$defaultmodule['type'] = 'default';
					$modules_tpl[] = $defaultmodule;
				}
			}
		}
				$extendpackage = pdo_getall('uni_account_group', array('uniacid' => $uniacid), array(), 'groupid');
		if(!empty($extendpackage)) {
			foreach ($extendpackage as $extendpackage_val) {
				if($extendpackage_val['groupid'] == -1){
					$modules_tpl[] = array(
						'id' => -1,
						'name' => '所有服务',
						'modules' => array(array('name' => 'all', 'title' => '所有模块')),
						'templates' => array(array('name' => 'all', 'title' => '所有模板')),
						'type' => 'extend' 					);
				}elseif ($extendpackage_val['groupid'] == 0) {

				}else {
					$ex_module = current(uni_groups(array($extendpackage_val['groupid'])));
					if (!empty($ex_module)) {
						$ex_module['type'] = 'extend';
						$modules_tpl[] = $ex_module;
					}
				}
			}
		}
	}

	$modules = user_modules($_W['uid']);
	$templates = pdo_getall('site_templates', array(), array('id', 'name', 'title'));
	$extend = pdo_get('uni_group', array('uniacid' => $uniacid));
	$extend_modules = iunserializer($extend['modules']);
	$extend['modules'] = array();
	foreach ($extend_modules as $modulenames) {
		if (!empty($modulenames)) {
			$extend['modules'] = $current_module_names = array_merge($extend['modules'], $modulenames);
		}
	}
	$extend['templates'] = iunserializer($extend['templates']);
	$canmodify = false;
	
		if ($_W['role'] == ACCOUNT_MANAGE_NAME_FOUNDER && !in_array($owner['uid'], $founders) || $_W['role'] == ACCOUNT_MANAGE_NAME_VICE_FOUNDER && $owner['uid'] != $_W['uid']) {
			$canmodify = true;
		}
	
	
	if (!empty($extend['modules'])) {
		$extend['modules'] = $current_module_names = array_unique($current_module_names);
		foreach ($extend['modules'] as $module_key => $module_val) {
			$extend['modules'][$module_key] = module_fetch($module_val);
		}
	}
	if (!empty($extend['templates'])) {
		$extend['templates'] = pdo_getall('site_templates', array('id' => $extend['templates']), array('id', 'name', 'title'));
	}
	
		$account_buy_modules = uni_site_store_buy_goods($uniacid,$_GPC['account_type']);
		if (!empty($account_buy_modules) && is_array($account_buy_modules)) {
			foreach ($account_buy_modules as &$module) {
				$module = module_fetch($module);
				$module['goods_id'] = pdo_getcolumn('site_store_goods', array('module' => $module['name'], 'status' => 1), 'id');
				$order_info = pdo_get('site_store_order', array('uniacid' => $uniacid, 'type' => STORE_ORDER_FINISH,  'goodsid' => $module['goods_id']), array('id', 'max(endtime) as endtime'));
				$module['order_id'] = $order_info['id'];
				$module['expire_time'] = $order_info['endtime'];
			}
		}
		unset($module);
		$store = table('store');
		$account_buy_group = uni_site_store_buy_goods($uniacid, STORE_TYPE_PACKAGE);
		$account_buy_package = array();
		if (is_array($account_buy_group) && !empty($account_buy_group)) {
			foreach ($account_buy_group as $group) {
				$account_buy_package[$group] = current(uni_groups(array($group)));
				$account_buy_package[$group]['goods_id'] = pdo_getcolumn('site_store_goods', array('module_group' => $group), 'id');
				$order_info = pdo_get('site_store_order', array('uniacid' => $uniacid, 'type' => STORE_ORDER_FINISH,  'goodsid' => $account_buy_package[$group]['goods_id']), array('id', 'max(endtime) as endtime'));
				$account_buy_package[$group]['order_id'] = $order_info['id'];
				$account_buy_package[$group]['expire_time'] = $order_info['endtime'];
				if (TIMESTAMP > $account_buy_package[$group]['expire_time']) {
					$account_buy_package[$group]['expire'] = true;
				} else {
					$account_buy_package[$group]['expire'] = false;
					$account_buy_package[$group]['near_expire'] = strtotime('-1 week', $account_buy_package[$group]['expire_time']) < time() ? true : false;
				}
				$account_buy_package[$group]['expire_time'] = date('Y-m-d', $account_buy_package[$group]['expire_time']);
			}
		}
		unset($group);
	

	template('account/manage-modules-tpl' . ACCOUNT_TYPE_TEMPLATE);
}