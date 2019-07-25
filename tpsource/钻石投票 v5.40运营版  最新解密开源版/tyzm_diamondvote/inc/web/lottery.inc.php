<?php
/**
 * 用户管理
 *
 * @author 羊子
 * @url http://tyzm.net/
 */
defined('IN_IA') or exit('Access Denied');
       
        //分页start
        global $_W, $_GPC;
        $rid       = $_GPC['rid'];
        $pindex    = max(1, intval($_GPC['page']));
        $psize     = 20;
        $condition = '';
        $uniacid   = $_W['uniacid'];
        if (!empty($_GPC['keyword'])) {
            $condition .= " AND CONCAT(`id`,`nickname`,`user_ip`) LIKE '%{$_GPC['keyword']}%'";
        }
		
        $list         = pdo_fetchall("SELECT * FROM " . tablename($this->tableredpack) . " WHERE uniacid = " . $uniacid . " AND rid=" . $rid . "   $condition ORDER BY id DESC LIMIT " . ($pindex - 1) * $psize . ',' . $psize);
        $total        = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename($this->tableredpack) . " WHERE uniacid = " . $uniacid . " AND rid=" . $rid . "    $condition");
        $pager        = pagination($total, $pindex, $psize);
        $redpacktotal = pdo_fetchcolumn('SELECT count(total_amount) FROM ' . tablename($this->tableredpack) . " WHERE uniacid = " . $uniacid . " AND result_code='SUCCESS' AND rid=" . $rid . "    $condition");
        
include $this->template('lottery');
 
    