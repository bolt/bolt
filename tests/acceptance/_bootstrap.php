<?php

if (!class_exists('TestUser')) {
    class TestUser implements ArrayAccess {
        public $username;
        public $password;
        public $userlevel;
        public $contenttypes;

        public function __construct($username, $password, $userlevel, $contenttypes) {
            $this->username = $username;
            $this->password = $password;
            $this->userlevel = $userlevel;
            $this->contenttypes = $contenttypes;
        }

        public function __toString() {
            return $this->username;
        }

        public function offsetExists($offset) {
            return isset($this->$offset);
        }

        public function offsetGet($offset) {
            return $this->$offset;
        }

        public function offsetSet($offset, $value) {
            $this->$offset = $value;
        }

        public function offsetUnset($offset) {
            unset($this->$offset);
        }

        public function getHashedPassword() {
            // Magic value from default configuration.
            $hash_strength = 10;
            $hasher = new \Hautelook\Phpass\PasswordHash($hash_strength, true);
            $hashedPassword= $hasher->HashPassword($this->password);
            return $hashedPassword;
        }

        public function create(WebGuy $I, $id) {
            $I->haveInDatabase('bolt_users',
                    array(
                        'id' => $id,
                        'username' => $this->username,
                        'password' => $this->getHashedPassword(),
                        'email' => $this->username . '@example.org',
                        'lastseen' => '1900-01-01',
                        'lastip' => '',
                        'displayname' => ucwords($this->username),
                        'userlevel' => $this->userlevel,
                        'contenttypes' => serialize($this->contenttypes),
                        'stack' => serialize(array()),
                        'enabled' => '1',
                        'shadowpassword' => '',
                        'shadowtoken' => '',
                        'shadowvalidity' => '1900-01-01',
                        'failedlogins' => '0',
                        'throttleduntil' => '1900-01-01',
                        ));
        }
    }
}

$users = array(
    'admin' => new TestUser('admin', 'admin1', 6, array('pages', 'entries', 'kitchensinks', 'dummies')),
    'bossdude' => new TestUser('bossdude', 'bossdude1', 4, array('pages', 'entries', 'kitchensinks', 'dummies')),
    'editor' => new TestUser('editor', 'editor1', 2, array('pages', 'entries', 'kitchensinks', 'dummies')),
    'pagewriter' => new TestUser('pagewriter', 'pagewriter1', 2, array('pages')),
    );

$I = new WebGuy($scenario);

// we'll start with ID = 2, because the database dump already has user #1.
$i = 2;
foreach ($users as $user) {
    $user->create($I, $i++);
}
