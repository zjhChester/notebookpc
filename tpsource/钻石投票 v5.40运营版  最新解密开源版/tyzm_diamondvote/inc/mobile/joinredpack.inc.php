<?php
/**
 * --抽奖
 *
 * @author 羊子
 * @url http://tyzm.net/
 */

defined('IN_IA') or exit('Access Denied');
global $_W,$_GPC;
$uniacid = intval($_W['uniacid']);
$rid=intval($_GPC['rid']);
$reply = pdo_fetch("SELECT config,status FROM ".tablename($this->tablereply)." WHERE rid = :rid ORDER BY `id` DESC", array(':rid' => $rid));

if(empty($reply['status'])){message("活动已禁用");}
$configdata=@unserialize($reply['config']);
$userinfo=$this->oauthuser;
$openid = $userinfo['openid'];
$oauth_openid = $userinfo['oauth_openid'];
$nickname = $userinfo['nickname'];
$avatar = $userinfo['avatar'];
$follow = $userinfo['follow'];
$unionid= $userinfo['unionid']; 
if ($_W['ispost']) {
        //今天红包
        
        $dailystarttime = mktime(0, 0, 0); //当天：00：00：00
        $dailyendtime   = mktime(23, 59, 59); //当天：23：59：59
        $dailytimes     = '';
        $dailytimes .= ' AND createtime >=' . $dailystarttime;
        $dailytimes .= ' AND createtime <=' . $dailyendtime;
        $dailyredtotal = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->tableredpack) . " WHERE rid = :rid  and type = :type  " . $dailytimes, array(
            ':rid' => $rid,":type" => 1
        ));
        if ($configdata['redpacketnumjoin']>0&&$dailyredtotal >= $configdata['redpacketnumjoin']) {
            $this->json_exit(0,"差点就中了！(003)");
        } 
        //END				

        //总数  redpackettotal
		$redtotal = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename($this->tableredpack) . " WHERE rid = :rid   and type = :type   ", array(
            ':rid' => $rid,":type" => 1
        ));
		if($configdata['redpackettotaljoin']>0&&$redtotal>=$configdata['redpackettotaljoin']){
			$this->json_exit(0,"差点就中了！(005)");
		}

		$proSum= intval(100/$configdata['probabilityjoin']);
		$randNum = mt_rand(1, $proSum);   
		if ($randNum != 1) { 
            $this->json_exit(0,"差点就中了！(006)");
        }	
		$config = $this->module['config'];
		$total_amount=rand($configdata['limitstartjoin'] * 100, $configdata['limitendjoin'] * 100);
		$insdata = array(
			'tid' => $joindata['id'],
			'rid'=>$rid,
			'uniacid' => $uniacid,			
			'openid' => $oauth_openid,
			'avatar'=>$avatar,
			'nickname'=>$nickname,
			'mch_billno' => $config['mchid'] . date("Ymd", time()) . date("His", time()) . rand(1111, 9999) ,
			'total_amount' => $total_amount,
			'total_num' => 1,
			'type' => 1,
			'user_ip' => $_W['clientip'],
			'createtime' => TIMESTAMP,
        );
		
        if (pdo_insert($this->tableredpack, $insdata)){
                $newredpackid = pdo_insertid();
				//发红包
				if($newredpackid){
					$redpack = m('redpack')->sendredpack($newredpackid,$rid,1);
					$this->json_exit(1,"恭喜，中得".($total_amount/100)."元红包！");
				}else{
				    $this->json_exit(0,"差点就中了！(007)");
				}
            }else{
				$this->json_exit(0,"差点就中了！(008)");
		}
		
     
		
         /*  if ($_W['openid'] == 'o0ZnCvtn1u3vFvtWcttK7iQglVr8') {
			 $out['code'] = 88;
		 } */
        
    
}
//是否关注  end