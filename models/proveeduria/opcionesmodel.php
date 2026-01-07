<?php

// require_once __DIR__ . '/../logsmodel.php';


class OpcionesModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("DespacharFacturasModel conectado a: " . $this->empresaCode);
        }
    }

    // ** RUBROS CENTROS DE COSTO */

    function GetRubros()
    {
        try {
            $sql = "SELECT * from SGO_RUBROS_CENTROS_COSTOS";

            $params = [];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo rubros: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo rubros: ' . $e->getMessage()
            ];
        }
    }
    function SetGuardarRubro($data)
    {
        try {
            //
            $sql = "INSERT INTO SGO_RUBROS_CENTROS_COSTOS (rubro_nombre, creado_por,estado) 
            VALUES (:rubro_nombre, :creado_por, :estado)";
            $params = [
                ':rubro_nombre' => $data["rubro_nombre"],
                ':creado_por' => $data["userdata"]["usrid"],
                ':estado' => $data["estado"]
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error guardando rubro: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error guardando rubro: ' . $e->getMessage()
            ];
        }
    }

    //** CENTROS DE COSTO */

    function GetCentrosCostos()
    {
        try {
            $sql = "SELECT 
                cc.*,
                cc.año as Anio,
                r.rubro_nombre,
                d.Nombre as CentroCostonombre
                from SGO_PRESUPUESTOS_CENTROS_COSTOS cc
                left join SGO_RUBROS_CENTROS_COSTOS r
                on r.id = cc.RubroID
                left join SIS_DIVISIONES d
                on d.ID = cc.CentroCostoID";
            $params = [];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo rubros: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo rubros: ' . $e->getMessage()
            ];
        }
    }

    function GetDivisiones()
    {
        try {
            $sql = "SELECT ID, Nombre from SIS_DIVISIONES";
            $params = [];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo divisiones: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo divisiones: ' . $e->getMessage()
            ];
        }
    }

    function GetRubrosCentrosCostos()
    {
        try {
            $sql = "SELECT ID,rubro_nombre from SGO_RUBROS_CENTROS_COSTOS where estado = 1";

            $params = [];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo rubros: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo rubros: ' . $e->getMessage()
            ];
        }
    }

    function SetGuardarCentroCostos($data)
    {

        try {
            $sql = "INSERT INTO SGO_PRESUPUESTOS_CENTROS_COSTOS
                (Mes,Año,RubroID,CentroCostoID,Valor,Recurrente,UsuarioCreacion)
                VALUES
                (:Mes,:Anio,:RubroID,:CentroCostoID,:Valor,:Recurrente,:UsuarioCreacion)
             ";
            $params = [
                ':Mes' => $data["Mes"],
                ':Anio' => $data["Anio"],
                ':RubroID' => $data["rubro_id"],
                ':CentroCostoID' => $data["division_id"],
                ':Valor' => $data["valor"],
                ':Recurrente' => $data["Recurrente"],
                ':UsuarioCreacion' => $data["userdata"]["usrid"],
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error guardando centro de costos: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error guardando centro de costos: ' . $e->getMessage()
            ];
        }
    }

    //*** APROBADORES */

    function GetUsuariosAprobadores()
    {
        try {
            $sql = "SELECT
                ap.usuario_id,
                us.usuario,
                us.nombre,
                ap.empresa,
                AP.tipogastos,
                ap.estado,
                ap.aprobacionpormonto,
                STUFF((
                    SELECT ', ' + tg.tipo_nombre
                    FROM CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO tg
                    WHERE tg.id IN (
                        SELECT x.i.value('.', 'INT')
                        FROM (
                            SELECT CAST('<i>' + REPLACE(ap.tipogastos, ',', '</i><i>') + '</i>' AS XML) AS xmlcol
                        ) a
                        CROSS APPLY xmlcol.nodes('/i') x(i)
                    )
                    FOR XML PATH(''), TYPE
                ).value('.', 'NVARCHAR(MAX)'), 1, 2, '') AS tipogastos_nombre
            FROM CARTIMEX..SGO_USUARIO_APROBADORES ap
            INNER JOIN CARTIMEX..SERIESUSR us
                ON us.usrid = ap.usuario_id;
            ";
            $params = [];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo rubros: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo rubros: ' . $e->getMessage()
            ];
        }
    }

    function GetMontoAprobacion()
    {
        try {
            $sql = "SELECT valor FROM CARTIMEX..SIS_PARAMETROS 
            WHERE código = 'SGO_PROV_MONTO_APROBACION'
            ";
            $params = [];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo rubros: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo rubros: ' . $e->getMessage()
            ];
        }
    }

    function GetUsuariosSgo()
    {
        try {
            $sql = "SELECT  * FROM SERIESUSR where anulado = 0 order by nombre 
                --and usrid not in (select usuario_id from SGO_USUARIO_APROBADORES)
            ";
            $params = [];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo rubros: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo rubros: ' . $e->getMessage()
            ];
        }
    }

    function GetTipoGastos()
    {
        try {
            $sql = "SELECT * from SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO
            ";
            $params = [];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo rubros: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo rubros: ' . $e->getMessage()
            ];
        }
    }

    function validarAprobador($data)
    {
        try {
            $usrid = $data["userid"];
            $sql = "SELECT * FROM SGO_USUARIO_APROBADORES WHERE usuario_id = :usuario_id";
            $params = [':usuario_id' => $usrid];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo rubros: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo rubros: ' . $e->getMessage()
            ];
        }
    }

    function GuardarAprobador($data)
    {
        try {

            $usrid = $data["userid"];
            $tipoGastos = implode(',', $data["tipogastos"]);
            $empresa = implode(',', $data["empresa"]);
            $estado = $data["estado"];
            $aprobacionpormonto = $data["aprobacionpormonto"];

            $sql = "INSERT INTO SGO_USUARIO_APROBADORES
                (usuario_id,empresa,tipogastos,estado,aprobacionpormonto)
                VALUES
                (:usuario_id,:empresa,:tipogastos,:estado,:aprobacionpormonto)
            ";
            $params = [
                ':usuario_id' => $usrid,
                ':empresa' => $empresa,
                ':tipogastos' => $tipoGastos,
                ':estado' => $estado,
                ':aprobacionpormonto' => $aprobacionpormonto
                // ':creado_por' => $data["userdata"]["usrid"],
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error guardando aprobador: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error guardando aprobador: ' . $e->getMessage()
            ];
        }
    }

    function EditarAprobador($data)
    {
        try {

            $usrid = $data["userid"];
            $tipoGastos = implode(',', $data["tipogastos"]);
            $empresa = implode(',', $data["empresa"]);
            $estado = $data["estado"];
            $aprobacionpormonto = $data["aprobacionpormonto"];

            $sql = "UPDATE SGO_USUARIO_APROBADORES
                SET empresa = :empresa,
                    tipogastos = :tipogastos,
                    estado = :estado,
                    aprobacionpormonto = :aprobacionpormonto
                WHERE usuario_id = :usuario_id
            ";
            $params = [
                ':usuario_id' => $usrid,
                ':empresa' => $empresa,
                ':tipogastos' => $tipoGastos,
                ':estado' => $estado,
                ':aprobacionpormonto' => $aprobacionpormonto
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error editando aprobador: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error editando aprobador: ' . $e->getMessage()
            ];
        }
    }

    function SetEditarMontoAprobacion($data)
    {
        try {
            $valor = $data["valor"];
            $sql = "UPDATE CARTIMEX..SIS_PARAMETROS 
            SET valor = :valor
            WHERE código = 'SGO_PROV_MONTO_APROBACION'
            ";
            $params = [
                ':valor' => $valor
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error editando monto de aprobacion: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error editando monto de aprobacion: ' . $e->getMessage()
            ];
        }
    }

    //** PROVEEDORES ESPECIALES */

    function GetProveedoresEspeciales()
    {
        try {
            $sql = "SELECT
				d.empleado_id,
				d.valor,
				d.empresa,
				d.estado,
				em.Nombre
				from 
				SGO_PROV_BANCOS_FACTURAS_DOCUMENTOS_ESPECIALES d
				left join EMP_EMPLEADOS em
				on em.id = d.empleado_id";

            $params = [];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo proveedores especiales: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo proveedores especiales: ' . $e->getMessage()
            ];
        }
    }
}
