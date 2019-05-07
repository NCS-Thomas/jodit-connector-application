<?php
$I = new ApiTester($scenario);

$I->wantTo('Try get filename by URL');

$I->sendGET('?action=files&source=test');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);

$baseUrl = $I->grabDataFromResponseByJsonPath('$.data.sources.test.baseurl');

$I->sendPOST('',  [
    'action' => 'getLocalFileByUrl',
    'source' => 'test',
    'url' => $baseUrl[0].'artio.jpg'
]);

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
        "path" => '',
        "name" => 'artio.jpg',
        "source" => 'test',
    ]
]);


