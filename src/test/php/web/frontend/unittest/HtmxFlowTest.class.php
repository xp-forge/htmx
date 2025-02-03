<?php namespace web\frontend\unittest;

use lang\IllegalStateException;
use test\{Assert, Test};
use web\auth\{Authorization, Flow, UseRequest, UseURL, UserInfo};
use web\frontend\HtmxFlow;
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class HtmxFlowTest {

  /** Calls authenticate() method and returns response */
  private function authenticate(array $headers, Flow $flow): Response {
    $res= new Response(new TestOutput());
    $flow->authenticate(new Request(new TestInput('GET', '/', $headers)), $res, null);
    return $res;
  }

  /** Returns a flow delegate */
  private function delegate(?callable $auth): Flow {
    return newinstance(Flow::class, [], [
      'userInfo'     => function(): UserInfo { return new UserInfo('strtoupper'); },
      'namespace'    => function() { return $this->namespace; },
      'authenticate' => $auth ?? function($request, $response, $session) { /* NOOP */ },
    ]);
  }

  #[Test]
  public function can_create() {
    new HtmxFlow($this->delegate(null));
  }

  #[Test]
  public function returns_delegates_user_information() {
    Assert::equals('TEST', (new HtmxFlow($this->delegate(null)))->userInfo()('test'));
  }

  #[Test]
  public function returns_delegates_default_url() {
    Assert::instance(UseRequest::class, (new HtmxFlow($this->delegate(null)))->url(true));
  }

  #[Test]
  public function delegates_namespacing() {
    $delegate= $this->delegate(null);
    (new HtmxFlow($delegate))->namespaced('test');

    Assert::equals('test', $delegate->namespace());
  }

  #[Test]
  public function delegates_target_url() {
    $delegate= $this->delegate(null);
    $url= new UseURL('https://example.com');
    (new HtmxFlow($delegate))->target($url);

    Assert::equals($url, $delegate->url());
  }

  #[Test]
  public function delegates_authentication() {
    $res= $this->authenticate([], new HtmxFlow($this->delegate(function($request, $response, $session) {
      $response->answer(302);
      $response->header('Location', '/login');
    })));

    Assert::equals(302, $res->status());
    Assert::equals('/login', $res->headers()['Location']);
  }

  #[Test]
  public function returns_error_code_and_triggers_authenticationexpired_event() {
    $res= $this->authenticate(['HX-Request' => 'true'], new HtmxFlow($this->delegate(function($request, $response, $session) {
      throw new IllegalStateException('Never called');
    })));

    Assert::equals(401, $res->status());
    Assert::equals('authenticationexpired', $res->headers()['HX-Trigger']);
  }

  #[Test]
  public function delegates_refreshing() {
    $flow= new HtmxFlow(new class() extends Flow {
      public function refresh(array $claims) {
        return newinstance(Authorization::class, [], [
          'claims' => function() use($claims) { return ['token' => 'new'] + $claims; }
        ]);
      }

      public function authenticate($request, $response, $session) {
        // NOOP
      }
    });

    Assert::equals(
      ['token' => 'new', 'refresh' => '6100'],
      $flow->refresh(['token' => 'old', 'refresh' => '6100'])->claims()
    );
  }
}