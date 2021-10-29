<?php

declare(strict_types=1);

namespace common\models;

use Sys;
use core\base\Model;

/**
 * Model implements login form functionality
 * 
 * @property-read User|null $user User tried to login
 */
class LoginForm extends Model
{
    /**
     * @var bool Allow multiple logins with same username at the same time.
     */
    public bool $allowMultiple = false;

    public $username;
    public $password;
    public $rememberme = 0;

    /**
     * @var User|null
     */
    private ?User $_user;

    public function normalizators(): array
    {
        return [
            // trim username and password
            [['username', 'password'], 'trim'],
        ];
    }

    public function rules(): array
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required', 'message' => 'Необходимы имя и пароль'],
            // rememberme must be a boolean value
            ['rememberme', 'booleanVal'],
            // password is validated by validatePassword()
            ['password', 'validatePassword', 'message' => 'Некорректное имя или пароль'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'username' => 'Логин',
            'password' => 'Пароль',
            'rememberme' => 'Запомнить меня',
        ];
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        switch ($this->allowMultiple) {
            case true:
                if (!$this->user->getAuthKey()) {
                    $this->user->generateAuthKey();
                }
                break;

            case false:
                $this->user->generateAuthKey();
        }

        return Sys::$app->user->login($this->user);
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @return bool true if password valid
     */
    public function validatePassword($attribute): bool
    {
        if ($this->hasErrors()) {
            return false;
        }

        if (!$this->user || !$this->user->validatePassword($attribute)) {
            return false;
        }

        return true;
    }

    /**
     * Get user tried to login. Finds by username.
     *
     * @return User|null
     */
    public function getUserAttribute(): ?User
    {
        if (!isset($this->_user)) {
            $this->_user = User::findByUsername($this->username);
        }

        return $this->_user;
    }
}