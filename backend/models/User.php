<?php

namespace backend\models;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;
use Yii;
use yii\db\Expression;
use yii\web\Request as WebRequest;

/**
 * Class User
 * @property integer $access_token_expired_at
 * @package api\models\user
 */
class User extends \common\models\User
{

    /**
     * Store JWT token header items.
     * @var array
     */
    protected static $decodedToken;

    /** @var  string to store JSON web token */
    public $access_token;

    public function fields()
    {
        return [
            'id',
            'username',
            'email',
            'roleLabel',
            'status'
        ];
    }

    /**
     * Logins user by given JWT encoded string. If string is correctly decoded
     * - array (token) must contain 'jti' param - the id of existing user
     * @param string $accessToken access token to decode
     * @return mixed|null          User model or null if there's no user
     * @throws \yii\web\ForbiddenHttpException if anything went wrong
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $secret = static::getSecretKey();
        // Decode token and transform it into array.
        // Firebase\JWT\JWT throws exception if token can not be decoded
        try {
            $headers = new stdClass();
            $decoded = JWT::decode($token, new Key($secret, static::getAlgo()), $headers);
        } catch (\Exception $e) {
            return false;
        }
        static::$decodedToken = (array)$decoded;
        // If there's no jti param - exception
        if (!isset(static::$decodedToken['jti'])) {
            return false;
        }
        // JTI is unique identifier of user.
        // For more details: https://tools.ietf.org/html/rfc7519#section-4.1.7
        $id = static::$decodedToken['jti'];

        return static::findByJTI($id);
    }

    public static function findByJTI($id)
    {
        /** @var User $user */
        $user = static::find()->where([
            '=',
            'id',
            $id
        ])
            ->andWhere([
                '>',
                'access_token_expired_at',
                new Expression('UNIX_TIMESTAMP()')
            ])->one();


        return $user;
    }

    /**
     * Generate access token
     *  This function will be called every on request to refresh access token.
     *
     *
     * @return bool whether the access token is generated or not
     */
    public function generateAccessTokenAfterUpdatingClientInfo()
    {
        // generate access token
        $this->generateAccessToken();

        $this->save(false);
        return true;
    }

    public function generateAccessToken()
    {
        // generate access token
//        $this->access_token = Yii::$app->security->generateRandomString();
        $tokens = $this->getJWT();
        $this->access_token = $tokens[0];   // Token
        $this->access_token_expired_at = $tokens[1]['exp']; // Expire
        $this->save(false);
    }

    /*
     * JWT Related Functions
     */

    /**
     * Encodes model data to create custom JWT with model.id set in it
     * @return array encoded JWT
     */
    public function getJWT()
    {
        // Collect all the data
        $secret = static::getSecretKey();
        $currentTime = time();
//        $expire = $currentTime + 86400; // 1 day
        $expire = PHP_INT_MAX;
        $request = Yii::$app->request;
        $hostInfo = '';
        // There is also a \yii\console\Request that doesn't have this property
        if ($request instanceof WebRequest) {
            $hostInfo = $request->hostInfo;
        }

        // Merge token with presets not to miss any params in custom
        // configuration
        $token = array_merge([
            'iat' => $currentTime,
            // Issued at: timestamp of token issuing.
            'iss' => $hostInfo,
            // Issuer: A string containing the name or identifier of the issuer application. Can be a domain name and can be used to discard tokens from other applications.
            'aud' => $hostInfo,
            'nbf' => $currentTime,
            // Not Before: Timestamp of when the token should start being considered valid. Should be equal to or greater than iat. In this case, the token will begin to be valid 10 seconds
            'exp' => $expire,
            // Expire: Timestamp of when the token should cease to be valid. Should be greater than iat and nbf. In this case, the token will expire 60 seconds after being issued.
            'data' => [
                'username' => $this->username,
//                'roleLabel' => $this->getRoleLabel(),
            ]
        ], static::getHeaderToken());
        // Set up id
        $token['jti'] = $this->getJTI();    // JSON Token ID: A unique string, could be used to validate a token, but goes against not having a centralized issuer authority.
        return [JWT::encode($token, $secret, static::getAlgo()), $token];
    }

    protected static function getHeaderToken()
    {
        return [];
    }

    /**
     * Getter for encryption algorytm used in JWT generation and decoding
     * Override this method to set up other algorytm.
     * @return string needed algorytm
     */
    public static function getAlgo()
    {
        return 'HS256';
    }


    protected static function getSecretKey()
    {
        return Yii::$app->params['jwtSecretCode'];
    }

    // And this one if you wish

    /**
     * Returns some 'id' to encode to token. By default is current model id.
     * If you override this method, be sure that findByJTI is updated too
     * @return integer any unique integer identifier of user
     */
    public function getJTI()
    {
        return $this->getId();
    }

}
