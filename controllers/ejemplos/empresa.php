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
        $this->folder = 'empresas'; // Especifica la carpeta donde está el modelo
        $this->loadModel('empresa'); // Cargar el modelo de empresa
    }

    

    /**
     * Crea una nueva empresa en la base de datos
     * Requiere autenticación JWT y método POST
     * Los datos de la empresa se reciben por JSON en el body
     * Retorna JSON con el resultado de la operación
     */
    public function create()
    {
        // Autenticar JWT y configurar modelo automáticamente (método POST requerido)
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada por el método helper
        }

        $data = $this->getJsonInput();
        $razon_social = $data['razon_social'] ?? null;
        $nombre_comercial = $data['nombre_comercial'] ?? null;
        $ruc = $data['ruc'] ?? null;
        $direccion = $data['direccion'] ?? null;
        $pais = $data['pais'] ?? null;
        $moneda = $data['moneda'] ?? 'USD';
        $zona_horaria = $data['zona_horaria'] ?? 'America/Guayaquil';
        $logo_url = $data['logo_url'] ?? null;
        $plan_id = $data['plan_id'] ?? 1;
        $fecha_expira = $data['fecha_expira'] ?? null;
        $estado = $data['estado'] ?? 'A';
        $usuario_admin_id = $data['usuario_admin_id'] ?? null;

        if (empty($razon_social) || empty($nombre_comercial) || empty($ruc) || empty($pais)) {
            $this->jsonResponse(["success" => false, 'error' => 'Faltan datos obligatorios'], 200);
            return;
        }

        $result = $this->model->createEmpresa([
            'razon_social' => $razon_social,
            'nombre_comercial' => $nombre_comercial,
            'ruc' => $ruc,
            'direccion' => $direccion,
            'pais' => $pais,
            'moneda' => $moneda,
            'zona_horaria' => $zona_horaria,
            'logo_url' => $logo_url,
            'plan_id' => $plan_id,
            'fecha_expira' => $fecha_expira,
            'estado' => $estado,
            'usuario_admin_id' => $usuario_admin_id,
            'creado_por' => $data["sessionData"]['id']
        ]);

        if ($result['success']) {
            $result["message"] = "Empresa creada exitosamente";
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 200);
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
        // Autenticar JWT y configurar modelo automáticamente (método POST requerido)
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada por el método helper
        }

        $data = $this->getJsonInput();
        $id_empresa = $data['id_empresa'] ?? null;
        $nombre = $data['nombre'] ?? null;
        $email = $data['email'] ?? null;
        $telefono = $data['telefono'] ?? null;
        $rol_contacto = $data['rol_contacto'] ?? 'Admin';
        $estado = $data['estado'] ?? 'A';
        // $creado_por = $data['creado_por'] ?? null;



        if (empty($id_empresa) || empty($nombre)) {
            $this->jsonResponse(["success" => false, 'error' => 'Faltan datos obligatorios', 'id_empresa' => $id_empresa, 'nombre' => $nombre], 200);
            return;
        }

        $result = $this->model->createContacto([
            'id_empresa' => $id_empresa,
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'rol_contacto' => $rol_contacto,
            'estado' => $estado,
            'creado_por' => $data["sessionData"]['id']
        ]);

        if ($result["success"]) {
            $result["message"] = "Contacto creado exitosamente";
            $this->jsonResponse($result, 201);
        } else {
            $this->jsonResponse($result, 200);
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


    /**
     * ====================================================================
     * EJEMPLO DE ENDPOINT CON SISTEMA MULTIEMPRESA
     * ====================================================================
     * 
     * Este método demuestra el patrón estándar para cualquier endpoint
     * en el sistema multiempresa:
     * 
     * 1. ✅ Autenticación automática con JWT
     * 2. ✅ Configuración automática de la empresa desde el token
     * 3. ✅ Ejecución de la lógica de negocio
     * 4. ✅ Respuesta estandarizada
     * 
     * ENDPOINT: POST /empresa/getAllEmpresas
     * HEADERS: Authorization: Bearer <jwt_token>
     * BODY: {} (puede incluir filtros opcionales)
     * 
     * RESPUESTA EXITOSA:
     * {
     *   "success": true,
     *   "querymessage": "Consulta ejecutada correctamente",
     *   "data": [...],
     *   "empresa_actual": "pruebas_computron",
     *   "total_registros": 5
     * }
     * 
     * RESPUESTA DE ERROR:
     * {
     *   "success": false,
     *   "error": "Token JWT inválido o expirado"
     * }
     * 
     * @return void
     */
    public function getAllEmpresas()
    {
        // ✅ PATRÓN ESTÁNDAR: Autenticar JWT y configurar empresa automáticamente
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        // ✅ Obtener datos opcionales del body
        $data = $this->getJsonInput();
        $filtros = $data['filtros'] ?? [];

        // ✅ Ejecutar lógica de negocio - el modelo ya está configurado con la empresa correcta
        $result = $this->model->getAllEmpresas();
        
        // ✅ Respuesta estandarizada con información adicional
        if ($result && $result['success']) {
            // Agregar información del contexto multiempresa
            $result['empresa_actual'] = $jwtData['empresa'] ?? 'N/A';
            $result['usuario'] = $jwtData['username'] ?? 'N/A';
            $result['total_registros'] = count($result['data'] ?? []);
            
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos de empresas',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A'
            ], 500);
        }
    }

    public function getEmpresaDataByUid()
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
        $uid = $data['uid'] ?? null;
        if (empty($uid)) {
            $this->jsonResponse(["success" => false, 'error' => 'Faltan datos obligatorios'], 400);
            return;
        }
        $result = $this->model->getEmpresaDataByUid($uid);
        if ($result) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse($result, 500);
        }
    }

    public function getContactosEmpresa()
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
        $uid = $data['uid'] ?? null;
        if (empty($uid)) {
            $this->jsonResponse(["success" => false, 'error' => 'Faltan datos obligatorios'], 400);
            return;
        }
        $result = $this->model->getContactosEmpresa($uid);
        if ($result) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse($result, 200);
        }
    }

    public function getPlanesEmpresa()
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

        $planes = $this->model->getPlanesEmpresa();
        $periodos = $this->model->getPlanesEmpresaPeriodos();
        $precios = $this->model->getPlanesEmpresaPeriodosPrecio();

        if ($planes && $periodos && $precios) {
            $this->jsonResponse([
                'planes' => $planes,
                'periodos' => $periodos,
                'precios' => $precios
            ], 200);
        } else {
            $this->jsonResponse(["success" => false, 'error' => 'Error al obtener datos'], 500);
        }
    }
    
    /**
     * Ejemplo: Consulta cruzada entre empresas
     * Estando conectado a una empresa, consulta datos de otra
     */
    public function consultaCruzada()
    {
        // Autenticar y configurar modelo con empresa actual
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;
        
        $data = $this->getJsonInput();
        $otraEmpresa = $data['empresa_destino'] ?? null;
        
        if (empty($otraEmpresa)) {
            $this->jsonResponse(["success" => false, 'error' => 'Empresa destino requerida'], 400);
            return;
        }
        
        // Opción 1: Usar método del modelo actual para consulta cruzada
        $resultado1 = $this->model->getUsersFromEmpresa($otraEmpresa);
        
        // Opción 2: Crear modelo temporal para otra empresa
        $modeloTemporal = $this->getModelForEmpresa('empresa', $otraEmpresa);
        $resultado2 = $modeloTemporal ? $modeloTemporal->getAllEmpresas() : null;
        
        // Opción 3: Comparar datos entre empresas
        $comparacion = $this->model->compararConOtraEmpresa($otraEmpresa);
        
        $this->jsonResponse([
            'success' => true,
            'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
            'empresa_consultada' => $otraEmpresa,
            'usuarios_otra_empresa' => $resultado1,
            'datos_modelo_temporal' => $resultado2,
            'comparacion' => $comparacion
        ], 200);
    }
    
    /**
     * Ejemplo: Consulta datos de múltiples empresas al mismo tiempo
     */
    public function consultaMultiEmpresa()
    {
        // Autenticar y configurar modelo con empresa actual
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;
        
        $data = $this->getJsonInput();
        $empresas = $data['empresas'] ?? ['pruebas_cartimex', 'pruebas_computron', 'sisco'];
        
        // Obtener datos de múltiples empresas
        $resultados = $this->model->getDataFromMultipleEmpresas($empresas);
        
        $this->jsonResponse([
            'success' => true,
            'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
            'resultados_multi_empresa' => $resultados
        ], 200);
    }
}
