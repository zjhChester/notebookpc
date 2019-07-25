<?php
/**
 * 钻石投票模块-后台管理-ajax
 *
 * @author 天涯织梦
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');
global $_GPC, $_W;
$ty=$_GPC['ty'];
$uniacid = intval($_W['uniacid']);
$id = intval($_GPC['id']);
$rid=intval($_GPC['rid']);
if($ty=='audit'){
	if($_W['ispost']){
		$audit = intval($_GPC['audit']);
		if(pdo_update($this->tablevoteuser,array('status'=>$audit),array('id' =>$id,'rid' =>$rid,'uniacid'=>$uniacid))){
			if($audit){
				$voteuser = pdo_fetch("SELECT openid FROM " . tablename($this->tablevoteuser) . " WHERE  id = :id AND uniacid = :uniacid AND rid = :rid", array(':id' => $id,':uniacid' => $uniacid,':rid' => $rid));
				$uservoteurl=$_W['siteroot']."app/".$this->createMobileUrl('view', array('id' =>$id,'rid' => $rid));
				$content='您报名的投票活动，已经审核通过，请<a href=\"'.$uservoteurl.'\">点击进入详情页面<\/a>';
				m('user') ->sendkfinfo($voteuser['openid'],$content);			
			}

			$out['status'] = 200;
		    exit(json_encode($out));
		}
	}
}

if($ty=='statusAll'){
	
		print_r($_GPC['idArr']);exit;
	
}
	



if($ty=='lock'){
	if($_W['ispost']){
		$lock = intval($_GPC['lock']);
		if($lock==1){
			$locktime=0;
		}else{
			$locktime=time()+30*24*3600;
		}

		if(pdo_update($this->tablevoteuser,array('locktime'=>$locktime),array('id' =>$id,'rid' =>$rid,'uniacid'=>$uniacid))){
			$out['status'] = 200;
		    exit(json_encode($out));
		}else{
			$out['status'] = 0;
		    exit(json_encode($out));
		}
	}
}

if($ty=='loaduser'){
	if($_W['ispost']){
		$account = pdo_getall('mc_mapping_fans', array(), array('fanid') , '' , 'fanid DESC' , 1);
		$fan=pdo_get('mc_mapping_fans', array('fanid' => rand(1,$account[0]['fanid'])));		
		$tag = @iunserializer(@base64_decode($fan['tag']));
		$out=array(
		   'status' => 200,
		   'fanid'=>$fan['fanid'],
		   'nickname'=>$fan['nickname'],
		   'openid'=>$fan['openid'],
		   'headimgurl'=>$tag['headimgurl'] ,
		);
		exit(json_encode($out));
	}
}

if($ty=='upgift'){
	if($_W['ispost']){
		$reply = pdo_fetch("SELECT giftdata,config FROM " . tablename($this->tablereply) . " WHERE uniacid=:uniacid AND rid = :rid ORDER BY `id` DESC", array(':uniacid' => $_W['uniacid'],':rid' => $_GPC['rid']));
		$giftdata=@unserialize($reply['giftdata']);
		$gift=$giftdata[$_GPC['giftid']];
		
		$giftdatain = array(
				'rid'=>$rid, 
				'tid'=>$_GPC['id'],
				'uniacid'=>$_W['uniacid'],
				'oauth_openid'=>$_GPC['openid'],
				'openid'=>$_GPC['openid'],
				'avatar' =>$_GPC['headimgurl'],
				'nickname'=>$_GPC['nickname'],
				'user_ip'=>$_W['clientip'],
				'gifticon'=>$gift['gifticon'],
				'giftcount'=>1,
				'gifttitle'=>$gift['gifttitle'],
				'giftvote'=>$gift['giftvote'],
				'fee'=>sprintf("%.2f",$gift['giftprice']),
				'ptid'=>date('YmdHi').random(12, 1),
				'ispay'=>1,
				'status'=>0,
				'isdeal'=>1,
				'gifttype'=>1,
				'createtime'=>time()
		);
		
		
		
		$re=pdo_insert($this->tablegift, $giftdatain);
		if($re){
			$setvotesql = 'update ' . tablename($this->tablevoteuser) . ' set votenum=votenum+'.$gift['giftvote'].',giftcount=giftcount+'.$giftdatain['fee'].',lastvotetime='.time().'  where id = '.$_GPC['id'];
			$resetvote=pdo_query($setvotesql);
			
			
			$out=array('status' => 200);
		}else{
			$out=array('status' => 0);
		}
		
		exit(json_encode($out));
	}
}


if($ty=='deletevoteuser'){
	$voteuser = pdo_fetch("SELECT * FROM " . tablename($this->tablevoteuser) . " WHERE  id = :id AND uniacid = :uniacid AND rid = :rid", array(':id' => $id,':uniacid' => $uniacid,':rid' => $rid));
	if(!empty($voteuser)){
		pdo_delete($this->tablevoteuser,array('id' => $id,'uniacid' => $uniacid));
		pdo_delete($this->tablevotedata,array('tid' => $id,'rid' => $rid,'uniacid' => $uniacid));
		pdo_delete($this->tablecount,array('tid' => $id,'rid' => $rid,'uniacid' => $uniacid));
		//删除本地图片
		// for ($i=1; $i < 6; $i++) { 
		// 	file_delete($voteuser['img'.$i]);
		// }
		message('删除成功！', $this->createWebUrl('votelist', array('name' => 'tyzm_diamondvote','rid'=>$rid)), 'success');
	}{
		message('删除失败，不存在该投票！', 'error');
	}
}
if($ty=='deleteviporder'){
	$viporder = pdo_fetch("SELECT * FROM " . tablename($this->tableviporder) . " WHERE  id = :id AND uniacid = :uniacid ", array(':id' => $id,':uniacid' => $uniacid));
	if(!empty($viporder)){
		pdo_delete($this->tableviporder,array('id' => $id,'uniacid' => $uniacid));
		message('删除成功！', $this->createWebUrl('viporder', array('name' => 'tyzm_diamondvote')), 'success');
	}{
		message('删除失败，不存在该投票！', 'error');
	}
}

if($ty=='deleteredpack'){
	$viporder = pdo_fetch("SELECT * FROM " . tablename($this->tableredpack) . " WHERE  id = :id AND uniacid = :uniacid ", array(':id' => $id,':uniacid' => $uniacid));
	if(!empty($viporder)){
		pdo_delete($this->tableredpack,array('id' => $id,'uniacid' => $uniacid));
		message('删除成功！', $this->createWebUrl('lottery', array('name' => 'tyzm_diamondvote','rid'=>$rid)), 'success');
	}{
		message('删除失败，不存在该红包！', 'error');
	}
}


if($ty=='setstatus'){
        $status = intval($_GPC['status']);
        if (empty($rid)) {
            message('抱歉，传递的参数错误！', '', 'error');
        }
        $temp = pdo_update($this->tablereply, array('status' => $status), array('rid' => $rid,'uniacid'=>$uniacid));
        message('状态设置成功！', $this->createWebUrl('manage', array('name' => 'tyzm_diamondvote')), 'success');
}
if($ty=='delposterimg'){
	//删除海报图片
	$dirName = ATTACHMENT_ROOT.'/images/'.$_W['uniacid'].'/tyzm_diamondvote/'.$rid.'/';
	$re=del_dir($dirName);
	$out['status'] = 200;
	exit(json_encode($out));
}
if($ty=='clearposter'){
	//删除海报图片
	$voteuser = pdo_fetch("SELECT id,createtime FROM " . tablename($this->tablevoteuser) . " WHERE  id = :id AND uniacid = :uniacid AND rid = :rid", array(':id' => $id,':uniacid' => $uniacid,':rid' => $rid));
	$file = ATTACHMENT_ROOT.'/images/'.$_W['uniacid'].'/tyzm_diamondvote/'.$rid.'/'.$voteuser['createtime'].'_' .$voteuser['id'] .'.jpg';
	
	if(file_exists($file)){
		if(!unlink($file)){
			$out['status'] = 0;
			exit(json_encode($out));
		}else{
			$out['status'] = 200;
			exit(json_encode($out));
		}
	}else{
		$out['status'] = 404;
		exit(json_encode($out));
	}
	

	
}

if($ty=='repeatredpack'){
	//发红包start
	$redpackid = intval($_GPC['redpackid']);
	$sendr = m('redpack')->sendredpack($redpackid,$rid);
	if($sendr==88){
		$sendr="红包发送成功，分享海报邀请好友，获得红包奖励！";
	}
	message($sendr, $this->createWebUrl('lottery', array('name' => 'tyzm_tuanyuan','rid'=>$rid)), 'success'); 
	//发红包end
} 
if($ty=='setvotestatus'){
	if($_W['ispost']){
		$status = intval($_GPC['status']);
		$setvotestatus=pdo_update($this->tablevotedata,array('status'=>$status),array('id' =>intval($_GPC['vid']),'tid' =>$id,'rid' =>$rid,'uniacid'=>$uniacid));
		if($setvotestatus){
			if($_GPC['status']==1){
				$setvotesql = 'update ' . tablename($this->tablevoteuser) . ' set votenum=votenum-1 where id = '.$id;
			}else{
				$setvotesql = 'update ' . tablename($this->tablevoteuser) . ' set votenum=votenum+1 where id = '.$id;
			}
			$resetvote=pdo_query($setvotesql);
			if($resetvote){
				$out['status'] = 200;
				exit(json_encode($out));
			}else{
				pdo_update($this->tablevotedata,array('status'=>(1-$_GPC['status'])),array('id' =>intval($_GPC['vid']),'tid' =>$id,'rid' =>$rid,'uniacid'=>$uniacid));
			}

		}
	}
}


if($ty=='setgiftstatus'){
	if($_W['ispost']){
		$status = intval($_GPC['status']);
		$setgiftstatus=pdo_update($this->tablegift,array('status'=>$status),array('id' =>intval($_GPC['vid']),'tid' =>$id,'rid' =>$rid,'uniacid'=>$uniacid));
		if($setgiftstatus){
			
			$out['status'] = 200;
				exit(json_encode($out));
		}
	}
}
if($ty=='downpic'){
$votedata = pdo_fetch("SELECT * FROM " . tablename($this->tablevoteuser) . " WHERE  id = :id AND uniacid = :uniacid AND rid = :rid", array(':id' => $id,':uniacid' => $uniacid,':rid' => $rid));
$formatdata=unserialize($votedata['formatdata']);
$serverid=$formatdata[intval($_GPC['imgid'])];
if(!empty($serverid)){
	
	$imgurl=m('attachment')->doMobileMedia(array('media_id'=>$serverid,'type'=>'image','width'=>500));
	if(empty($imgurl)){
		$out['status'] = 0;
		$out['msg'] = "下载失败，请重试";
		exit(json_encode($out));
	}else{
		switch (intval($_GPC['imgid'])){    
			case 0 :  
			    $uparray = array('img1'=>$imgurl); 
                break;    				
			case 1 :     
				$uparray = array('img2'=>$imgurl);   
				break;     
			case 2 :     
				$uparray = array('img3'=>$imgurl);   
				break;     
			case 3 :     
				$uparray = array('img4'=>$imgurl);     
				break;   
            case 4 :     
				$uparray = array('img5'=>$imgurl);   
				break;   				
		}
		pdo_update($this->tablevoteuser,$uparray,array('id' =>$votedata['id']));
		$out['status'] = 1;
		$out['imgurl'] = tomedia($imgurl);
		exit(json_encode($out));
	}
}




}
	

		
		
		