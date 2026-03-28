<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "token".
 *
 * @property int $id
 * @property string $token
 * @property int $user_id
 * @property string $type
 * @property int $created_at
 * @property int $expires_at
 *
 * @property User $user
 */
class Token extends \yii\db\ActiveRecord
{
    const TYPE_PASSWORD_RESET = 'password_reset';
    const TYPE_PASSWORD_RESET_CODE = 'password_reset_code';
    const TYPE_EMAIL_VERIFICATION = 'email_verification';
    const TYPE_ATHLETE_INVITATION = 'athlete_invitation';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'token';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['token', 'user_id', 'type', 'created_at', 'expires_at'], 'required'],
            [['user_id', 'created_at', 'expires_at'], 'integer'],
            [['token', 'type'], 'string', 'max' => 255],
            [['token'], 'unique'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'token' => Yii::t('app', 'Token'),
            'user_id' => Yii::t('app', 'User ID'),
            'type' => Yii::t('app', 'Type'),
            'created_at' => Yii::t('app', 'Created At'),
            'expires_at' => Yii::t('app', 'Expires At'),
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getIsExpired()
    {
        return $this->expires_at < time();
    }

    public static function findByToken($token)
    {
        return self::find()->where(['token' => $token])->one();
    }

    public static function generateToken($userId, $type, $duration = null)
    {
        $token = new self();
        $token->token = $type == self::TYPE_PASSWORD_RESET_CODE ? self::generateCode() : Yii::$app->security->generateRandomString(32);
        $token->user_id = $userId;
        $token->type = $type;
        $token->created_at = time();
        $token->expires_at = intval($duration ? ($duration === PHP_INT_MAX ? $duration : time() + $duration) : time() + 3600); // Default 1 hour

        if (!$token->save()) {
            Yii::error('Could not save token: ' . json_encode($token->getErrors()), __METHOD__);
            return null;
        }

        return $token;
    }

    public function getLink()
    {
        switch ($this->type) {
            case self::TYPE_PASSWORD_RESET:
                return Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $this->token]);
            case self::TYPE_EMAIL_VERIFICATION:
                return Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $this->token]);
            case self::TYPE_ATHLETE_INVITATION:
                return Yii::$app->urlManager->createAbsoluteUrl(['site/athlete-invitation', 'token' => $this->token]);
            default:
                return null;
        }
    }

    private static function generateCode()
    {
        $code = rand(100000, 999999);

        $query = self::find()->where(['type' => self::TYPE_PASSWORD_RESET_CODE, 'token' => $code]);

        while ($query->exists()) {
            $code = rand(100000, 999999);
            $query = self::find()->where(['type' => self::TYPE_PASSWORD_RESET_CODE, 'token' => $code]);
        }

        return strval($code);
    }

}
