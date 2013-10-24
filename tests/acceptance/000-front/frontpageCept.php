<?php
$I = new WebGuy($scenario);
$I->wantTo('see that the frontpage works');
$I->amOnPage('');
$I->see('A sample site');
