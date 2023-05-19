<?php

namespace app\controllers;
use Exception;

use app\models\User;

use Yii;

class UserController extends \yii\web\Controller{

    public function behaviors() 	
    {     	
        $behaviors = parent::behaviors();  
        //add Bearer authentication filter     	
       /*$behaviors['authenticator'] = [         	
            'class' => \yii\filters\auth\HttpBearerAuth::class, 
            'except' => ['options','login','asignar','register'] 
        ];*/

        /*$behaviors['access'] = [         	
            'class' => \yii\filters\AccessControl::class,
            'only' => ['users'], // acciones a las que se aplicará el control
            'except' => ['options'],	// acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['users'], // acciones que siguen esta regla
                    'roles' => ['verUsuario'] // control por roles  permisos
            ]
            ],
             ];
*/
        
        return $behaviors;
    }

    public function beforeAction($action){

        $this->enableCsrfValidation =false;     	
        // Cambiamos el formato de respuesta a JSON
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;  

        if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {         	
            Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');         	
            Yii::$app->end();     	
        }  

        // Mantenemos su comportamiento predeterminado	
        return parent::beforeAction($action); 	
    }

    public function actionUsers(){
        $users = User::find()->all();
        return $users;

    }

    public function actionLogin() 	{
        $params = Yii::$app->getRequest()->getBodyParams();      	
        try {         	
        $username = isset($params['username']) ? $params['username'] : null;         	
        $password = isset($params['password']) ? $params['password'] : null;          	
        $user = User::findOne(['username' => $username]);   
        if ($user){             	
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
        }catch(Exception $e){         	
            Yii::$app->getResponse()->setStatusCode(500);         	
            $response = [             
                'success' => false,             	
                'message' => $e->getMessage(),             	
                'code' => $e->getCode(),         	
            ];     	
        }              	
        return $response; 	
    }

    public function actionCrearUsuario(){
        $parametros = Yii::$app->request->getBodyParams();
        $parametros['password'] = Yii::$app->getSecurity()->generatePasswordHash( $parametros['password'] );
        $user = new User();
        $user->load($parametros);
        if($user->save()){
            $respuesta = [
                'success'=> true,
                'mensaje' => 'usuario registrado'
            ];
        }else{
            $respuesta = [
                'success'=> false,
                'mensaje' => 'usuario  no registrado'
            ];
        }
        return $respuesta;
    }
    
    public function actionRegister($rol) {
        $body = Yii::$app->getRequest()->getBodyParams();
        $contrasenia =  Yii::$app->getRequest()->getBodyParam('password');
        $encriptado = Yii::$app->getSecurity()->generatePasswordHash($contrasenia);
        $token = Yii::$app->security->generateRandomString();
       
        $user = new User();
        $user->load($body,'');
        $user->password = $contrasenia;
        $user->password_hash = $encriptado;
        $user->access_token = $token;

        if($user->save()){
            // Yii::$app->authManager usa la clase ManagerInterface
            $auth = Yii::$app->authManager;
            $role = $auth->getRole($rol);
            $auth->assign($role, $user->id); 
            $respuesta = [
                'ID' => $user->id,
                'mensaje' => $user
            ];
        }else{
            $respuesta = [
                'mensaje' => 'el usuario no se registro'
            ];
        }
        return $respuesta;
    }

    public function actionRoles($rol){
        // Yii::$app->authManager usa la clase ManagerInterface
        $auth = Yii::$app->authManager;

        // Crear un rol
        $role = $auth->createRole($rol);
        $auth->add($role);
        return $auth;
    }

    //crear un nuevo permiso
    public function actionPermisos($permiso){
        // Yii::$app->authManager usa la clase ManagerInterface
        $auth = Yii::$app->authManager;

        // Crear un permiso
        $permission = $auth->createPermission($permiso);
        $permission->description = $permiso;
        $auth->add($permission); // Guardar el permiso
        return $auth;
    }
    //asignar el permiso $permission = verUsuario a un usuario de idUsuario

    public function actionAsignar(){
        $auth = Yii::$app->authManager;
        $permission = $auth->getPermission('verUsuario');
        $idUsuario = 10;
        $auth->assign($permission, $idUsuario); 
    }
    //public function action
}