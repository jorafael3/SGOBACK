<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';


class FondoreservasModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
    }


    function getFondosReservas($data = [])
    {
        try {
           
           
            $sql = "EXEC SGO_EMP_INGRESAR_FONDOS @EMPRESA = :EMPRESA, @cedula = :cedula , @VISTO = :VISTO";
          
            $params = [
                
                ':EMPRESA' => $data['empresa'] ?? null,
                ':cedula' => $data['cedula'] ?? null,
                ':VISTO' => $data['VISTO'] ?? null
            ];
            
            $stmt = $this->query($sql, $params);
            
    
            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en getFondosReservas: " . $e->getMessage());
            error_log("Exception in getFondosReservas: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    function guardarFondoReserva($data = [])
    {
        try {
            // Desactivar transacciones implícitas en SQL Server
            $this->query("SET IMPLICIT_TRANSACTIONS OFF", []);
            
            // NO usar transacciones de PDO, dejar que SQL Server maneje el autocommit
            // Ejecutar el stored procedure usando execute() que hace commit automático
            $sql = "EXEC SGO_EMP_INGRESAR_FONDOS @EMPRESA = :EMPRESA, @cedula = :cedula, @VISTO = :VISTO";
          
            $params = [
                ':EMPRESA' => $data['empresa'] ?? null,
                ':cedula' => $data['cedula'] ?? null,
                ':VISTO' => $data['VISTO'] ?? 0
            ];
            
            // Usar execute() en lugar de query() para operaciones de escritura
            $executeResult = $this->db->execute($sql, $params);
            
            // Ahora hacer un SELECT para obtener el resultado actualizado
            $sqlSelect = "SELECT Código as cedula, Nombre as nombre, ProvisionaFR as Ingresado 
                          FROM " . ($data['empresa'] == 'Cartimex' ? 'CARTIMEX' : 'COMPUTRONSA') . "..EMP_EMPLEADOS 
                          WHERE Código = :cedula";
            $selectResult = $this->query($sqlSelect, [':cedula' => $data['cedula']]);
            
            $outputData = null;
            if ($selectResult && isset($selectResult['data']) && !empty($selectResult['data'])) {
                $outputData = $selectResult['data'][0] ?? null;
            }
            
            return [
                'success' => true,
                'stored_procedure_output' => $outputData,
                'params_sent' => $params,
                'execute_result' => $executeResult,
                'debug_info' => 'Used execute() method with autocommit'
            ];

        } catch (Exception $e) {
            $this->logError("Error en guardarFondoReserva: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

}
