<?php
/**
 * 钻石投票-批量上传
 *
 * @author 天涯织梦
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');
//require TYZM_MODEL_FUNC.'/uploads.php';
global $_W,$_GPC;
$op=$_GPC['op'];
$rid=intval($_GPC['rid']);
	 if($_W['ispost']){
		    $cture=0;
			$cflase=0;
			for ($k = 0; $k < count($_POST['imgname']); $k++) {
				$instdata = array(
					'rid'=>$rid,
					'uniacid'=>$_W['uniacid'],
					'avatar'=>$_POST['imgurl'][$k], 
					'name'=>$_POST['imgname'][$k],
					'img1'=>$_POST['imgurl'][$k],
					'introduction'=>'',
					'status'=>1,
				);
				$lastid = pdo_getall($this->tablevoteuser, array('rid' => $rid, 'uniacid' => $_W['uniacid']), array('noid') , '' , 'noid DESC' , array(1));
				$instdata['noid']=$lastid[0]['noid']+1;
				$instdata['createtime']=time();
				$result =pdo_insert($this->tablevoteuser, $instdata);
				if($result){
					$cture++;
				}else{
					$cflase++;
				}
			} 
			message('操作完成，成功'.$cture.'个，失败'.$cflase.'个。', $this->createWebUrl('votelist', array('name' => 'tyzm_diamondvote','rid'=>$rid)), 'success');
	 }
	 
	 include $this->template('uploadvote');