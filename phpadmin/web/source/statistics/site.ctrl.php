<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('statistics');

$dos = array('current_account', 'all_account', 'get_account_api');
$do = in_array($do, $dos) ? $do : 'current_account';
$support_type = array(
	'time' => array('today', 'week', 'month', 'daterange'),
);

if ($do == 'current_account') {
	$today = stat_visit_all_bydate('today');
	$today = !empty($today) ? current($today) : 0;
	$yesterday = stat_visit_all_bydate('yesterday');
	$yesterday = !empty($yesterday) ? current($yesterday) : 0;
	template('statistics/site-current-account');
}

if ($do == 'get_account_api') {
	$data = array();
	$time_type = trim($_GPC['time_type']);
	if (!in_array($time_type, $support_type['time'])) {
		iajax(-1, '参数错误！');
	}
	$daterange = array();
	if (!empty($_GPC['daterange'])) {
		$daterange = array(
			'start' => date('Ymd', strtotime($_GPC['daterange']['startDate'])),
			'end' => date('Ymd', strtotime($_GPC['daterange']['endDate'])),
		);
	}
	$result = stat_visit_all_bydate($time_type, $daterange);
	if ($time_type == 'today') {
		$data_x = array(date('Ymd'));
	}
	if ($time_type == 'week') {
		$data_x = stat_date_range(date('Ymd', strtotime('-7 days')), date('Ymd'));
	}
	if ($time_type == 'month') {
		$data_x = stat_date_range(date('Ymd', strtotime('-30 days')), date('Ymd'));
	}
	if ($time_type == 'daterange') {
		$data_x = stat_date_range($daterange['start'], $daterange['end']);
	}
	if (empty($result)) {
		foreach ($data_x as $val) {
			$data_y[] = 0;
		}
		iajax(0, array('data_x' => $data_x, 'data_y' => $data_y));
	}
	foreach ($data_x as $key => $data) {
		foreach ($result as $date => $val) {
			if (strtotime($date) != strtotime($data)) {
				continue;
			}
			$data_y[$key] = $val;
		}
		if (empty($data_y[$key])) {
			$data_y[$key] = 0;
		}
	}
	iajax(0, array('data_x' => $data_x, 'data_y' => $data_y));
}