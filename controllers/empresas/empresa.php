<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class Empresa extends Controller
{
    /**
     * Constructor de la clase Empresa
     * Inicializa el modelo de empresa
     */
    public function __construct()
    {
        parent::__construct();
        // $this->loadModel('empresa');
    }

    /**
     * Crea una nueva empresa en la base de datos
     * Requiere autenticación JWT y método POST
     * Los datos de la empresa se reciben por JSON en el body
     * Retorna JSON con el resultado de la operación
     */
    public function create()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);

        if (!JwtHelper::validateJwt($jwt)) {
            $this->jsonResponse(["success" => false, 'error' => 'Token JWT inválido o expirado'], 401);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(["success" => false, 'error' => 'Método no permitido'], 405);
            return;
        }

        $data = $this->getJsonInput();
        $razon_social = $data['razon_social'] ?? null;
        $nombre_comercial = $data['nombre_comercial'] ?? null;
        $ruc = $data['ruc'] ?? null;
        $pais = $data['pais'] ?? null;
        $moneda = $data['moneda'] ?? 'USD';
        $zona_horaria = $data['zona_horaria'] ?? 'America/Guayaquil';
        $logo_url = $data['logo_url'] ?? null;
        $plan_id = $data['plan_id'] ?? 1;
        $fecha_expira = $data['fecha_expira'] ?? null;
        $estado = $data['estado'] ?? 'A';
        $usuario_admin_id = $data['usuario_admin_id'] ?? null;
        $creado_por = $data['creado_por'] ?? null;

        if (empty($razon_social) || empty($plan_id)) {
            $this->jsonResponse(["success" => false, 'error' => 'Faltan datos obligatorios'], 400);
            return;
        }

        $result = $this->model->createEmpresa([
            'razon_social' => $razon_social,
            'nombre_comercial' => $nombre_comercial,
            'ruc' => $ruc,
            'pais' => $pais,
            'moneda' => $moneda,
            'zona_horaria' => $zona_horaria,
            'logo_url' => $logo_url,
            'plan_id' => $plan_id,
            'fecha_expira' => $fecha_expira,
            'estado' => $estado,
            'usuario_admin_id' => $usuario_admin_id,
            'creado_por' => $creado_por
        ]);

        if ($result['success']) {
            $result["message"] = "Empresa creada exitosamente";
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 500);
        }
    }

    /**
     * Crea un nuevo contacto para una empresa
     * Requiere autenticación JWT y método POST
     * Los datos del contacto se reciben por JSON en el body
     * Retorna JSON con el resultado de la operación
     */
    public function createContacto()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);

        if (!JwtHelper::validateJwt($jwt)) {
            $this->jsonResponse(["success" => false, 'error' => 'Token JWT inválido o expirado'], 401);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(["success" => false, 'error' => 'Método no permitido'], 405);
            return;
        }

        $data = $this->getJsonInput();
        $id_empresa = $data['id_empresa'] ?? null;
        $nombre = $data['nombre'] ?? null;
        $email = $data['email'] ?? null;
        $telefono = $data['telefono'] ?? null;
        $rol_contacto = $data['rol_contacto'] ?? 'Admin';
        $estado = $data['estado'] ?? 'A';
        $creado_por = $data['creado_por'] ?? null;

        if (empty($id_empresa) || empty($nombre)) {
            $this->jsonResponse(["success" => false, 'error' => 'Faltan datos obligatorios', 'id_empresa' => $id_empresa, 'nombre' => $nombre], 400);
            return;
        }

        $result = $this->model->createContacto([
            'id_empresa' => $id_empresa,
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'rol_contacto' => $rol_contacto,
            'estado' => $estado,
            'creado_por' => $creado_por
        ]);

        if ($result["success"]) {
            $result["message"] = "Contacto creado exitosamente";
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 500);
        }
    }

    /**
     * Asocia un plan a una empresa y actualiza el historial y la empresa
     * Requiere JWT y método POST
     * Recibe id_empresa, plan_id, fecha_fin, creado_por por JSON
     */

    public function asociarPlanEmpresa()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);
        if (!JwtHelper::validateJwt($jwt)) {
            $this->jsonResponse(["success" => false, 'error' => 'Token JWT inválido o expirado'], 401);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(["success" => false, 'error' => 'Método no permitido'], 405);
            return;
        }
        $data = $this->getJsonInput();
        $id_empresa = $data['id_empresa'] ?? null;
        $plan_id = $data['plan_id'] ?? null;
        $fecha_fin = $data['fecha_fin'] ?? null;
        $creado_por = $data['creado_por'] ?? null;
        if (empty($id_empresa) || empty($plan_id)) {
            $this->jsonResponse(["success" => false, 'error' => 'Faltan datos obligatorios'], 400);
            return;
        }
        $result = $this->model->asociarPlanEmpresa($id_empresa, $plan_id, $fecha_fin, $creado_por);
        if ($result) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse($result, 500);
        }
    }

    
    public function getAllEmpresas()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);
        if (!JwtHelper::validateJwt($jwt)) {
            $this->jsonResponse(["success" => false, 'error' => 'Token JWT inválido o expirado'], 401);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(["success" => false, 'error' => 'Método no permitido'], 405);
            return;
        }
        $result = $this->model->getAllEmpresas();
        if ($result) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse($result, 500);
        }
    }
}
