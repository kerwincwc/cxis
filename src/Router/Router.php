<?php

/**
* @author      Bram(us) Van Damme <bramus@bram.us>
* @copyright   Copyright (c), 2013 Bram(us) Van Damme
* @license     MIT public license
*/
namespace Cxis\Router;
/**
* Class Router.
*/
class Router
{

  /**
   * @var array The route patterns and their handling functions
   */
  private $afterRoutes = array();

  /**
   * @var array The before middleware route patterns and their handling functions
   */
  private $beforeRoutes = array();

  /**
   * @var array [object|callable] The function to be executed when no route has been matched
   */
  protected $notFoundCallback = [];

  /**
   * @var string Current base route, used for (sub)route mounting
   */
  private $baseRoute = '';

  /**
   * @var string The Request Method that needs to be handled
   */
  private $requestedMethod = '';

  /**
   * @var string The Server Base Path for Router Execution
   */
  private $serverBasePath;

  /**
   * @var string Default Controllers Namespace
   */
  private $namespace = '';

  private $enableLogging = '';

  public function __construct($enableLogging=false){
    $this->enableLogging = $enableLogging;
    return $this;
  }

  /**
   * Store a before middleware route and a handling function to be executed when accessed using one of the specified methods.
   *
   * @param string          $methods Allowed methods, | delimited
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function before($methods, $pattern, $fn)
  {
      $pattern = $this->baseRoute . '/' . trim($pattern, '/');
      $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;

      foreach (explode('|', $methods) as $method) {
          $this->beforeRoutes[$method][] = array(
              'pattern' => $pattern,
              'fn' => $fn,
          );
      }
  }

  /**
   * Check whether the current user is logged in.
   *
   * @param boolean         $lr      Specify if login is required
   */
  public function login_required( $lr = false ){
      if( $lr AND ( isset( $_SESSION[ $GLOBALS['config']['login_session'] ] ) AND $_SESSION[ $GLOBALS['config']['login_session'] ] ) ) :
        return true ;
      else:
        return false ;
      endif;
  }

  /**
   * Check whether the current user has the necessary security roles to access the resource.
   *
   * @param string          $sr      Security roles required to access the resource
   * @param string          $ur      Current user security roles
   */
  public function check_roles( $sr, $ur = null ){
      $usr = $GLOBALS['config']['session_key'] . '_userroles' ;
      $ur = (is_null( $ur ) OR $ur == '' ) ? ( isset( $_SESSION[$usr] ) ? $_SESSION[$usr] : 'GUEST' ) : $ur ;
      $srt = (isset($sr) AND ($sr!='' OR !is_null($sr))) ? $sr : 'ANY';
      $srs = strtolower( $srt );
      $srk = (substr($srs,0,5)=='role_' and isset($GLOBALS['security_group'][$srs]))?$GLOBALS['security_group'][$srs]:$srt;
      $srr = explode(",",strtoupper("MASTER,SM_MASTER,{$srk}"));
      $_ok = 0;$_bad = 0;
      $urc = explode(",",strtoupper($ur));
      foreach($urc as $user){
          if(in_array($user, $srr) OR in_array('ANY', $srr)){
              $_ok++;
          }else{
              $_bad++;
          }
      }
      return $_ok > 0 ? true : false ;
  }

  /**
   * Check whether the current user has the necessary security roles to access the resource.
   *
   * @param string          $sr      Security roles required to access the resource
   * @param string          $ur      Current user security roles
   */
  public function log4j($outcome){
    if( $this->enableLogging ):
        $db4jConfig = $GLOBALS['ENV']['database']['default'];
        $dbDriver4j = $db4jConfig['driver'] ;
        $dbServer4j = $db4jConfig['host'] ;
        $dbPort4j = $db4jConfig['port'] ;
        $dbName4j = $db4jConfig['database'] ;
        $dbTable4j = $db4jConfig['access_log'] ;
        $dbUser4j= $db4jConfig['username'] ;
        $dbPass4j = $db4jConfig['password'] ;
        $route = isset($_SERVER['REDIRECT_URL'])?$_SERVER['REDIRECT_URL']:'/';
        if(strpos($route, '/static/')==0):
        try {
            $ipAddress = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'] ;
            $ipCountry = isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : null ;
            $user = isset($_SESSION[$GLOBALS['config']['session_key'].'_username'])?$_SESSION[$GLOBALS['config']['session_key'].'_username']:'external';
            $route_2=json_encode(explode("/",$route));
            $info=json_encode([
                "METHOD"=>isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:null,
                "REQUEST_TIME_FLOAT"=>isset($_SERVER['REQUEST_TIME_FLOAT'])?$_SERVER['REQUEST_TIME_FLOAT']:null,
                "REQUEST_URI"=>isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:null,
                "QUERY_STRING"=>isset($_SERVER['QUERY_STRING'])?$_SERVER['QUERY_STRING']:null,
                "UA_PLATFORM"=>isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM'])?$_SERVER['HTTP_SEC_CH_UA_PLATFORM']:null,
                "UA_MOBILE"=>isset($_SERVER['HTTP_SEC_CH_UA_MOBILE'])?$_SERVER['HTTP_SEC_CH_UA_MOBILE']:null,
                "CH_UA"=>isset($_SERVER['HTTP_SEC_CH_UA'])?$_SERVER['HTTP_SEC_CH_UA']:null,
                "CF_RAY"=>isset($_SERVER['HTTP_CF_RAY'])?$_SERVER['HTTP_CF_RAY']:null,
                "USER_AGENT"=>isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:null,
                "UNIQUE_ID"=>isset($_SERVER['UNIQUE_ID'])?$_SERVER['UNIQUE_ID']:null,
                "FORWARDED_FOR"=>isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:null,
                "WARP_TAG_ID"=>isset($_SERVER['HTTP_CF_WARP_TAG_ID'])?$_SERVER['HTTP_CF_WARP_TAG_ID']:null,
                "COUNTRY"=>isset($_SERVER['HTTP_CF_IPCOUNTRY'])?$_SERVER['HTTP_CF_IPCOUNTRY']:null,
                "ADDRESS"=>isset($_SERVER['HTTP_CF_CONNECTING_IP'])?$_SERVER['HTTP_CF_CONNECTING_IP']:$_SERVER['REMOTE_ADDR'],
            ]);
            $data = array($user, $route, $outcome, $ipAddress, $ipCountry, session_id(), $route_2, $info); 
            $conn = new \PDO("{$dbDriver4j}:host={$dbServer4j}:{$dbPort4j};dbname={$dbName4j}" , $dbUser4j , $dbPass4j);
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt=$conn->prepare("INSERT INTO {$dbTable4j} (user,route,outcome,ip_address,ip_country,uuid,route_2,details) VALUES(?,?,?,?,?,?,?,?)");
            $stmt->execute($data);
            $conn=null;
            return true;
        } catch(\PDOException $e) {
            return false;
        }
        endif;
    endif;
  }


  /**
   * Store a route and a handling function to be executed when accessed using one of the specified methods.
   *
   * @param string          $methods Allowed methods, | delimited
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   * @param boolean         $lr      Log-in required
   * @param string          $sr      Security Roles
   */
  public function match($methods, $pattern, $fn, $lr=false, $sc='ANY')
  {
      $pattern = $this->baseRoute . '/' . trim($pattern, '/');
      $pattern = $this->baseRoute ? rtrim($pattern, '/') : $pattern;
      foreach (explode('|', $methods) as $method) {
          $this->afterRoutes[$method][] = array(
              'pattern' => $pattern,
              'fn' => $fn,
              'lr' => $lr,
              'sc' => $sc,
          );
      }
  }

  /**
   * Shorthand for a route accessed using any method.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function all($pattern, $fn, $lr=false, $sc='ANY')
  {
      $this->match('GET|POST|PUT|DELETE|OPTIONS|PATCH|HEAD', $pattern, $fn, $lr, $sc);
  }

  /**
   * Shorthand for a route accessed using GET.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function get($pattern, $fn, $lr=false, $sc='ANY')
  {
      $this->match('GET', $pattern, $fn, $lr, $sc);
  }

  /**
   * Shorthand for a route accessed using POST.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function post($pattern, $fn, $lr=false, $sc='ANY')
  {
      $this->match('POST', $pattern, $fn, $lr, $sc);
  }

  /**
   * Shorthand for a route accessed using PATCH.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function patch($pattern, $fn, $lr=false, $sc='ANY')
  {
      $this->match('PATCH', $pattern, $fn, $lr, $sc);
  }

  /**
   * Shorthand for a route accessed using DELETE.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function delete($pattern, $fn, $lr=false, $sc='ANY')
  {
      $this->match('DELETE', $pattern, $fn, $lr, $sc);
  }

  /**
   * Shorthand for a route accessed using PUT.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function put($pattern, $fn, $lr=false, $sc='ANY')
  {
      $this->match('PUT', $pattern, $fn, $lr, $sc);
  }

  /**
   * Shorthand for a route accessed using OPTIONS.
   *
   * @param string          $pattern A route pattern such as /about/system
   * @param object|callable $fn      The handling function to be executed
   */
  public function options($pattern, $fn, $lr=false, $sc='ANY')
  {
      $this->match('OPTIONS', $pattern, $fn, $lr, $sc);
  }

  /**
   * Mounts a collection of callbacks onto a base route.
   *
   * @param string   $baseRoute The route sub pattern to mount the callbacks on
   * @param callable $fn        The callback method
   */
  public function mount($baseRoute, $fn, $lr=false, $sc='ANY')
  {
          // Track current base route
          $curBaseRoute = $this->baseRoute;

          // Build new base route string
          $this->baseRoute .= $baseRoute;
        if( ( !$lr OR ( $lr AND $this->login_required() ) ) AND ( $this->check_roles( $sc ) ) ) :
          // Call the callable
            call_user_func($fn);
        else:
          $this->trigger404();
        endif;
          // Restore original base route
          $this->baseRoute = $curBaseRoute;
  }

  /**
   * Get all request headers.
   *
   * @return array The request headers
   */
  public function getRequestHeaders()
  {
      $headers = array();

      // If getallheaders() is available, use that
      if (function_exists('getallheaders')) {
          $headers = getallheaders();

          // getallheaders() can return false if something went wrong
          if ($headers !== false) {
              return $headers;
          }
      }

      // Method getallheaders() not available or went wrong: manually extract 'm
      foreach ($_SERVER as $name => $value) {
          if ((substr($name, 0, 5) == 'HTTP_') || ($name == 'CONTENT_TYPE') || ($name == 'CONTENT_LENGTH')) {
              $headers[str_replace(array(' ', 'Http'), array('-', 'HTTP'), ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
          }
      }

      return $headers;
  }

  /**
   * Get the request method used, taking overrides into account.
   *
   * @return string The Request method to handle
   */
  public function getRequestMethod()
  {
      // Take the method as found in $_SERVER
      $method = $_SERVER['REQUEST_METHOD'];

      // If it's a HEAD request override it to being GET and prevent any output, as per HTTP Specification
      // @url http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html#sec9.4
      if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
          ob_start();
          $method = 'GET';
      }

      // If it's a POST request, check for a method override header
      elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
          $headers = $this->getRequestHeaders();
          if (isset($headers['X-HTTP-Method-Override']) && in_array($headers['X-HTTP-Method-Override'], array('PUT', 'DELETE', 'PATCH'))) {
              $method = $headers['X-HTTP-Method-Override'];
          }
      }

      return $method;
  }

  /**
   * Set a Default Lookup Namespace for Callable methods.
   *
   * @param string $namespace A given namespace
   */
  public function setNamespace($namespace)
  {
      if (is_string($namespace)) {
          $this->namespace = $namespace;
      }
  }

  /**
   * Get the given Namespace before.
   *
   * @return string The given Namespace if exists
   */
  public function getNamespace()
  {
      return $this->namespace;
  }

  /**
   * Execute the router: Loop all defined before middleware's and routes, and execute the handling function if a match was found.
   *
   * @param object|callable $callback Function to be executed after a matching route was handled (= after router middleware)
   *
   * @return bool
   */
  public function run($callback = null)
  {
      // Define which method we need to handle
      $this->requestedMethod = $this->getRequestMethod();

      // Handle all before middlewares
      if (isset($this->beforeRoutes[$this->requestedMethod])) {
          $this->handle($this->beforeRoutes[$this->requestedMethod]);
      }

      // Handle all routes
      $numHandled = 0;
      if (isset($this->afterRoutes[$this->requestedMethod])) {
          $numHandled = $this->handle($this->afterRoutes[$this->requestedMethod], true);
      }

      // If no route was handled, trigger the 404 (if any)
      if ($numHandled === 0) {
          $this->trigger404($this->afterRoutes[$this->requestedMethod]);
      } // If a route was handled, perform the finish callback (if any)
      else {

          if ($callback && is_callable($callback)) {
              $callback();
          }
      }

      // If it originally was a HEAD request, clean up after ourselves by emptying the output buffer
      if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
          ob_end_clean();
      }

      // Return true if a route was handled, false otherwise
      return $numHandled !== 0;
  }

  /**
   * Set the 404 handling function.
   *
   * @param object|callable|string $match_fn The function to be executed
   * @param object|callable $fn The function to be executed
   */
  public function set404($match_fn, $fn = null)
  {
    if (!is_null($fn)) {
      $this->notFoundCallback[$match_fn] = $fn;
    } else {
      $this->notFoundCallback['/'] = $match_fn;
    }
  }

  /**
   * Triggers 404 response
   *
   * @param string $pattern A route pattern such as /about/system
   */
  public function trigger404($match = null,$denied=0){

      // Counter to keep track of the number of routes we've handled
      $numHandled = 0;

      // handle 404 pattern
      if (count($this->notFoundCallback) > 0)
      {
          // loop fallback-routes
          foreach ($this->notFoundCallback as $route_pattern => $route_callable) {

            // matches result
            $matches = [];

            // check if there is a match and get matches as $matches (pointer)
            $is_match = $this->patternMatches($route_pattern, $this->getCurrentUri(), $matches, PREG_OFFSET_CAPTURE);

            // is fallback route match?
            if ($is_match) {

              // Rework matches to only contain the matches, not the orig string
              $matches = array_slice($matches, 1);

              // Extract the matched URL parameters (and only the parameters)
              $params = array_map(function ($match, $index) use ($matches) {

                // We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
                if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                  if ($matches[$index + 1][0][1] > -1) {
                    return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                  }
                } // We have no following parameters: return the whole lot

                return isset($match[0][0]) && $match[0][1] != -1 ? trim($match[0][0], '/') : null;
              }, $matches, array_keys($matches));

              $this->invoke($route_callable);

              ++$numHandled;
            }
          }
      }
      $this->log4j(($denied!=1?'not found':'denied'));
      if (($numHandled == 0) && (isset($this->notFoundCallback['/']))) {
          $this->invoke($this->notFoundCallback['/']);
      } elseif ($numHandled == 0) {
          header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
      }
  }

  public function trigger401($match = null){
    $this->log4j('denied');
    header($_SERVER['SERVER_PROTOCOL'] . ' 401 Not Allowed');
  }

  public function triggerlogin($match = null){
    $this->log4j('redirected');
    $loginURL = $GLOBALS['config']['login_page'];
    header('location:' . $GLOBALS['config']['root'] . "/{$loginURL}?next=" . $match);
  }

  /**
  * Replace all curly braces matches {} into word patterns (like Laravel)
  * Checks if there is a routing match
  *
  * @param $pattern
  * @param $uri
  * @param $matches
  * @param $flags
  *
  * @return bool -> is match yes/no
  */
  private function patternMatches($pattern, $uri, &$matches, $flags)
  {
    // Replace all curly braces matches {} into word patterns (like Laravel)
    $pattern = preg_replace('/\/{(.*?)}/', '/(.*?)', $pattern);

    // we may have a match!
    return boolval(preg_match_all('#^' . $pattern . '$#', $uri, $matches, PREG_OFFSET_CAPTURE));
  }

  /**
   * Handle a a set of routes: if a match is found, execute the relating handling function.
   *
   * @param array $routes       Collection of route patterns and their handling functions
   * @param bool  $quitAfterRun Does the handle function need to quit after one route was matched?
   *
   * @return int The number of routes handled
   */
  private function handle($routes, $quitAfterRun = false)
  {
      // Counter to keep track of the number of routes we've handled
      $numHandled = 0;

      // The current page URL
      $uri = $this->getCurrentUri();

      // Loop all routes
      foreach ($routes as $route) {

          // get routing matches
          $is_match = $this->patternMatches($route['pattern'], $uri, $matches, PREG_OFFSET_CAPTURE);

          // is there a valid match?
          if ($is_match) {

              // Rework matches to only contain the matches, not the orig string
              $matches = array_slice($matches, 1);

              // Extract the matched URL parameters (and only the parameters)
              $params = array_map(function ($match, $index) use ($matches) {

                  // We have a following parameter: take the substring from the current param position until the next one's position (thank you PREG_OFFSET_CAPTURE)
                  if (isset($matches[$index + 1]) && isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                      if ($matches[$index + 1][0][1] > -1) {
                          return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                      }
                  } // We have no following parameters: return the whole lot

                  return isset($match[0][0]) && $match[0][1] != -1 ? trim($match[0][0], '/') : null;
              }, $matches, array_keys($matches));

              // Call the handling function with the URL parameters if the desired input is callable
              $this->invoke($route['fn'], $params, $route['lr'] , $route['sc'] , $uri );

              ++$numHandled;

              // If we need to quit, then quit
              if ($quitAfterRun) {
                  break;
              }
          }
      }

      // Return the number of routes handled
      return $numHandled;
  }

  private function invoke($fn, $params = array(), $lr = false, $sc = 'ANY' , $uri = null)
  {
    if( ( !$lr OR ( $lr AND $this->login_required($lr) ) ) AND ( $this->check_roles( $sc ) ) ){
      if (is_callable($fn)) {
          call_user_func_array($fn, $params);
      }
      // If not, check the existence of special parameters
      elseif (stripos($fn, '@') !== false) {
          // Explode segments of given route
          list($controller, $method) = explode('@', $fn);

          // Adjust controller class if namespace has been set
          if ($this->getNamespace() !== '') {
              $controller = $this->getNamespace() . '\\' . $controller;
          }

          try {
              $reflectedMethod = new \ReflectionMethod($controller, $method);
              // Make sure it's callable
              if ($reflectedMethod->isPublic() && (!$reflectedMethod->isAbstract())) {
                  if ($reflectedMethod->isStatic()) {
                      forward_static_call_array(array($controller, $method), $params);
                  } else {
                      // Make sure we have an instance, because a non-static method must not be called statically
                      if (\is_string($controller)) {
                          $controller = new $controller();
                      }
                      call_user_func_array(array($controller, $method), $params);
                  }
              }
          } catch (\ReflectionException $reflectionException) {
              // The controller class is not available or the class does not have the method $method
          }
      }
      $this->log4j('granted');
    }else{
      if( $lr AND !$this->login_required($lr) ){
        if( !is_null($uri) ){ $uri = $uri; }else{ $uri=null; }
        $this->triggerlogin( $uri );
      }else{
        $this->trigger404(null,1);
      }
    }
  }

  /**
   * Define the current relative URI.
   *
   * @return string
   */
  public function getCurrentUri()
  {
      // Get the current Request URI and remove rewrite base path from it (= allows one to run the router in a sub folder)
      $uri = substr(rawurldecode($_SERVER['REQUEST_URI']), strlen($this->getBasePath()));

      // Don't take query params into account on the URL
      if (strstr($uri, '?')) {
          $uri = substr($uri, 0, strpos($uri, '?'));
      }

      // Remove trailing slash + enforce a slash at the start
      return '/' . trim($uri, '/');
  }

  /**
   * Return server base Path, and define it if isn't defined.
   *
   * @return string
   */
  public function getBasePath()
  {
      // Check if server base path is defined, if not define it.
      if ($this->serverBasePath === null) {
          $this->serverBasePath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
      }

      return $this->serverBasePath;
  }

  /**
   * Explicilty sets the server base path. To be used when your entry script path differs from your entry URLs.
   * @see https://github.com/bramus/router/issues/82#issuecomment-466956078
   *
   * @param string
   */
  public function setBasePath($serverBasePath)
  {
      $this->serverBasePath = $serverBasePath;
  }
}
