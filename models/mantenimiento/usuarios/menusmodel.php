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

    function GetMenusLista()
    {
        try {
            $sql = "SELECT 
                *
            FROM CARTIMEX..SGO_MENU_SISTEMA
            ORDER BY menuid ASC";

            $params = [];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo menús: " . $e->getMessage());
            return false;
        }
    }

    function CrearSubMenu($data)
    {
        try {

            $sql = "DECLARE @Id INT;
                EXEC CARTIMEX..SGO_MENU_INSERTAR_NUEVO
                    @Empresa = :empresa,
                    @Titulo = :titulo,
                    @Type = :type,
                    @Icono = :icono,
                    @Orden = :orden,
                    @Badge = :badge,
                    @BadgeValue = :badge_value,
                    @BadgeColor = :badge_color,
                    @CreadoPor = :creado_por,
                    @NuevoMenuId = @Id OUTPUT;
                SELECT @Id AS NuevoMenuId;";

            $params = [
                ':empresa' => $data['Empresa'],
                ':titulo' => $data['Titulo'],
                ':type' => $data['Type'],
                ':icono' => $data['Icono'],
                ':orden' => $data['Orden'],
                ':badge' => $data['Badge'],
                ':badge_value' => $data['BadgeValue'],
                ':badge_color' => $data['BadgeColor'],
                ':creado_por' => $data['sessionData']["usrid"],
            ];

            if ($data['PadreId'] != null || $data['PadreId'] != "") {
                $sql = "DECLARE @Padre INT;
                    EXEC CARTIMEX..SGO_MENU_INSERTAR_NUEVO
                        @Empresa = :empresa,
                        @Titulo = :titulo,
                        @Type = :type,
                        @Path = NULL,
                        @PadreId = :padre_id,
                        @Orden = :orden,
                        @CreadoPor = :creado_por,
                        @NuevoMenuId = @Padre OUTPUT;";
                $params = array_merge($params, [
                    ':empresa' => $data['Empresa'],
                    ':titulo' => $data['Titulo'],
                    ':type' => $data['Type'],
                    ':padre_id' => $data['PadreId'],
                    ':orden' => $data['Orden'],
                    ':creado_por' => $data['sessionData']["usrid"],
                ]);
            }

            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error creando submenú: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al crear el submenú'];
        }
    }
    function CrearEnlace($data)
    {
        try {

            $sql = "DECLARE @Id INT;
                EXEC CARTIMEX..SGO_MENU_INSERTAR_NUEVO
                    @Empresa = :empresa,
                    @Titulo = :titulo,
                    @Type = :type,
                    @Path = :path,
                    @PadreId = :padre_id,
                    @Orden = :orden,
                    @CreadoPor = :creado_por,
                    @NuevoMenuId = @Id OUTPUT;";

            $params = [
                ':empresa' => $data['Empresa'],
                ':titulo' => $data['Titulo'],
                ':type' => $data['Type'],
                ':path' => $data['Path'],
                ':padre_id' => $data['PadreId'],
                ':orden' => $data['Orden'],
                ':creado_por' => $data['sessionData']["usrid"],
            ];

            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error creando enlace de menú: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al crear el enlace de menú'];
        }
    }
}
