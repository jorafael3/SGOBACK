<?php

require_once __DIR__ . '/../../libs/JwtHelper.php';

class Admivacaciones extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'recursoshumanos/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('admivacaciones'); // Cargar el modelo correcto
    }



    function getvacaciones()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->getvacaciones($data);

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




    function generarpdfvacacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->generarpdfvacacion($data);

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
