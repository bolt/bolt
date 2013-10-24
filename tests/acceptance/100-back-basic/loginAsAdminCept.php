<?php
$I = new WebGuy($scenario);
$I->wantTo('log into the backend as Admin');
$I->loginAs($adminUser);
$I->see('Dashboard');
