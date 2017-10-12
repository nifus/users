<?php
return [
    'login' => 'email',
    'model' => '\User',
    'view' => [
        'login' => 'users::member.Login'
    ],
    'redirect' => [
        'login' => 'main',
        'logout'=> 'main',
        'register' => 'main'
    ]
];