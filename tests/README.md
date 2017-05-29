Running Tests
=============

If you are contributing to Bolt, we run a suite of tests that need to pass
prior to a pull request being merged.

**NOTE:** All tests need to be executed from the git root directory.


PHPUnit (unit & functional)
---------------------------

Running **all** PHPUnit tests can simply be done by running:

```bash
./vendor/bin/phpunit
```

Should you have one test class that is failing, you can specifically run that
classes tests by specifying the relative path the the class, e.g.

```bash
./vendor/bin/phpunit tests/phpunit/unit/Application/ApplicationTest.php
```


Codeception (acceptance)
------------------------

Prior to a first run on a new repository, or if Codeception has been updated,
the first step must be to run Codeception's `build` command:

```bash
./vendor/codeception/codeception/codecept build
```

To run the acceptance tests run Codeception's `run` command:

```bash
./vendor/codeception/codeception/codecept run
```
