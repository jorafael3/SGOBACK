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




    function GetSolicitudesCargasFamiliares()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->GetSolicitudesCargasFamiliares($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener GetSolicitudesCargasFamiliares',
                'details' => $result
            ], 200);
        }
    }



    function GetSolicitudesEnfermedades()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->GetSolicitudesEnfermedades($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener GetSolicitudesEnfermedades',
                'details' => $result
            ], 200);
        }
    }




    function AprobarCargaFamiliar()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->AprobarCargaFamiliar($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener AprobarCargaFamiliar',
                'details' => $result
            ], 200);
        }
    }





    function RechazarCargaFamiliar()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->RechazarCargaFamiliar($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener RechazarCargaFamiliar',
                'details' => $result
            ], 200);
        }
    }



    function AprobarDatosMedicos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->AprobarDatosMedicos($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener AprobarDatosMedicos',
                'details' => $result
            ], 200);
        }
    }




    function RechazarDatosMedicos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->RechazarDatosMedicos($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener AprobarDatosMedicos',
                'details' => $result
            ], 200);
        }
    }



    function GetSolicitudesDatosPersonales()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->GetSolicitudesDatosPersonales($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener AprobarDatosMedicos',
                'details' => $result
            ], 200);
        }
    }





    function AprobarDatosPersonales()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->AprobarDatosPersonales($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener AprobarDatosMedicos',
                'details' => $result
            ], 200);
        }
    }




    function RechazarDatosPersonales()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->RechazarDatosMedicos($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener AprobarDatosMedicos',
                'details' => $result
            ], 200);
        }
    }



    function GetSolicitudesEstudios()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->GetSolicitudesEstudios($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener GetSolicitudesEstudios',
                'details' => $result
            ], 200);
        }
    }




      function AprobarEstudio()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->AprobarEstudio($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener AprobarDatosMedicos',
                'details' => $result
            ], 200);
        }
    }




    function RechazarEstudio()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        $data = $this->getJsonInput();

        $result = $this->model->RechazarEstudio($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener AprobarDatosMedicos',
                'details' => $result
            ], 200);
        }
    }
}
