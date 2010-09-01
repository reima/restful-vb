<?php

class RestRequest {
  public $params = null;
  public $headers = null;

  public function method() {
    return $_SERVER['REQUEST_METHOD'];
  }

  public function isGet()    { return $this->method() == 'GET'; }
  public function isPost()   { return $this->method() == 'POST'; }
  public function isPut()    { return $this->method() == 'PUT'; }
  public function isDelete() { return $this->method() == 'DELETE'; }
  public function isHead()   { return $this->method() == 'HEAD'; }

  public function params() {
    if (!is_null($this->params))
      return $this->params;

    $this->params = array_merge($_GET, $this->getBodyParams());
    return $this->params;
  }

  public function headers() {
    if (!is_null($this->headers))
      return $this->headers;

    $this->headers = getallheaders();
    return $this->headers;
  }

  public function header($name) {
    $headers = $this->headers();
    return isset($headers[$name]) ? $headers[$name] : '';
  }

  public function path() {
    return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  }

  public function relativePath() {
    $request_uri = $this->path();
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    if (strncmp($script_dir, $request_uri, strlen($script_dir)) === 0) {
      return substr($request_uri, strlen($script_dir));
    } else {
      return $request_uri;
    }
  }

  private function getBodyParams() {
    if ($this->isGet() || $this->isHead()) {
      return array();
    } else if ($this->isPost()) {
      return $_POST;
    } else {
      parse_str(file_get_contents('php://input'), $result);
      return $result;
    }
  }
}

class RestService {
  private static $mime_types = array(
    'html' => 'text/html',
    'json' => 'application/json',
    'plain' => 'text/plain',
    'xhtml' => 'application/xhtml+xml',
    'xml' => 'application/xml',
  );

  protected $request = null;

  public function setRequest(RestRequest $request) {
    $this->request = $request;
  }

  public function status($status) {
    header($_SERVER['SERVER_PROTOCOL'] . ' ' . $status);
  }

  public function notFound() {
    $this->status(404);
    $this->contentType('plain');
    return "Not found";
  }

  public function unauthorized() {
    $this->status(401);
    $this->contentType('plain');
    return "Unauthorized";
  }

  public function contentType($type, $encoding='utf-8') {
    // TODO: Output encoding
    if (isset(RestService::$mime_types[$type]))
      $type = RestService::$mime_types[$type];
    header('Content-Type: ' . $type);
  }
}

class RestRoute {
  private $method;
  private $path;
  private $compiled;
  private $keys;

  public function __construct($method, $path) {
    $this->method = $method;
    $this->path = $path;
    $this->compile();
  }

  public function match($method, $path) {
    if (strcasecmp($this->method, $method) !== 0)
      return false;

    if (!preg_match($this->compiled, $path, $groups))
      return false;

    if (empty($this->keys))
      return array();

    array_shift($groups);
    return array_combine($this->keys, $groups);
  }

  private function compile() {
    $this->keys = array();
    $parts = explode('/', $this->path);
    foreach ($parts as $key => $part) {
      if (strlen($part) == 0) continue;
      if ($part[0] == ':') {
        $parts[$key] = '([^/]+)';
        $this->keys[] = substr($part, 1);
      } else {
        $parts[$key] = preg_quote($part, '@');
      }
    }
    $this->compiled = '@^' . join('/', $parts) . '$@';
  }
}

class RestDispatcher {
  private $routes = array();
  private $service;

  public function __construct(RestService $service) {
    $this->service = $service;
  }

  public function addRoute($method, $path, $function) {
    $this->routes[] = array(new RestRoute($method, $path), $function);
    if (strtoupper($method) == 'GET') {
      $this->routes[] = array(new RestRoute('HEAD', $path), $function);
    }
  }

  public function dispatch() {
    $request = new RestRequest();
    $this->service->setRequest($request);

    $method = $request->method();
    $path = $request->relativePath();
    $routed = false;
    foreach ($this->routes as $route) {
      list($route, $function) = $route;
      if (($route_params = $route->match($method, $path)) !== false) {
        $routed = true;
        $params = array_merge($request->params(), $route_params);
        $result = $this->service->$function($params);
        if (!$request->isHead())
          echo $result;
        break;
      }
    }

    if (!$routed)
      echo $this->service->notFound();
  }
}

?>