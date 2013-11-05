<?php
$I = new WebGuy($scenario);
$I->wantTo('log into the backend as Page Editor');
$I->loginAs($users['pagewriter']);
$I->see('Dashboard');
