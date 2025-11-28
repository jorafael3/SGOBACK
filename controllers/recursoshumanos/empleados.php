<?php

require_once __DIR__ . '/../../libs/JwtHelper.php';

class Empleados extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'recursoshumanos/';
        $this->loadModel('empleados');
    }

    /**
     * Obtener datos personales del empleado
     */
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

    /**
     * Obtener cargas familiares del empleado
     */
    function GetCargasFamiliares()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetCargasFamiliares($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener cargas familiares',
                'details' => $result
            ], 200);
        }
    }

    /**
     * Obtener vacaciones del empleado
     */
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


    function SolicitudActualizacionDatos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->SolicitudActualizacionDatos($data);

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
