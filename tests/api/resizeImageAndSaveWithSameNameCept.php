<?php
$I = new ApiTester($scenario);

$I->wantTo('Resize image and save it with same name');

$name = 'test' . rand(10000, 20000);
copy(__DIR__ . '/../files/artio.jpg', __DIR__ . '/../files/' . $name . '.jpg');

$I->sendPOST('?',  [
    'action' => 'fileUpload',
    'source' => 'test'
], ['files' => [
    realpath(__DIR__ . '/../files/'.$name.'.jpg'),
]]);

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->sendPOST('',  [
    'action' => 'imageResize',
    'source' => 'test',
    'box' => [
        'w' => 30,
        'h' => 30,
    ],
    'name' => $name . '.jpg',
    'newname' => $name
]);

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);


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

$info = getimagesize($baseUrl[0].$name.'.jpg');

$I->assertEquals(30, (int)$info[0]);

$I->sendGET('?action=fileRemove&source=test&name='.$name.'.jpg');

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);