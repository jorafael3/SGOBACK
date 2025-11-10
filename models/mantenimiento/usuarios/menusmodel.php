<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class MenusModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
    }

    function GetMenusLista() {
        try {
            $sql = "SELECT 
                *
            FROM SGO_MENU_SISTEMA
            ORDER BY orden";

            $params = [];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo menús: " . $e->getMessage());
            return false;
        }
    }
}
