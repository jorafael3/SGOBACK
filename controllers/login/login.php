<?php
// ...existing code...
// Este archivo fue movido a controllers/login/login.php
// Copia aquí el contenido original de tu controlador Login
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
// header("Access-Control-Allow-Origin: http://localhost:4200");
//     header("Access-Control-Allow-Origin: *");
//     header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
//     header("Access-Control-Allow-Headers: Content-Type, Authorization");
//     http_response_code(200);
//     exit;
// }
// =====================================================
// ARCHIVO: controllers/login.php
// =====================================================

/**
 * Controlador de Login
 */
require_once __DIR__ . '/../../libs/JwtHelper.php';
require_once __DIR__ . '../../../libs/EmailService.php';


class Login extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'login'; // Especifica la carpeta donde está el modelo
        $this->loadModel('login'); // Cargar el modelo de login
    }

    // public function render()
    // {
    //     // Si ya está logueado, redirigir
    //     if ($this->isAuthenticated()) {
    //         header('Location: ' . URL . 'principal');
    //         exit;
    //     }

    //     $this->view->assign('csrf_token', $this->generateCsrfToken());
    //     $this->view->render('login/index');
    // }

    public function authenticate()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(["success" => false, 'error' => 'Método no permitido'], 405);
                return;
            }

            // Validar CSRF
            // if (!$this->validateCsrf()) {
            //     $this->jsonResponse(['error' => 'Token CSRF inválido', 'csrf_token' => $this->generateCsrfToken()], 403);
            //     return;
            // }

            $data = $this->getJsonInput();
            $username = trim($data['username']) ?? null;
            $password = trim($data['password']) ?? null;
            $empresa_code = trim($data['empresa_code']) ?? null;

            // echo json_encode($data);
            // exit;

            if (empty($username) || empty($password)) {
                $this->jsonResponse(["success" => false, 'error' => 'Usuario y contraseña son requeridos'], 400);
                return;
            }

            if (empty($empresa_code)) {
                $this->jsonResponse(["success" => false, 'error' => 'Código de empresa es requerido'], 400);
                return;
            }

            // Configurar el modelo para usar la empresa específica
            $this->model->setEmpresa($empresa_code);

            $result = $this->model->authenticate($username, $password, $empresa_code);
            // $result =  ['success' => true, 'message' => 'Método no implementado'];

            if ($result['success']) {
                // Generar JWT
                $token = $this->generateJwtToken($result['user_data']);
                $result['user_data']['token'] = $token;
                $this->jsonResponse($result, 200);
            } else {
                $this->jsonResponse($result, 200);
            }
        } catch (Exception $e) {
            error_log("Error en authenticate: " . $e->getMessage());
            $this->jsonResponse(["success" => false, 'error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Genera un JWT para el usuario autenticado
     */
    protected function generateJwtToken($user)
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $expTime = TOKEN_EXPIRATION; // Tiempo de expiración en segundos (puedes cambiarlo)
        $payload = json_encode([
            'sub' => $user['usrid'],
            'username' => $user['usuario'],
            'empresa' => $user['empresa'],
            'empresa_name' => $user['empresa_name'] ?? 'Empresa Desconocida',
            'iat' => time(),
            'exp' => time() + $expTime // Expira en $expTime segundos
        ]);
        $base64UrlHeader = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function logout()
    {
        session_start();
        session_unset();
        session_destroy();

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => true, 'message' => 'Logout exitoso']);
        } else {
            header('Location: ' . URL . 'login');
            exit;
        }
    }


    function validate_cedula()
    {
        $data = $this->getJsonInput();
        $cedula = $data['cedula'] ?? '';
        $empresa = $data['empresa'] ?? '';

        // Validar que solo contenga dígitos numéricos (sin letras, espacios, guiones, etc.)
        if (!preg_match('/^\d{10}$/', $cedula)) {
            echo json_encode([
                'success' => false,
                "message" => 'La cédula debe contener exactamente 10 dígitos numéricos, sin letras, espacios o caracteres especiales.'
            ]);
            exit();
        }

        $isValid = $this->validarCedulaEcuatoriana($cedula);
        if (!$isValid) {
            echo json_encode([
                'success' => $isValid,
                "message" => 'Cédula no válida, por favor verifique.'
            ]);
            exit();
        }

        $buscarCedulaDobra = $this->model->buscarCedulaDobra($cedula, $empresa);
        if ($buscarCedulaDobra['success'] && count($buscarCedulaDobra['data']) == 0) {
            echo json_encode([
                'success' => false,
                "message" => 'Cédula no encontrada en la base de datos de Dobra.'
            ]);
            exit();
        }


        $email1 = $buscarCedulaDobra["data"][0]["email"];
        $email2 = $buscarCedulaDobra["data"][0]["email_personal"];
        $empleadoid = $buscarCedulaDobra["data"][0]["ID"];
        $generateCode = rand(100000, 999999);


        // $validarUsuarioExistente = $this->model->validarUsuarioExistente($empleadoid, $empresa);
        // if ($validarUsuarioExistente['success'] && count($validarUsuarioExistente['data']) > 0) {
        //     echo json_encode([
        //         'success' => false,
        //         "message" => 'Ya existe un usuario registrado para esta cédula.'
        //     ]);
        //     exit();
        // }

        if ($email1 == "" && $email2 == "") {
            echo json_encode([
                'success' => false,
                "message" => 'No hay correos electrónicos asociados a esta cédula, por favor contacte a RRHH.'
            ]);
            exit();
        }



        $generar_codigo = $this->model->GenerarCodigo($empleadoid, $empresa, $generateCode);
        $emailService = new EmailService();
        // Preparar destinatarios
        $destinatarios = [];


        if ($email1 == $email2) {
            if (filter_var($email1, FILTER_VALIDATE_EMAIL)) {
                $destinatarios[] = $email1;
            }
        } else {
            if (filter_var($email1, FILTER_VALIDATE_EMAIL)) {
                $destinatarios[] = $email1;
            }
            if (filter_var($email2, FILTER_VALIDATE_EMAIL)) {
                $destinatarios[] = $email2;
            }
        }

        // Datos para la plantilla
        $datos = [
            'empleado_nombre' => $buscarCedulaDobra['data'][0]['Nombre'] ?? 'Empleado',
            'codigo' => $generateCode,
        ];
        // Enviar email
        $mail = $emailService->enviar(
            $destinatarios,
            'Codigo nuevo usuario: ',
            $datos,
            'codigo_usuario',
            null,
            "Codigo para nuevo usuario",
            [],
            $data["empresa"]
        );
        // Si es válida, devolver éxito
        echo json_encode([
            'success' => true,
            "message" => 'Cédula válida.',
            "data" => $buscarCedulaDobra["data"],
            "empresa" => $empresa,
            "empleadoid" => $empleadoid,
            "codigo_generado" => $generar_codigo,
            "emails_enviados_a" => $destinatarios
        ]);
        exit();
    }

    function validate_codigo()
    {
        $data = $this->getJsonInput();
        $codigo = $data['code'] ?? '';
        $empresa = $data['empresa'] ?? '';
        $empleadoId = $data['empleadoId'] ?? '';
        $nombre = $data['nombre'] ?? '';


        $validate_codigo = $this->model->validate_codigo($codigo, $empresa, $empleadoId);
        // echo json_encode($validate_codigo);
        // exit();
        if ($validate_codigo['success'] && count($validate_codigo['data']) == 0) {
            echo json_encode([
                'success' => false,
                "message" => 'Código no encontrado en la base de datos de Dobra.'
            ]);
            exit();
        }
        $variantesUsuario = $this->generarVariantesUsuario($nombre);
        $varianteseleccionada = "";
        for ($i = 0; $i < count($variantesUsuario); $i++) {
            $validarUsuario = $this->model->ValidarNombreUsuario($variantesUsuario[$i], $empresa);
            if ($validarUsuario['data'][0]['count'] > 0) {
                $variantesUsuario[$i] = null; // Marca como no disponible
            } else {
                $varianteseleccionada = $variantesUsuario[$i];
                break; // Salir del ciclo si se encuentra una variante disponible
            }
        }


        // Si es válida, devolver éxito
        echo json_encode([
            'success' => true,
            "message" => 'Código válido.',
            "data" => $validate_codigo["data"],
            // "variantes_usuario" => $variantesUsuario,
            "nombre_usuario" => strtoupper($varianteseleccionada),
        ]);
        exit();
    }

    function generarVariantesUsuario($nombreCompleto, $cantidad = 5)
    {
        $nombreCompleto = strtolower(trim($nombreCompleto));

        // Quitar acentos
        $nombreCompleto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $nombreCompleto
        );

        $p = preg_split('/\s+/', $nombreCompleto);

        // Estructura esperada:
        // APELLIDO1 APELLIDO2 NOMBRE1 NOMBRE2
        $apellido1 = $p[0];
        $nombre1   = $p[2];
        $nombre2   = $p[3] ?? '';

        $variantes = [];

        // 1) jalvarado
        $variantes[] = substr($nombre1, 0, 1) . $apellido1;

        // 2) jralvarado
        $baseNumerica = substr($nombre1, 0, 1)
            . substr($nombre2, 0, 1)
            . $apellido1;

        $variantes[] = $baseNumerica;

        // 3+) jralvarado1, jralvarado2, ...
        $i = 1;
        while (count($variantes) < $cantidad) {
            $variantes[] = $baseNumerica . $i;
            $i++;
        }

        return $variantes;
    }

    function validarCedulaEcuatoriana($cedula)
    {
        // Quitar espacios
        $cedula = trim($cedula);

        // Debe tener exactamente 10 dígitos
        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        // Provincia válida (01 - 24)
        $provincia = intval(substr($cedula, 0, 2));
        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        // Tercer dígito menor a 6
        $tercerDigito = intval($cedula[2]);
        if ($tercerDigito >= 6) {
            return false;
        }

        // Dígito verificador
        $digitoVerificador = intval($cedula[9]);
        $suma = 0;

        for ($i = 0; $i < 9; $i++) {
            $valor = intval($cedula[$i]);

            // Posiciones impares (0,2,4,6,8)
            if ($i % 2 === 0) {
                $valor *= 2;
                if ($valor > 9) {
                    $valor -= 9;
                }
            }

            $suma += $valor;
        }

        $resultado = (10 - ($suma % 10)) % 10;

        return $resultado === $digitoVerificador;
    }

    function registrar_usuario()
    {
        $data = $this->getJsonInput();

        // echo json_encode($data);
        // exit();

        $result = $this->model->registrar_usuario($data);
        if ($result['success']) {

            $menu = $this->model->Registrar_Menus($result["data"][0]["usrid"], $data["empresa"]);

            $this->jsonResponse([
                'success' => true,
                'message' => 'Usuario registrado exitosamente.',
                'data' => $menu,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }
}
