HTMX for XP web frontends
=========================

[![Build status on GitHub](https://github.com/xp-forge/htmx/workflows/Tests/badge.svg)](https://github.com/xp-forge/htmx/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_4plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/htmx/version.svg)](https://packagist.org/packages/xp-forge/htmx)

HTMX Integration

Authentication
--------------
Wrap any authentication flow in a *HtmxFlow* to ensure authentication does not redirect but instead yields an error code and triggers an event:

```diff
+ use web\frontend\HtmxFlow;

- $auth= new SessionBased($flow, $sessions);
+ $auth= new SessionBased(new HtmxFlow($flow), $sessions);
```

Handle this inside JavaScript with something along the lines of the following:

```javascript
window.addEventListener('authenticationexpired', e => {
  if (confirm('Authentication expired. Do you want to re-authenticate?')) {
    window.location.reload();
  }
});
````
