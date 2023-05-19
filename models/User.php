<?php

namespace app\models;

use Exception;
use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $nombres
 * @property string $username
 * @property string|null $password
 * @property string $password_hash
 * @property string $access_token
 * @property string|null $auth_key
 */
class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface 
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    public function beforeAction($action) 	
    {     	
        if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {         	
            Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');         	
            Yii::$app->end();     	
        }     	     	
        return parent::beforeAction($action); 	
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombres', 'username', 'password_hash', 'access_token'], 'required'],
            [['nombres', 'username', 'password', 'password_hash', 'access_token', 'auth_key'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombres' => 'Nombres',
            'username' => 'Username',
            'password' => 'Password',
            'password_hash' => 'Password Hash',
            'access_token' => 'Access Token',
            'auth_key' => 'Auth Key',
        ];
    }


    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        $user = User::findOne($id);
        return $user ? new static($user) : null;
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $user = User::findOne(['access_token' => $token]);     	
        if ($user) {      
        // Evita mostrar el token de usuario   	
        $user->access_token = null; 
        // Almacena el usuario en Yii::$app->user->identity  
        return new static($user);     	
        }     	
        return null; // Almacena null en Yii::$app->user->identity
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        $user = User::findOne(['username' => $username]);
        return $user ? new static($user) : null;
    }
    

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === $password;
    }

    public function actionLogin() 	{
        $params = Yii::$app->getRequest()->getBodyParams();      	
        try {         	
        $username = isset($params['username']) ? $params['username'] : null;         	
        $password = isset($params['password']) ? $params['password'] : null;          	
        $user = User::findOne(['username' => $username]);    
                  
        if ($user) {             	
            // Verificamos la contraseña
            if(Yii::$app->security->validatePassword($password, $user->password_hash)) {
                //Si las contraseñas coinciden directo devolvemos la respuesta
                $response = [
                    'success' => true,
                    'accessToken' => $user->access_token
                ];  
                return $response;        	
                }          	
                }     
            
            // Si no se encuentra el usuario u no coinciden las contraseñas envia error
            Yii::$app->getResponse()->setStatusCode(400);
            $response = [             
                'success' => false,             	
                'message' => 'Usuario y/o Contraseña incorrecto.'         	
            ];                  	
        } catch(Exception $e) {         	
            Yii::$app->getResponse()->setStatusCode(500);         	
            $response = [             
                'success' => false,             	
                'message' => $e->getMessage(),             	
                'code' => $e->getCode(),         	
            ];     	
        }              	
        return $response; 	
    }


}


