<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
require_once __DIR__ . '../../../../libs/EmailService.php';
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
        // $this->jsonResponse([
        //         "success" => true,
        //         "data" => $params,
        //     ], 200);
        // exit;
        $params['usrid'] = $params['userdata']['usuario'] ?? null;

        $result = $this->model->Guardar_Vacaciones_empleados($params);
        if ($result && $result['success']) {
            try {
                $emailService = new EmailService();

                $empleado = $params["userdata"]['nombre'];
                $jefe = $params["DATOS_EMPLEADO"]['Jefe'];
                $jefeCorreo = $params["DATOS_EMPLEADO"]['CorreoJefe'];
                $dia_solicitados = $params['dias'];
                $dia_inicio = $params['inicio'];
                $dia_fin = $params['fin'];
                $dia_regreso = $params['regreso'];

                // Preparar destinatarios
                $destinatarios = [
                    $jefeCorreo,
                    "jalvaradoe3@gmail.com",
                    // "sistema@cartimex.com"
                ];

                // Datos para la plantilla
                $datos = [
                    'jefe' => $jefe,
                    'empleado' => $empleado,
                    'dias_solicitados' => $dia_solicitados,
                    'dia_inicio' => date('Y-m-d', strtotime($dia_inicio)),
                    'dia_fin' => date('Y-m-d', strtotime($dia_fin)),
                    'dia_regreso' => date('Y-m-d', strtotime($dia_regreso))
                ];

                // Enviar email
                $mail = $emailService->enviar(
                    $destinatarios,
                    'Solicitud de Vacaciones -> ' . $empleado,
                    $datos,
                    'vacaciones',
                    null,
                    "Solicitud de Vacaciones",
                    [],
                    // $params["userdata"]["empresa_name"]
                    $params["userdata"]["empleado_empresa"]
                );
            } catch (Exception $e) {
                error_log("Error enviando email: " . $e->getMessage());
            }
            $this->jsonResponse([
                "success" => true,
                "message" => "Vacaciones solicitadas correctamente",
                "data" => $result,
                "email" => $mail
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }


}
