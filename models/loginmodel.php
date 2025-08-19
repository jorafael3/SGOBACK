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

    public function __construct()
    {
        parent::__construct();
    }

    public function authenticate($username, $password)
    {
        try {
            // Verificar intentos de login fallidos
            if ($this->isAccountLocked($username)) {
                return [
                    'success' => false,
                    'message' => 'Cuenta bloqueada temporalmente por múltiples intentos fallidos'
                ];
            }

            // Buscar usuario
            $user = $this->getUserByUsername($username);

            // if (!$user["success"]) {
            //     $this->recordFailedAttempt($username);
            //     return [
            //         'success' => false,
            //         'message' => 'Credenciales inválidas'
            //     ];
            // }

            // // Verificar si el usuario está activo
            if ($user['data'][0]["estado"] != "A") {
                return [
                    'success' => false,
                    'message' => 'Cuenta desactivada'
                ];
            }

            // Verificar contraseña
            if (!password_verify($password, $user["data"][0]['password_hash'])) {
                $this->recordFailedAttempt($username);
                return [
                    'success' => false,
                    'message' => 'Credenciales inválidas'
                ];
            }

            // Login exitoso
            // $this->clearFailedAttempts($username);
            // $this->updateLastLogin($user['id']);

            // Remover campos sensibles
            unset($user['data'][0]['password_hash']);

            return [
                'success' => true,
                'message' => 'Login exitoso',
                'user_data' => $user['data'][0]
            ];
        } catch (Exception $e) {
            $this->logError("Error en authenticate: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno de autenticación'
            ];
        }
    }

    private function getUserByUsername($username)
    {
        try {
            $sql = "SELECT * FROM adm_Usuarios_Admin WHERE usuario = :username LIMIT 1";
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
