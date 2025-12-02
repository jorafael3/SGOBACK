<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class parametros extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'financiero/opciones'; // Especifica la carpeta donde está el modelo
        $this->loadModel('parametros'); // Cargar el modelo correcto
    }

    function Cargar_OPT_Param()
    {
        $result = $this->model->Cargar_OPT_Param();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener los parámetros',
                "respuesta" => $result
            ], 200);
        }
    }
    
    function Guardar_OPT_Param()
    {
        $params = $this->getJsonInput();
        $result = $this->model->Guardar_OPT_Param($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar los parámetros',
                "respuesta" => $result
            ], 200);
        }
    }
}