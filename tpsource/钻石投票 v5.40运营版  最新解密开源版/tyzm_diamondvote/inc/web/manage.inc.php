<?php
/**
 * 钻石投票模块-后台管理
 *
 * @author 天涯织梦
 * @url http://bbs.we7.cc/
 */

defined('IN_IA') or exit('Access Denied');
          
		global $_GPC, $_W;
		$this->authorization();
		$pindex = max(1, intval($_GPC['page']));
        $psize = 20;
		if (!empty($_GPC['keyword'])) {
			$keyword=$_GPC['keyword'];
			$condition .= " AND CONCAT(`title`) LIKE '%{$keyword}%'";
		}
		$list = pdo_fetchall("SELECT * FROM ".tablename($this->tablereply)." WHERE uniacid = '{$_W['uniacid']} ' $condition  ORDER BY status DESC,createtime DESC LIMIT ".($pindex - 1) * $psize.",{$psize}");
		if (!empty($list)) {
             $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($this->tablereply) . " WHERE uniacid = '{$_W['uniacid']}' $condition");
             $pager = pagination($total, $pindex, $psize); 

			 foreach ($list as &$item){             

				if ($item['status'] ==1) {
					if ($item['starttime'] > time()) {//未开始
						$item['status'] = 3;
					}elseif ($item['endtime'] < time()) {//已结束
						$item['status'] = 4;
					}
                }
				
				$item['jointotal'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename($this->tablevoteuser) . " WHERE   rid = :rid  ", array(':rid' => $item['rid']));
				$item['votetotal'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename($this->tablevotedata) . " WHERE   rid = :rid AND votetype=0 ", array(':rid' => $item['rid']));
				$item['giftcount'] = pdo_fetchcolumn('SELECT sum(fee) FROM ' . tablename($this->tablegift) . " WHERE   rid = :rid AND ispay=1 AND gifttype=0 ", array(':rid' => $item['rid']));
                $item['giftcount']=!empty($item['giftcount'])?$item['giftcount']:0; 
				$item['virtualpvtotal']=pdo_fetchcolumn("SELECT sum(vheat) FROM ".tablename($this->tablevoteuser)." WHERE rid = :rid ", array(':rid' => $item['rid']));
				$item['pvtotal']=pdo_fetchcolumn("SELECT sum(pv_total) FROM ".tablename($this->tablecount)." WHERE rid = :rid ", array(':rid' => $item['rid']));
				$item['pvtotal']=$item['virtualpvtotal']+$item['pvtotal'];

				$item['sharetotal']=pdo_fetchcolumn("SELECT sum(share_total) FROM ".tablename($this->tablecount)." WHERE rid = :rid ", array(':rid' => $item['rid']));
				$item['sharetotal']=!empty($item['sharetotal'])?$item['sharetotal']:0; 
				
                $item['config']=@unserialize($item['config']); 
				$item['qrcode']=$_W['siteroot']."app/".$this->createMobileUrl('Qrcodeurl')."&url=".urlencode($_W['siteroot'].'app/'.$this->createMobileUrl('index',array('rid'=>$item['rid'])));
				
          
		  }
			
         }

		include $this->template('new_manage');
		
		