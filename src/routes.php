<?php

\Route::any('/login',       ['uses'=>'Nifus\Users\CUser@Login',     'as'=>'nifus.users.login']);
\Route::get('/logout',      ['uses'=>'Nifus\Users\CUser@Logout',    'as'=>'nifus.users.logout']);
\Route::any('/register',    ['uses'=>'Nifus\Users\CUser@Register',  'as'=>'nifus.users.register']);
/*
\Route::any('/forget',['uses'=>'Nifus\Users\Member@Forget',  'as'=>'users.forget']);
\Route::any('/reset_password',['uses'=>'Nifus\Users\Member@ResetPassword',  'as'=>'users.reset_password']);*/
