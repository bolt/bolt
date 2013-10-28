<?php
$I = new WebGuy($scenario);
$I->wantTo('log into the backend as Admin');
$I->loginAs($users['admin']);
$I->see('Dashboard');
