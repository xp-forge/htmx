<?php namespace web\frontend;

use web\auth\{Flow, URL, UserInfo};

/**
 * Wraps around any authentication flow and ensures that if there is the
 * need to (re-)authenticate HTMX requests, a 401 error is sent back and
 * an `authenticationexpired` event is triggered instead of redirecting.
 *
 * @see  https://htmx.org/reference/#headers
 * @test web.frontend.unittest.HtmxFlowTest
 */
class HtmxFlow extends Flow {
  private $delegate;

  /** Creates a new instance, wrapping around a given flow */
  public function __construct(Flow $delegate) { $this->delegate= $delegate; }

  /**
   * Sets session namespace for this flow. Used to prevent conflicts
   * in session state with multiple OAuth flows in place.
   *
   * @param  string $namespace
   * @return self
   */
  public function namespaced($namespace) {
    $this->delegate->namespaced($namespace);
    return $this;
  }

  /**
   * Targets a given URL
   *
   * @param  web.auth.URL $url
   * @return self
   */
  public function target(URL $url) {
    $this->delegate->target($url);
    return $this;
  }

  /**
   * Returns URL
   *
   * @param  bool $default
   * @return ?web.auth.URL
   */
  public function url($default= false): URL {
    return $this->delegate->url($default);
  }

  /**
   * Returns a user info instance
   *
   * @return web.auth.UserInfo
   */
  public function userInfo(): UserInfo {
    return $this->delegate->userInfo();
  }

  /**
   * Refreshes access token given a refresh token if necessary.
   *
   * @param  [:var] $claims
   * @return ?web.auth.Authorization
   * @throws lang.IllegalStateException
   */
  public function refresh(array $claims) { return $this->delegate->refresh($claims); }

  /**
   * Executes authentication flow, returning the authentication result
   *
   * @param  web.Request $request
   * @param  web.Response $response
   * @param  web.session.Session $session
   * @return var
   */
  public function authenticate($request, $response, $session) {
    if ('true' === $request->header('HX-Request')) {
      $response->answer(401);
      $response->header('HX-Trigger', 'authenticationexpired');
      return null;
    }

    return $this->delegate->authenticate($request, $response, $session);
  }
}