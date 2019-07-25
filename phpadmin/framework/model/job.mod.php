<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');



function job_list($uid, $isfounder = false) {
	$table = table('job')->where('isdeleted',0);
	if (!$isfounder) {
				$table->where('uid', $uid);
	}
	return $table->getall('id');
}



function job_single($id) {
	return table('job')->getById($id);
}



function job_create_delete_account($uniacid, $accountName, $uid) {
	global $_W;
	
	$job = table('job');
	$core_count = table('attachment')->where('uniacid', $uniacid)->count();
	$wechat_count = table('attachment')->local(false)->where('uniacid', $uniacid)->count();
	$total = $core_count + intval($wechat_count);
	return $job->createDeleteAccountJob($uniacid, $accountName, $total, $uid);
}

function job_execute($id) {
	$job = job_single($id);

	$type = $job['type'];
	if (intval($job['status']) == 1) {
		return error(1, '任务已结束');
	}
	$result = null;
	switch ($type) {
		case $type : $result = job_execute_delete_account($job); break;
	}
	return $result;
}


function job_execute_delete_account($job) {

	$uniacid = $job['uniacid'];
		$core_attchments = table('attachment')->where('uniacid', $uniacid)
		->searchWithPage(1, 10)->getall('id');

	array_walk($core_attchments, function($item) {
		$path = $item['attachment'];
		file_delete($path);
	});

	$wechat_attachments = table('attachment')->local(false)->where('uniacid', $uniacid)
		->searchWithPage(1, 10)->getall('id');
	array_walk($wechat_attachments, function($item) {
		$path = $item['attachment'];
		file_delete($path);
	});

		if (count($core_attchments) == 0 && count($wechat_attachments) == 0) {
		table('attachmentgroup')->deleteByUniacid($uniacid);
		$upjob = table('job')->where('id', $job['id']);
		$upjob->fill('status', 1);		$upjob->fill('endtime', TIMESTAMP);		$upjob->save();
		return error(0,  array('finished'=>1, 'progress'=>100, 'id'=>$job['id'], 'endtime'=>time()));
	}


		$core_ids = array_keys($core_attchments);
	$wechat_ids = array_keys($wechat_attachments);

	if (count($core_ids) > 0) {
		table('attachment')->deleteById($core_ids);
	}
	if (count($wechat_ids) > 0) {
		table('attachment')->local(false)->deleteById($wechat_ids);
	}

	$handled = count($core_ids) + count($wechat_ids);
	$all_handled = intval($job['handled']) + $handled;
	$total = intval($job['total']);
	table('job')->where('id', $job['id'])->fill('handled', $all_handled)
		->fill('updatetime',TIMESTAMP)->save();
	return error(0, array('finished'=>0, 'progress'=>intval($all_handled/$total*100), 'id'=>$job['id']));

}


function job_clear($uid, $isfounder = false) {
	return table('job')->clear($uid, $isfounder);
}
