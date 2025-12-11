<?php

// require_once __DIR__ . '/../logsmodel.php';

require("listactmodel.php");

class crearactModel extends Model
{

    private $listactModel;
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
        $this->listactModel = new listactModel();
        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("GuiasPickupModel conectado a: " . $this->empresaCode);
        }
    }

    function Guardar_datos($param)
    {
        try {
            $Creado_por = $param['usrid'] ?? null;
            $Tipo = $param["Tipo"] ?? null;
            $MarcaID = $param["MarcaID"] ?? null;
            $MarcaNombre = $param["MarcaNombre"] ?? null;
            $Referencia = $param["Referencia"] ?? null;
            $Periodo = $param["Periodo"] ?? null;
            $Concepto = $param["Concepto"] ?? null;
            $valor = $param["valor"] ?? 0;
            $Fecha_actividad = $param["Fecha_actividad"] ?? null;
            $Cheek_marca = $param["Cheek_marca"] ?? 0;

            $sql = "INSERT INTO SGO_Actividades_Marcas (creado_por, Tipo, Marca, MarcaNombre, Referencia, Periodo, Concepto, valor, Fecha_actividad, Tipo_marca)
            VALUES (:usuario, :Tipo, :MarcaID, :MarcaNombre, :Referencia, :Periodo, :Concepto, :valor, :Fecha_actividad, :Cheek_marca)";
            $params = [
                ':usuario' => $Creado_por,
                ':Tipo' => $Tipo,
                ':MarcaID' => $MarcaID,
                ':MarcaNombre' => $MarcaNombre,
                ':Referencia' => $Referencia,
                ':Periodo' => $Periodo,
                ':Concepto' => $Concepto,
                ':valor' => $valor,
                ':Fecha_actividad' => $Fecha_actividad,
                ':Cheek_marca' => $Cheek_marca
            ];
            $query = $this->db->execute($sql, $params);
            if ($query) {
                $lastId = $this->db->lastInsertId();
                $lastId = str_pad($lastId, 10, '0', STR_PAD_LEFT);

                $TIPOS_MARCAS = $this->Cargar_Tipos_Marcas();

                $tipo_marca_filtrado = array_filter($TIPOS_MARCAS['data'], function ($item) use ($Tipo) {
                    return $item['id'] == $Tipo;
                });
                if (!empty($tipo_marca_filtrado) && isset(array_values($tipo_marca_filtrado)[0]["consolidado"]) && array_values($tipo_marca_filtrado)[0]["consolidado"] == 1) {
                    $param["ACTIVIDAD_ID"] = $lastId;
                    #TODO: Revisar error en el model listact
                    $queryListAct = $this->listactModel->Consolidar_Actividades($param);
                    if($queryListAct && $queryListAct["success"]) return $query;
                }
            } else {
                return [];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    function Cargar_Tipos_Marcas()
    {
        try {
            $sql = "SELECT * FROM SGO_ACTIVIDADES_MARCAS_TIPOS";
            $query = $this->db->query($sql, []);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Cargar_Marcas($param)
    {
        try {
            $marca = $param['marca'] ?? '';
            if ($marca === 0) {
                $sql = "SELECT ID,Nombre from SIS_PARAMETROS with(NOLOCK) where PadreID = '0000000026'";
            } else {
                $sql = "SELECT ID, Nombre FROM ACR_ACREEDORES with(NOLOCK)";
            }
            $query = $this->db->query($sql, []);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }


}