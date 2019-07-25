<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
if ($action != 'display' && $action != 'privileges') {
	define('FRAME', 'system');
} else {
		define('FRAME', '');
}

if ($controller == 'account' && $action == 'manage') {
	if ($_GPC['account_type'] == ACCOUNT_TYPE_APP_NORMAL) {
		define('ACTIVE_FRAME_URL', url('account/manage/display', array('account_type' => ACCOUNT_TYPE_APP_NORMAL)));
	}
}

$account_param = WeAccount::create(array('type' => $_GPC['account_type']));
define('ACCOUNT_TYPE', $account_param->type);
define('TYPE_SIGN', $account_param->typeSign);
define('ACCOUNT_TYPE_NAME', $account_param->typeName);
define('ACCOUNT_TYPE_TEMPLATE', $account_param->typeTempalte);
