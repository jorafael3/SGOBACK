<?php
// =====================================================
// ARCHIVO: models/loginmodel.php
// =====================================================

/**
 * Modelo de Login
 */
class LoginModel extends Model

{
    protected $table = 'adm_usuario';
    protected $primaryKey = 'id';
    protected $fillable = ['username', 'email', 'password', 'active', 'last_login'];
    protected $hidden = ['password'];

    public function __construct($empresaCode = null)
    {
        parent::__construct($empresaCode);
    }

    public function authenticate($username, $password, $empresa_code = null)
    {
        try {
            // Si se proporciona un código de empresa, cambiar la conexión
            if ($empresa_code) {
                $this->setEmpresa($empresa_code);
            }

            // Verificar intentos de login fallidos
            // if ($this->isAccountLocked($username)) {
            //     return [
            //         'success' => false,
            //         'message' => 'Cuenta bloqueada temporalmente por múltiples intentos fallidos'
            //     ];
            // }



            // Buscar usuario
            $user = $this->getUserByUsername($username);

            // echo json_encode($user);
            // exit;

            if (!$user["success"]) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas 1'
                ];
            }

            if (empty($user['data'])) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas 2'
                ];
            }

            // // Verificar si el usuario está activo
            if ($user['data'][0]["anulado"] == "1") {
                return [
                    'success' => false,
                    'message' => 'Cuenta desactivada'
                ];
            }

            // Verificar contraseña
            // if (!password_verify($password, $user["data"][0]['password_hash'])) {
            //     $this->recordFailedAttempt($username);
            //     return [
            //         'success' => false,
            //         'message' => 'Credenciales inválidas'
            //     ];
            // }
            if (!(strtolower(trim($password)) === strtolower(trim($user["data"][0]['clave'])))) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas, verificar usuario y contraseña'
                ];
            }

            // Login exitoso
            // $this->clearFailedAttempts($username);
            // $this->updateLastLogin($user['id']);
            $empresaActual = $empresa_code ?? $this->empresaCode;
            $user["data"][0]["empresa"] = $empresaActual;
            $user["data"][0]["empresa_code"] = $empresaActual;

            // Agregar el nombre de la empresa desde la configuración
            global $EMPRESAS;
            $user["data"][0]["empresa_name"] = $EMPRESAS[$empresaActual]['name'] ?? 'Empresa Desconocida';

            // Remover campos sensibles
            unset($user['data'][0]['clave']);

            return [
                'success' => true,
                'message' => 'Login exitoso',
                'user_data' => $user['data'][0]
            ];
        } catch (Exception $e) {
            $this->logError("Error en authenticate: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno de autenticación: ' . $e->getMessage()
            ];
        }
    }

    private function getUserByUsername($username)
    {
        try {
            $sql = "SELECT
                    u.*,
                    e.Nombre as EMPLEADO_NOMBRE,
                    d.Nombre as EMPLEADO_DEPARTAMENTO_NOMBRE,
                    d.ID as EMPLEADO_DEPARTAMENTO_ID,
                    s.Código as SUCURSAL_EMPLEADO,
                    s.ID as SUCURSAL_EMPLEADO_ID,
                    b.Nombre as BODEGA_EMPLEADO_NOMBRE,
                    b.Código as BODEGA_EMPLEADO_CODIGO,
                    b.ID as BODEGA_EMPLEADO_ID,
                    s.Nombre as SUCURSAL_NOMBRE
                    from SERIESUSR u
                    left join EMP_EMPLEADOS e
                    on e.ID = u.EmpleadoID
                    left join SIS_DEPARTAMENTOS d
                    on d.ID = e.DepartamentoID
                    left join SIS_SUCURSALES s
                    on s.ID = u.lugartrabajo
                    left join(
                        SELECT top 1 * from INV_BODEGAS
                        order by Código
                    ) as b on b.Sucursal = s.Código
                WHERE u.usuario = :username
                ";
            $stmt = $this->query($sql, [
                ':username' => $username
            ]);

            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error en getUserByUsername: " . $e->getMessage());
            return false;
        }
    }

    private function isAccountLocked($username)
    {
        // Implementar lógica de bloqueo de cuenta
        // Esto podría estar en una tabla separada de intentos de login
        return false; // Por ahora deshabilitado
    }

    private function recordFailedAttempt($username)
    {
        // Registrar intento fallido en tabla de logs
        try {
            $sql = "INSERT INTO login_attempts (username, ip_address, attempted_at) VALUES (:username, :ip, :attempted_at)";
            $this->query($sql, [
                ':username' => $username,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ':attempted_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $this->logError("Error registrando intento fallido: " . $e->getMessage());
        }
    }

    private function clearFailedAttempts($username)
    {
        // Limpiar intentos fallidos
        try {
            $sql = "DELETE FROM login_attempts WHERE username = :username";
            $this->query($sql, [':username' => $username]);
        } catch (Exception $e) {
            $this->logError("Error limpiando intentos fallidos: " . $e->getMessage());
        }
    }

    private function updateLastLogin($userId)
    {
        try {
            $this->update($userId, [
                'last_login' => date('Y-m-d H:i:s'),
                'last_ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
            ]);
        } catch (Exception $e) {
            $this->logError("Error actualizando último login: " . $e->getMessage());
        }
    }

    //*** REGISTER */

    function buscarCedulaDobra($cedula, $emprea)
    {
        try {
            $sql = "SELECT email, email_personal, Nombre, ID from \"$emprea\"..EMP_EMPLEADOS WHERE Código = :cedula";
            $stmt = $this->query($sql, [':cedula' => $cedula]);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo tipos de gastos: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo tipos de gastos: ' . $e->getMessage()
            ];
        }
    }

    function validarUsuarioExistente($empleado_id, $empresa)
    {
        try {
            $this->setEmpresa("produccion_cartimex");
            $sql = "SELECT COUNT(*) as count FROM " . $empresa . "..SERIESUSR WHERE EmpleadoID = :empleado_id";
            $stmt = $this->query($sql, [':empleado_id' => $empleado_id]);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error validando nombre de usuario: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error validando nombre de usuario: ' . $e->getMessage()
            ];
        }
    }

    function GenerarCodigo($empleado, $empresa, $generateCode)
    {
        try {
            $this->setEmpresa("produccion_cartimex");
            $updateSql = "UPDATE CARTIMEX..sgo_login_codigo_verificacion
                set usado = 1
                WHERE empleado = :empleado AND empresa = :empresa;";
            $this->query($updateSql, [':empleado' => $empleado, ':empresa' => $empresa]);

            $sql = "INSERT INTO CARTIMEX..sgo_login_codigo_verificacion
                (
                    empleado,
                    empresa,
                    codigo
                )VALUES
                (
                    :empleado,
                    :empresa,
                    :codigo
                );";
            $stmt = $this->db->execute($sql, [':empleado' => $empleado, ':empresa' => $empresa, ':codigo' => $generateCode]);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo tipos de gastos: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo tipos de gastos: ' . $e->getMessage()
            ];
        }
    }

    function validate_codigo($codigo, $empresa, $empleadoId)
    {
        try {
            $this->setEmpresa("produccion_cartimex");
            $sql = "SELECT * from CARTIMEX..sgo_login_codigo_verificacion 
                WHERE codigo = :codigo AND empresa = :empresa AND empleado = :empleadoId AND usado = 0
                ORDER BY id DESC;";
            $stmt = $this->query($sql, [
                ':codigo' => $codigo,
                ':empresa' => $empresa,
                ':empleadoId' => $empleadoId
            ]);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo tipos de gastos: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo tipos de gastos: ' . $e->getMessage()
            ];
        }
    }

    function ValidarNombreUsuario($nombreUsuario, $empresa)
    {
        try {
            $this->setEmpresa("produccion_cartimex");


            $sql = "SELECT COUNT(*) as count FROM \"$empresa\"..SERIESUSR WHERE usuario = :nombreUsuario";
            $stmt = $this->query($sql, [':nombreUsuario' => $nombreUsuario]);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error validando nombre de usuario: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error validando nombre de usuario: ' . $e->getMessage()
            ];
        }
    }

    function registrar_usuario($parametros)
    {
        try {
            $this->setEmpresa("produccion_cartimex");

            $usuario = $parametros["usuario"];
            $clave = $parametros["password"];
            $empleado_id = $parametros["empleado_id"];
            $empresa = $parametros["empresa"];

            $sql = "
            SET NOCOUNT ON;
                Declare 
                @lastid varchar(20),
                @sucursal varchar(10),
                @nombre varchar(100),
                @lugartrabajo varchar(10)

                SELECT @lastid = RIGHT('0000000000' + CAST(ISNULL(MAX(usrid) + 1, 1) AS VARCHAR(10)), 10) FROM " . $empresa . "..SERIESUSR
                SELECT @sucursal = SucursalID, @nombre = Nombre FROM " . $empresa . "..EMP_EMPLEADOS WHERE ID = '$empleado_id'
                SELECT @lugartrabajo = ID FROM SIS_SUCURSALES WHERE Código = @sucursal
            
                INSERT INTO " . $empresa . "..SERIESUSR 
                (
                   usrid,
                   usuario,
                   nombre,
                   clave,
                   lugartrabajo,
                   EmpleadoId,
                   empleado_empresa
                ) 
                VALUES 
                (
                   @lastid,
                   :usuario,
                   @nombre,
                   :clave,
                   @lugartrabajo,
                   :EmpleadoId,
                   :empleado_empresa
                );
                
                    SELECT @lastid as usrid
                ";

            $p = [
                ':usuario' => $usuario,
                ':clave' => $clave,
                ':EmpleadoId' => $empleado_id,
                ':empleado_empresa' => $empresa
            ];
            $stmt = $this->db->query($sql, $p);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error registrando usuario: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error registrando usuario: ' . $e->getMessage()
            ];
        }
    }

    function Registrar_Menus($usrid, $empresa)
    {
        try {
            
            $this->setEmpresa("produccion_cartimex");
            $sql = "INSERT INTO CARTIMEX..SGO_MENU_USUARIOS (UsuarioID, MenuID, usuario_empresa) 
                VALUES
                (" . $usrid . ", '26', '" . $empresa . "'),
                (" . $usrid . ", '42', '" . $empresa . "')
                ";
            $stmt = $this->db->execute($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error registrando menús: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error registrando menús: ' . $e->getMessage()
            ];
        }
    }
}
