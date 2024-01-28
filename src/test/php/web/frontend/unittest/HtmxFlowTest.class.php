<?php namespace web\frontend\unittest;

use lang\IllegalStateException;
use test\{Assert, Test};
use web\auth\Flow;
use web\frontend\HtmxFlow;
use web\io\{TestInput, TestOutput};
use web\{Request, Response};

class HtmxFlowTest {

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
    $res= $this->authenticate(['Hx-Request' => 'true'], new HtmxFlow(new class() extends Flow {
      public function authenticate($request, $response, $session) {
        throw new IllegalStateException('Never called');
      }
    }));

    Assert::equals(401, $res->status());
    Assert::equals('authenticationexpired', $res->headers()['HX-Trigger']);
  }
}