<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class solicitarvacaciones extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'recursoshumanos/vacaciones/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('solicitarvacaciones'); // Cargar el modelo correcto
    }

    function Cargar_Vaciones_Pedidas()
    {
        // $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        // if (!$jwtData) {
        //     return; // La respuesta de error ya fue enviada automáticamente
        // }

        $params = $this->getJsonInput();
        $result = $this->model->Cargar_Vaciones_Pedidas($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }

    function GetDatosPersonales()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->getDatosPersonales($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos personales',
                'details' => $result
            ], 200);
        }
    }

    function GetVacaciones()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetVacaciones($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener vacaciones',
                'details' => $result
            ], 200);
        }
    }
}
