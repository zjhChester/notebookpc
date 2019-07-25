<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
} 
/*
 * 返回以$name为名字的类对象
 * */
require TYZM_MODEL_LIB.'/ipdata/ip.php';
function m($name = '') {
	static $_modules = array();
	if (isset($_modules[$name])) {
		return $_modules[$name];
	} 
	
	$model = TYZM_MODEL_MODEL."/" . strtolower($name) . '.php';
	if (!is_file($model)) {
		die(' Model ' . $name . ' Not Found!');
	} 
	require $model;
	$class_name = 'Tyzm_' . ucfirst($name);//调用该类
	$_modules[$name] = new $class_name();
	return $_modules[$name];
} 
function is_array2($array) {
	if (is_array($array)) {
		foreach ($array as $k => $v) {
			return is_array($v);
		} 
		return false;
	}  
	return false;
} 
function is_weixin(){
	global $_W;
	if($_SERVER['HTTP_HOST']=="weili.nowbeta.com"){
		//message("抱歉，页面违反微信相关规则，已强制下线！",'','error');
	}
	$useragent = addslashes($_SERVER['HTTP_USER_AGENT']);
	$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
	if(strpos($useragent, 'MicroMessenger') === false && strpos($useragent, 'Windows Phone') === false ){
	  $url="http://qr.liantu.com/api.php?text=".urlencode($http_type .$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	  $html='<html><head><meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" /><title>只能在微信中打开</title><meta name="format-detection" content="telephone=no, address=no" /><meta name="apple-mobile-web-app-capable" content="yes" /><meta name="apple-touch-fullscreen" content="yes" /></head><body><style>.codeImage table{margin:0 auto;}</style><div class="box" style=" padding-top:1.5rem;min-height:18.45rem;"><div class="codeImage" style="text-align: center;"><img src="'.$url.'" alt="placeholder+image"></div><div class="info" style="margin:auto 0;margin-top:20px;color:#666;text-align: center;font-size: 28px;">请用手机微信扫描二维码</div></div><script>(function(){var s="_"+Math.random().toString(36).slice(2);document.write(\'<div id="\'+s+\'"></div>\');(window.slotbydup=window.slotbydup||[]).push({id:"4345361",container:s,size:"20,3",display:"inlay-fix"})})();</script><script src="http://dup.baidustatic.com/js/om.js"></script></body></html>';
	  echo $html;
	  exit;
	}
	
	
}

function Rrcodeurl($url){
	require (IA_ROOT . '/framework/library/qrcode/phpqrcode.php');
	$errorCorrectionLevel = "L";
	$matrixPointSize = "6";
	QRcode::png($url, false, $errorCorrectionLevel, $matrixPointSize);
}
function del_dir($dir){
    if(is_dir($dir)){
        foreach(scandir($dir) as $row){
            if($row == '.' || $row == '..'){
                continue;
            }
            $path = $dir .'/'. $row;
            if(filetype($path) == 'dir'){
                del_dir($path);
            }else{
                unlink($path);
            }
        }
        rmdir($dir);
    }else{
        return false;
    }
}	

function  ip2address($ip){
	$ipdata=Ip::find($ip);
	return $ipdata[0].$ipdata[1].$ipdata[2];
}
	
