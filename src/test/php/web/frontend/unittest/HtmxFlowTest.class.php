<?php namespace web\frontend\unittest;

use lang\IllegalStateException;
use test\{Assert, Test};
use web\auth\{Authorization, Flow};
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

  #[Test]
  public function can_create() {
    new HtmxFlow(new class() extends Flow {
      public function authenticate($request, $response, $session) {
        // NOOP
      }
    });
  }

  #[Test]
  public function delegates_authentication() {
    $res= $this->authenticate([], new HtmxFlow(new class() extends Flow {
      public function authenticate($request, $response, $session) {
        $response->answer(302);
        $response->header('Location', '/login');
      }
    }));

    Assert::equals(302, $res->status());
    Assert::equals('/login', $res->headers()['Location']);
  }

  #[Test]
  public function returns_error_code_and_triggers_authenticationexpired_event() {
    $res= $this->authenticate(['HX-Request' => 'true'], new HtmxFlow(new class() extends Flow {
      public function authenticate($request, $response, $session) {
        throw new IllegalStateException('Never called');
      }
    }));

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