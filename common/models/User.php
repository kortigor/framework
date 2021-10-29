<?php

declare(strict_types=1);

namespace common\models;

use core\orm\ActiveRecord;
use core\interfaces\IdentityInterface;
use core\helpers\StringHelper;

/**
 * @property string $id User unique ID in uuid6 format.
 * @property string $username User login name.
 * @property string $fullname Full user name.
 * @property string $email User's e-mail.
 * @property string $userimage User's avatar or photo image file name.
 * @property int $status User status.
 * @property string $password Encrypted user password.
 * @property string $auth_key Auth key for "remember me".
 * @property string $verification_token Email verification token.
 * @property string $password_reset_token Key for password reset.
 * @property string $access_token Key to access without login.
 * @property \Carbon\Carbon $birthdate User's birthdate.
 * @property \Carbon\Carbon $created_at Record creation date.
 * @property \Carbon\Carbon $updated_at Record last update date.
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_INACTIVE = 1;
    const STATUS_ACTIVE = 10;

    public $incrementing = false;
    protected $table = 'users';
    protected $keyType = 'string';

    protected $fillable = [
        'username',
        'fullname',
        'birthdate',
        'email',
        'status',
        'role',
    ];

    protected $casts = [
        'birthdate' => 'date:d.m.Y',
    ];

    public function rules(): array
    {
        return [
            [['username', 'password', 'email'], 'required'],
            ['id', 'uuid'],
            ['username', 'regex', '/^[\w_-]+$/is'],
            ['email', 'email'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id): ?self
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null): ?self
    {
        return static::findOne(['access_token' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return self|null
     */
    public static function findByUsername(string $username): ?self
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return self|null
     */
    public static function findByPasswordResetToken(string $token): ?self
    {
        return static::findOne(['password_reset_token' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by verification email token
     *
     * @param string $token verify email token
     * @return self|null
     */
    public static function findByVerificationToken(string $token): ?self
    {
        return static::findOne(['verification_token' => $token, 'status' => self::STATUS_INACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function validateToken(string $token, int $expire = 0): bool
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = $expire === 0 ? time() : $expire;
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey(): string
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave(): void
    {
        $this->attributes['birthdate'] = $this->birthdate->format('Y-m-d');
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey(string $authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey(): void
    {
        $this->auth_key = StringHelper::generateRandomString(64);
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken(): void
    {
        $this->password_reset_token = StringHelper::generateRandomString(64) . '_' . time();
    }

    /**
     * Generates new token for email verification
     */
    public function generateEmailVerificationToken(): void
    {
        $this->verification_token = StringHelper::generateRandomString(64) . '_' . time();
    }

    /**
     * Generates new access token
     */
    public function generateAccessToken(): void
    {
        $this->access_token = StringHelper::generateRandomString(64) . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken(): void
    {
        $this->password_reset_token = null;
    }
}