<?php

return [
    'secret' => env('JWT_SECRET', 'changeme-smartcheckout'),
    'ttl' => env('JWT_TTL', 3600), // seconds
];
