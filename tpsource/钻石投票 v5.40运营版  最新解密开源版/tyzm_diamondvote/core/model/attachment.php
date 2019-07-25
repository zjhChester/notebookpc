<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
} 
class Tyzm_Attachment{
	public function __construct() {
		global $_W;
	}
	public function doMobileMedia($media){
		global $_W;
		$media['width']=!empty($media['width'])?$media['width']:600;
		$unisetting = uni_setting_load();
		load()->model('setting');
		$jsauth_acid=$unisetting['jsauth_acid'];
		if(empty($jsauth_acid)){
			if(!empty($unisetting['oauth']['account']) && $_W['account']['level']<3){
				$jsauth_acid=$unisetting['oauth']['account'];
			}else{
				$jsauth_acid=$unisetting['uniacid'];
			}
		} 
		/*
		load()->model('cache');
		$cachekey = "accesstoken:{$jsauth_acid}";
		$cache = cache_load($cachekey);

		load()->classs('weixin.account');
		$access_token = $cache['token'];
		
		*/
		$account_api = WeAccount::create($jsauth_acid);
		$access_token = $account_api->getAccessToken();
		
		$mediatypes = array('image', 'voice', 'thumb');
		if (empty($media) || empty($media['media_id']) || (!empty($media['type']) && !in_array($media['type'], $mediatypes))) {
			return error(-1, '微信下载媒体资源参数错误');
		}
		if(is_error($access_token)){
			return $token;
		}
		$sendapi = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id={$media['media_id']}";
		$response = ihttp_get($sendapi);
		if(!empty($response['headers']['Content-disposition']) && strexists($response['headers']['Content-disposition'], $media['media_id'])){
			global $_W;
			$filename =str_replace( array('attachment; filename=', '"',' '),'',$response['headers']['Content-disposition']);
			$filename = 'images/'.$_W['uniacid'].'/diamondvote/'.date('Y/m/').$filename;
			load()->func('file');
			file_write($filename, $response['content']);
			file_image_thumb(ATTACHMENT_ROOT.$filename,ATTACHMENT_ROOT.$filename,$media['width']);
			//是否开启投票远程附件
			$modulelist = uni_modules(false);
			$remote = $modulelist['tyzm_diamondvote']['config']['remote'];
			if(empty($remote['type'])){ 
				file_remote_upload($filename);
			}else{
				self::file_voteremote_upload($filename,$remote);
				if($remote['type']=='1'){
					$url=$remote['ftp']['url'];
				}elseif($remote['type']=='2'){
					$url=$remote['alioss']['url'];
				}elseif($remote['type']=='3'){
					$url=$remote['qiniu']['url'];
				}elseif($remote['type']=='4'){
					$url=$remote['cos']['url'];
				}
				$filename=$url."/".$filename;
			}
			return $filename;
		} else {
			$response = json_decode($response['content'], true);
			return error($response['errcode'], $response['errmsg']);
		}
    }
	function file_voteremote_upload($filename,$remote, $auto_delete_local = true) {
		global $_W;
		if (empty($remote['type'])) {
			return false;
		}
		if ($remote['type'] == '1') {
			require_once(IA_ROOT . '/framework/library/ftp/ftp.php');
			$ftp_config = array(
				'hostname' => $remote['ftp']['host'],
				'username' => $remote['ftp']['username'],
				'password' => $remote['ftp']['password'],
				'port' => $remote['ftp']['port'],
				'ssl' => $remote['ftp']['ssl'],
				'passive' => $remote['ftp']['pasv'],
				'timeout' => $remote['ftp']['timeout'],
				'rootdir' => $remote['ftp']['dir'],
			);
			$ftp = new Ftp($ftp_config);
			if (true === $ftp->connect()) {
				$response = $ftp->upload(ATTACHMENT_ROOT . '/' . $filename, $filename);
				if ($auto_delete_local) {
					file_delete($filename);
				}
				if (!empty($response)) {
					return true;
				} else {
					return error(1, '远程附件上传失败，请检查配置并重新上传');
				}
			} else {
				return error(1, '远程附件上传失败，请检查配置并重新上传');
			}
		} elseif ($remote['type'] == '2') {
			require_once('../framework/library/alioss/autoload.php');
			load()->model('attachment');
			$buckets = attachment_alioss_buctkets($remote['alioss']['key'], $remote['alioss']['secret']);
			$endpoint = 'http://'.$buckets[$remote['alioss']['bucket']]['location'].'.aliyuncs.com';
			try {
				$ossClient = new \OSS\OssClient($remote['alioss']['key'], $remote['alioss']['secret'], $endpoint);
				$ossClient->uploadFile($remote['alioss']['bucket'], $filename, ATTACHMENT_ROOT.$filename);
			} catch (\OSS\Core\OssException $e) {
				return error(1, $e->getMessage());
			}
			if ($auto_delete_local) {
				file_delete($filename);
			}
			
		}elseif ($remote['type'] == '3') {
			require_once(IA_ROOT . '/framework/library/qiniu/autoload.php');
			$auth = new Qiniu\Auth($remote['qiniu']['accesskey'],$remote['qiniu']['secretkey']);
			$uploadmgr = new Qiniu\Storage\UploadManager();
			$putpolicy = Qiniu\base64_urlSafeEncode(json_encode(array('scope' => $remote['qiniu']['bucket'].':'. $filename)));
			$uploadtoken = $auth->uploadToken($remote['qiniu']['bucket'], $filename, 3600, $putpolicy);
			list($ret, $err) = $uploadmgr->putFile($uploadtoken, $filename, ATTACHMENT_ROOT. '/'.$filename);
			if ($auto_delete_local) {
				file_delete($filename);
			}
			if ($err !== null) {
				return error(1, '远程附件上传失败，请检查配置并重新上传');
			} else {
				return true;
			}
		} elseif ($remote['type'] == '4') {
			if (!empty($remote['cos']['local'])) {
			    require(IA_ROOT.'/framework/library/cosv4.2/include.php');
				qcloudcos\Cosapi :: setRegion($remote['cos']['local']);
				$uploadRet = qcloudcos\Cosapi::upload($remote['cos']['bucket'], ATTACHMENT_ROOT .$filename,'/'.$filename,'',3 * 1024 * 1024, 0);
			} else {
				require(IA_ROOT.'/framework/library/cos/include.php');
				$uploadRet = \Qcloud_cos\Cosapi::upload($remote['cos']['bucket'], ATTACHMENT_ROOT .$filename,'/'.$filename,'',3 * 1024 * 1024, 0);
			}
			if ($uploadRet['code'] != 0) {
				switch ($uploadRet['code']) {
					case -62:
						$message = '输入的appid有误';
						break;
					case -79:
						$message = '输入的SecretID有误';
						break;
					case -97:
						$message = '输入的SecretKEY有误';
						break;
					case -166:
						$message = '输入的bucket有误';
						break;
				}
				return error(-1, $message);
			}
			if ($auto_delete_local) {
				file_delete($filename);
			}
		}
	}


	
	
}