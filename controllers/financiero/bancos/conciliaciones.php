<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class conciliaciones extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'financiero/bancos/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('conciliaciones'); // Cargar el modelo correcto
    }

    function ComprobarConciliaciones()
    {
        $params = $this->getJsonInput();
        $result = $this->model->ComprobarConciliaciones($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }
    
    function Conciliaciones()
    {
        $params = $this->getJsonInput();
        $result = $this->model->Conciliaciones($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }

}
