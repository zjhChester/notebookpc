<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
} 
class Tyzm_User{
	public function __construct() {
		global $_W;
	} 
	function Get_checkoauth(){
		global $_GPC,$_W;
		load()->model('mc');
		/*
		if($_W['account']['level']<=3){
			$passkey=$_COOKIE["passkey"][$_W['uniacid']];
			if(!empty($passkey)){
		        $pass = @json_decode(base64_decode($passkey), true);
				$hash = md5("{$pass['openid']}{$pass['uniacid']}{$_W['config']['setting']['authkey']}");
			    if($hash==$pass['hash'] && !empty($pass['openid']) && $pass['uniacid']==$_W['uniacid']){
			        $_W['openid']=$pass['openid']; 
			    }			
			}
	    }
	    */
        if($_W['openid']){
        	$_SESSION['oauth_openid']=empty($_SESSION['oauth_openid'])?$_W['openid']:$_SESSION['oauth_openid'];
        	$fans = pdo_get('mc_mapping_fans', array('uniacid' => $_W['uniacid'],'openid'=>$_W['openid']));
        	//print_r($fans);exit;
            if(!empty($fans) && !empty($fans['openid'])){
            	$fans['tag']=@unserialize(@base64_decode($fans['tag']));
            	//本公众号粉丝，获取到用户信息，直接返回信息。
	            if(!empty($fans['nickname']) && !empty($fans['tag']) && !empty($fans['tag']['avatar'])){
	            	    if($_W['account']['level']!=4){
							$oauth_openid=$_SESSION['oauth_openid'];
	            	    }else{
	            	    	$oauth_openid=$fans['openid'];
	            	    }
						$nickname=$fans['nickname'];
						$avatar=$fans['tag']['avatar'];
						$unionid=$fans['unionid'];
						$openid=$fans['openid'];
						$follow=$fans['follow'];
						$returncode=1;
	            }else{
	                //本公众号粉丝，未获取到用户信息，需要其他渠道获取。
	            	$openid=$_W['openid'];
	            	$follow=$fans['follow'];
	            	//判断是非借权
	            	if(!empty($_SESSION['oauth_acid'])){
	            		//借权，直接获取信息，返回本公众号openid,follow和借权来的头像和昵称
                        $oauth_userinfo=@unserialize(@base64_decode($_SESSION['userinfo']));
                        if(empty($oauth_userinfo['nickname']) && empty($oauth_userinfo['tag']['avatar'])){
							$oauth_userinfo=mc_oauth_userinfo();
                        }
						$oauth_openid=$oauth_userinfo['openid'];
						$nickname=$oauth_userinfo['nickname'];
						$avatar=$oauth_userinfo['avatar'];
						$unionid=$oauth_userinfo['unionid'];
						$returncode=2;
	            	}else{
	            		//非借权
	            		if($_W['account']['level']==2){
							$oauth_openid = $_W['fans']['openid'];
							$nickname = $_W['fans']['tag']['nickname'];
							$avatar = $_W['fans']['tag']['avatar'];
							$unionid= $_W['fans']['unionid']; 
							$returncode=3;
						}else{
							$member = mc_fetch(intval($_SESSION['uid']), array('avatar','nickname'));//无openid 无follow 有avatar 有nickname
							$oauth_openid = $openid;		
							if(empty($member['nickname'])){
								$nickname = "微信用户";
							}else{
								$nickname = $member['nickname'];
							}
							if(empty($member['avatar'])){
								$avatar = $_W['siteroot']."/addons/tyzm_diamondvote/template/static/images/defaultuser.jpg";
							}else{
								$avatar = $member['avatar'];
							}
							$unionid= $_W['fans']['unionid'];
							$returncode=4;
						}
	            	}
	            }
	        }else{
	        	//非本公众号用户信息，可能来自借权公众号
	        	if(!empty($_SESSION['oauth_acid'])){
	     				//借权，直接获取信息，返回本公众号openid,follow和借权来的头像和昵称
						 $oauth_userinfo=@unserialize(@base64_decode($_SESSION['userinfo']));
						 if(empty($oauth_userinfo['nickname']) || empty($oauth_userinfo['tag']['avatar'])){
							$oauth_userinfo=mc_oauth_userinfo();
                        }
						//print_r($oauth_userinfo);
						if(!empty($oauth_userinfo['unionid']) && $_W['account']['level']>=3){
							$rid=intval($_GPC['rid']);
							$modulelist = uni_modules(false);
							$isopenweixin=$modulelist['tyzm_diamondvote']['config']['isopenweixin'];
							if($isopenweixin){
								$unioninfo = pdo_fetch("SELECT follow,openid FROM " . tablename('mc_mapping_fans') . " WHERE unionid = :unionid AND uniacid=:uniacid", array(':unionid' => $oauth_userinfo['unionid'],':uniacid' => $_W['uniacid']));
							}
						}
						if(empty($unioninfo)){
							$oauth_openid = $oauth_userinfo['openid'];
							$nickname = $oauth_userinfo['nickname'];
							$avatar = $oauth_userinfo['avatar'];
							$unionid= $oauth_userinfo['unionid']; 
							$openid = $_W['openid'];
							$follow = 0;	
							$returncode=5;			
						}else{
							$oauth_openid = $oauth_userinfo['openid'];
							$nickname = $oauth_userinfo['nickname'];
							$avatar = $oauth_userinfo['avatar'];
							$unionid= $oauth_userinfo['unionid']; 
							$openid = $unioninfo['openid'];
							$follow = $unioninfo['follow'];
							$returncode=6;
						}
	            }
	        }
		    //过滤emoji
			$nickname = json_encode($nickname);
			$nickname = @preg_replace("#(\\\u[ed][0-9a-f]{3})#ie","",$nickname); //处理方式2，将emoji的unicode留下，其他不动 
			$nickname = json_decode($nickname);
			//过滤emoji
		    $userinfo=array(
				'oauth_openid'=>$oauth_openid,
				'nickname'=>$nickname,
				'avatar'=>$avatar,
				'unionid'=>$unionid,
				'openid'=>$openid,
				'follow'=>$follow,
				'returncode'=>$returncode,
			);	
			return $userinfo;
        }else{

        }
	}

	function sendkfinfo($openid,$content){//发送信息
		global $_GPC,$_W;
		$send['touser'] = trim($openid); 
		$send['msgtype'] = 'text'; 
		$send['text'] = array('content' => urlencode($content)); 
		$acc = WeAccount::create($_W['acid']); 
		$data = $acc->sendCustomNotice($send);
    }

}