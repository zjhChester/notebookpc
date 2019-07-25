<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
error_reporting(0);
define('IN_MOBILE', true);
require '../../framework/bootstrap.inc.php';
$_W['uniacid'] = intval($_POST['reqReserved']);
load()->web('common');
load()->classs('coupon');
$_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
$_W['acid'] = $_W['uniaccount']['acid'];
$setting = uni_setting($_W['uniacid'], array('payment'));
if(!is_array($setting['payment'])) {
	exit('没有设定支付参数.');
}
$payment = $setting['payment']['unionpay'];
require '__init.php';

if (!empty($_POST) && verify($_POST) && $_POST['respMsg'] == 'success') {
	$sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `uniontid`=:uniontid';
	$params = array();
	$params[':uniontid'] = $_POST['orderId'];
	$log = pdo_fetch($sql, $params);
	if(!empty($log) && $log['status'] == '0') {
		$log['tag'] = iunserializer($log['tag']);
		$log['tag']['queryId'] = $_POST['queryId'];

		$record = array();
		$record['status'] = 1;
		$record['tag'] = iserializer($log['tag']);
		pdo_update('core_paylog', $record, array('plid' => $log['plid']));
		if ($log['is_usecard'] == 1 && !empty($log['encrypt_code'])) {
			$coupon_info = pdo_get('coupon', array('id' => $log['card_id']), array('id'));
			$coupon_record = pdo_get('coupon_record', array('code' => $log['encrypt_code'], 'status' => '1'));
			load()->model('activity');
		 	$status = activity_coupon_use($coupon_info['id'], $coupon_record['id'], $log['module']);
		}

		$site = WeUtility::createModuleSite($log['module']);
		if(!is_error($site)) {
			$method = 'payResult';
			if (method_exists($site, $method)) {
				$ret = array();
				$ret['weid'] = $log['uniacid'];
				$ret['uniacid'] = $log['uniacid'];
				$ret['result'] = 'success';
				$ret['type'] = $log['type'];
				$ret['from'] = 'nofity';
				$ret['tid'] = $log['tid'];
				$ret['user'] = $log['openid'];
				$ret['fee'] = $log['fee'];
				$ret['tag'] = $log['tag'];
								$ret['is_usecard'] = $log['is_usecard'];
				$ret['card_type'] = $log['card_type'];
				$ret['card_fee'] = $log['card_fee'];
				$ret['card_id'] = $log['card_id'];
				$site->$method($ret);
				exit('success');
			}
		}
	}
}
exit('fail');
