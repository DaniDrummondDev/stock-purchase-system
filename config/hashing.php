<?php

return [

    'driver' => 'argon2id',

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    'argon' => [
        'memory' => 19456,
        'threads' => 1,
        'time' => 2,
    ],

    'rehash_on_login' => true,

];
