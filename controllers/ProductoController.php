<?php


namespace app\controllers;

use app\models\Producto;
use app\models\Categoria;
use app\models\Marca;
use app\models\Seccion;
use app\models\ProductoCategoria;
use yii\data\Pagination;
use Exception;
use yii\db\IntegrityException;

use yii\db\ActiveQuery;


use Yii;
use yii\base\InvalidCallException;

class ProductoController extends \yii\web\Controller{

    public function beforeAction($action){

        $this->enableCsrfValidation =false;     	
        // Cambiamos el formato de respuesta a JSON
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;  

        // Deshabilita la validación CSRF, por defecto requerida en formularios 
        $this->enableCsrfValidation = false; 

        if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {         	
            Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');         	
            Yii::$app->end();     	
        }     
            

        // Mantenemos su comportamiento predeterminado	
        return parent::beforeAction($action); 	
    }
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [         	
            'class' => \yii\filters\auth\HttpBearerAuth::class,         	
            'except' => ['options']     	
        ];

        return $behaviors;
    }

    public function actionIndex(){
        $query = Producto::find();

        $pagination = new Pagination ([
            'defaultPageSize' => 5,
            'totalCount' => $query->count(),
        ]);
        $productos = $query->offset($pagination->offset)
        ->limit($pagination->limit)->all();

        return $productos;

    }
    
    /**
     * Editar un producto
     * @param int $id El ID del registro a editar
    */
    public function actionActualizar($id) 
    {
        $body = Yii::$app->request->getBodyParams();

    // Buscamos el registro a editar
    // Equivalente a “SELECT * FROM producto WHERE id=$id”
    // Equivalente a Producto::find()->where(['id' => $id])->one();
        $producto = Producto::findOne($id);

        $producto->load($body,'');
        if ($producto->save()) {
        return $producto;
    } else {
        return $producto->errors;
    }
    }


    public function actionRecuperar($id){
        $producto = Producto::findOne($id);
        return $producto;
    }

    public function actionEliminarProducto($id){
        $producto = Producto::findOne($id);
        
        if($producto){
            try{
                $producto->delete();
                $response = "Producto eliminado correctamente"; 
            }catch(IntegrityException $e){
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'message' => 'El producto esta en uso',
                    'code' => $e->getCode(),
                ];
            }
        }else{
            throw new \yii\web\NotFoundHttpException("El producto no existe en los registros");
        }
        return $response;
        
    }

    public function actionObtenerProductos(){
        $productos = Producto::find()->all();
        return $productos;
    }

    public function actionCrearProductos(){
        $producto = new Producto();
        $body = Yii::$app->request->getBodyParams();
        $producto->load($body,'');
        if($producto->save()){
            return $producto;
        }else{
            return $producto->errors;
        }
    }

    //$cantidad de productos a mostrar en cada pagina

    public function actionPaginacion(){
            $query = Producto::find();
            $count = $query->count();
            $cantidadProductosPorPagina = 30;
            $pagination= new Pagination(['defaultPageSize'=>$cantidadProductosPorPagina,'totalCount'=>$count,]);
            $articles = $query->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();
            return  /*$articles;*/$respuesta=[
                "datos" =>  $articles,
                "paginas" => $count,
                "cantidad" => $cantidadProductosPorPagina
            ];
    }
    
    //Un servicio que devuelva una sección según su ID con todos los productos pertenecientes a la sección 

    public function actionSeccion($id){
        $seccion = Seccion::findOne($id);
        if($seccion){
            $productos = Producto::find()->where(['seccion_id' => $id])->all();
            $respuesta = [
                'success' => true,
                'message' => "La accion se realizo correctamente",
                
                "id" => $seccion->id,
                "codigo" => $seccion->codigo,
                "descripcion" => $seccion->descripcion,
                "productos" => $productos
                    
            ];
        }else{
            throw new \yii\web\NotFoundHttpException("La seccion no existe en los registros");
        }
        return $respuesta;
    }

    /*
        Un servicio que sume la cantidad de productos de
        una marca (suma de stocks)
    */
    //datos prueba: 92+80= 172    marca 702
    public function actionTotalMarca($idMarca){
        $marca = Marca::findOne($idMarca);
        if($marca){
            $productos = Producto::find()
            ->where(['marca_id' => $idMarca])
            ->all();
            $suma = 0;
            foreach($productos as $p){
                $suma += $p->stock;
            }
            $respuesta = [
                'success' => true,
                'message' => "Se obtuvo la cantidad total de productos de la marca",
                'data' => [
                    "id" => $idMarca,
                    "marca" => $marca->nombre,
                    "descripcion" => $marca->descripcion,
                    "cantidad de productos" => $suma
                ]
            ];
        }else{
            throw new \yii\web\NotFoundHttpException("La marca no existe en los registros");
        }
        return $respuesta;
    }


    /*
        Un servicio que devuelva el producto con el mayor stock
    */
    public function actionMayorStock(){
        $mayor = Producto::find()->max('stock');
        //return ["stock" => $mayor];
        $productos = Producto::find()->where(['stock' => $mayor])->all();
        return [
            'success' => true,
            'data' => [
                "maximo stock" => $mayor,
                "productos" => $productos
                ]
        ];
    }

    /*
    Un servicio que verifique si un producto tiene stock (stock > 0)
    */
    public function actionStockMayorCero($id){
        $producto = Producto::findOne($id);
        if($producto){
            if($producto->stock > 0){
                $respuesta = [
                    'success' => true,
                    'message' => "El producto tiene stock mayor a cero",
                    'data' => [
                        "nombre"=>$producto->nombre,
                        "stock" => $producto->stock
                        ]
                ];
            }else{
                $respuesta = [
                    'success' => false,
                    'message' => "El producto no tiene stock mayor a cero",
                    'data' => [
                        "nombre"=>$producto->nombre,
                        "stock" => $producto->stock
                        ]
                ];
            }
        }else{
            throw new \yii\web\NotFoundHttpException("el producto de $id no existe en los registros");
        }
        return $respuesta;
    }

    /*
    *Enlace de productos con categorias
    */
    public function actionEnlazarProductoCategoria($idProducto, $idCategoria){// $idProducto, $idCategoria
        $producto = Producto::findOne($idProducto);
        if($producto){
            $categoria = Categoria::findOne($idCategoria);
            if($categoria){
                if($producto->getCategorias()->where(['id' => $idCategoria])->one()){
                    $respuesta = ['mensaje' => 'enlace existente'];
                }else{
                    try{
                        $producto->link('categorias', $categoria);
                        $respuesta = [
                            'success' => true,
                            'message' => "El enlace del producto a la categoria se realizo correctamente"
                        ];

                    }catch(Exception $e){
                        Yii::$app->getResponse()->setStatusCode(500);
                        $respuesta = [
                            'success' => false,
                            'mensaje' => $e->getMessage(),
                            'error' => $e
                        ];
                    }
                }
            }else{
                throw new \yii\web\NotFoundHttpException("La categoria de id: $idCategoria no existe en los registros");
            }
        }else{
            throw new yii\web\NotFoundHttpException("El producto de id $idProducto no existe en los registros");
        }
        return $respuesta;
    }

    /*
    * Desenlace de productos con categorias
    */


    public function actionDesenlazarProductoCategoria($idProducto, $idCategoria){
        $producto = Producto::findOne($idProducto);
        if($producto){
            $categoria = Categoria::findOne($idCategoria);
            if($categoria){
                $categorias = $producto->getCategorias();
                $enlace = $categorias->where(['id' => $idCategoria])->one();
                if($enlace){
                    try{
                        $producto->unlink('categorias',$categoria,true);  //yii\base\InvalidCallException
                        $respuesta = [
                            'success' => true,
                            'message' => "El desenlace del producto con la categoria se realizo correctamente"
                        ];
                    }catch(Exception $e){
                        Yii::$app->getResponse()->setStatusCode(500);
                        $respuesta = [
                            'success' => false,
                            'message' => $e->getMessage()
                        ];
                    }
                }else{
                    $respuesta = [
                        'mensaje' => 'no existe el enlace'
                    ];


                }
            }else{
                throw new \yii\web\NotFoundHttpException("La categoria de id: $idCategoria no existe en los registros");
            }
        }else{
            throw new \yii\web\NotFoundHttpException("El producto de id $idProducto no existe en los registros");
        }
        return $respuesta;
    }

}