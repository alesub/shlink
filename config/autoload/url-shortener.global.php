<?php

declare(strict_types=1);

use function Shlinkio\Shlink\Common\env;

return [

    'url_shortener' => [
        'domain' => [
            'schema' => env('SHORTENED_URL_SCHEMA', 'http'),
            'hostname' => env('SHORTENED_URL_HOSTNAME'),
        ],
        'validate_url' => true,
    ],

];
