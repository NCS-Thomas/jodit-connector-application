<?php

use Aws\S3\S3Client;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;

define('LOCAL', ($_ENV['env'] === 'local'));

// define filesystem
$config['adapter'] = null;
if (LOCAL) {
    $config = [
        'root' => __DIR__.'/files/',
        'sources' => [
            'test' => [
                'root' => __DIR__.'/files/',
                'baseurl' => 'http://localhost:8181/files/',
            ],
            'folder1' => [
                'root' => __DIR__.'/files/folder1/',
                'baseurl' => 'http://localhost:8181/files/folder1/',
                'maxFileSize' => '1kb'
            ]
        ],
    ];

    $config['adapter'] = new Local($config['root']);
} else {
    $config = [
        'root' => '/files/',
        'sources' => [
            'test' => [
                'root' => '/files/',
                'baseurl' => 'https://s3-eu-west-1.amazonaws.com/images.ncs.ninja/files/',
            ],
            'folder1' => [
                'root' => '/files/folder1/',
                'baseurl' => 'https://s3-eu-west-1.amazonaws.com/images.ncs.ninja/files/folder1/',
                'maxFileSize' => '1kb'
            ]
        ],
    ];

    $client = new S3Client([
        'credentials' => [
            'key'    => $_ENV['AWS_KEY'],
            'secret' => $_ENV['AWS_SECRET'],
        ],
        'region' => $_ENV['AWS_REGION'],
        'version' => $_ENV['AWS_VERSION'],
    ]);

    $config['adapter'] = new AwsS3Adapter($client, $_ENV['AWS_S3_BUCKET_NAME'], $config['root']);
}

$config = array_merge($config, [
    'allowCrossOrigin' => true,
    'accessControl' => [],
    'debug' => true,
    'roleSessionVar' => 'JoditUserRole'
]);

$config['accessControl'][] = [
	'role'                => '*',
	'extensions'          => '*',
	'path'                => '/',
	'FILES'               => true,
	'FILE_MOVE'           => true,
	'FILE_UPLOAD'         => true,
	'FILE_UPLOAD_REMOTE'  => true,
	'FILE_REMOVE'         => true,
	'FILE_RENAME'         => true,

	'FOLDERS'             => true,
	'FOLDER_MOVE'         => true,
	'FOLDER_REMOVE'       => true,
	'FOLDER_RENAME'       => true,

	'IMAGE_RESIZE'        => true,
	'IMAGE_CROP'          => true,
];

$config['accessControl'][] = [
	'role'                => '*',
	'path'                => LOCAL ? __DIR__ . '/files/ceicom/' : '/files/ceicom/',

	'FILE_MOVE'           => false,
	'FILE_UPLOAD'         => false,
	'FILE_UPLOAD_REMOTE'  => false,
	'FILE_RENAME'         => false,
	'FOLDER_CREATE'       => false,
];

return $config;