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
        // $param['EmpleadoID'] = $param['userdata']['EmpleadoID'] ?? null;
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

    function Guardar_Vacaciones_empleados()
    {
        // $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        // if (!$jwtData) {
        //     return; // La respuesta de error ya fue enviada automáticamente
        // }

        $params = $this->getJsonInput();
        $param['usrid'] = $param['userdata']['usuario'] ?? null;
        $result = $this->model->Guardar_Vacaciones_empleados($params);
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
