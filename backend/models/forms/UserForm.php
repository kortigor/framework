<?php

declare(strict_types=1);

namespace backend\models\forms;

use core\base\ModelForm;
use common\models\User;

/**
 * @property array $statuses
 */
class UserForm extends ModelForm
{
    const SCENARIO_CREATE = 'create';
    const SCENARIO_UPDATE = 'update';
    const SCENARIO_IMAGE_CHANGE = 'imageChange';
    const SCENARIO_PASSWORD_CHANGE = 'passChange';
    const SCENARIO_ADMIN_CREATE = 'adminCreate';
    const SCENARIO_ADMIN_UPDATE = 'adminUpdate';
    const SCENARIO_ADMIN_PASSWORD_CHANGE = 'adminPassChange';

    public $username;
    public $fullname;
    public $email;
    public $birthdate;
    public $userimage;
    public $password;
    public $newPassword1;
    public $newPassword2;
    public $status;
    public $rights;

    private User $user;

    protected array $guarded = [
        'userimage'
    ];

    public function __construct(User $user)
    {
        $this->filesDir = $user->imageDirPhp;
        $this->fileAttribute = 'userimage';
        $this->maxFileSize = 1000 * 1000 * 1; // 1Mb
        $this->fileExtension = 'jpg,png';
        $this->fileMimeType = 'image/jpeg,image/png';
        $this->maxImageWidth = 800;
        $this->fill($user->toArray(), '');
        $this->user = $user;
        parent::__construct();
    }

    public function normalizators(): array
    {
        return [
            [['username', 'fullname', 'email', 'birthdate'], 'trim'],
            [['status'], 'intval'],
        ];
    }

    public function rules(): array
    {
        return [
            [['username', 'fullname', 'email', 'birthdate', 'status', 'role'], 'required', 'message' => 'Необходимо заполнить'],
            ['fullname', 'regex', 'arguments' => ['#^[а-яё\s]+$#isu'], 'message' => 'Только кириллица'],
            ['status', 'oneOf', array_keys($this->statuses)],

            ['username', 'regex', 'arguments' => ['#^[\w_-]+$#is'], 'message' => 'Только латиница и цифры'],
            ['username', 'uniqueAttribute', 'username', $this->user, 'message' => 'Имя уже используется'],

            ['birthdate', 'date', 'd.m.Y', 'message' => 'Некорректная дата'],

            ['email', 'email', 'message' => 'Некорректный email'],
            ['email', 'uniqueAttribute', 'email', $this->user, 'message' => 'Email уже используется'],

            [['newPassword1', 'newPassword2'], 'lengthBetween', 8, 50, 'message' => 'От %s до %s символов'],
            [['newPassword1', 'newPassword2'], 'validateChangedPasswordsIsTheSame', 'message' => 'Пароли не совпадают'],

            ['password', 'validatePassword', 'message' => 'Некорректный пароль'],
            ['rights', 'required', 'message' => 'Задайте права'],
        ];
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_CREATE] = [
            'username',
            'fullname',
            'email',
            'userimage',
            'newPassword1',
            'newPassword2'
        ];

        $scenarios[self::SCENARIO_UPDATE] = [
            'fullname',
            'email',
            'userimage'
        ];

        $scenarios[self::SCENARIO_PASSWORD_CHANGE] = [
            'password',
            'newPassword1',
            'newPassword2'
        ];

        $scenarios[self::SCENARIO_IMAGE_CHANGE] = [
            'userimage',
        ];

        $scenarios[self::SCENARIO_ADMIN_CREATE] = [
            'username',
            'fullname',
            'birthdate',
            'email',
            'userimage',
            'status',
            'role',
            'rights',
            'newPassword1',
            'newPassword2'
        ];

        $scenarios[self::SCENARIO_ADMIN_UPDATE] = [
            'username',
            'fullname',
            'birthdate',
            'email',
            'status',
            'role',
            'rights'
        ];

        $scenarios[self::SCENARIO_ADMIN_PASSWORD_CHANGE] = [
            'newPassword1',
            'newPassword2'
        ];

        return $scenarios;
    }

    public function attributeLabels(): array
    {
        return [
            'username' => 'Логин',
            'fullname' => 'Имя',
            'birthdate' => 'Дата рождения',
            'email' => 'Email',
            'userimage' => 'Фото',
            'password' => 'Текущий пароль',
            'newPassword1' => 'Новый пароль',
            'newPassword2' => 'Повторите новый пароль',
            'status' => 'Статус',
        ];
    }

    public function getStatusesAttribute(): array
    {
        return [
            User::STATUS_ACTIVE => 'Активен',
            User::STATUS_INACTIVE => 'Заблокирован',
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $password
     * @return bool true if password valid
     */
    public function validatePassword(string $password): bool
    {
        return $this->user->validatePassword($password);
    }

    /**
     * Validates that newPassword1 is equal to newPassword2
     * This method serves as the inline validation for password changing.
     * @return bool
     */
    public function validateChangedPasswordsIsTheSame(): bool
    {
        return $this->newPassword1 === $this->newPassword2;
    }
}