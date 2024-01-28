<?php namespace web\frontend\unittest;

use test\{Assert, Test};
use web\auth\Flow;
use web\frontend\HtmxFlow;

class HtmxFlowTest {

  #[Test]
  public function can_create() {
    new HtmxFlow(new class() extends Flow {
      public function authenticate($request, $response, $session) {
        // NOOP
      }
    });
  }
}