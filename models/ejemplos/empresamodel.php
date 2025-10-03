<?php

require_once __DIR__ . '/../logsmodel.php';


class EmpresaModel extends Model
{
    public function __construct($empresaCode = null)
    {
        parent::__construct($empresaCode);
    }

    private function generateUuid()
    {
        // Genera un UUID v4
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    function getAllEmpresas()
    {
        try {
            $sql = "SELECT 
            *
            FROM SERIESUSR e
            WHERE e.anulado != '1'
            and usuario = :usuario";
            $params = [":usuario" => "jralvarado"];
            // $stmt = $this->query($sql);
            // return $stmt;

            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->queryInEmpresa('pruebas_computron', "SELECT * FROM SERIESUSR WHERE anulado != '1' AND usuario = :usuario", [":usuario" => "jralvarado"]);
            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Ejemplo: Obtener usuarios de una empresa específica (consulta cruzada)
     * @param string $empresaCode Código de la empresa a consultar
     * @return array|false
     */
    function getUsersFromEmpresa($empresaCode)
    {
        try {
            $sql = "SELECT * FROM SERIESUSR WHERE anulado != '1'";
            return $this->queryInEmpresa($empresaCode, $sql);
        } catch (Exception $e) {
            $this->logError("Error obteniendo usuarios de empresa $empresaCode: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejemplo: Obtener datos de múltiples empresas al mismo tiempo
     * @param array $empresas Array de códigos de empresa
     * @return array
     */
    function getDataFromMultipleEmpresas($empresas = ['pruebas_cartimex', 'pruebas_computron', 'sisco'])
    {
        try {
            $queries = [];

            foreach ($empresas as $empresa) {
                $queries[$empresa] = [
                    'sql' => "SELECT COUNT(*) as total_users FROM SERIESUSR WHERE anulado != '1'",
                    'params' => []
                ];
            }

            return $this->queryMultipleEmpresas($queries);
        } catch (Exception $e) {
            $this->logError("Error obteniendo datos multi-empresa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejemplo: Comparar datos entre empresa actual y otra empresa
     * @param string $otraEmpresa Código de la otra empresa
     * @return array
     */
    function compararConOtraEmpresa($otraEmpresa)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM SERIESUSR WHERE anulado != '1'";

            // Consulta en empresa actual
            $resultadoActual = $this->query($sql);

            // Consulta en otra empresa
            $resultadoOtra = $this->queryInEmpresa($otraEmpresa, $sql);

            return [
                'success' => true,
                'empresa_actual' => $this->empresaCode,
                'otra_empresa' => $otraEmpresa,
                'datos_actual' => $resultadoActual,
                'datos_otra' => $resultadoOtra
            ];
        } catch (Exception $e) {
            $this->logError("Error comparando empresas: " . $e->getMessage());
            return false;
        }
    }
}
