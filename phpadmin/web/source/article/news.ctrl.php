<?php
/**
 * [WeEngine System] Copyright (c) 2014 WE7.CC
 * WeEngine is NOT a free software, it under the license terms, visited http://www.we7.cc/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

$dos = array('category_post', 'category', 'category_del', 'list', 'post', 'batch_post', 'del', 'displaysetting');
$do = in_array($do, $dos) ? $do : 'list';
permission_check_account_user('system_article_news');

if ($do == 'category_post') {
	$_W['page']['title'] = '编辑分类-新闻分类-文章管理';
	if (checksubmit('submit')) {
		$i = 0;
		if (!empty($_GPC['title'])) {
			foreach ($_GPC['title'] as $k => $v) {
				$title = safe_gpc_string($v);
				if (empty($title)) {
					continue;
				}
				$data = array(
					'title' => $title,
					'displayorder' => intval($_GPC['displayorder'][$k]),
					'type' => 'news',
				);
				pdo_insert('article_category', $data);
				$i++;
			}
		}
		itoast('添加分类成功', url('article/news/category'), 'success');
	}
	template('article/news-category-post');
}

if ($do == 'category') {
	$_W['page']['title'] = '分类列表-新闻分类-文章管理';
	if (checksubmit('submit')) {
		if (!empty($_GPC['ids'])) {
			foreach ($_GPC['ids'] as $k => $v) {
				$data = array(
					'title' => safe_gpc_string($_GPC['title'][$k]),
					'displayorder' => intval($_GPC['displayorder'][$k])
				);
				pdo_update('article_category', $data, array('id' => intval($v)));
			}
			itoast('修改分类成功', referer(), 'success');
		}
	}

	$data = table('articlecategory')->getNewsCategoryLists();
	template('article/news-category');
}

if ($do == 'category_del') {
	$id = intval($_GPC['id']);
	pdo_delete('article_category', array('id' => $id, 'type' => 'news'));
	pdo_delete('article_news', array('cateid' => $id));
	itoast('删除分类成功', referer(), 'success');
}

if ($do == 'post') {
	$_W['page']['title'] = '编辑新闻-新闻列表';
	$id = intval($_GPC['id']);
	$new = table('articlenews')->searchWithId($id)->get();
	if (empty($new)) {
		$new = array(
			'is_display' => 1,
			'is_show_home' => 1,
		);
	}
	if (checksubmit()) {
		$title = trim($_GPC['title']) ? trim($_GPC['title']) : itoast('新闻标题不能为空', '', 'error');
		$cateid = intval($_GPC['cateid']) ? intval($_GPC['cateid']) : itoast('新闻分类不能为空', '', 'error');
		$content = trim($_GPC['content']) ? trim($_GPC['content']) : itoast('新闻内容不能为空', '', 'error');
		$data = array(
			'title' => $title,
			'cateid' => $cateid,
			'content' => safe_gpc_html(htmlspecialchars_decode($content)),
			'source' => safe_gpc_string($_GPC['source']),
			'author' => safe_gpc_string($_GPC['author']),
			'displayorder' => intval($_GPC['displayorder']),
			'click' => intval($_GPC['click']),
			'is_display' => intval($_GPC['is_display']),
			'is_show_home' => intval($_GPC['is_show_home']),
			'createtime' => TIMESTAMP,
		);
		if (!empty($_GPC['thumb'])) {
			$data['thumb'] = $_GPC['thumb'];
		} elseif (!empty($_GPC['autolitpic'])) {
			$match = array();
			preg_match('/attachment\/(.*?)(\.gif|\.jpg|\.png|\.bmp)/', $data['content'], $match);
			if (!empty($match[1])) {
				$data['thumb'] = $match[1].$match[2];
			}
		} else {
			$data['thumb'] = '';
		}

		if (!empty($new['id'])) {
			pdo_update('article_news', $data, array('id' => $id));
		} else {
			pdo_insert('article_news', $data);
		}
		itoast('编辑文章成功', url('article/news/list'), 'success');
	}

	$categorys = table('articlecategory')->getNewsCategoryLists();
	template('article/news-post');
}

if ($do == 'list') {
	$_W['page']['title'] = '所有新闻-新闻列表';

	$pindex = max(1, intval($_GPC['page']));
	$psize = 20;

	$article_table = table('articlenews');
	$cateid = intval($_GPC['cateid']);
	$createtime = intval($_GPC['createtime']);
	$title = safe_gpc_string($_GPC['title']);

	if (!empty($cateid)) {
		$article_table->searchWithCateid($cateid);
	}

	if (!empty($createtime)) {
		$article_table->searchWithCreatetimeRange($createtime);
	}

	if (!empty($title)) {
		$article_table->searchWithTitle($title);
	}

	$order = !empty($_W['setting']['news_display']) ? $_W['setting']['news_display'] : 'displayorder';

	$article_table->searchWithPage($pindex, $psize);
	$news = $article_table->getArticleNewsLists($order);
	$total = $article_table->getLastQueryTotal();
	$pager = pagination($total, $pindex, $psize);

	$categorys = table('articlecategory')->getNewsCategoryLists($order);
	template('article/news');
}

if ($do == 'batch_post') {
	if (checksubmit()) {
		if (!empty($_GPC['ids'])) {
			foreach ($_GPC['ids'] as $k => $v) {
				$data = array(
					'title' => trim($_GPC['title'][$k]),
					'displayorder' => intval($_GPC['displayorder'][$k]),
					'click' => intval($_GPC['click'][$k]),
				);
				pdo_update('article_news', $data, array('id' => intval($v)));
			}
			itoast('编辑新闻列表成功', referer(), 'success');
		}
	}
}

if ($do == 'del') {
	$id = intval($_GPC['id']);
	pdo_delete('article_news', array('id' => $id));
	itoast('删除文章成功', referer(), 'success');
}

if ($do == 'displaysetting') {
	$setting = safe_gpc_string($_GPC['setting']);
	$data = $setting == 'createtime' ? 'createtime' : 'displayorder';
	setting_save($data, 'news_display');
	itoast('更改成功！', referer(), 'success');
}
