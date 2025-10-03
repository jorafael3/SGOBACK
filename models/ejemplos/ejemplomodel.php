<?php
/**
 * =====================================================
 * MODELO DE EJEMPLO - SISTEMA MULTIEMPRESA
 * =====================================================
 * 
 * Este modelo sirve como PLANTILLA Y DOCUMENTACIÓN para implementar
 * el sistema multiempresa en cualquier modelo del proyecto.
 * 
 * HEREDA DE LA CLASE MODEL BASE que ya incluye:
 * - Configuración automática de empresa
 * - Métodos para consultas cruzadas
 * - Validación y sanitización
 * - Logging de errores
 * 
 * @author Sistema Multiempresa
 * @version 1.0
 * @date 2025-10-03
 */

class EjemploModel extends Model
{
    // ✅ Configuración del modelo
    protected $table = 'SERIESUSR'; // Tabla principal del modelo
    protected $primaryKey = 'usrid'; // Clave primaria
    protected $fillable = ['usuario', 'nombre', 'email']; // Campos permitidos para inserción/actualización
    protected $hidden = ['clave']; // Campos que no se deben mostrar en respuestas

    /**
     * Constructor - acepta empresa específica
     * @param string|null $empresaCode Código de empresa
     */
    public function __construct($empresaCode = null)
    {
        parent::__construct($empresaCode);
    }

    /**
     * ====================================================================
     * EJEMPLO 1: CONSULTA BÁSICA EN EMPRESA ACTUAL
     * ====================================================================
     * 
     * Método básico que consulta datos en la empresa configurada actualmente.
     * Esta es la forma estándar de hacer consultas.
     * 
     * @return array|false Resultado de la consulta
     */
    public function obtenerDatos()
    {
        try {
            $sql = "SELECT usrid, usuario, nombre, email 
                    FROM SERIESUSR 
                    WHERE anulado != '1' 
                    ORDER BY usuario";
            
            $result = $this->query($sql);
            
            if ($result && $result['success']) {
                return [
                    'success' => true,
                    'data' => $result['data'],
                    'empresa' => $this->empresaCode,
                    'total' => count($result['data'])
                ];
            }
            
            return [
                'success' => false,
                'error' => 'No se pudieron obtener los datos'
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en obtenerDatos: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno en consulta'
            ];
        }
    }

    /**
     * ====================================================================
     * EJEMPLO 2: CONSULTA CRUZADA A OTRA EMPRESA
     * ====================================================================
     * 
     * Muestra cómo usar queryInEmpresa() para consultar en otra empresa
     * sin afectar la conexión actual del modelo.
     * 
     * @param string $empresaCode Código de la empresa a consultar
     * @return array|false
     */
    public function consultarOtraEmpresa($empresaCode)
    {
        try {
            $sql = "SELECT COUNT(*) as total_usuarios, 
                           MAX(fecha_ingreso) as ultimo_ingreso
                    FROM SERIESUSR 
                    WHERE anulado != '1'";
            
            // ✅ Consulta en otra empresa usando método heredado
            $result = $this->queryInEmpresa($empresaCode, $sql);
            
            if ($result && $result['success']) {
                return [
                    'success' => true,
                    'empresa_consultada' => $empresaCode,
                    'data' => $result['data'][0] ?? [],
                    'info' => 'Consulta ejecutada en empresa: ' . $empresaCode
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Error en consulta cruzada'
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en consultarOtraEmpresa: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error en consulta cruzada: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ====================================================================
     * EJEMPLO 3: CONSULTA MÚLTIPLE EN VARIAS EMPRESAS
     * ====================================================================
     * 
     * Muestra cómo usar queryMultipleEmpresas() para obtener datos
     * de múltiples empresas en una sola operación.
     * 
     * @param array $empresas Lista de códigos de empresa
     * @return array
     */
    public function consultarMultiplesEmpresas($empresas = ['pruebas_cartimex', 'pruebas_computron', 'sisco'])
    {
        try {
            // ✅ Preparar consultas para múltiples empresas
            $queries = [];
            
            foreach ($empresas as $empresa) {
                $queries[$empresa] = [
                    'sql' => "SELECT 
                                COUNT(*) as total_usuarios,
                                COUNT(CASE WHEN anulado != '1' THEN 1 END) as usuarios_activos,
                                COUNT(CASE WHEN anulado = '1' THEN 1 END) as usuarios_inactivos
                              FROM SERIESUSR",
                    'params' => []
                ];
            }
            
            // ✅ Ejecutar consultas múltiples usando método heredado
            $resultados = $this->queryMultipleEmpresas($queries);
            
            // ✅ Procesar resultados
            $resumen = [
                'success' => true,
                'empresas_consultadas' => count($empresas),
                'resultados' => [],
                'resumen_general' => [
                    'total_usuarios_todas_empresas' => 0,
                    'total_activos_todas_empresas' => 0
                ]
            ];
            
            foreach ($resultados as $empresa => $resultado) {
                if ($resultado && $resultado['success'] && !empty($resultado['data'])) {
                    $datos = $resultado['data'][0];
                    $resumen['resultados'][$empresa] = [
                        'empresa' => $empresa,
                        'total_usuarios' => $datos['total_usuarios'] ?? 0,
                        'usuarios_activos' => $datos['usuarios_activos'] ?? 0,
                        'usuarios_inactivos' => $datos['usuarios_inactivos'] ?? 0,
                        'status' => 'success'
                    ];
                    
                    // Agregar al resumen general
                    $resumen['resumen_general']['total_usuarios_todas_empresas'] += ($datos['total_usuarios'] ?? 0);
                    $resumen['resumen_general']['total_activos_todas_empresas'] += ($datos['usuarios_activos'] ?? 0);
                } else {
                    $resumen['resultados'][$empresa] = [
                        'empresa' => $empresa,
                        'status' => 'error',
                        'error' => 'No se pudo consultar esta empresa'
                    ];
                }
            }
            
            return $resumen;
            
        } catch (Exception $e) {
            $this->logError("Error en consultarMultiplesEmpresas: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error en consulta múltiple: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ====================================================================
     * EJEMPLO 4: COMPARACIÓN ENTRE EMPRESAS
     * ====================================================================
     * 
     * Compara datos entre la empresa actual y otra empresa específica.
     * 
     * @param string $otraEmpresa Código de la empresa a comparar
     * @return array
     */
    public function compararConOtraEmpresa($otraEmpresa)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_usuarios,
                        COUNT(CASE WHEN anulado != '1' THEN 1 END) as usuarios_activos,
                        AVG(CASE WHEN anulado != '1' THEN 1.0 ELSE 0.0 END) * 100 as porcentaje_activos
                    FROM SERIESUSR";
            
            // ✅ Consulta en empresa actual
            $resultadoActual = $this->query($sql);
            
            // ✅ Consulta en otra empresa
            $resultadoOtra = $this->queryInEmpresa($otraEmpresa, $sql);
            
            if ($resultadoActual && $resultadoActual['success'] && 
                $resultadoOtra && $resultadoOtra['success']) {
                
                $datosActual = $resultadoActual['data'][0] ?? [];
                $datosOtra = $resultadoOtra['data'][0] ?? [];
                
                return [
                    'success' => true,
                    'empresa_actual' => $this->empresaCode,
                    'empresa_comparada' => $otraEmpresa,
                    'datos_empresa_actual' => $datosActual,
                    'datos_empresa_comparada' => $datosOtra,
                    'comparacion' => [
                        'diferencia_total_usuarios' => ($datosActual['total_usuarios'] ?? 0) - ($datosOtra['total_usuarios'] ?? 0),
                        'diferencia_usuarios_activos' => ($datosActual['usuarios_activos'] ?? 0) - ($datosOtra['usuarios_activos'] ?? 0),
                        'empresa_con_mas_usuarios' => ($datosActual['total_usuarios'] ?? 0) > ($datosOtra['total_usuarios'] ?? 0) ? $this->empresaCode : $otraEmpresa
                    ]
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Error al obtener datos para comparación'
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en compararConOtraEmpresa: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error en comparación: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ====================================================================
     * EJEMPLO 5: OPERACIONES CRUD CON EMPRESA ESPECÍFICA
     * ====================================================================
     */

    /**
     * Crear usuario en la empresa actual
     * @param array $datos Datos del usuario
     * @return array
     */
    public function crearUsuario($datos)
    {
        try {
            // ✅ Validar datos requeridos
            if (empty($datos['usuario']) || empty($datos['nombre'])) {
                return [
                    'success' => false,
                    'error' => 'Usuario y nombre son requeridos'
                ];
            }
            
            // ✅ Usar método create heredado (ya maneja la empresa actual)
            $resultado = $this->create($datos);
            
            if ($resultado) {
                return [
                    'success' => true,
                    'id' => $resultado,
                    'empresa' => $this->empresaCode,
                    'message' => 'Usuario creado exitosamente'
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Error al crear usuario'
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en crearUsuario: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno al crear usuario'
            ];
        }
    }

    /**
     * ====================================================================
     * EJEMPLO 6: CONSULTA CON PARÁMETROS Y FILTROS
     * ====================================================================
     * 
     * @param array $filtros Filtros para la consulta
     * @return array
     */
    public function buscarUsuarios($filtros = [])
    {
        try {
            $sql = "SELECT usrid, usuario, nombre, email, fecha_ingreso 
                    FROM SERIESUSR 
                    WHERE anulado != '1'";
            $params = [];
            
            // ✅ Agregar filtros dinámicamente
            if (!empty($filtros['usuario'])) {
                $sql .= " AND usuario LIKE :usuario";
                $params[':usuario'] = '%' . $filtros['usuario'] . '%';
            }
            
            if (!empty($filtros['nombre'])) {
                $sql .= " AND nombre LIKE :nombre";
                $params[':nombre'] = '%' . $filtros['nombre'] . '%';
            }
            
            $sql .= " ORDER BY usuario";
            
            $result = $this->query($sql, $params);
            
            if ($result && $result['success']) {
                return [
                    'success' => true,
                    'data' => $result['data'],
                    'empresa' => $this->empresaCode,
                    'filtros_aplicados' => $filtros,
                    'total' => count($result['data'])
                ];
            }
            
            return [
                'success' => false,
                'error' => 'No se encontraron resultados'
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en buscarUsuarios: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error en búsqueda'
            ];
        }
    }

    /**
     * ====================================================================
     * DOCUMENTACIÓN DE MÉTODOS HEREDADOS DISPONIBLES
     * ====================================================================
     * 
     * MÉTODOS DE LA CLASE MODEL BASE:
     * 
     * CONSULTAS BÁSICAS:
     * - $this->query($sql, $params) - Consulta en empresa actual
     * - $this->getAll() - Obtener todos los registros
     * - $this->getById($id) - Obtener por ID
     * - $this->where($conditions) - Consulta con condiciones
     * - $this->create($data) - Crear registro
     * - $this->update($id, $data) - Actualizar registro
     * - $this->delete($id) - Eliminar registro
     * - $this->count($conditions) - Contar registros
     * 
     * CONSULTAS MULTIEMPRESA:
     * - $this->queryInEmpresa($empresa, $sql, $params) - Consulta en otra empresa
     * - $this->queryMultipleEmpresas($queries) - Consulta en múltiples empresas
     * - $this->setEmpresa($empresaCode) - Cambiar empresa del modelo
     * 
     * PROPIEDADES CONFIGURABLES:
     * - $this->table - Tabla principal
     * - $this->primaryKey - Clave primaria
     * - $this->fillable - Campos permitidos para inserción
     * - $this->hidden - Campos ocultos en respuestas
     * - $this->empresaCode - Empresa actual
     * - $this->empresasConfig - Configuración de todas las empresas
     * 
     * EJEMPLO DE USO EN OTROS MODELOS:
     * 
     * class MiModel extends Model {
     *     protected $table = 'mi_tabla';
     *     protected $primaryKey = 'id';
     *     
     *     public function __construct($empresaCode = null) {
     *         parent::__construct($empresaCode);
     *     }
     *     
     *     public function miMetodo() {
     *         // Consulta en empresa actual
     *         $resultado1 = $this->query("SELECT * FROM mi_tabla");
     *         
     *         // Consulta en otra empresa
     *         $resultado2 = $this->queryInEmpresa('otra_empresa', "SELECT * FROM mi_tabla");
     *         
     *         return $resultado1;
     *     }
     * }
     */
}
?>
