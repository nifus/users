<?php
namespace Nifus\Users;

use View;

class CUser extends \Controller {

    protected $model;


    function __construct(){
       $this->model =  \Config::get('users::main.model');
    }


    /**
     * Login form && auth
     * @return mixed
     */
	public function Login()
	{
        $model = $this->model;
        $error = null;
        $login = \Config::get('users::main.login'); // set login field
        $form = $model::getLoginForm();
        if ( \Input::has('password') ){
            $request = \Input::all();
            $credentials = array(
                $login     => $request[$login],
                'password'  => $request['password'],
            );
            try {
               // dd($credentials);
                $user = $model::Auth($credentials);
                $route = \Config::get('users::main.redirect.login');
                \Event::fire('auth.login', ['success'=>true,'user'=>$user]);
                return is_string($route) ?  \Redirect::route($route) : \Redirect::to( $route($user) );

            }catch ( \Exception $e) {
                \Event::fire('auth.login', ['success'=>false,'error'=>$e->getMessage()]);
                if ( \Request::ajax() ){
                    return \Response::json(['error' => $e->getMessage()]);
                }else{
                    $form->setError( $e->getMessage() );
                }
            }
        }


        $data = [
            'login_key' =>$login,
            'login_form' => $form,
        ];

        return View::make('users::user.login',$data);
    }

    public function Logout()
    {
        $logout_route = \Config::get('users::main.redirect.logout');
        $user = \Sentry::getUser();
        if ($user) {
            \Sentry::logout();
        }
        \Event::fire('auth.logout', ['success'=>true,'user'=>$user]);
        return \Redirect::route($logout_route);
    }

    public function Register()
    {
        $error = null;
        $login = \Config::get('users::main.login');
        try {
            if ( \Input::has('email') && \Input::has('code')==null ){
                $request = [
                    'password' => User::createPassword(8),
                    'first_name' => \Input::get('first_name'),
                    'email' => \Input::get('email'),
                    'phone' => \Input::get('phone'),
                ];
                $user = User::createUser($request);
                \Event::fire('user.register', ['success'=>true,'user'=>$user]);
                $msg = 'Пользователь добавлен, на почту выслан код активации';
            }elseif( \Input::has('code')  ){

                $user =  \Sentry::findUserByActivationCode(\Input::get('code'));
                if ( $user && $user->attemptActivation(\Input::get('code')))
                {
                    $pass = User::createPassword(8);
                    $user->update(['password' => $pass ]);
                    $user->passwordMail($pass);
                    $msg = 'Пользователь активирован, на почту выслан ваш пароль';
                    \Sentry::login($user, true);

                    $route = '/';
                    \Event::fire('user.activated', ['success'=>true,'user'=>$user]);
                    if ( \Request::ajax() ){
                        return \Response::json(['url' => $route]);
                    }else{
                       // return is_string($route) ?  \Redirect::route($route) : \Redirect::to( $route($user) );
                    }
                }
            }

            if ( \Request::ajax() ){
                return \Response::json(['msg' => $msg]);
            }else{
                $error = $msg;
            }
            return $error;
        }catch ( \Cartalyst\Sentry\Users\UserExistsException $e)
        {
            $user = \Sentry::findUserByLogin(\Input::get('email'));
            if ( $user && $user->activated==0 ){
                $user->registerMail();
                $msg = 'Пользователь добавлен, на почту выслан код активации';
                return \Response::json(['msg' => $msg]);

            }else{
                $msg = 'Пользователь с таким E-Mail уже существует';
                return \Response::json(['error' => $msg]);
            }
        }
        catch ( \Exception $e) {
            if ( \Request::ajax() ){
                return \Response::json(['error' => $e->getMessage()]);
            }else{
                $error = $e->getMessage();
            }
        }



       // return '';
    }

    /**
     * Восстановленеи пароля
     *
     * @return string
     */
    function Forget(){
        try {
            if ( \Input::has('code')  ){
                User::ResetPassword( \Input::get('code') );
                $msg = 'Новый пароль выслан вам на E-Mail';
                
            }elseif( \Input::has('email') && \Input::has('code')==null ){
                User::ForgetPassword( \Input::get('email') );
                $msg = 'Инструкция по смене пароля отправлена на E-Mail';
            }else{
                throw new \Exception('Ошибка запроса');
            }
            if ( \Request::ajax() ){
                return \Response::json(['msg' => $msg]);
            }else{
                $error = $msg;
            }

            return $error;
        } catch (\Exception $e) {
            if ( \Request::ajax() ){
                return \Response::json(['error' => $e->getMessage()]);
            }else{
                $error = $e->getMessage();
            }
            return $error;
        }
    }


}