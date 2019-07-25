<?php
/**
 * 钻石投票-投票
 *
 * @author 天涯织梦
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');
global $_W,$_GPC;
is_weixin();
$rid=intval($_GPC['rid']);
$id=intval($_GPC['id']);
$ty=intval($_GPC['ty']);
$userinfo=$this->oauthuser;
$userinfo['verify']=intval($_GPC['verify']);
$votere=m('vote')->setvote($userinfo,$rid,$id,$_GPC['latitude'],$_GPC['longitude'],0);
exit(json_encode($votere));



function checkcode($code) {
	global $_W, $_GPC;
	session_start();
	$codehash = md5(strtolower($code) . $_W['config']['setting']['authkey']);
	if (!empty($_GPC['__code']) && $codehash == $_SESSION['__code']) {
		$return = true;
	} else {
		$return = false;
	}
	$_SESSION['__code'] = '';
	isetcookie('__code', '');
	return $return;
}

