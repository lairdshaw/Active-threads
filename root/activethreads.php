<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 * If not, see <http://www.gnu.org/licenses/>.
 */

define('IN_MYBB', 1);
require_once './global.php';

define('THIS_SCRIPT', 'activethreads.php');
define('ACT_ITEMS_PER_PAGE', 20);

if (!isset($lang->activethreads)) {
	$lang->load('activethreads');
}

function act_make_url($days, $hours, $mins, $date, $sort, $order, $page, $encode = true) {
	$ret = "activethreads.php?days=$days&hours=$hours&mins=$mins&date=".urlencode($date)."&sort=$sort&order=$order&page=$page";
	if ($encode) {
		$ret = htmlspecialchars_uni($ret);
	}

	return $ret;
}

if (!is_array($plugins_cache)) {
	$plugins_cache = $cache->read('plugins');
}
$active_plugins = $plugins_cache['active'];

if ($active_plugins && $active_plugins['activethreads']) {
	// The below conditional and the code included in its remit has been adapted and largely copied wholesale from misc.php.
	// The main addition is that it requires a date range to be stipulated for the posts to be considered from the thread.
	if ($mybb->input['action'] == 'whoposted') {
		$lang->load('misc');
		$numposts = 0;
		$altbg = alt_trow();
		$whoposted = '';
		$tid = $mybb->get_input('tid', MyBB::INPUT_INT);
		$thread = get_thread($tid);
		$modal = $mybb->get_input('modal', MyBB::INPUT_INT);
		$min_dateline = $mybb->get_input('min_dateline', MyBB::INPUT_INT);
		$max_dateline = $mybb->get_input('max_dateline', MyBB::INPUT_INT);

		// Make sure we are looking at a real thread here.
		if (!$thread) {
			error($lang->error_invalidthread);
		}

		// Make sure we are looking at a real thread here.
		if (($thread['visible'] == -1 && !is_moderator($thread['fid'], 'canviewdeleted'))
		    ||
		    ($thread['visible'] == 0 && !is_moderator($thread['fid'], "canviewunapprove"))
		    ||
		    $thread['visible'] > 1
		) {
			error($lang->error_invalidthread);
		}

		if (is_moderator($thread['fid'], 'canviewdeleted') || is_moderator($thread['fid'], 'canviewunapprove')) {
			if (is_moderator($thread['fid'], 'canviewunapprove') && !is_moderator($thread['fid'], 'canviewdeleted')) {
				$show_posts = 'p.visible IN (0,1)';
			} else if(is_moderator($thread['fid'], 'canviewdeleted') && !is_moderator($thread['fid'], 'canviewunapprove')) {
				$show_posts = 'p.visible IN (-1,1)';
			} else {
				$show_posts = 'p.visible IN (-1,0,1)';
			}
		} else	$show_posts = 'p.visible = 1';

		// Does the thread belong to a valid forum?
		$forum = get_forum($thread['fid']);
		if (!$forum || $forum['type'] != 'f') {
			error($lang->error_invalidforum);
		}

		// Does the user have permission to view this thread?
		$forumpermissions = forum_permissions($forum['fid']);

		if ($forumpermissions['canview'] == 0
		    ||
		    $forumpermissions['canviewthreads'] == 0
		    ||
		    (isset($forumpermissions['canonlyviewownthreads']) && $forumpermissions['canonlyviewownthreads'] != 0 && $thread['uid'] != $mybb->user['uid'])
		) {
			error_no_permission();
		}

		// Check if this forum is password protected and we have a valid password
		check_forum_password($forum['fid']);

		if ($mybb->get_input('sort') != 'username') {
			$sortsql = ' ORDER BY posts DESC';
		} else	$sortsql = ' ORDER BY p.username ASC';
		$whoposted = '';
		$query = $db->query("
			SELECT COUNT(p.pid) AS posts, p.username AS postusername, u.uid, u.username, u.usergroup, u.displaygroup
			FROM ".TABLE_PREFIX."posts p
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
			WHERE tid='".$tid."' AND $show_posts AND p.dateline >= '$min_dateline' AND p.dateline <= '$max_dateline'
			GROUP BY u.uid, p.username, u.uid, u.username, u.usergroup, u.displaygroup
			".$sortsql);
		while ($poster = $db->fetch_array($query)) {
			if ($poster['username'] == '') {
				$poster['username'] = $poster['postusername'];
			}
			$poster['username'] = htmlspecialchars_uni($poster['username']);
			$poster['postusername'] = htmlspecialchars_uni($poster['postusername']);
			$poster_name = format_name($poster['username'], $poster['usergroup'], $poster['displaygroup']);
			if ($modal) {
				$onclick = '';
				if ($poster['uid']) {
					$onclick = "location.href='".get_profile_link($poster['uid'])."'; return false;";
				}
				$profile_link = build_profile_link($poster_name, $poster['uid'], '_blank', $onclick);
			} else {
				$profile_link = build_profile_link($poster_name, $poster['uid']);
			}
			$numposts += $poster['posts'];
			eval('$whoposted .= "'.$templates->get('misc_whoposted_poster').'";');
			$altbg = alt_trow();
		}
		$numposts = my_number_format($numposts);
		$poster['posts'] = my_number_format($poster['posts']);
		if ($modal) {
			eval('$whop = "'.$templates->get('activethreads_whoposted', 1, 0).'";');
			echo $whop;
		} else {
			require_once MYBB_ROOT."inc/class_parser.php";
			$parser = new postParser;

			// Get thread prefix
			$breadcrumbprefix = '';
			$threadprefix = array('prefix' => '');
			if ($thread['prefix']) {
				$threadprefix = build_prefixes($thread['prefix']);
				if (!empty($threadprefix['displaystyle'])) {
					$breadcrumbprefix = $threadprefix['displaystyle'].'&nbsp;';
				}
			}

			$thread['subject'] = htmlspecialchars_uni($parser->parse_badwords($thread['subject']));

			// Build the navigation.
			build_forum_breadcrumb($forum['fid']);
			add_breadcrumb($breadcrumbprefix.$thread['subject'], get_thread_link($thread['tid']));
			add_breadcrumb($lang->who_posted);

			eval('$whoposted = "'.$templates->get('activethreads_whoposted_page').'";');
			output_page($whoposted);
		}
		exit;
	}

	if ($mybb->settings[C_ACT.'_per_usergroup'] == '1') {
		// $mybb->usergroup['act_max_interval_in_mins'] gives us the maximum value of this
		// setting of all of the usergroups to which the user belongs, but, semantically,
		// for this setting, 0 is maximum, so we need to check manually whether any of the
		// user's usergroups have this setting set to its maximum of 0.
		$groupscache = $cache->read('usergroups');
		$unlimited = false;
		foreach (explode(',', $mybb->usergroup['all_usergroups']) as $gid) {
			if ($groupscache[$gid]['act_max_interval_in_mins'] == 0) {
				$unlimited = true;
				break;
			}
		}
		$max_interval = $unlimited ? 0 : $mybb->usergroup['act_max_interval_in_mins'];
	} else	$max_interval = $mybb->settings[C_ACT.'_max_interval_in_mins'];

	$days = $mybb->get_input('days', MyBB::INPUT_INT);
	$hours = $mybb->get_input('hours', MyBB::INPUT_INT);
	$mins = $mybb->get_input('mins', MyBB::INPUT_INT);
	$date = $mybb->get_input('date');
	$page = $mybb->get_input('page', MyBB::INPUT_INT);
	$sort = $mybb->get_input('sort');
	if (!$sort) {
		$sort = $mybb->settings[C_ACT.'_default_sort_field'];
	}
	$order = $mybb->get_input('order');
	if (!$order) {
		$order = $mybb->settings[C_ACT.'_default_sort_direction'];
	}

	switch ($sort) {
	case 'min_dateline':
	case 'max_dateline':
	case 'thread_subject':
	case 'thread_username':
	case 'thread_dateline':
	case 'forum_name':
	case 'min_username':
	case 'max_username':
		break;
	default:
		$sort = 'num_posts';
	}

	if ($order != 'ascending') {
		$order = 'descending';
	}

	$using_defaults = false;
	if ($days == 0 && $hours == 0 && $mins == 0) {
		$days = $mybb->settings[C_ACT.'_default_days'];
		$hours = $mybb->settings[C_ACT.'_default_hours'];
		$mins = $mybb->settings[C_ACT.'_default_mins'];
		$using_defaults = true;
	}

	if (!$page) $page = 1;

	$mins_before = $days;
	$mins_before = $mins_before * 24 + $hours;
	$mins_before = $mins_before * 60 + $mins;

	if ($max_interval > 0 && $mins_before > $max_interval) {
		if ($using_defaults) {
			$mins_before = $max_interval;
			$mins = $mins_before % 60;
			$hours1 = ($mins_before - $mins) / 60;
			$hours = $hours1 % 24;
			$days = ($hours1 - $hours) / 24;
		} else	error($lang->sprintf($lang->act_err_excess_int, my_number_format($mins_before), my_number_format($max_interval)));
	}
	$secs_before = $mins_before * 60;

	if ($date) {
		if ($mybb->user['dst'] == 1) {
			$timezone = (float)$mybb->user['timezone']+1;
		} else	$timezone = (float)$mybb->user['timezone'];
		$tz_org = date_default_timezone_get();
		date_default_timezone_set('GMT');
		$ts_epoch = strtotime($date, TIME_NOW + $timezone*60*60) - $timezone*60*60;
		$date_for_title = $date;
		date_default_timezone_set($tz_org);
	} else {
		$ts_epoch = TIME_NOW;
		$date = 'Now';
		$date_for_title = $lang->act_now;
	}
	$date_prior = my_date('normal', $ts_epoch - $secs_before);

	$conds = 'p.dateline >= '.($ts_epoch - $secs_before).' AND p.dateline <= '.$ts_epoch;

	// Make sure the user only sees threads and (counts of) posts s/he is allowed to see.
	$fids = get_unviewable_forums(true);
	if ($inact_fids = get_inactive_forums()) {
		if ($fids) $fids .= ',';
		$fids .= $inact_fids;
	}
	if ($fids) {
		$conds = '('.$conds.') AND f.fid NOT IN ('.$fids.')';
	}
	$onlyusfids = array();
	$group_permissions = forum_permissions();
	foreach ($group_permissions as $fid => $forum_permissions) {
		if ($forum_permissions['canonlyviewownthreads'] == 1) {
			$onlyusfids[] = $fid;
		}
	}
	if ($onlyusfids) {
		$conds .= '('.$conds.' AND ((t.fid IN('.implode(',', $onlyusfids).') AND t.uid="'.$mybb->user['uid'].'") OR t.fid NOT IN('.implode(',', $onlyusfids).')))';
	}
	$conds = '('.$conds.') AND p.visible > 0';

	$inner_sql = "
 SELECT     t.tid,
            t.subject AS thread_subject,
            t.dateline AS thread_dateline,
            uthr.uid AS thread_uid,
            uthr.username AS thread_username,
            uthr.usergroup AS thread_usergroup,
            t.closed AS thread_closed,
            t.lastpost AS thread_lastpost,
            t.replies AS thread_replies,
            t.views AS thread_views,
            t.icon AS thread_icon,
            t.poll AS thread_poll,
            t.prefix AS thread_prefix,
            f.parentlist,
            f.fid,
            f.name AS forum_name,
            count(p.pid) AS num_posts,
            MIN(p.pid) AS min_pid,
            MAX(p.pid) AS max_pid
 FROM       ".TABLE_PREFIX."threads t
 INNER JOIN ".TABLE_PREFIX."posts p  ON t.tid = p.tid
 INNER JOIN ".TABLE_PREFIX."forums f ON f.fid = t.fid
 INNER JOIN ".TABLE_PREFIX."users uthr ON uthr.uid = t.uid
 WHERE      $conds
 GROUP BY   p.tid";
	$res = $db->query("SELECT count(*) AS cnt FROM ($inner_sql) AS mainq");
	$rows = $db->fetch_array($res);
	$tot_rows = $rows['cnt'];

	$order_by = $sort.' '.($order == 'descending' ? 'DESC' : 'ASC');

	$sql = "
SELECT mainq.tid,
       mainq.thread_subject,
       mainq.thread_dateline,
       mainq.thread_uid,
       mainq.thread_usergroup,
       mainq.thread_username,
       mainq.thread_lastpost,
       mainq.thread_replies,
       mainq.thread_views,
       mainq.thread_closed,
       mainq.thread_icon,
       mainq.thread_poll,
       mainq.thread_prefix,
       mainq.parentlist,
       mainq.fid,
       mainq.forum_name,
       mainq.num_posts,
       mainq.min_pid,
       pmin.uid AS min_uid,
       pmin.subject AS min_subject,
       pmin.dateline AS min_dateline,
       umin.username AS min_username,
       umin.usergroup AS min_usergroup,
       mainq.max_pid,
       pmax.uid AS max_uid,
       pmax.subject AS max_subject,
       pmax.dateline AS max_dateline,
       umax.username AS max_username,
       umax.usergroup AS max_usergroup
FROM
($inner_sql) AS mainq
INNER JOIN ".TABLE_PREFIX."posts pmax ON mainq.max_pid = pmax.pid
INNER JOIN ".TABLE_PREFIX."users umax ON umax.uid      = pmax.uid
INNER JOIN ".TABLE_PREFIX."posts pmin ON mainq.min_pid = pmin.pid
INNER JOIN ".TABLE_PREFIX."users umin ON umin.uid      = pmin.uid
ORDER BY   $order_by
LIMIT ".(($page-1) * ACT_ITEMS_PER_PAGE).", ".ACT_ITEMS_PER_PAGE;

	$res = $db->query($sql);
	$rows = $fids = $forum_names = $tids = $moved_threads = array();
	
	$result_rows = '';
	while (($row = $db->fetch_array($res))) {
		$fids[] = $row['fid'];
		$forum_names[$row['fid']] = $row['forum_name'];
		foreach (explode(',', $row['parentlist']) as $fid) {
			if (empty($forum_names[$fid])) {
				$forum_names[$fid] = null;
			}
		}

		// The logic below has been taken from inc/forumdisplay.php

		// If this is a moved thread, then set the tid for participation marking and thread read marking to that of the moved thread
		if (substr($row['closed'], 0, 5) == 'moved') {
			$tid = substr($row['closed'], 6);
			if (!isset($tids[$tid])) {
				$moved_threads[$tid] = $row['tid'];
				$tids[$row['tid']] = $tid;
			}
		} else {
			// Otherwise, set it to the plain thread ID
			$tids[$row['tid']] = $row['tid'];
			if (isset($moved_threads[$row['tid']])) {
				unset($moved_threads[$row['tid']]);
			}
		}

		$rows[$row['tid']] = $row;
	}

	// This check for participation by the current user in any of these threads - for 'dot' folder icons - has also been adapted from inc/forumdisplay.php
	if (!empty($tids)) {
		$tids = implode(',', $tids);
	}
	if ($mybb->settings['dotfolders'] != 0 && $mybb->user['uid'] && !empty($rows)) {
		$res = $db->simple_select('posts', "DISTINCT tid,uid", "uid='{$mybb->user['uid']}' AND tid IN ({$tids}) AND visible > 0");
		while ($row = $db->fetch_array($res)) {
			if (!empty($moved_threads[$row['tid']])) {
				$row['tid'] = $moved_threads[$row['tid']];
			}
			if ($rows[$row['tid']]) {
				$rows[$row['tid']]['doticon'] = 1;
			}
		}
	}

	// This logic for determining each thread's last read date by the user has also been taken from inc/forumdisplay.php
	if ($mybb->user['uid'] && $mybb->settings['threadreadcut'] > 0 && !empty($rows)) {
		$res = $db->simple_select('threadsread', '*', "uid='{$mybb->user['uid']}' AND tid IN ({$tids})");
		while ($row = $db->fetch_array($res)) {
			if (!empty($moved_threads[$row['tid']])) {
				$row['tid'] = $moved_threads[$row['tid']];
			}
			if($rows[$row['tid']]) {
				$rows[$row['tid']]['lastread'] = $row['dateline'];
			}
		}
	}

	// This logic for determining each forum's read date cutoff has been adapted from inc/forumdisplay.php
	$forum_reads = array();
	$read_cutoff = TIME_NOW - $mybb->settings['threadreadcut']*60*60*24;
	if ($fids && $mybb->settings['threadreadcut'] > 0 && $mybb->user['uid']) {
		$fids_list = implode(',', $fids);
		$res = $db->simple_select('forumsread', 'fid, dateline', "fid IN ({$fids_list}) AND uid='{$mybb->user['uid']}'");
		while ($row = $db->fetch_array($res)) {
			$forum_read = $row['dateline'];
			if ($forum_read == 0 || $forum_read < $read_cutoff) {
				$forum_read = $read_cutoff;
			}
			$forum_reads[$row['fid']] = $forum_read;
		}
	} else {
		$forum_read = my_get_array_cookie('forumread', $fid);

		if (isset($mybb->cookies['mybb']['readallforums']) && !$forum_read) {
			$forum_read = $mybb->cookies['mybb']['lastvisit'];
		}

		foreach ($fids as $fid) {
			$forum_reads[$fid] = $forum_read;
		}
	}

	$missing_fids = array();
	foreach ($forum_names as $fid => $name) {
		if (is_null($name)) {
			$missing_fids[] = $fid;
		}
	}
	if ($missing_fids) {
		$res = $db->simple_select('forums', 'fid,name', 'fid IN ('.implode(',', $missing_fids).')');
		while (($post = $db->fetch_array($res))) {
			$forum_names[$post['fid']] = $post['name'];
		}
	}

	$i = 1;
	$icon_cache = $cache->read('posticons');
	if ($rows) {
		foreach ($rows as $row) {
			$i = 1 - $i;
			$bgcolor = 'trow'.($i+1);
			$fid = $row['fid'];
			$tid = $row['tid'];
			$moved = explode('|', $row['closed']);
			$thread_url = get_thread_link($tid);
			$max_subj_chars = $mybb->settings[C_ACT.'_max_displayed_subject_chars'];
			if ($max_subj_chars > 0) {
				$ellipsis = my_strlen($row['thread_subject']) > $max_subj_chars ? '&hellip;' : '';
				$thread_subject = htmlspecialchars_uni(my_substr($row['thread_subject'], 0, $max_subj_chars)).$ellipsis;
			} else	$thread_subject = htmlspecialchars_uni($row['thread_subject']);
			eval('$thread_link = "'.$templates->get('activethreads_thread_link').'";');
			$threadauthor_username = $mybb->settings[C_ACT.'_format_threadauthor_username']
			                     ? format_name($row['thread_username'], $row['thread_usergroup'])
			                     : htmlspecialchars_uni($row['thread_username']);
			$threadauthor_username_url = get_profile_link($row['thread_uid']);
			eval('$threadauthor_link = "'.$templates->get('activethreads_threadauthor_username_link').'";');
			$thread_date = my_date('normal', $row['thread_dateline']);
			$num_posts_fmt = my_number_format($row['num_posts']);

			$forum_links = '';
			$fids = explode(',', $row['parentlist']);
			$fid_last = array_pop($fids);
			foreach ($fids as $fid2) {
				if ($forum_links) eval('$forum_links .= "'.$templates->get('activethreads_forum_separator').'";');
				$forum_url = get_forum_link($fid2);
				$forum_name = htmlspecialchars_uni($forum_names[$fid2]);
				eval('$forum_links .= "'.$templates->get('activethreads_forum_link').'";');
			}
			eval('$forum_links .= "'.$templates->get('activethreads_forum_separator_last').'";');
			$forum_url = get_forum_link($fid_last);
			$forum_name = htmlspecialchars_uni($forum_names[$fid_last]);
			eval('$forum_links .= "'.$templates->get('activethreads_forum_link').'";');

			$earliestpost_url = get_post_link($row['min_pid']).'#pid'.$row['min_pid'];
			$earliestpost_date = my_date('normal', $row['min_dateline']);
			eval('$earliestpost_date_link = "'.$templates->get('activethreads_earliestpost_date_link').'";');
			$earliestposter_username = $mybb->settings[C_ACT.'_format_earliestposter_username']
			                     ? format_name($row['min_username'], $row['min_usergroup'])
			                     : htmlspecialchars_uni($row['min_username']);
			$earliestposter_username_url = get_profile_link($row['min_uid']);
			eval('$earliestposter_username_link = "'.$templates->get('activethreads_earliestposter_username_link').'";');
			$latestpost_url = get_post_link($row['max_pid']).'#pid'.$row['max_pid'];
			$latestpost_date = my_date('normal', $row['max_dateline']);
			eval('$latestpost_date_link = "'.$templates->get('activethreads_latestpost_date_link').'";');
			$latestposter_username = $mybb->settings[C_ACT.'_format_latestposter_username']
			                     ? format_name($row['max_username'], $row['max_usergroup'])
			                     : htmlspecialchars_uni($row['max_username']);
			$latestposter_username_url = get_profile_link($row['max_uid']);
			eval('$latestposter_username_link = "'.$templates->get('activethreads_latestposter_username_link').'";');

			// The below logic for determining the folder image, icon and whether to show an "unread" arrow
			// has been adapted from code in inc/forumdisplay.php.
			$folder = '';
			$folder_label = '';
			$lang->load('forumdisplay');
			if (isset($row['doticon'])) {
				$folder = "dot_";
				$folder_label .= $lang->icon_dot;
			}
			$gotounread = '';
			if ($mybb->settings['threadreadcut'] > 0 && $mybb->user['uid'] && $row['thread_lastpost'] > $forum_reads[$fid]) {
				if (!empty($row['lastread'])) {
					$last_read = $row['lastread'];
				} else {
					$last_read = $read_cutoff;
				}
			} else {
				$last_read = my_get_array_cookie('threadread', $tid);
			}
			if ($forum_reads[$fid] > $last_read) {
				$last_read = $forum_reads[$fid];
			}
			if ($row['thread_lastpost'] > $last_read && $moved[0] != 'moved') {
				$folder .= 'new';
				$folder_label .= $lang->icon_new;
				$new_class = 'subject_new';
				$thread = array(
					'newpostlink' => get_thread_link($tid, 0, 'newpost')
				);
				eval('$gotounread = "'.$templates->get('forumdisplay_thread_gotounread').'";');
				$unreadpost = 1;
			} else {
				$folder_label .= $lang->icon_no_new;
				$new_class = 'subject_old';
			}
			if ($row['thread_replies'] >= $mybb->settings['hottopic'] || $row['thread_views'] >= $mybb->settings['hottopicviews']) {
				$folder .= 'hot';
				$folder_label .= $lang->icon_hot;
			}
			if ($row['closed'] == 1) {
				$folder .= 'close';
				$folder_label .= $lang->icon_close;
			}
			if ($moved[0] == 'moved') {
				$folder = 'move';
				$gotounread = '';
			}
			$folder .= 'folder';
			if ($row['thread_icon'] > 0 && $icon_cache[$row['thread_icon']]) {
				$icon = $icon_cache[$row['thread_icon']];
				$icon['path'] = str_replace("{theme}", $theme['imgdir'], $icon['path']);
				$icon['path'] = htmlspecialchars_uni($icon['path']);
				$icon['name'] = htmlspecialchars_uni($icon['name']);
				eval('$icon = "'.$templates->get('forumdisplay_thread_icon').'";');
			} else	$icon = "&nbsp;";
			$prefix = '';
			if ($row['thread_poll']) {
				$prefix = $lang->poll_prefix;
			}
			if ($moved[0] == "moved") {
				$prefix = $lang->moved_prefix;
			}
			// If this thread has a prefix, insert a space between prefix and subject
			$threadprefix_disp = $threadprefix = '';
			if ($row['thread_prefix'] != 0) {
				$threadprefix = build_prefixes($row['thread_prefix']);
				if (!empty($threadprefix)) {
					$threadprefix_disp = $threadprefix['displaystyle'].'&nbsp;';
				}
			}
			$size = 35;
			$avatar_margin = $size + 5;
			$dims = 'width="'.$size.'" height="'.$size.'"';
			if ($settings[C_ACT.'_display_thread_avatar'] != 0) {
				$margin_thread = $size + $avatar_margin;
				$memprofile = get_user($row['thread_uid']);
				$useravatar = format_avatar($memprofile['avatar']);
				$useravatar['width_height'] = $dims;
				eval('$threadauthor_avatar = "'.$templates->get('activethreads_threadauthor_avatar').'";');
				$margin_thread = $avatar_margin;
			} else {
				$threadauthor_avatar = '';
				$margin_thread = 0;
			}
			if ($settings[C_ACT.'_display_earliestpost_avatar'] != 0) {
				$memprofile = get_user($row['min_uid']);
				$useravatar = format_avatar($memprofile['avatar']);
				$useravatar['width_height'] = $dims;
				eval('$earliestpost_avatar = "'.$templates->get('activethreads_earliestposter_avatar').'";');
				$margin_earliest = $avatar_margin;
			} else {
				$earliestpost_avatar = '';
				$margin_earliest = 0;
			}
			if ($settings[C_ACT.'_display_latestpost_avatar'] != 0) {
				$memprofile = get_user($row['max_uid']);
				$useravatar = format_avatar($memprofile['avatar']);
				$useravatar['width_height'] = $dims;
				eval('$latestpost_avatar = "'.$templates->get('activethreads_latestposter_avatar').'";');
				$margin_latest = $avatar_margin;
			} else {
				$latestpost_avatar = '';
				$margin_latest = 0;
			}
			eval('$result_rows .= "'.$templates->get('activethreads_result_row', 1, 0).'";');
		}

		$sorter = ' [<a href="'.act_make_url($days, $hours, $mins, $date, $sort, ($order == 'ascending' ? 'descending' : 'ascending'), $page).'">'.($order == 'ascending' ? $lang->act_desc_for_sq_br : $lang->act_asc_for_sq_br).'</a>]';
		$num_posts_heading    = '<a href="'.act_make_url($days, $hours, $mins, $date, 'num_posts', 'descending', $page).'">'.$lang->act_num_posts.'</a>';
		$min_dateline_heading = '<a href="'.act_make_url($days, $hours, $mins, $date, 'min_dateline', 'descending', $page).'">'.$lang->act_earliest_posting.'</a>';
		$max_dateline_heading = '<a href="'.act_make_url($days, $hours, $mins, $date, 'max_dateline', 'descending', $page).'">'.$lang->act_latest_posting.'</a>';
		$thread_subject_heading = '<a href="'.act_make_url($days, $hours, $mins, $date, 'thread_subject', 'ascending', $page).'">'.$lang->act_thread.'</a>';
		$thread_username_heading = '<a href="'.act_make_url($days, $hours, $mins, $date, 'thread_username', 'ascending', $page).'">'.$lang->act_author.'</a>';
		$thread_dateline_heading = '<a href="'.act_make_url($days, $hours, $mins, $date, 'thread_dateline', 'descending', $page).'">'.$lang->act_start.'</a>';
		$forum_name_heading      = '<a href="'.act_make_url($days, $hours, $mins, $date, 'forum_name', 'ascending', $page).'">'.$lang->act_cont_forum.'</a>';
		$min_author_heading      = '<a href="'.act_make_url($days, $hours, $mins, $date, 'min_username', 'ascending', $page).'">'.$lang->act_author.'</a>';
		$max_author_heading      = '<a href="'.act_make_url($days, $hours, $mins, $date, 'max_username', 'ascending', $page).'">'.$lang->act_author.'</a>';
		switch ($sort) {
		case 'num_posts':
			$num_posts_heading    = $lang->act_num_posts.$sorter;
			break;
		case 'min_dateline':
			$min_dateline_heading = $lang->act_earliest_posting.$sorter;
			break;
		case 'max_dateline':
			$max_dateline_heading = $lang->act_latest_posting.$sorter;
			break;
		case 'thread_subject':
			$thread_subject_heading = $lang->act_thread.$sorter;
			break;
		case 'thread_username':
			$thread_username_heading = $lang->act_author.$sorter;
			break;
		case 'thread_dateline':
			$thread_dateline_heading = $lang->act_start.$sorter;
			break;
		case 'forum_name':
			$forum_name_heading = $lang->act_cont_forum.$sorter;
			break;
		case 'min_username':
			$min_author_heading = $lang->act_author.$sorter;
			break;
		case 'max_username':
			$max_author_heading = $lang->act_author.$sorter;
			break;
		}
		$lang_act_recent_threads_title = $lang->sprintf($lang->act_act_recent_threads_title, $days, $hours, $mins, $date_prior, $date_for_title);
		$asc_checked           = ($order == 'ascending'    ? ' checked="checked"'   : '');
		$desc_checked          = ($order == 'descending'   ? ' checked="checked"'   : '');
		$num_posts_selected    = ($sort  == 'num_posts'    ? ' selected="selected"' : '');
		$min_dateline_selected = ($sort  == 'min_dateline' ? ' selected="selected"' : '');
		$max_dateline_selected = ($sort  == 'max_dateline' ? ' selected="selected"' : '');
		$thread_subject_selected  = ($sort  == 'thread_subject'  ? ' selected="selected"' : '');
		$thread_username_selected = ($sort  == 'thread_username' ? ' selected="selected"' : '');
		$thread_dateline_selected = ($sort  == 'thread_dateline' ? ' selected="selected"' : '');
		$forum_name_selected      = ($sort  == 'forum_name'      ? ' selected="selected"' : '');
		$min_username_selected    = ($sort  == 'min_username'    ? ' selected="selected"' : '');
		$max_username_selected    = ($sort  == 'max_username'    ? ' selected="selected"' : '');
		eval('$results_html = "'.$templates->get('activethreads_results', 1, 0).'";');

	} else {
		$results_html = '<p style="text-align: center">'.$lang->act_no_results.'</p>';
	}
	$act_before_date_tooltip = htmlspecialchars_uni($lang->act_before_date_tooltip);
	$act_set_period_of_interest_tooltip = htmlspecialchars_uni($lang->act_set_period_of_interest_tooltip);
	$act_set_period_of_interest = htmlspecialchars_uni($lang->act_set_period_of_interest);
	$multipage = multipage($tot_rows, ACT_ITEMS_PER_PAGE, $page, act_make_url($days, $hours, $mins, $date, $sort, $order, '{page}'));
	add_breadcrumb($lang->act_act_recent_threads_breadcrumb, C_ACT.'.php');
	$post_code_string = "&amp;my_post_key={$mybb->post_code}";
	eval('$html = "'.$templates->get('activethreads_page', 1, 0).'";');
	output_page($html);
} else	echo $lang->act_inactive;
