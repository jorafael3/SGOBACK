<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class flujo extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'financiero/flujo/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('flujo'); // Cargar el modelo correcto
    }





    function cargar_girados_no_cobrados()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->cargar_girados_no_cobrados($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos FinalizarRecepcion',
                'details' => $result
            ], 200);
        }
    }


    

    function cheques_pos_fechados()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->cheques_pos_fechados($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos FinalizarRecepcion',
                'details' => $result
            ], 200);
        }
    }

}
