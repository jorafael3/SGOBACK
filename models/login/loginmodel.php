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

            if (!$user["success"]) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ];
            }

            if (empty($user['data'])) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas'
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
            if(!($password === $user["data"][0]['clave'])){
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
            $sql = "SELECT * FROM SERIESUSR WHERE usuario = :username";
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
}
