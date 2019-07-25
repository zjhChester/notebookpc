<?php
/**
 * 送礼投票-投票
 *
 * @author 天涯织梦
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');
global $_W,$_GPC;

is_weixin();
$rid=intval($_GPC['rid']);
$id=intval($_GPC['id']);
if(empty($id)){message("无来源错误(0201)"); }
$ty=$_GPC['ty'];
$count=intval($_GPC['count']);
$count=empty($count) ? 1 : $count;

$userinfo=$this->oauthuser;
$oauth_openid=$userinfo['oauth_openid'];
m('domain')->randdomain($rid,1);
if(empty($oauth_openid)){
	message("无法获取OPNEID，请查看是否借权或配置好公众号！(0101)"); 
}
$reply = pdo_fetch("SELECT rid,title,sharetitle,shareimg,sharedesc,config,style,giftdata,starttime,endtime,apstarttime,apendtime,votestarttime,voteendtime,status,description  FROM " . tablename($this->tablereply) . " WHERE rid = :rid ", array(':rid' => $rid));
$reply['style']=@unserialize($reply['style']);
$reply=@array_merge ($reply,unserialize($reply['config']));unset($reply['config']);
if(empty($reply['status'])){message("活动已禁用");}
if(empty($reply)){
	message("参数错误"); 
}

if($reply['starttime']>time()){
	message("活动还没有开始");
}
 
//活动未开始
if($reply['endtime']<time()){
	message("活动已经结束");
}

//活动未开始
if(empty($reply['status'])){
	message("活动已禁用"); 
}

//投票时间
if($reply['votestarttime']> time()){
	message("未开始投票！"); 
}elseif($reply['voteendtime']<time()){
	message("已结束投票！");
}
$giftdata=@unserialize($reply['giftdata']);	



$voteuser = pdo_fetch("SELECT * FROM " . tablename($this->tablevoteuser) . " WHERE rid = :rid AND  id = :id ", array(':rid' => $rid,':id' => $id));

$voteuser['avatar']=!empty($voteuser['avatar'])?$voteuser['avatar']:tomedia($voteuser["img1"]); 

if($ty['ispost']){
	//是否达到最小人数
	if(!empty($reply['minnumpeople'])){
		$condition="";
		if($reply['ischecked']==1){
		  $condition.=" AND status=1 ";
		}
		$jointotal = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename($this->tablevoteuser) . " WHERE   rid = :rid  ".$condition , array(':rid' => $rid));
		if($reply['minnumpeople']>$jointotal){
			exit(json_encode(array('status' => '0', 'msg' => "活动还未开始，没有达到最小参赛人数！")));
		}
	}
	$gift=$giftdata[$_GPC['giftid']];
	
	
	
	//最多送礼物
	$diamondsy=$reply['everyonediamond']-$voteuser['giftcount'];
	if($gift['giftprice']*$count > $diamondsy && !empty($reply['everyonediamond'])){
		if($diamondsy>0){
			exit(json_encode(array('status' => '0', 'msg' => "最多还能送".$diamondsy."元礼物，修改后再送！:-D")));
		}else{
			exit(json_encode(array('status' => '0', 'msg' => "最多能送".$reply['everyonediamond']."元礼物，给其他人点机会吧！:-D")));
		} 
	}	
	$tid=date('YmdHi').random(12, 1);
	$params = array(
		'tid' => $tid,
		'ordersn' => $tid,
		'title' => '投票送礼付款',
		'fee' => sprintf("%.2f",$gift['giftprice']*$count),
		'user' => $_W['member']['uid'],
		'module' => $this->module['name'],
	);
	
	
	
	$acid=!empty($_SESSION['oauth_acid'])?$_SESSION['oauth_acid']:$_SESSION['acid'];
	if(!empty($_SESSION['oauth_acid'])){
		$acid=$_SESSION['oauth_acid'];
		$account_wechats = pdo_fetch("SELECT uniacid FROM " . tablename('account_wechats') . " WHERE  acid = :acid ", array(':acid' => $acid));
	    $uniacid=$account_wechats['uniacid'];
	}else{
		$acid=$_SESSION['acid'];
		$uniacid=$_W['uniacid'];
	}
	

	$giftdata = array(
			'rid'=>$rid, 
			'tid'=>$id,
			'uniacid'=>$_W['uniacid'],
			'oauth_openid'=>$userinfo['oauth_openid'],
			'openid'=>$userinfo['openid'],
			'avatar' =>$userinfo['avatar'],
			'nickname'=>$userinfo['nickname'],
			'user_ip'=>$_W['clientip'],
			'gifticon'=>$gift['gifticon'],
			'gifttitle'=>$gift['gifttitle'],
			'giftcount'=>$count,
			'giftvote'=>$gift['giftvote']*$count,
			'fee'=>$params['fee'],
			'ptid'=>$tid,
			'ispay'=>0,
			'status'=>0,
			'createtime'=>time()
	);
	if(pdo_insert($this->tablegift, $giftdata)){
		// if(empty($reply['defaultpay'])){
		// 	$out['status'] = 200;
		// 	$out['pay_url'] = $_W['siteroot']."payment/wechat/pay.php?i={$uniacid}&auth={$auth}&ps={$sl}&payopenid={$giftdata['oauth_openid']}";
		// }else{
		    $_share['title'] ="在线支付";
			$this->pay($params);
		// }
	}else{
		exit(json_encode(array('status' => '0', 'msg' => "操作失败，请刷新后再试！")));
	}
	exit;
}
$lsun=0;
foreach ($giftdata as $key => $value) {
	$xiuyu=$key%3;
	if(empty($xiuyu)){
		$i++;
	}
	$giftlist[$i][$key]=$value; 
	$lsun=$key;
}
$pvtotal=pdo_fetch("SELECT pv_total FROM ".tablename($this->tablecount)." WHERE tid = :tid AND rid = :rid ", array(':tid' => $id,':rid' => $rid));
if(empty($pvtotal)){
	$pvtotal['pv_total']=0;
}
$pvtotal['pv_total']=$pvtotal['pv_total']+$voteuser['vheat'];
$reply['giftunit']=$reply['giftunit']?$reply['giftunit']:"点";
$_share['title'] =!empty($reply['sharetitle'])?$reply['sharetitle']:$reply['title'];
$_share['imgUrl'] =!empty($reply['shareimg'])?tomedia($reply['shareimg']):tomedia($reply['thumb']);
$_share['desc'] =!empty($reply['sharedesc'])?$reply['sharedesc']:$reply['description'];
$_W['page']['sitename']=$reply['title'];

include $this->template(m('tpl')->style('payvote',$reply['style']['template']));






