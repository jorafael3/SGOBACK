<?php

require_once __DIR__ . '/../../libs/JwtHelper.php';

class Rolespagos extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'recursoshumanos/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('rolespagos'); // Cargar el modelo correcto
    }




    function enviarrolespagos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->enviarrolespagos($data);

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



    function BuscarRolIndividual()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->BuscarRolIndividual($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener BuscarRolIndividual',
                'details' => $result
            ], 200);
        }
    }




    function EnviarRolIndividual()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $data = $this->getJsonInput();

        // Enviar correo con el PDF
        $result = $this->model->enviarRolCorreo($data);

        if ($result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error'] ?? 'Error desconocido al enviar el correo'
            ], 500);
        }
    }


}
