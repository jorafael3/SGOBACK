<?php

require_once __DIR__ . '/../../libs/JwtHelper.php';

class Administrador extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'recursoshumanos/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('administrador'); // Cargar el modelo correcto    
    }




    function GetEmpleadosPorEmpresa()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->GetEmpleadosPorEmpresa($data);

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



    function getEmpleadoIndividual()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->getEmpleadoIndividual($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener getEmpleadoIndividual',
                'details' => $result
            ], 200);
        }
    }







}
