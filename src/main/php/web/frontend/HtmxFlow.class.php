<?php namespace web\frontend;

use web\auth\Flow;

/**
 * Wraps around any authentication flow and ensures that if there is the
 * need to (re-)authenticate HTMX requests, a 401 error is sent back and
 * an `authenticationexpired` event is triggered instead of redirecting.
 *
 * @see  https://htmx.org/reference/#headers
 */
class HtmxFlow extends Flow {
  private $delegate;

  /** Creates a new instance, wrapping around a given flow */
  public function __construct(Flow $delegate) { $this->delegate= $delegate; }

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
    if ('true' === $request->header('Hx-Request')) {
      $response->answer(401);
      $response->header('HX-Trigger', 'authenticationexpired');
      return null;
    }

    return $this->delegate->authenticate($request, $response, $session);
  }
}