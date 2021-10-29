<?php

declare(strict_types=1);

namespace customer\models\forms;

use Sys;
use core\base\ModelForm;
use core\traits\GetSetByPropsTrait;
use customer\entities\Employee;
use customer\entities\EmployeeRole;
use customer\entities\StatusEmployee;

/**
 * Employee edit form.
 * 
 * @property-read Employee $user Employee edited by form.
 */
class EmployeeForm extends ModelForm
{
    use GetSetByPropsTrait;

    // const SCENARIO_IMAGE_CHANGE = 'imageChange';
    const SCENARIO_PASSWORD_CHANGE = 'passChange';
    const SCENARIO_ADMIN_CREATE = 'adminCreate';
    const SCENARIO_ADMIN_UPDATE = 'adminUpdate';
    const SCENARIO_ADMIN_PASSWORD_CHANGE = 'adminPassChange';

    public $username;
    public $fullname;
    public $email;
    public $password;
    public $newPassword1;
    public $newPassword2;
    public $status;
    public $role;

    public function __construct(private Employee $user)
    {
        $this->fill($user->toArray(), '');
        parent::__construct();
    }

    public function normalizators(): array
    {
        return [
            [['username', 'fullname', 'email'], 'trim'],
            [['status', 'role'], 'intval'],
        ];
    }

    public function rules(): array
    {
        return [
            [['username', 'fullname', 'email', 'status', 'role'], 'required', 'message' => 'Необходимо заполнить'],

            ['fullname', 'regex' => '#^[а-яё\s]+$#isu', 'message' => 'Имя по-русски'],
            ['status', 'oneOf' => [array_keys(StatusEmployee::list())]],

            ['username', 'regex' => '/^[\w_-]+$/is', 'message' => 'Некорректный логин'],
            ['username', 'uniqueAttribute' => ['username', $this->user], 'message' => 'Логин уже используется'],

            ['email', 'email', 'message' => 'Некорректный email'],
            ['email', 'uniqueAttribute' => ['email', $this->user], 'message' => 'Email уже используется'],

            [['newPassword1', 'newPassword2'], 'lengthBetween' => [8, 50], 'message' => 'От %s до %s символов'],
            [['newPassword1', 'newPassword2'], 'validateChangedPasswordsIsTheSame', 'message' => 'Пароли не совпадают'],

            ['password', 'validatePassword', 'message' => 'Некорректный пароль'],

            ['role', 'oneOf' => [array_keys(EmployeeRole::list())], 'message' => 'Необходимо выбрать'],
            ['role', 'validateAdminCantFireMyself', 'message' => 'Нельзя забрать у себя роль администратора', 'on' => self::SCENARIO_ADMIN_UPDATE],
        ];
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();

        $scenarios[self::SCENARIO_PASSWORD_CHANGE] = [
            'password',
            'newPassword1',
            'newPassword2'
        ];

        $scenarios[self::SCENARIO_ADMIN_CREATE] = [
            'username',
            'fullname',
            'email',
            'status',
            'newPassword1',
            'newPassword2',
            'role',
        ];

        $scenarios[self::SCENARIO_ADMIN_UPDATE] = [
            'username',
            'fullname',
            'email',
            'status',
            'role',
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
            'email' => 'Email',
            'password' => 'Текущий пароль',
            'newPassword1' => 'Новый пароль',
            'newPassword2' => 'Повторите новый пароль',
            'status' => 'Статус',
            'role' => 'Роль',
        ];
    }

    /**
     * Roles list
     * 
     * @return array
     */
    public function roleList(): array
    {
        // foreach (EmployeeRole::list() as $value => $description) {
        //     $group = $value < 200 ? 'от Биомер' : 'от Клиентов';
        //     $list[$group][$value] = $description;
        // };
        foreach (EmployeeRole::list() as $value => $description) {
            if ($value < 200) {
                $list[$value] = $description;
            }
        };

        return $list;
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $value
     * @return bool true if password valid
     */
    public function validatePassword(string $value): bool
    {
        return $this->user->validatePassword($value);
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

    /**
     * Check that admin not trying to take away ADMINISTRATOR role from self.
     * 
     * @param int $value role value
     * 
     * @return bool true if not trying.
     */
    public function validateAdminCantFireMyself(int $value): bool
    {
        $adminSelfEdit = isAdmin() && $this->user->id === Sys::$app->user->identity->getId();
        if ($adminSelfEdit && $value !== EmployeeRole::ADMINISTRATOR) {
            return false;
        }

        return true;
    }

    public function getUserAttribute(): Employee
    {
        return $this->user;
    }

    /**
     * Available statuses list.
     * 
     * @return array
     */
    public function statusList(): array
    {
        return StatusEmployee::list();
    }
}