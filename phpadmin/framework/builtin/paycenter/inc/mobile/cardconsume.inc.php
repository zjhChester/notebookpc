<?php

defined('IN_IA') or exit('Access Denied');
global $_W, $_GPC;
load()->model('activity');
$op = trim($_GPC['op']) ? trim($_GPC['op']) : 'consume';
$user_permission = permission_account_user('system');
if($op == 'consume') {
	$type = intval($_GPC['type']);
	$qrcode = trim($_GPC['code']);
	if($_W['isajax']) {
		$code = trim($_GPC['code']);
		$record = pdo_get('coupon_record', array('code' => $code));
		if(empty($record)) {
			message(error(-1, '卡券记录不存在'), '', 'ajax');
		}
		$status = activity_coupon_use($record['couponid'], $record['id'], 'paycenter');
		if (!is_error($status)) {
			message(error('0', ''), '', 'ajax');
		} else {
			message(error('-1', $status['message']),'' , 'ajax');
		}
	}
}
include $this->template('cardconsume');