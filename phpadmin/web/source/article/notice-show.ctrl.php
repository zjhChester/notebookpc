<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');
load()->model('article');
load()->model('user');

$dos = array( 'detail', 'list', 'like_comment', 'more_comments');
$do = in_array($do, $dos) ? $do : 'list';

if($do == 'detail') {
	$id = intval($_GPC['id']);
	$notice = article_notice_info($id);
	if(is_error($notice)) {
		itoast('公告不存在或已删除', referer(), 'error');
	}
	$comment_status = setting_load('notice_comment_status');
	$comment_status = empty($comment_status['notice_comment_status']) ? 0 : 1;

	if (checksubmit('submit')) {
		$comment_table = table('article_comment');
		if (empty($comment_status)) {
			itoast('未开启评论功能！', referer(), 'error');
		}
		$content = safe_gpc_string($_GPC['content']);
		if (empty($content)) {
			itoast('评论内容不能为空！', referer(), 'error');
		}
		$result = $comment_table->addComment(array(
			'articleid' => $id,
			'content' => $content,
			'uid' => $_W['uid'],
		));
		itoast($result ? '评论成功' : '评论失败', url('article/notice-show/detail', array('id' => $id, 'page' => 1)), $result ? 'success' : 'error');
	}

	$_W['page']['title'] = $notice['title'] . '-公告列表';
	
	pdo_update('article_notice', array('click +=' => 1), array('id' => $id));

	if(!empty($_W['uid'])) {
		pdo_update('article_unread_notice', array('is_new' => 0), array('notice_id' => $id, 'uid' => $_W['uid']));
	}
	$title = $notice['title'];
}

if ($do == 'more_comments') {
	$order = empty($_GPC['order']) || $_GPC['order'] == 'id' ? 'id' : 'like_num';
	$pageindex = max(1, intval($_GPC['page']));
	$pagesize = 15;
	$comment_table = table('article_comment');
	$comment_list = $comment_table->getComments(intval($_GPC['id']), $pageindex, $pagesize, $order);
	$comment_list['list'] = empty($comment_list['list']) ? array() : array_values($comment_list['list']);
	$comment_list['pager'] = pagination($comment_list['total'], $pageindex, $pagesize, '', array('ajaxcallback' => true, 'callbackfuncname' => 'changePage'));
	iajax(0, $comment_list);
}

if ($do == 'like_comment') {
	$articleid = intval($_GPC['articleid']);
	$comment_id = intval($_GPC['id']);
	$article_comment_table = table('article_comment');

	$comment = $article_comment_table->getById($comment_id);
	if (empty($comment)) {
		iajax(1, '评论不存在');
	}
	if ($article_comment_table->hasLiked($articleid, $comment_id)) {
		iajax(1, '已赞');
	}

	if ($article_comment_table->likeComment($_W['uid'], $articleid, $comment_id)) {
		iajax(0);
	} else {
		iajax(1, '操作失败，请重试。');
	}
}

if($do == 'list') {
	$_W['page']['title'] = '-新闻列表';
	$categroys = article_categorys('notice');
	$categroys[0] = array('title' => '所有公告');

	$cateid = intval($_GPC['cateid']);
	$_W['page']['title'] = $categroys[$cateid]['title'] . '-公告列表';

	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;
	$filter = array('cateid' => $cateid);
	$notices = article_notice_all($filter, $pindex, $psize);
	$total = intval($notices['total']);
	$data = $notices['notice'];
	$pager = pagination($total, $pindex, $psize);
}

template('article/notice-show');