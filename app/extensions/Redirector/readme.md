Bolt Redirector
===============

A wicked little [Bolt] [1] 1.2 extension that allows you to perform pre-app `301 Moved Permanently` redirects. Kinda handy when you're moving from your silly flat file website/overly complicated CMS to Bolt. ;)

Installation
------------

If you'd like to use the latest `master` instead of the version included in the [bolt/bolt](http://github.com/bolt/bolt) repo, you can install the extension by copying the downloaded `Redirector` directory into the `extensions` directory. Then, activate by adding `Redirector` to the array of enabled extensions in the main `app/config/config.yml` file:

```yml
enabled_extensions: [ Redirector ]
```

You can grab the extension as a [zip ball] [3] / [tar ball] [4].

Documentation
-------------

To learn how to use Bolt Redirector, the official documentation can be found on our official website at: http://code.foundrybusiness.co.za/bolt-redirector

Coming Soon
-----------

1. The ability to match an entire route, including the GET array
2. The ability to forward redirected POST requests (perhaps we'll match any method?) - this would incorporate point 1

Contributing
------------

If you feel that something is missing, not done right, or can be optimised, please submit a pull request. If you feel that features can be added, please submit an issue.

License
-------

Bolt Redirect is licensed under the Open Source [MIT License] [2].

  [1]: http://bolt.cm/                                  "Bolt"
  [2]: http://opensource.org/licenses/mit-license.php   "MIT License"
  [3]: https://github.com/foundry-code/bolt-redirector/zipball/master
  [4]: https://github.com/foundry-code/bolt-redirector/tarball/master
