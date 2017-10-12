<?php
namespace Nifus\Users;

use \Cartalyst\Sentry\Users\Eloquent\User as SentryUserModel;
use \Nifus\FormBuilder\FormBuilder as FormBuilder;


class User extends SentryUserModel {
    /*
    protected
        $fillable = array('fio','phone', 'password', 'email','activated');
*/
    /*
    function __construct($array){
        $this->setHasher(new \Cartalyst\Sentry\Hashing\NativeHasher);
        parent::__construct($array);
    }*/

    static function createUser($request){
        try{
            $user = \Sentry::createUser($request);
            $adminGroup = \Sentry::findGroupById(2);
            $user->addGroup($adminGroup);
            $user->registerMail();

            return $user;
        }catch (\Cartalyst\Sentry\Users\LoginRequiredException $e)
        {
            throw new \Exception( trans('users:main.errors.request_email') );
        }


    }

    public function registerMail(){
        $user = $this;
        \Mail::send('users::layout.emails.Create', array('activation_code' => $this->getActivationCode()),
            function($message) use ($user) {
                $message->to($user->email, $user->first_name)
                    ->subject('Активация пользователя');
            });
    }

    public function passwordMail($pass){
        $user = $this;
        \Mail::send('users::layout.emails.Password', array('pass' => $pass),
            function($message) use ($user) {
                $message->to($user->email, $user->first_name)
                    ->subject('Пароль пользователя');
            });
    }


    /**
     * Аунтификация пользователя
     *
     * @param array $credentials
     * @return mixed
     * @throws \Exception
     */
    static function Auth( array $credentials){
        try {
            $throttleProvider = \Sentry::getThrottleProvider();
            $throttleProvider->disable();
            return \Sentry::authenticate($credentials, true);
        } catch (\Cartalyst\Sentry\Users\LoginRequiredException $e) {
            throw new \Exception( trans('users::main.errors.require_email') );
        } catch (\Cartalyst\Sentry\Users\PasswordRequiredException $e) {
            throw new \Exception( trans('users::main.errors.require_pass'));
        } catch (\Cartalyst\Sentry\Users\UserNotFoundException $e) {
            throw new \Exception( trans('users::main.errors.user_not_found') );
        } catch (\Cartalyst\Sentry\Users\WrongPasswordException $e) {
            throw new \Exception( trans('users::main.errors.wrong_pass'));
        } catch (\Cartalyst\Sentry\Users\UserNotActivatedException $e) {
            throw new \Exception( trans('users::main.errors.user_not_activated'));
        }
    }

    static function ForgetPassword($email){
        $user = \Sentry::findUserByLogin($email);
        if ( is_null($user) ){
            throw new \Exception( trans('users::main.errors.user_not_found') );
        }
        \Mail::send('users::layout.emails.Forget',
            array('reset_code' => $user->getResetPasswordCode()),
            function($message) use ($user) {
                $message->to($user->email, $user->first_name)
                    ->subject('Восстановление пароля');
            });
        return $user;
    }

    /**
     * Reset user password && send email
     *
     * @param $code
     * @return mixed
     * @throws \Exception
     */
    static function ResetPassword($code)
    {
        try{
            $user = \Sentry::findUserByResetPasswordCode($code);
            $new_password = str_random(6);
            if (!$user->checkResetPasswordCode($code)) {
                throw new \Exception('Неверный код');
            }
            if (!$user->attemptResetPassword($code, $new_password)) {
                throw new \Exception('Не удалось сменить пароль');
            }

            \Mail::send('users::layout.emails.ResetPassword',
                array('password' => $new_password),
                function ($message) use ($user) {
                    $message->to($user->email, $user->first_name)
                        ->subject('Восстановление пароля. Шаг второй');
                });
            return $user;

        }catch ( \Cartalyst\Sentry\Users\UserNotFoundException $e ){
            throw new \Exception('Неверный ключ');
        }
    }


    static function getLoginForm(){
        $login = \Config::get('users::main.login');
        $email = !empty($_COOKIE['email']) ? $_COOKIE['email'] : '';


        if ( $login=='email' ){
            $field = FormBuilder::createField('text')
                ->setLabel( trans('users::main.email') )
                ->setValid('email')
                ->setValue($email)
                ->setName('email')
                ->set('placeholder','you@email.com')
                ->setClass('form-control input-lg');
        }else{
            $field = FormBuilder::createField('text')
                ->setLabel( trans('users::main.login') )
                ->setValue($email)
                ->setValid('')
                ->setName('login')
                ->setClass('form-control input-lg');
        }

        $form = FormBuilder::create('login_form')
            ->setMethod('post')
            ->setEnctype('multipart/form-data')
            ->setRender('bootstrap3')
            ->setCols(1)
            ->setExtensions(['Placeholder', 'Validetta'])
          //  ->set('ajax',['url'=>route('poll.show.create')])
            ->setFields([
                $field,
              FormBuilder::createField('password')
                    ->setLabel( trans('users::main.password') )
                    ->set('placeholder','********')
                    ->setValid('min:6')
                    ->setClass('form-control input-lg')
                    ->setName('password'),
              FormBuilder::createField('button')
                  ->setClass('btn btn-primary')->setType('submit')
                  ->setValue(  trans('users::main.login_btn') ),
            ]);

        return $form;
    }

    static function getRegisterForm(){
        $login = \Config::get('users::main.login');
        if ( $login=='email' ){
            $field = FormBuilder::createField('text')
                ->setLabel( trans('users::main.email') )
                ->setValid('email')
                ->set('placeholder',trans('users::main.placeholder_email') )
                ->setName('email')
               // ->set('style','width:280px')
                ->setClass('form-control input-lg');
        }else{
            $field = FormBuilder::createField('text')
                ->setLabel( trans('users::main.login') )
                ->setValid('min:3')
                ->setName('login')
                ->setClass('form-control input-lg');
        }

        $form = FormBuilder::create('register_form')
            ->setRender('array')
            ->setExtensions(['Ajax','Placeholder'])
            //  ->set('ajax',['url'=>route('poll.show.create')])
            ->setFields([
                FormBuilder::createField('hidden')
                    ->setName('id')
                    ->setId('user_id'),
                $field,
                FormBuilder::createField('text')
                    ->setLabel( 'Имя' )
                    ->set('placeholder','Ваше имя или название организации')
                    ->setValid('min:2')
                    ->setClass('form-control input-lg')
                    ->setName('first_name'),
                FormBuilder::createField('text')
                    ->setLabel( 'Телефон' )
                    ->set('placeholder','Ваш телефон, например +79034567891')
                    ->setValid('min:12')
                    ->setClass('form-control input-lg')
                    ->setName('phone'),
                FormBuilder::createField('text')
                    ->setLabel( 'Код' )
                    ->set('placeholder','Укажите код высланный вам на E-Mail')
                    ->setClass('form-control input-lg')
                    ->setName('code'),
            ]);

        return $form->render();
    }

    static function getForgetForm(){
        $email = !empty($_COOKIE['email']) ? $_COOKIE['email'] : '';
        $form = FormBuilder::create('forget_form')
            ->setRender('array')
            ->setExtensions(['Ajax','Placeholder'])
            //  ->set('ajax',['url'=>route('poll.show.create')])
            ->setFields([
                FormBuilder::createField('text')
                    ->setLabel( 'E-Mail' )
                    ->setValue($email)
                    ->setValid('email')
                    ->setName('email')
                    ->set('placeholder','Укажите ваш E-Mail адрес')
                    ->setClass('form-control input-lg'),
                FormBuilder::createField('text')
                    ->setLabel( 'Код' )
                    ->setName('code')
                    ->set('placeholder','Укажите код высланный вам на E-Mail')
                    ->setClass('form-control input-lg')
            ]);

        return $form->render();
    }

    static function createPassword($length){
        $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
        $numChars = strlen($chars);
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= substr($chars, rand(1, $numChars) - 1, 1);
        }
        return $string;
    }

}