<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
} 
class Tyzm_Common{
	public function __construct() {
		global $_W;
	}
	public function get_ip($filename,$num){
		//随机取IP地址
		$nickname_array = array();
		$random_array = array();
		$file_new=fopen($filename,"r");
		$file_read = fread($file_new, filesize($filename)); 
		fclose($file_new);
		$arr_new = unserialize($file_read);
		for($i=0;$i<$num;$i++){
			$random=rand(0,count($arr_new)-1);
			$random_array[$i] = $random;
			$nickname = $arr_new[$random];
			$all_ip = explode('-', $nickname);
			$ip_A = explode('.', $all_ip[0]);
			$ip_B = explode('.', $all_ip[1]);
			$nickname_array[$i] = rand($ip_A[0], $ip_B[0]).'.'.rand($ip_A[1], $ip_B[1]).'.'.rand($ip_A[2], $ip_B[2]).'.'.rand($ip_A[3], $ip_B[3]);
		}
		return $nickname_array;
	}
	public function sendTplNotice($touser, $template_id, $postdata, $url = '', $account = null) {
		global $_W;
		load() -> model('account');
		if (!$account) {
			if (!empty($_W['acid'])) {
				$account= WeAccount :: create($_W['acid']);
			} else {
				$acid = pdo_fetchcolumn("SELECT acid FROM " . tablename('account_wechats') . " WHERE `uniacid`=:uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
				$account= WeAccount :: create($acid);
			} 
		} 
		if (!$account) {
			return;
		} 
		return $account -> sendTplNotice($touser, $template_id, $postdata, $url);
	} 
	public function oauth_uniacid(){
		global $_W;
		//借权uniacid start
		if($_W['account']['level']==4){
			$uniacid=$_W['uniacid'];
		}elseif($_W['oauth_account']['level']==4){
			$oauth_acid=$_W['oauth_account']['acid'];
			$account_wechats = pdo_fetch("SELECT uniacid FROM " . tablename('account_wechats') . " WHERE acid = :acid ", array(':acid' => $oauth_acid));
			$uniacid=$account_wechats['uniacid'];
		}else{
			$uniacid=$_W['uniacid'];
		}
		//借权end
        return $uniacid;
	}
	public function Check_browser(){
		global $_W,$_GPC;
		$useragent = addslashes($_SERVER['HTTP_USER_AGENT']);
        if(strpos($useragent, 'MicroMessenger') === false && strpos($useragent, 'Windows Phone') === false ){
          echo "请使用微信打开";
          exit;
        }
	}
	public function  json_exit($status,$msg){
		exit(json_encode(array('status' => $status, 'msg' => $msg)));
	}
	public function Get_address($latitude,$longitude){
		global $_W;
		//如果不传地理位置时，自动转换ip定位
		load()->func('communication');
		if(empty($latitude) && empty($longitude)){
			$getipurl="http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=".$_W['clientip'];
			$resp = ihttp_get($getipurl);
			$locationData = json_decode($resp['content'], true);
			$address = $locationData['country'].$locationData['province'].$locationData['city'];
			return $address;
		}else{
			$mapkey=array('65c56be24ebfac24c966dacc192aacb3','ecd569c220ee996c90072f8e18a98ad7','af6fad46848d89e1ee25cbf75d1e33a7');
            $mkey= $mapkey[array_rand($mapkey,1)];
            $mapAPIUrl="http://restapi.amap.com/v3/geocode/regeo?key=".$mkey."&location=" . $longitude . "," . $latitude . "&batch=false&roadlevel=0";
            $resp = ihttp_get($mapAPIUrl);
            if (is_error($resp)) {
				$this->json_exit(0,$resp["message"]);
			}
			$locationData = json_decode($resp['content'], true);
			if ($locationData['status'] == 1) {
				$result = $locationData['regeocode'];
				$address = $result['formatted_address'];
				return $address;
			}else{
				$this->json_exit(0,$locationData['info']."(".$locationData['infocode'].")");
			}
		}
    }

    public function ip2address($ip=0){
		global $_W;
		$ip=empty($ip)?$_W['clientip']:$ip;
		//如果不传地理位置时，自动转换ip定位
		load()->func('communication');
		$getipurl="http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=".$ip;
		$resp = ihttp_get($getipurl);
		$locationData = json_decode($resp['content'], true);
		$address = $locationData['country'].$locationData['province'].$locationData['city'];
		return $address;
       }
	public function hex2rgb($hexColor) {
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {
            $rgb = array(
                'r' => hexdec(substr($color, 0, 2)),
                'g' => hexdec(substr($color, 2, 2)),
                'b' => hexdec(substr($color, 4, 2))
            );
        } else {
            $color = $hexColor;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = array(
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b)
            );
        }
        return $rgb;
    }
    public function rand_type($valve){
		//随机数
		$aa=explode("-",$valve);
		if(count($aa)==2){
            $a=rand($aa[0],$aa[1]);
		}else{
            $a=$valve;
		}
		return intval($a);
	}

} 