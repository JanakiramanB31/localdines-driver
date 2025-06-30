<?php

return [
  'default' => 'default',
  'documentations' => [
    'default' => [
      'api' => [
        'title' => 'Lumen API',
      ],
      'routes' => [
        'api' => 'api/documentation',
        'docs' => 'api/docs.json',
      ],
      'paths' => [
        'docs_json' => 'api-docs.json',
        'docs_yaml' => 'api-docs.yaml',
        'annotations' => [
          base_path('app'),
        ],
      ],
    ],
  ],
  'swagger_version' => '3.0',
];
