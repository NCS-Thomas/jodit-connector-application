<?php
$I = new ApiTester($scenario);

$I->wantTo('Check moving folder to another directory');

$folder = 'testMove' . rand(10000, 100000);

// create test folder
$I->sendGET('?action=folderCreate&source=test&name=' . $folder);

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();
$I->seeResponseContainsJson([
    'success' => true,
    'data' => [
        'code' => 220,
    ]
]);

// add a test file
$I->sendPOST('?',  [
    'action' => 'fileUpload',
    'source' => 'test',
    'path' => $folder,
], ['files' => [
    realpath(__DIR__ . '/../files/regina.jpg')
]]);

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();
$I->seeResponseContainsJson([
    'success' => true,
    'data' => [
        'code' => 220,
        'files' => [
            $folder.'/regina.jpg',
        ],
        'isImages' => [
            true,
        ]
    ]
]);

// move the folder
$I->sendGET('?action=folderMove&source=test&from='.$folder.'&path=folder1');

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();
$I->seeResponseContainsJson([
    'success' => true,
    'data' => [
        'code' => 220,
    ]
]);

// clean up
$I->sendGET('?action=folderRemove&source=test&name=folder1/'.$folder);

$I->seeResponseIsJson();
$I->seeResponseContainsJson([
    'success' => true,
    'data' => [
        'code' => 220
    ]
]);