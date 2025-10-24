<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class Usuarios extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'mantenimiento/usuarios/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('usuarios'); // Cargar el modelo correcto
    }

    function GetUsuarios()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->getUsuarios($data);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener facturas guías pickup',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function GetDepartamentosLogistica()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->getDepartamentosLogistica();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener facturas guías pickup',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function CrearUsuario()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();



        $VALIDAR_USUARIO = $this->model->ValidarUsuarioCreado($data);

        if ($VALIDAR_USUARIO && $VALIDAR_USUARIO['success'] && count($VALIDAR_USUARIO['data']) > 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'El usuario ya existe',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $VALIDAR_USUARIO
            ], 200);
            return;
        }
        $CrearUsuario = $this->model->CrearUsuario($data);

        if ($CrearUsuario && $CrearUsuario['success']) {
            $CrearUsuario['message'] = 'Usuario creado exitosamente';
            $this->jsonResponse($CrearUsuario, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al crear usuario',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $CrearUsuario
            ], 200);
        }
    }
}
