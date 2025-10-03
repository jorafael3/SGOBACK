<?php
/**
 * =====================================================
 * CONTROLADOR DE EJEMPLO - SISTEMA MULTIEMPRESA
 * =====================================================
 * 
 * Este controlador sirve como PLANTILLA Y DOCUMENTACIÓN para implementar
 * el sistema multiempresa en cualquier controlador del proyecto.
 * 
 * CARACTERÍSTICAS DEL SISTEMA MULTIEMPRESA:
 * 1. Autenticación automática con JWT
 * 2. Configuración automática de la empresa desde el token
 * 3. Consultas cruzadas entre empresas
 * 4. Métodos helper para simplificar el código
 * 
 * EMPRESAS CONFIGURADAS (ver config/.env):
 * - pruebas_cartimex (SQL Server)
 * - pruebas_computron (SQL Server) 
 * - sisco (MySQL)
 * 
 * @author Sistema Multiempresa
 * @version 1.0
 * @date 2025-10-03
 */

require_once __DIR__ . '/../../libs/JwtHelper.php';

class EjemploMultiempresa extends Controller
{
    /**
     * Constructor del controlador
     * Configura el modelo automáticamente
     */
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'ejemplos'; // Carpeta donde está el modelo
        $this->loadModel('ejemplo'); // Carga models/ejemplos/ejemplomodel.php
    }

    /**
     * ====================================================================
     * EJEMPLO 1: CONSULTA BÁSICA CON EMPRESA AUTOMÁTICA
     * ====================================================================
     * 
     * Este método muestra el patrón básico para cualquier endpoint:
     * 1. Autenticar JWT y configurar empresa automáticamente
     * 2. Ejecutar lógica de negocio
     * 3. Retornar respuesta
     * 
     * ENDPOINT: POST /ejemplomultiempresa/consultaBasica
     * HEADERS: Authorization: Bearer <jwt_token>
     * BODY: {} (vacío o con parámetros opcionales)
     * 
     * RESPUESTA:
     * {
     *   "success": true,
     *   "empresa_actual": "pruebas_computron",
     *   "data": [...],
     *   "info": "Consulta ejecutada en la empresa del JWT"
     * }
     */
    public function consultaBasica()
    {
        // ✅ PATRÓN ESTÁNDAR: Una línea para autenticar y configurar empresa
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido, 1 = GET, 3 = PUT, 4 = DELETE, 0 = cualquiera
        if (!$jwtData) {
            return; // Error ya enviado automáticamente
        }

        // ✅ A partir de aquí, $this->model está configurado con la empresa del JWT
        $result = $this->model->obtenerDatos();
        
        // ✅ Respuesta estándar
        if ($result && $result['success']) {
            $this->jsonResponse([
                'success' => true,
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                'data' => $result['data'],
                'info' => 'Consulta ejecutada en la empresa del JWT'
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos'
            ], 500);
        }
    }

    /**
     * ====================================================================
     * EJEMPLO 2: CONSULTA CRUZADA A OTRA EMPRESA
     * ====================================================================
     * 
     * Muestra cómo consultar datos de una empresa diferente a la actual
     * sin perder la conexión original.
     * 
     * ENDPOINT: POST /ejemplomultiempresa/consultaCruzada
     * HEADERS: Authorization: Bearer <jwt_token>
     * BODY: {"empresa_destino": "pruebas_cartimex"}
     * 
     * RESPUESTA:
     * {
     *   "success": true,
     *   "empresa_actual": "pruebas_computron",
     *   "empresa_consultada": "pruebas_cartimex",
     *   "datos_empresa_actual": [...],
     *   "datos_otra_empresa": [...]
     * }
     */
    public function consultaCruzada()
    {
        // ✅ Autenticar y configurar empresa actual
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();
        $empresaDestino = $data['empresa_destino'] ?? null;

        if (empty($empresaDestino)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Parámetro empresa_destino es requerido'
            ], 400);
            return;
        }

        // ✅ Consulta en empresa actual
        $datosEmpresaActual = $this->model->obtenerDatos();

        // ✅ Consulta cruzada en otra empresa (MÉTODO 1: desde el modelo)
        $datosOtraEmpresa = $this->model->consultarOtraEmpresa($empresaDestino);

        $this->jsonResponse([
            'success' => true,
            'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
            'empresa_consultada' => $empresaDestino,
            'datos_empresa_actual' => $datosEmpresaActual,
            'datos_otra_empresa' => $datosOtraEmpresa,
            'info' => 'Consulta cruzada entre empresas'
        ], 200);
    }

    /**
     * ====================================================================
     * EJEMPLO 3: MODELO TEMPORAL PARA OTRA EMPRESA
     * ====================================================================
     * 
     * Muestra cómo crear un modelo completamente independiente
     * para trabajar con otra empresa.
     * 
     * ENDPOINT: POST /ejemplomultiempresa/modeloTemporal
     * HEADERS: Authorization: Bearer <jwt_token>
     * BODY: {"empresa_destino": "sisco"}
     */
    public function modeloTemporal()
    {
        // ✅ Autenticar y configurar empresa actual
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();
        $empresaDestino = $data['empresa_destino'] ?? null;

        if (empty($empresaDestino)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Parámetro empresa_destino es requerido'
            ], 400);
            return;
        }

        // ✅ Crear modelo temporal para otra empresa (MÉTODO 2: desde el controlador)
        $modeloTemporal = $this->getModelForEmpresa('ejemplo', $empresaDestino);
        
        if (!$modeloTemporal) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'No se pudo crear modelo para la empresa especificada'
            ], 500);
            return;
        }

        // ✅ Usar el modelo temporal
        $datosModeloTemporal = $modeloTemporal->obtenerDatos();

        $this->jsonResponse([
            'success' => true,
            'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
            'empresa_temporal' => $empresaDestino,
            'datos_modelo_temporal' => $datosModeloTemporal,
            'info' => 'Modelo temporal creado para otra empresa'
        ], 200);
    }

    /**
     * ====================================================================
     * EJEMPLO 4: CONSULTA MÚLTIPLE (TODAS LAS EMPRESAS)
     * ====================================================================
     * 
     * Muestra cómo obtener datos de múltiples empresas en una sola llamada.
     * 
     * ENDPOINT: POST /ejemplomultiempresa/consultaMultiple
     * HEADERS: Authorization: Bearer <jwt_token>
     * BODY: {"empresas": ["pruebas_cartimex", "pruebas_computron", "sisco"]}
     */
    public function consultaMultiple()
    {
        // ✅ Autenticar y configurar empresa actual
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();
        $empresas = $data['empresas'] ?? ['pruebas_cartimex', 'pruebas_computron', 'sisco'];

        // ✅ Consulta múltiple usando el método del modelo base
        $resultadosMultiples = $this->model->consultarMultiplesEmpresas($empresas);

        $this->jsonResponse([
            'success' => true,
            'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
            'empresas_consultadas' => $empresas,
            'resultados' => $resultadosMultiples,
            'info' => 'Consulta ejecutada en múltiples empresas'
        ], 200);
    }

    /**
     * ====================================================================
     * EJEMPLO 5: COMPARACIÓN ENTRE EMPRESAS
     * ====================================================================
     * 
     * Muestra cómo comparar datos entre la empresa actual y otra empresa.
     * 
     * ENDPOINT: POST /ejemplomultiempresa/compararEmpresas
     * HEADERS: Authorization: Bearer <jwt_token>
     * BODY: {"empresa_comparar": "pruebas_cartimex"}
     */
    public function compararEmpresas()
    {
        // ✅ Autenticar y configurar empresa actual
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();
        $empresaComparar = $data['empresa_comparar'] ?? null;

        if (empty($empresaComparar)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Parámetro empresa_comparar es requerido'
            ], 400);
            return;
        }

        // ✅ Usar método de comparación del modelo
        $comparacion = $this->model->compararConOtraEmpresa($empresaComparar);

        $this->jsonResponse([
            'success' => true,
            'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
            'empresa_comparada' => $empresaComparar,
            'comparacion' => $comparacion,
            'info' => 'Comparación entre empresas completada'
        ], 200);
    }

    /**
     * ====================================================================
     * EJEMPLO 6: MANEJO DE DIFERENTES MÉTODOS HTTP
     * ====================================================================
     * 
     * Muestra los diferentes métodos HTTP disponibles en el helper.
     */
    
    public function ejemploGET()
    {
        // ✅ GET requerido (parámetro 1)
        $jwtData = $this->authenticateAndConfigureModel(1);
        if (!$jwtData) return;

        $this->jsonResponse([
            'success' => true,
            'method' => 'GET',
            'empresa' => $jwtData['empresa'] ?? 'N/A'
        ], 200);
    }

    public function ejemploPUT()
    {
        // ✅ PUT requerido (parámetro 3)
        $jwtData = $this->authenticateAndConfigureModel(3);
        if (!$jwtData) return;

        $data = $this->getJsonInput();
        $this->jsonResponse([
            'success' => true,
            'method' => 'PUT',
            'empresa' => $jwtData['empresa'] ?? 'N/A',
            'data_received' => $data
        ], 200);
    }

    public function ejemploDELETE()
    {
        // ✅ DELETE requerido (parámetro 4)
        $jwtData = $this->authenticateAndConfigureModel(4);
        if (!$jwtData) return;

        $this->jsonResponse([
            'success' => true,
            'method' => 'DELETE',
            'empresa' => $jwtData['empresa'] ?? 'N/A'
        ], 200);
    }

    public function ejemploCualquierMetodo()
    {
        // ✅ Cualquier método HTTP (parámetro 0)
        $jwtData = $this->authenticateAndConfigureModel(0);
        if (!$jwtData) return;

        $this->jsonResponse([
            'success' => true,
            'method' => $_SERVER['REQUEST_METHOD'],
            'empresa' => $jwtData['empresa'] ?? 'N/A'
        ], 200);
    }

    /**
     * ====================================================================
     * DOCUMENTACIÓN DE MÉTODOS HELPER DISPONIBLES
     * ====================================================================
     * 
     * MÉTODOS DEL CONTROLADOR BASE (Controller):
     * 
     * 1. $this->authenticateAndConfigureModel($method)
     *    - Parámetros: 0=cualquiera, 1=GET, 2=POST, 3=PUT, 4=DELETE
     *    - Retorna: array con datos del JWT o false
     *    - Funciones: Valida JWT + configura modelo + valida método HTTP
     * 
     * 2. $this->configureModelWithJWT()
     *    - Sin validación de método HTTP
     *    - Solo configura el modelo con la empresa del JWT
     * 
     * 3. $this->getCurrentEmpresa()
     *    - Retorna el código de empresa del JWT actual
     * 
     * 4. $this->getModelForEmpresa($modelName, $empresaCode)
     *    - Crea modelo temporal para otra empresa
     * 
     * MÉTODOS DEL MODELO BASE (Model):
     * 
     * 1. $this->queryInEmpresa($empresaCode, $sql, $params)
     *    - Consulta en otra empresa sin cambiar conexión actual
     * 
     * 2. $this->queryMultipleEmpresas($queries)
     *    - Consultas en múltiples empresas
     * 
     * 3. $this->setEmpresa($empresaCode)
     *    - Cambiar empresa del modelo actual
     * 
     * ESTRUCTURA DE EMPRESAS EN CONFIG:
     * 
     * $EMPRESAS = [
     *     'pruebas_cartimex' => [
     *         'host' => '10.5.1.86',
     *         'database' => 'CARTIMEX',
     *         'username' => 'jalvarado',
     *         'password' => '12345',
     *         'driver' => 'sqlsrv'
     *     ],
     *     'pruebas_computron' => [...],
     *     'sisco' => [...]
     * ];
     */
}
?>
