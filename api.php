<?php
require_once('./rest.php');
require_once('./functions.php');

class MobileAPI extends RestService {
  public function postSession($params) {
    return $this->notFound();
  }

  public function deleteSession($params) {
    return $this->notFound();
  }

  public function getForum($params) {
    $id = isset($params['id']) ? intval($params['id']) : -1;

    if ($id != -1) {
      $foruminfo = fetch_forum($id);
      if (!$foruminfo)
        return $this->notFound();

      if (!can_view_forum($id))
        return $this->unauthorized();
    } else {
      $foruminfo = array();
    }

    $foruminfo['subforums'] = fetch_subforum_list($id);

    $perpage = isset($params['perpage']) ? intval($params['perpage']) : 10;
    $page = isset($params['page']) ? intval($params['page']) : 1;
    $foruminfo['threads'] = fetch_threads($id, $perpage, $page);

    return $this->encodeOutput(array(
      'status' => 'ok',
      'result' => $foruminfo)
    );
  }

  public function getThread($params) {
    return $this->notFound();
  }

  public function postThreadReply($params) {
    return $this->notFound();
  }

  public function postThread($params) {
    return $this->notFound();
  }

  private function encodeOutput($data) {
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
$dispatcher->addRoute('POST',   '/session',           'postSession');
$dispatcher->addRoute('DELETE', '/session',           'deleteSession');
$dispatcher->addRoute('GET',    '/forum',             'getForum');
$dispatcher->addRoute('GET',    '/forum/:id',         'getForum');
$dispatcher->addRoute('GET',    '/thread/:id',        'getThread');
$dispatcher->addRoute('POST',   '/thread/:id/reply',  'postThreadReply');
$dispatcher->addRoute('POST',   '/thread',            'postThread');
$dispatcher->dispatch();

?>
