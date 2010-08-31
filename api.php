<?php
require_once('./rest.php');


class MobileAPI extends RestService {
  public function postSession($params) {
    return $this->notFound();
  }

  public function deleteSession($params) {
    return $this->notFound();
  }

  public function getForum($params) {
    return $this->notFound();
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
    // Something like this:
    //if ($this->request->accepts('json')) {
    //  return json_encode($data);
    //} else {
    //  return xml_encode($data);
    //}
  }
}

header('Content-type: text/plain');

$dispatcher = new RestDispatcher(new MobileAPI());
$dispatcher->addRoute('POST',   '/session',           'postSession');
$dispatcher->addRoute('DELLTE', '/session',           'deleteSession');
$dispatcher->addRoute('GET',    '/forum',             'getForum');
$dispatcher->addRoute('GET',    '/forum/:id',         'getForum');
$dispatcher->addRoute('GET',    '/thread/:id',        'getThread');
$dispatcher->addRoute('POST',   '/thread/:id/reply',  'postThreadReply');
$dispatcher->addRoute('POST',   '/thread',            'postThread');
$dispatcher->dispatch();

?>
