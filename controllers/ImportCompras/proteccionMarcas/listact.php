<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class listact extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'ImportCompras/proteccionMarcas'; // Especifica la carpeta donde está el modelo
        $this->loadModel('listact'); // Cargar el modelo correcto
    }

    // Consolidados

    function marca_creada(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->marca_creada($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos', 'data' => $result], 200);
        }
    }
    
    function Cargar_Actividades_por_Consolidado(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Cargar_Actividades_por_Consolidado($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos', 'datos'=> $result], 200);
        }
    }
    
    function Cargar_Gastos_Por_Consolidado(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Cargar_Gastos_Por_Consolidado($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos', 'datos'=> $result], 200);
        }
    }
    

    // Actividades Individuales

    function Actividades_Individuales(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Actividades_Individuales($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos', 'datos'=> $result], 200);
        }
    }
    
    function marca_creada2(){
        $result = $this->model->marca_creada2();
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos'], 200);
        }
    }

    function Facturas_marcas(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Facturas_marcas($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos', 'datos'=> $result], 200);
        }
    }

    function Actualizar_actividad(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Actualizar_actividad($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos', 'datos'=> $result], 200);
        }
    }
    
    function Cargar_Tipos_Marcas(){
        $result = $this->model->Cargar_Tipos_Marcas();
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos'], 200);
        }
    }
    
    function Cargar_Marcas_Editar(){
        $result = $this->model->Cargar_Marcas_Editar();
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos'], 200);
        }
    }

    function Buscar_Proteccion(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Buscar_Proteccion($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos'], 200);
        }
    }
    
    function Consolidar_Actividades(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Consolidar_Actividades($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos', 'datos'=> $result], 200);
        }
    }
    
    function GUARDAR_DATOS(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'];
        $result = $this->model->GUARDAR_DATOS($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos', 'datos'=> $result], 200);
        }
    }
    
    function GUARDAR_NOTA_CREDITO(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->GUARDAR_NOTA_CREDITO($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos'], 200);
        }
    }
    
    function Eliminar_Actividad(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Eliminar_Actividad($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos'], 200);
        }
    }
    
    function Eliminar_actividad_creada(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Eliminar_actividad_creada($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos'], 200);
        }
    }
    
    function Buscar_Documentos(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Buscar_Documentos($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos'], 200);
        }
    }
    
    function Validar_Pagos(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Validar_Pagos($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos', 'datos'=> $result], 200);
        }
    }
    
    function Agregar_Pago(){
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Agregar_Pago($params);
        if($result && $result['success']){
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse( ['success' => false, 'error' => 'Error al obtener los datos'], 200);
        }
    }
}