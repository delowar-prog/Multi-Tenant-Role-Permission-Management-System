<?php
return [
  'paths' => ['api/*', 'login', 'register'],
  'allowed_methods' => ['*'],
  'allowed_origins' => ['http://localhost:3000','http://127.0.0.1:3000','http://localhost:3001','http://localhost:3002',],
  'allowed_headers' => ['*'],
  'supports_credentials' => false, // token mode => false
];

