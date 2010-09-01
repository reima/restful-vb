<?php
chdir('..');
require_once('./global.php');
require_once(DIR . '/includes/functions.php');

function str($str, $amp = false) {
  global $vbulletin;

  if ($amp)
    $str = str_replace('&amp;', '&', $str);

  $charset = vB_Template_Runtime::fetchStyleVar('charset');
  if ($charset == '') $charset ='ISO-8859-1';
  return to_utf8($str, $charset);
}

function fetch_forum($forumid) {
  $foruminfo = fetch_foruminfo($forumid);
  if (!$foruminfo)
    return false;

  return array(
    'id' => $foruminfo['forumid'],
    'title' => str($foruminfo['title'], true),
    'description' => str($foruminfo['description'], true),
  );
}

function can_view_forum($forumid) {
  global $vbulletin;

  $forumperms = fetch_permissions($forumid);
  return ($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']);
}

// @see construct_subforum_bit
function fetch_subforum_list($parentid = -1) {
  global $vbulletin;
  cache_ordered_forums(0, 1);

  $result = array();

  if (!isset($vbulletin->iforumcache["$parentid"]))
    return $result;

  foreach ($vbulletin->iforumcache["$parentid"] as $forumid) {
    $forum = $vbulletin->forumcache["$forumid"];
    $forumperms = $vbulletin->userinfo['forumpermissions']["$forumid"];
    if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) AND
         ($vbulletin->forumcache["$forumid"]['showprivate'] == 1 OR
           (!$vbulletin->forumcache["$forumid"]['showprivate'] AND
            !$vbulletin->options['showprivateforums'])
         )
       ) {
      // no permission to view current forum
      continue;
    }

    if (!$forum['displayorder'] OR
        !($forum['options'] & $vbulletin->bf_misc_forumoptions['active'])) {
      // forum not active
      continue;
    }

    // get on/off status
    //$lastpostinfo = $vbulletin->forumcache["$lastpostarray[$forumid]"];
    //$forum['statusicon'] = fetch_forum_lightbulb($forumid, $lastpostinfo, $forum);
    //$show['newposticon'] = ($forum['statusicon'] ? true : false);
    $result[] = array(
      'id' => $forum['forumid'],
      'title' => str($forum['title'], true),
      'description' => str($forum['description'], true),
    );
  }

  return $result;
}

function fetch_threads($forumid, $perpage = 10, $page = 1) {
  global $db;

  if ($page < 1) $page = 1;
  $offset = ($page - 1) * $perpage;

  $allthreads = array();

  // Show sticky threads only on first page
  if ($page == 1) {
    $stickies = $db->query_read_slave("
      SELECT t.threadid, t.title, t.replycount
      FROM " . TABLE_PREFIX . "thread t
      WHERE t.forumid = $forumid
        AND t.visible = 1
        AND t.sticky = 1
        AND t.open <> 10
      ORDER BY t.lastpost DESC
    ");
    while ($sticky = $db->fetch_array($stickies)) {
      $sticky['sticky'] = true;
      $allthreads[] = $sticky;
    }
  }

  $threads = $db->query_read_slave("
    SELECT t.threadid, t.title, t.replycount
    FROM " . TABLE_PREFIX . "thread t
    WHERE t.forumid = $forumid
      AND t.visible = 1
      AND t.sticky <> 1
      AND t.open <> 10
    ORDER BY t.lastpost DESC
    LIMIT $offset, $perpage
  ");

  $result = array();
  while ($thread = $db->fetch_array($threads)) {
    $allthreads[] = $thread;
  }

  foreach ($allthreads as $thread) {
     $result[] = array(
      'id' => intval($thread['threadid']),
      'title' => str($thread['title'], true),
      'replycount' => intval($thread['replycount']),
      'sticky' => $thread['sticky'] ? true : false,
    );
  }

  return $result;
}

?>