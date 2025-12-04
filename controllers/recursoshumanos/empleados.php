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



    function ActualizarDatosPersonales()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->ActualizarDatosPersonales($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al actualizar datos personales',
                'details' => $result
            ], 200);
        }
    }



    function ActualizarCargasEmpleado()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $input = $this->getJsonInput();

        // Usuario autenticado
        $creadoPor = $jwtData['username'] ?? 'SISTEMA';

        // Obtener cargas desde el frontend
        $cargas = $input['cargasFamiliares'] ?? [];

        // Si no es array → normalizar
        if (!is_array($cargas)) {
            $cargas = [$input];
        }

        $resultados = [];
        $errores = [];

        foreach ($cargas as $carga) {

            // Asignar empleadoId desde raíz si no viene dentro
            if (empty($carga['empleadoId'])) {
                $carga['empleadoId'] = $input['empleadoId'] ?? null;
            }

            // Asignar usuario de sesión
            $carga['Creado_Por'] = $creadoPor;

            // Llamar modelo
            $result = $this->model->ActualizarCargasEmpleado($carga);

            // Clasificar resultado
            if ($result['success']) {
                $resultados[] = $result;
            } else {
                $errores[] = [
                    'carga' => $carga['Nombres'] ?? 'Desconocido',
                    'error' => $result['error']
                ];
            }
        }

        // Responder al front
        if (empty($errores) && !empty($resultados)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cargas familiares actualizadas correctamente',
                'details' => $resultados
            ]);
        } elseif (!empty($resultados) && !empty($errores)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Algunas cargas se guardaron, otras fallaron',
                'details' => $resultados,
                'errors' => $errores
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'No se pudieron actualizar las cargas familiares',
                'details' => $errores
            ]);
        }
    }




    function ActualizarEstudios()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $input = $this->getJsonInput();

        // Usuario autenticado
        $creadoPor = $jwtData['username'] ?? 'SISTEMA';

        // Obtener cargas desde el frontend
        $estudios = $input['estudios'] ?? [];

        // Si no es array → normalizar
        if (!is_array($estudios)) {
            $estudios = [$input];
        }

        $resultados = [];
        $errores = [];

        foreach ($estudios as $estudio) {

            // Asignar empleadoId desde raíz si no viene dentro
            if (empty($estudio['empleadoId'])) {
                $estudio['empleadoId'] = $input['empleadoId'] ?? null;
            }

            // Asignar usuario de sesión
            $estudio['Creado_Por'] = $creadoPor;

            // Llamar modelo
            $result = $this->model->ActualizarEstudios($estudio);

            // Clasificar resultado
            if ($result['success']) {
                $resultados[] = $result;
            } else {
                $errores[] = [
                    'estudio' => $estudio['titulo'] ?? 'Desconocido',
                    'error' => $result['error']
                ];
            }
        }

        // Responder al front
        if (empty($errores) && !empty($resultados)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Estudios actualizados correctamente',
                'details' => $resultados
            ]);
        } elseif (!empty($resultados) && !empty($errores)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Algunos estudios se guardaron, otros fallaron',
                'details' => $resultados,
                'errors' => $errores
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'No se pudieron actualizar los estudios',
                'details' => $errores
            ]);
        }
    }



    function GetCargasEstudios()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetCargasEstudios($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener cargas estudios',
                'details' => $result
            ], 200);
        }
    }



    function ActualizarEnfermedades()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $input = $this->getJsonInput();

        // Usuario autenticado
        $creadoPor = $jwtData['username'] ?? 'SISTEMA';

        // Normalizar entrada: siempre trabajar con un array
        $datos = [$input];

        $resultados = [];
        $errores = [];

        foreach ($datos as $dato) {

            // Asegurar empleadoId
            if (empty($dato['empleadoId'])) {
                $errores[] = ['error' => 'EmpleadoID es obligatorio'];
                continue;
            }

            $dato['Creado_Por'] = $creadoPor;

            // Llamar modelo
            $result = $this->model->ActualizarEnfermedades($dato);

            if ($result['success']) {
                $resultados[] = $result;
            } else {
                $errores[] = $result;
            }
        }

        // Respuesta final
        if (empty($errores)) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Datos médicos actualizados correctamente',
                'result' => $resultados
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Algunos registros fallaron',
                'errors' => $errores
            ]);
        }
    }



    function Getenfermedades()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->Getenfermedades($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener cargas estudios',
                'details' => $result
            ], 200);
        }
    }


    function ConsultarRolesPago()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->ConsultarRolesPago($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener roles de pago',
                'details' => $result
            ], 200);
        }
    }




    function DescargarRolPago()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $data = $this->getJsonInput();
        $rolId = $data['rolId'] ?? null;

        if (!$rolId) {
            return $this->jsonResponse(['error' => 'rolId requerido'], 400);
        }

        // Generar PDF
        $result = $this->model->generarPDF($rolId);

        if ($result['success']) {
            // Devolver PDF directamente al navegador
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="ROL_' . $result['rolId'] . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $result['pdfData'];
            exit; // Importante: detener la ejecución para no agregar contenido adicional
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error'] ?? 'Error desconocido al generar PDF'
            ], 500);
        }
    }




    function ActualizarPassword()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->ActualizarPassword($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error'] ?? 'Error al actualizar la contraseña',
                'details' => $result
            ], 200);
        }
    }

}
