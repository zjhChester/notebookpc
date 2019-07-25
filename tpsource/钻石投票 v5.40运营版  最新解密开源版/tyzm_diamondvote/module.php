<?php

defined('IN_IA') or die('Access Denied');
require IA_ROOT . '/addons/tyzm_diamondvote/defines.php';
require TYZM_MODEL_FUNC . '/function.php';
class tyzm_diamondvoteModule extends WeModule
{
    public $table_reply = "tyzm_diamondvote_reply";
    public $tablevoteuser = "tyzm_diamondvote_voteuser";
    public $tablevotedata = "tyzm_diamondvote_votedata";
    public $tablegift = "tyzm_diamondvote_gift";
    public $tablecount = "tyzm_diamondvote_count";
    public $table_fans = "tyzm_diamondvote_fansdata";
    public $tableredpack = "tyzm_diamondvote_redpack";
    public $tablefriendship = "tyzm_diamondvote_friendship";
    public $tablelooklist = "tyzm_diamondvote_looklist";
    public $tableviporder = "tyzm_diamondvote_viporder";
    public $tableblacklist = "tyzm_diamondvote_blacklist";
    public $tabledomainlist = "tyzm_diamondvote_domainlist";
    public $tablesetmeal = "tyzm_diamondvote_setmeal";
    public function fieldsFormDisplay($rid = 0)
    {
        global $_W, $_GPC;
        $template = $this->tplmobile();
        $AdPiece = cache_load('AdPiece');
        $adrefreshtime = $AdPiece['refreshtime'];
        $creditnames = uni_setting($_W['uniacid'], array('creditnames'));
        if ($creditnames) {
            foreach ($creditnames["creditnames"] as $index => $creditname) {
                if ($creditname['enabled'] == 0) {
                    unset($creditnames['creditnames'][$index]);
                }
            }
            $scredit = implode(', ', array_keys($creditnames['creditnames']));
        } else {
            $scredit = '';
        }
        $baiduapikey = $this->module["config"]["baiduapikey"];
        $ipcity = $retData['retData']['city'];
        if (empty($rid)) {
            $issetmeal = pdo_tableexists('tyzm_diamondvote_setmeal');
            if ($issetmeal && empty($_W['isfounder'])) {
                $setmealstatus = 0;
                $meal = pdo_get($this->tablesetmeal, array("userid" => $_W["user"]["uid"]), array("count", "starttime", "endtime", "status"));
                if (empty($meal)) {
                    $setmealstatus = 1;
                    $setmealmsg = '未开通套餐，请联系管理员开通套餐，QQ：53089823';
                }
                if ($meal['status']) {
                    $setmealstatus = 1;
                    $setmealmsg = '套餐已禁用，请联系管理员开通，QQ：53089823';
                }
                if ($meal['starttime'] > time()) {
                    $setmealstatus = 1;
                    $setmealmsg = '套餐未生效，请联系管理员开通，QQ：53089823';
                }
                if ($meal['endtime'] < time()) {
                    $setmealstatus = 1;
                    $setmealmsg = '套餐已过期，请联系管理员开通，QQ：53089823';
                }
                $totalrid = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename($this->table_reply) . " WHERE   uniacid = :uniacid  ", array(":uniacid" => $_W["uniacid"]));
                if ($totalrid >= $meal['count']) {
                    $setmealstatus = 1;
                    if ($meal['count']) {
                        $setmealmsg = $meal['count'] . '个活动套餐已用完，请联系管理员升级套餐，QQ：53089823';
                    }
                }
            }
            if (empty($_GPC['copyid'])) {
                $reply = array('title' => '年度投票，震惊联合国，男神女神，萌宝帅宠，你也来一发！', 'thumb' => '../addons/tyzm_diamondvote/template/static/images/topimg.jpg', 'description' => '全民投票的时代，你怎么能错过，邀请好友一起来帮你吧。', 'starttime' => time(), 'endtime' => time() + 10 * 84400, 'apstarttime' => time(), 'apendtime' => time() + 10 * 84400, 'votestarttime' => time(), 'voteendtime' => time() + 10 * 84400, 'topimg' => '../addons/tyzm_diamondvote/template/static/images/topimg.jpg', 'bgcolor' => '#fff', 'eventrule' => '请设置活动规则内容，支持多媒体HTML！', 'prizemsg' => '请设置活动奖品内容，支持多媒体HTML！', 'area' => '', 'ischecked' => 1, 'followguide' => '关注公众号后，点击菜单或回复投票关键字即可参加投票。', 'diamondvalue' => 1, 'votecredit1' => 0, 'votecredit2' => 0, 'gvotecredit1' => 0, 'gvotecredit2' => 0, 'joincredit1' => 0, 'joincredit2' => 0, 'minupimg' => 1, 'maxupimg' => 5, 'posterkey' => $this->createRandomStr(5), "exchange" => 3, "jdexchange" => 3, "diamondmodel" => $_W["account"]["level"] < 3 ? 1 : 0, "rankingnum" => 20, "followvote" => 0, "everyoneuser" => 2, "dailyvote" => 6, "everyonevote" => 50, "everyonediamond" => 0, "giftscale" => 1, "giftunit" => "点", "virtualpv" => 0, "voteadimg" => '', "minnumpeople" => 0, "bill_hint" => "邀请方法：1、长按图片保存；2、发送给好友或朋友圈；3、好友帮忙投票，获取红包抽奖机会！", "bill_bg" => $_W["siteroot"] . "/addons/tyzm_diamondvote/template/static/images/posterbg.jpg", "act_name" => "投票奖励红包", "send_name" => "钻石投票", "wishing" => "恭喜发财，大吉大利！", "remark" => "玩越多得越多，奖励越多！", "act_namejoin" => "投票奖励红包", "send_namejoin" => "钻石投票", "wishingjoin" => "恭喜发财，大吉大利！", "remarkjoin" => "玩越多得越多，奖励越多！", "isredpack" => 0, "isredpackjoin" => 0, "rstatus" => 1);
                $giftdata = array('0' => array('gifticon' => $_W['siteroot'] . '/addons/tyzm_diamondvote/template/static/images/diamond.png', 'gifttitle' => '钻石', 'giftprice' => 1, 'giftvote' => 3), '1' => array('gifticon' => $_W['siteroot'] . '/addons/tyzm_diamondvote/template/static/images/smiley_angry.png', 'gifttitle' => '生气', 'giftprice' => 1, 'giftvote' => '-2'));
                $applydata = array('0' => array('infoname' => '手机', 'infotype' => 'mobile', 'notnull' => '1'), '2' => array('infoname' => '收货地址', 'infotype' => 'affectivestatus', 'notnull' => '1'));
                $tpl_setinput = $this->tpl_setinput($applydata);
            } else {
                $reply = pdo_fetch('SELECT * FROM ' . tablename($this->table_reply) . " WHERE rid = :rid ORDER BY `id` DESC", array(":rid" => $_GPC["copyid"]));
                $reply['title'] = $reply['title'] . '复制' . random(5, true);
                $reply['rstatus'] = $reply['status'];
                $reply = @array_merge($reply, @unserialize($reply['config']));
                $reply['style'] = @unserialize($reply['style']);
                $addata = @unserialize($reply['addata']);
                $giftdata = @unserialize($reply['giftdata']);
                $applydata = @unserialize($reply['applydata']);
                $bill_data = json_decode(str_replace('&quot;', '\'', $reply['bill_data']), true);
                $tpl_setinput = $this->tpl_setinput($applydata);
                unset($reply['id'], $reply['voteadurl']);
            }
        } else {
            $reply = pdo_fetch('SELECT * FROM ' . tablename($this->table_reply) . " WHERE rid = :rid ORDER BY `id` DESC", array(":rid" => $rid));
            $reply['rstatus'] = $reply['status'];
            $reply = @array_merge($reply, @unserialize($reply['config']));
            $reply['style'] = @unserialize($reply['style']);
            if (empty($reply['style']['template'])) {
                $reply['style']['template'] = 'default';
                $reply['style']['setstyle'] = 'default_index';
            }
            $addata = @unserialize($reply['addata']);
            $giftdata = @unserialize($reply['giftdata']);
            $applydata = @unserialize($reply['applydata']);
            $bill_data = json_decode(str_replace('&quot;', '\'', $reply['bill_data']), true);
            $tpl_setinput = $this->tpl_setinput($applydata);
        }
        if (!file_exists(MODULE_ROOT . '/lib/font/font.ttf')) {
            $nofont = 1;
        }
        include $this->template("form");
    }
    public function fieldsFormValidate($rid = 0)
    {
        return '';
    }
    public function fieldsFormSubmit($rid)
    {
        global $_W, $_GPC;
        $id = intval($_GPC['reply_id']);
        $config = @iserializer(array('jumpdomain' => $_GPC['jumpdomain'], 'isfollow' => $_GPC['isfollow'], 'followvote' => $_GPC['followvote'], 'indexorder' => $_GPC['indexorder'], 'infoorder' => $_GPC['infoorder'], 'unsubscribe' => $_GPC['unsubscribe'], 'diamondvalue' => $_GPC['diamondvalue'], 'diamondmodel' => $_GPC['diamondmodel'], 'exchange' => $_GPC['exchange'], 'jdexchange' => $_GPC['jdexchange'], 'rankingnum' => $_GPC['rankingnum'], 'divideinto' => $_GPC['divideinto'], 'followguide' => $_GPC['followguide'], 'notice' => $_GPC['notice'], 'everyoneuser' => $_GPC['everyoneuser'], 'dailyvote' => $_GPC['dailyvote'], 'everyonevote' => $_GPC['everyonevote'], 'isoneself' => $_GPC['isoneself'], 'ischecked' => $_GPC['ischecked'], 'voteadimg' => $_GPC['voteadimg'], 'voteadtext' => $_GPC['voteadtext'], 'voteadurl' => $_GPC['voteadurl'], 'everyonediamond' => $_GPC['everyonediamond'], 'isvotemsg' => $_GPC['isvotemsg'], 'virtualpv' => intval($_GPC['virtualpv']), 'iseggnone' => $_GPC['iseggnone'], 'locationtype' => $_GPC['locationtype'], 'isdiamondnone' => $_GPC['isdiamondnone'], 'minnumpeople' => intval($_GPC['minnumpeople']), 'minupimg' => intval($_GPC['minupimg']), 'maxupimg' => intval($_GPC['maxupimg']), 'perminute' => intval($_GPC['perminute']), 'perminutevote' => intval($_GPC['perminutevote']), 'lockminutes' => intval($_GPC['lockminutes']), 'votegive_type' => $_GPC['votegive_type'], 'votegive_num' => $_GPC['votegive_num'], 'joingive_type' => $_GPC['joingive_type'], 'joingive_num' => $_GPC['joingive_num'], 'giftgive_type' => $_GPC['giftgive_type'], 'giftgive_num' => $_GPC['giftgive_num'], 'awardgive_type' => $_GPC['awardgive_type'], 'awardgive_num' => $_GPC['awardgive_num'], 'isshowgift' => $_GPC['isshowgift'], 'weixinopen' => $_GPC['weixinopen'], 'isredpack' => $_GPC['isredpack'], 'act_name' => $_GPC['act_name'], 'send_name' => $_GPC['send_name'], 'wishing' => $_GPC['wishing'], 'remark' => $_GPC['remark'], 'redpackettotal' => $_GPC['redpackettotal'], 'lotterychance' => $_GPC['lotterychance'], 'probability' => $_GPC['probability'], 'ipplace' => $_GPC['ipplace'], 'redpackarea' => $_GPC['redpackarea'], 'redpacketnum' => $_GPC['redpacketnum'], 'everyonenum' => $_GPC['everyonenum'], 'limitstart' => $_GPC['limitstart'], 'limitend' => $_GPC['limitend'], 'isposter' => $_GPC['isposter'], 'posterkey' => $_GPC['posterkey'], 'bill_bg' => $_GPC['bill_bg'], 'bill_hint' => $_GPC['bill_hint'], 'defaultpay' => $_GPC['defaultpay'], 'usepwd' => $_GPC['usepwd'], 'giftscale' => $_GPC['giftscale'], 'giftunit' => $_GPC['giftunit'], 'isgiftad' => $_GPC['isgiftad'], 'upimgtype' => $_GPC['upimgtype'], 'upvideotype' => $_GPC['upvideotype'], 'musicshare' => $_GPC['musicshare'], 'verifycode' => $_GPC['verifycode'], 'isindexslide' => $_GPC['isindexslide'], 'islinkpage' => $_GPC['islinkpage'], 'linkpagetime' => $_GPC['linkpagetime'], 'indexsound' => $_GPC['indexsound'], 'index_ad' => $_GPC['index_ad'], 'prize_ad' => $_GPC['prize_ad'], 'rank_ad' => $_GPC['rank_ad'], 'view_ad' => $_GPC['view_ad'], 'gift_ad' => $_GPC['gift_ad'], 'isredpackjoin' => $_GPC['isredpackjoin'], 'remarkjoin' => $_GPC['remarkjoin'], 'wishingjoin' => $_GPC['wishingjoin'], 'send_namejoin' => $_GPC['send_namejoin'], 'act_namejoin' => $_GPC['act_namejoin'], 'limitstartjoin' => $_GPC['limitstartjoin'], 'limitendjoin' => $_GPC['limitendjoin'], 'probabilityjoin' => $_GPC['probabilityjoin'], 'redpacketnumjoin' => $_GPC['redpacketnumjoin'], 'redpackettotaljoin' => $_GPC['redpackettotaljoin'], 'indexnum' => $_GPC['indexnum'], 'isreport' => $_GPC['isreport']));
        $k = 0;
        while ($k < count($_POST['adtitle'])) {
            $addata[$k] = array('type' => 1, 'adtitle' => $_POST['adtitle'][$k], 'adimg' => $_POST['adimg'][$k], 'adurl' => $_POST['adurl'][$k]);
            $k++;
        }
        $addata = @iserializer($addata);
        $k = 0;
        while ($k < count($_POST['gifttitle'])) {
            $giftdata[$k] = array('gifticon' => $_POST['gifticon'][$k], 'gifttitle' => $_POST['gifttitle'][$k], 'giftprice' => $_POST['giftprice'][$k], 'giftvote' => $_POST['giftvote'][$k]);
            $k++;
        }
        $style = @iserializer(array('template' => $_POST['template'], 'setstyle' => $_POST['setstyle']));
        $giftdata = @iserializer($giftdata);
        $k = 0;
        while ($k < count($_POST['infoname'])) {
            $applydata[$k] = array('infoname' => $_POST['infoname'][$k], 'infotype' => $_POST['infotype'][$k], 'notnull' => $_POST['notnull'][$k], 'isshow' => $_POST['isshow'][$k]);
            $k++;
        }
        $applydata = @iserializer($applydata);
        $insert = array('rid' => $rid, 'uniacid' => $_W['uniacid'], 'title' => $_GPC['title'], 'thumb' => $_GPC['thumb'], 'description' => $_GPC['description'], 'starttime' => strtotime($_GPC['time']['start']), 'endtime' => strtotime($_GPC['time']['end']), 'apstarttime' => strtotime($_GPC['aptime']['start']), 'apendtime' => strtotime($_GPC['aptime']['end']), 'votestarttime' => strtotime($_GPC['votetime']['start']), 'voteendtime' => strtotime($_GPC['votetime']['end']), 'topimg' => $_GPC['topimg'], 'bgcolor' => $_GPC['bgcolor'], 'style' => $style, 'eventrule' => htmlspecialchars_decode($_GPC['eventrule']), 'prizemsg' => htmlspecialchars_decode($_GPC['prizemsg']), 'config' => $config, 'addata' => $addata, 'giftdata' => $giftdata, 'bill_data' => htmlspecialchars_decode($_GPC['bill_data']), 'applydata' => $applydata, 'area' => $_GPC['area'], 'sharetitle' => $_GPC['sharetitle'], 'shareimg' => $_GPC['shareimg'], 'sharedesc' => $_GPC['sharedesc'], 'status' => $_GPC['rstatus'], 'createtime' => time());
        if (empty($id)) {
            $issetmeal = pdo_tableexists('tyzm_diamondvote_setmeal');
            if ($issetmeal && empty($_W['isfounder'])) {
                $setmealstatus = 0;
                $meal = pdo_get($this->tablesetmeal, array("userid" => $_W["user"]["uid"]), array("count", "starttime", "endtime", "status"));
                if (empty($meal) && empty($_W['isfounder'])) {
                    $setmealstatus = 1;
                    $setmealmsg = '未开通套餐，请联系管理员开通套餐，QQ：53089823';
                }
                if ($meal['status']) {
                    $setmealstatus = 1;
                    $setmealmsg = '套餐已禁用，请联系管理员开通，QQ：53089823';
                }
                if ($meal['starttime'] > time()) {
                    $setmealstatus = 1;
                    $setmealmsg = '套餐未生效，请联系管理员开通，QQ：53089823';
                }
                if ($meal['endtime'] < time()) {
                    $setmealstatus = 1;
                    $setmealmsg = '套餐已过期，请联系管理员开通，QQ：53089823';
                }
                $totalrid = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename($this->table_reply) . " WHERE   uniacid = :uniacid  ", array(":uniacid" => $_W["uniacid"]));
                if ($totalrid >= $meal['count']) {
                    $setmealstatus = 1;
                    if ($meal['count']) {
                        $setmealmsg = $meal['count'] . '个活动套餐已用完，请联系管理员升级套餐，QQ：53089823';
                    }
                }
                if ($setmealstatus) {
                    message($setmealmsg);
                }
            }
            pdo_insert($this->table_reply, $insert);
        } else {
            unset($insert['createtime']);
            pdo_update($this->table_reply, $insert, array("id" => $id));
        }
        message('设置成功！', $this->createWebUrl("manage", array("name" => "tyzm_diamondvote")), "success");
    }
    public function ruleDeleted($rid)
    {
        pdo_delete($this->table_reply, array("rid" => $rid));
        pdo_delete($this->tablevoteuser, array("rid" => $rid));
        pdo_delete($this->tablevotedata, array("rid" => $rid));
        pdo_delete($this->tablegift, array("rid" => $rid));
        pdo_delete($this->tablecount, array("rid" => $rid));
        pdo_delete($this->tableredpack, array("rid" => $rid));
        pdo_delete($this->tabledomainlist, array("rid" => $rid));
    }
    public function settingsDisplay($settings)
    {
        global $_W, $_GPC;
        load()->model("attachment");
        if (checksubmit()) {
            load()->func("file");
            $certroot = MODULE_ROOT . '/template/certdata/' . $_W['uniacid'] . '/' . $_GPC['certkey'];
            if ($this->module["config"]["certkey"] != $_GPC["certkey"] && !empty($this->module["config"]["certkey"])) {
                $oldcertroot = MODULE_ROOT . '/template/certdata/' . $_W['uniacid'] . '/' . $this->module["config"]["certkey"];
                rename($oldcertroot, $certroot);
            }
            $r = mkdirs($certroot);
            if (!empty($_GPC['apiclient_cert'])) {
                $ret = file_put_contents($certroot . '/apiclient_cert.pem', trim($_GPC['apiclient_cert']));
                $r = $r && $ret;
            }
            if (!empty($_GPC['apiclient_key'])) {
                $ret = file_put_contents($certroot . '/apiclient_key.pem', trim($_GPC['apiclient_key']));
                $r = $r && $ret;
            }
            if (!$r) {
                message('证书保存失败, 请保证 /addons/tyzm_voicekey/template/certdata/ 目录可写');
            }
            $remote = array('type' => intval($_GPC['type']), 'ftp' => array('ssl' => intval($_GPC['ftp']['ssl']), 'host' => $_GPC['ftp']['host'], 'port' => $_GPC['ftp']['port'], 'username' => $_GPC['ftp']['username'], 'password' => strexists($_GPC['ftp']['password'], '*') ? $_W['setting']['remote']['ftp']['password'] : $_GPC['ftp']['password'], 'pasv' => intval($_GPC['ftp']['pasv']), 'dir' => $_GPC['ftp']['dir'], 'url' => $_GPC['ftp']['url'], 'overtime' => intval($_GPC['ftp']['overtime'])), 'alioss' => array('key' => $_GPC['alioss']['key'], 'secret' => strexists($_GPC['alioss']['secret'], '*') ? $_W['setting']['remote']['alioss']['secret'] : $_GPC['alioss']['secret'], 'bucket' => $_GPC['alioss']['bucket']), 'qiniu' => array('accesskey' => trim($_GPC['qiniu']['accesskey']), 'secretkey' => strexists($_GPC['qiniu']['secretkey'], '*') ? $_W['setting']['remote']['qiniu']['secretkey'] : trim($_GPC['qiniu']['secretkey']), 'bucket' => trim($_GPC['qiniu']['bucket']), 'district' => intval($_GPC['qiniu']['district']), 'url' => trim($_GPC['qiniu']['url'])), 'cos' => array('appid' => trim($_GPC['cos']['appid']), 'secretid' => trim($_GPC['cos']['secretid']), 'secretkey' => strexists(trim($_GPC['cos']['secretkey']), '*') ? $_W['setting']['remote']['cos']['secretkey'] : trim($_GPC['cos']['secretkey']), 'bucket' => trim($_GPC['cos']['bucket']), 'url' => trim($_GPC['cos']['url']), 'local' => trim($_GPC['cos']['local'])));
            if ($remote['type'] == ATTACH_OSS) {
                if (trim($remote['alioss']['key']) == '') {
                    message('阿里云OSS-Access Key ID不能为空');
                }
                if (trim($remote['alioss']['secret']) == '') {
                    message('阿里云OSS-Access Key Secret不能为空');
                }
                $buckets = attachment_alioss_buctkets($remote['alioss']['key'], $remote['alioss']['secret']);
                if (is_error($buckets)) {
                    message('OSS-Access Key ID 或 OSS-Access Key Secret错误，请重新填写');
                }
                list($remote["alioss"]["bucket"], $remote["alioss"]["url"]) = explode("@@", $_GPC["alioss"]["bucket"]);
                if (empty($buckets[$remote['alioss']['bucket']])) {
                    message('Bucket不存在或是已经被删除');
                }
                $remote['alioss']['url'] = 'http://' . $remote['alioss']['bucket'] . '.' . $buckets[$remote['alioss']['bucket']]['location'] . '.aliyuncs.com';
                $remote['alioss']['ossurl'] = $buckets[$remote['alioss']['bucket']]['location'] . '.aliyuncs.com';
                if (!empty($_GPC['custom']['url'])) {
                    $url = trim($_GPC['custom']['url'], '/');
                    if (!strexists($url, 'http://') && !strexists($url, 'https://')) {
                        $url = 'http://' . $url;
                    }
                    $remote['alioss']['url'] = $url;
                }
            } else {
                if ($remote['type'] == ATTACH_FTP) {
                    if (empty($remote['ftp']['host'])) {
                        message('FTP服务器地址为必填项.');
                    }
                    if (empty($remote['ftp']['username'])) {
                        message('FTP帐号为必填项.');
                    }
                    if (empty($remote['ftp']['password'])) {
                        message('FTP密码为必填项.');
                    }
                } else {
                    if ($remote['type'] == ATTACH_QINIU) {
                        if (empty($remote['qiniu']['accesskey'])) {
                            message('请填写Accesskey', referer(), 'info');
                        }
                        if (empty($remote['qiniu']['secretkey'])) {
                            message('secretkey', referer(), 'info');
                        }
                        if (empty($remote['qiniu']['bucket'])) {
                            message('请填写bucket', referer(), 'info');
                        }
                        if (empty($remote['qiniu']['url'])) {
                            message('请填写url', referer(), 'info');
                        } else {
                            $remote['qiniu']['url'] = strexists($remote['qiniu']['url'], 'http') ? trim($remote['qiniu']['url'], '/') : 'http://' . trim($remote['qiniu']['url'], '/');
                        }
                        $auth = attachment_qiniu_auth($remote['qiniu']['accesskey'], $remote['qiniu']['secretkey'], $remote['qiniu']['bucket'], $remote['qiniu']['district']);
                        if (is_error($auth)) {
                            $message = $auth['message']['error'] == 'bad token' ? 'Accesskey或Secretkey填写错误， 请检查后重新提交' : 'bucket填写错误或是bucket所对应的存储区域选择错误，请检查后重新提交';
                            message($message, referer(), 'info');
                        }
                    } else {
                        if ($remote['type'] == ATTACH_COS) {
                            if (empty($remote['cos']['appid'])) {
                                message('请填写APPID', referer(), 'info');
                            }
                            if (empty($remote['cos']['secretid'])) {
                                message('请填写SECRETID', referer(), 'info');
                            }
                            if (empty($remote['cos']['secretkey'])) {
                                message('请填写SECRETKEY', referer(), 'info');
                            }
                            if (empty($remote['cos']['bucket'])) {
                                message('请填写BUCKET', referer(), 'info');
                            }
                            if (empty($remote['cos']['url'])) {
                                $remote['cos']['url'] = 'http://' . $remote['cos']['bucket'] . '-' . $remote['cos']['appid'] . '.cos.myqcloud.com';
                            } else {
                                if (strexists($remote['cos']['url'], '.cos.myqcloud.com') && !strexists($url, '//' . $remote['cos']['bucket'] . '-')) {
                                    $remote['cos']['url'] = 'http://' . $remote['cos']['bucket'] . '-' . $remote['cos']['appid'] . '.cos.myqcloud.com';
                                }
                                $remote['cos']['url'] = strexists($remote['cos']['url'], 'http') ? trim($remote['cos']['url'], '/') : 'http://' . trim($remote['cos']['url'], '/');
                            }
                            $auth = attachment_cos_auth($remote['cos']['bucket'], $remote['cos']['appid'], $remote['cos']['secretid'], $remote['cos']['secretkey'], $remote['cos']['local']);
                            if (is_error($auth)) {
                                message($auth['message'], referer(), 'info');
                            }
                        }
                    }
                }
            }
            $dat = array('certkey' => $_GPC['certkey'], 'isopenweixin' => $_GPC['isopenweixin'], 'key' => $_GPC['key'], 'agentkey' => $_GPC['agentkey'], 'mchid' => $_GPC['mchid'], 'apikey' => $_GPC['apikey'], 'remote' => $remote);
            $this->saveSettings($dat);
            message('保存成功', 'refresh');
        }
        $remote = $settings['remote'];
        if (!empty($remote['alioss']['key']) && !empty($remote['alioss']['secret'])) {
            $buckets = attachment_alioss_buctkets($remote['alioss']['key'], $remote['alioss']['secret']);
        }
        if (!empty($_GPC['datelimit'])) {
            $starttime = strtotime($_GPC['datelimit']['start']);
            $endtime = strtotime($_GPC['datelimit']['end']) + 86399;
            $dailytimes .= ' AND createtime > ' . $starttime . ' AND createtime < ' . $endtime;
        } else {
            $dailystarttime = mktime(0, 0, 0);
            $dailyendtime = mktime(23, 59, 59);
            $dailytimes = '';
            $dailytimes .= ' AND createtime >=' . $dailystarttime;
            $dailytimes .= ' AND createtime <=' . $dailyendtime;
        }
        $item['dailyjointotal'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename($this->tablevoteuser) . " WHERE   uniacid = :uniacid  " . $dailytimes, array(":uniacid" => $_W["uniacid"]));
        $item['dailyvotetotal'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename($this->tablevotedata) . " WHERE   uniacid = :uniacid AND votetype=0 " . $dailytimes, array(":uniacid" => $_W["uniacid"]));
        $item['dailygiftcount'] = pdo_fetchcolumn('SELECT sum(fee) FROM ' . tablename($this->tablegift) . " WHERE   uniacid = :uniacid AND ispay=1 AND gifttype=0 " . $dailytimes, array(":uniacid" => $_W["uniacid"]));
        $item['dailygiftnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename($this->tablegift) . " WHERE   uniacid = :uniacid AND ispay=1 AND gifttype=0 " . $dailytimes, array(":uniacid" => $_W["uniacid"]));
        $item['dailygiftcount'] = !empty($item['dailygiftcount']) ? $item['dailygiftcount'] : 0;
        $item['jointotal'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename($this->tablevoteuser) . " WHERE   uniacid = :uniacid  ", array(":uniacid" => $_W["uniacid"]));
        $item['votetotal'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename($this->tablevotedata) . " WHERE   uniacid = :uniacid AND votetype=0 ", array(":uniacid" => $_W["uniacid"]));
        $item['giftcount'] = pdo_fetchcolumn('SELECT sum(fee) FROM ' . tablename($this->tablegift) . " WHERE   uniacid = :uniacid AND ispay=1 AND gifttype=0 ", array(":uniacid" => $_W["uniacid"]));
        $item['pvtotal'] = pdo_fetchcolumn('SELECT sum(pv_total) FROM ' . tablename($this->tablecount) . " WHERE uniacid = :uniacid ", array(":uniacid" => $_W["uniacid"]));
        $item['giftcount'] = !empty($item['giftcount']) ? $item['giftcount'] : 0;
        if (IMS_VERSION >= 0.8) {
            $bucket_datacenter = attachment_alioss_datacenters();
        }
        include $this->template("setting");
    }
    public function tpl_setinput($arrayvalue = array())
    {
        if (is_array($arrayvalue)) {
            foreach (@$arrayvalue as $row) {
                $html .= '
					<div  style="margin-left:-15px;">
					  <div class="col-sm-10" style="margin-bottom:10px">
						<div class="input-group">
						  <input type="text" class="form-control" name="infoname[]" value="' . $row['infoname'] . '" placeholder="请输入表单名称">
						  <span class="input-group-addon"></span>
						  <select  class="form-control" name="infotype[]">
							<option value="">请选择输入类型</option>
							<option value="realname"' . ($row['infotype'] == 'realname' ? 'selected ="selected"' : '') . '  >真实姓名</option>
							<option value="mobile" ' . ($row['infotype'] == 'mobile' ? 'selected ="selected"' : '') . ' >手机号码</option>
							<option value="email" ' . ($row['infotype'] == 'email' ? 'selected ="selected"' : '') . ' >电子邮箱</option>
							<option value="sex" ' . ($row['infotype'] == 'sex' ? 'selected ="selected"' : '') . ' >性别</option>
							<option value="qq" >QQ号</option>
							<option value="birthyear" >出生生日</option>
							<option value="telephone"' . ($row['infotype'] == 'telephone' ? 'selected ="selected"' : '') . ' >固定电话</option>
							<option value="idcard" ' . ($row['infotype'] == 'idcard' ? 'selected ="selected"' : '') . '>证件号码</option>
							<option value="address" ' . ($row['infotype'] == 'address' ? 'selected ="selected"' : '') . '>邮寄地址</option>
							<option value="zipcode"' . ($row['infotype'] == 'zipcode' ? 'selected ="selected"' : '') . ' >邮编</option>
							<option value="nationality" ' . ($row['infotype'] == 'nationality' ? 'selected ="selected"' : '') . '>国籍</option>
							<option value="resideprovince"' . ($row['infotype'] == 'resideprovince' ? 'selected ="selected"' : '') . ' >居住地址</option>
							<option value="graduateschool" ' . ($row['infotype'] == 'graduateschool' ? 'selected ="selected"' : '') . '>毕业学校</option>
							<option value="company"' . ($row['infotype'] == 'company' ? 'selected ="selected"' : '') . ' >公司</option>
							<option value="education" ' . ($row['infotype'] == 'education' ? 'selected ="selected"' : '') . '>学历</option>
							<option value="occupation" ' . ($row['infotype'] == 'occupation' ? 'selected ="selected"' : '') . '>职业</option>
							<option value="position" ' . ($row['infotype'] == 'position' ? 'selected ="selected"' : '') . '>职位</option>
							<option value="revenue" ' . ($row['infotype'] == 'revenue' ? 'selected ="selected"' : '') . '>年收入</option>
							<option value="affectivestatus" ' . ($row['infotype'] == 'affectivestatus' ? 'selected ="selected"' : '') . '>情感状态</option>
							<option value="lookingfor"' . ($row['infotype'] == 'lookingfor' ? 'selected ="selected"' : '') . ' > 交友目的</option>
							<option value="bloodtype"' . ($row['infotype'] == 'bloodtype' ? 'selected ="selected"' : '') . ' >血型</option>
							<option value="height"' . ($row['infotype'] == 'height' ? 'selected ="selected"' : '') . ' >身高</option>
							<option value="weight" ' . ($row['infotype'] == 'weight' ? 'selected ="selected"' : '') . '>体重</option>
							<option value="alipay" ' . ($row['infotype'] == 'alipay' ? 'selected ="selected"' : '') . '>支付宝帐号</option>
							<option value="taobao"' . ($row['infotype'] == 'taobao' ? 'selected ="selected"' : '') . ' >阿里旺旺</option>
							<option value="vqqcom"' . ($row['infotype'] == 'vqqcom' ? 'selected ="selected"' : '') . ' >腾讯视频</option>
							<option value="site"' . ($row['infotype'] == 'site' ? 'selected ="selected"' : '') . ' >主页</option>
							<option value="bio" ' . ($row['infotype'] == 'bio' ? 'selected ="selected"' : '') . '>自我介绍</option>
							<option value="interest" ' . ($row['infotype'] == 'interest' ? 'selected ="selected"' : '') . '>兴趣爱好</option>

						  </select>
							 <div class="input-group-btn" style="width:95px">
								<select  class="form-control" name="notnull[]">
												<option value="1" ' . ($row['notnull'] == '1' ? 'selected ="selected"' : '') . ' >必填</option>
												<option value="0" ' . ($row['notnull'] == '0' ? 'selected ="selected"' : '') . ' >非必填</option>
								</select>
							 </div>
							 <div class="input-group-btn" style="width:130px">
								<select  class="form-control" name="isshow[]">
												<option value="0" ' . ($row['isshow'] == '0' ? 'selected ="selected"' : '') . ' >前台不显示</option>
												<option value="1" ' . ($row['isshow'] == '1' ? 'selected ="selected"' : '') . ' >前台显示</option>
								</select>
							 </div>
						</div>
					  </div>
					  <div class="col-sm-1 del_box" style="margin-top:5px" ><a href="javascript:;" ><i class="fa fa-times-circle"></i></a></div>
					</div>				
				';
            }
        }
        return $html;
    }
    public function welcomeDisplay()
    {
        ob_end_clean();
        @header('Location: ' . $this->createWebUrl("manage"));
        die;
    }
    public function createRandomStr($length)
    {
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $strlen = 26;
        while ($length > $strlen) {
            $str .= $str;
            $strlen += 26;
        }
        $str = str_shuffle($str);
        return substr($str, 0, $length);
    }
    public function tplmobile()
    {
        global $_W;
        $template = array('default_new' => array('title' => '默认模板', 'icon' => 'icon.jpg', 'style' => array('default' => array('css' => 'index', 'color' => '#ccc'), 'yellow' => array('css' => 'default_yellow', 'color' => '#fff900'), 'blue' => array('css' => 'default_blue', 'color' => '#04b0f5'), 'purple' => array('css' => 'default_purple', 'color' => '#c400d0'))));
        return $template;
    }
}