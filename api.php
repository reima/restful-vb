<?php
require_once('./rest.php');
require_once('./functions.php');

class MobileAPI extends RestService {
  public function getSession($params) {
    return $this->encodeOutput(get_userinfo());
  }

  public function postSession($params) {
    $userinfo = login($params['username'], $params['password']);
    if ($userinfo === false)
      return $this->unauthorized();

    $this->status(201); // 201 Created
    return $this->encodeOutput($userinfo);
  }

  public function deleteSession($params) {
    return $this->encodeOutput(logout());
  }

  public function getForum($params) {
    $id = isset($params['id']) ? intval($params['id']) : -1;
    if (!isset($params['mode'])) $params['mode'] = 'all';

    $foruminfo = fetch_forum($id);
    if ($id != -1) {
      if (!$foruminfo)
        return $this->notFound();

      if (!can_view_forum($foruminfo))
        return $this->notAllowed();
    }

    if ($params['mode'] != 'all')
      $foruminfo = array();

    if (in_array($params['mode'], array('subforums', 'all')))
      $foruminfo['subforums'] = fetch_subforum_list($id);

    if (in_array($params['mode'], array('threads', 'all'))) {
      $perpage = isset($params['perpage']) ? intval($params['perpage']) : 10;
      $page = isset($params['page']) ? intval($params['page']) : 1;
      $foruminfo['threads'] = fetch_threads($id, $perpage, $page);
    }

    return $this->encodeOutput($foruminfo);
  }

  public function getThread($params) {
    $id = isset($params['id']) ? intval($params['id']) : -1;
    if (!isset($params['mode'])) $params['mode'] = 'all';

    $threadinfo = fetch_thread($id);
    if (!$threadinfo)
      return $this->notFound();

    if (!can_view_thread($threadinfo))
      return $this->notAllowed();

    if ($params['mode'] != 'all')
      $threadinfo = array();

    if (in_array($params['mode'], array('posts', 'all'))) {
      $perpage = isset($params['perpage']) ? intval($params['perpage']) : 10;
      $page = isset($params['page']) ? intval($params['page']) : 1;
      $threadinfo['posts'] = fetch_posts($id, $perpage, $page);
    }

    return $this->encodeOutput($threadinfo);
  }

  public function postThreadReply($params) {
    return $this->notImplemented();
  }

  public function postThread($params) {
    return $this->notImplemented();
  }

  private function encodeOutput($data) {
    shutdown();
    $this->contentType('json');
    return json_encode($data);
    // Something like this:
    //if ($this->request->accepts('json')) {
    //  return json_encode($data);
    //} else {
    //  return xml_encode($data);
    //}
  }
}

$dispatcher = new RestDispatcher(new MobileAPI());
$dispatcher->addRoute('GET',    '/session',           'getSession');
$dispatcher->addRoute('POST',   '/session',           'postSession');
$dispatcher->addRoute('DELETE', '/session',           'deleteSession');
$dispatcher->addRoute('GET',    '/forum',             'getForum');
$dispatcher->addRoute('GET',    '/forum/:id',         'getForum');
$dispatcher->addRoute('GET',    '/thread/:id',        'getThread');
$dispatcher->addRoute('POST',   '/thread/:id/reply',  'postThreadReply');
$dispatcher->addRoute('POST',   '/thread',            'postThread');
$dispatcher->dispatch();

?>
