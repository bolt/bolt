Travis Scripts
==============


Composer Install Set-up Script
------------------------------

For acceptance testing Composer installs in Travis, we run `tests/travis/composer-setup`
to create a Composer project install. 

* Copy the Bolt repository to a hidden directory
* Checkout the commit or pull request to be tests
* Create a new branch called `travis-install` based on the detached HEAD 
* Change the branch-alias of `dev-master` to `dev-travis-install`
* Create a new Bolt Composer project
* Require development packages for test
* Require the `dev-travis-install` alias

To test the `tests/travis/composer-setup` script failures, you can simulate the
Travis environment by creating the following script:

```
#!/bin/bash

# Set this to a PR number to test a PR
TRAVIS_PULL_REQUEST=1555
# Set this to a commit you want to use as HEAD
TRAVIS_COMMIT="e6f2d5222c88ed52e192aa6b6662e96014c017fe"
# Set this to the location of Bolt clone directory
TRAVIS_BUILD_DIR=/path/to/bolt/clone

COMPOSER_PR_REPO=$TRAVIS_BUILD_DIR/../.bolt-git-install
COMPOSER_INSTALL=$TRAVIS_BUILD_DIR/../.composer-install

source $TRAVIS_BUILD_DIR/tests/travis/composer-setup

pushd $COMPOSER_INSTALL

pushd public/
php -S localhost:8123 -t . index.php &
SERVER_PID=$!
popd

./vendor/codeception/codeception/codecept build  --config ./vendor/bolt/bolt/codeception.yml
./vendor/codeception/codeception/codecept run --config ./vendor/bolt/bolt/codeception.yml

kill "$SERVER_PID"
popd
```
