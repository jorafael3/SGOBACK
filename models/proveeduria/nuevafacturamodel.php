<?php

// require_once __DIR__ . '/../logsmodel.php';


class NuevaFacturaModel extends Model
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

    function Cargar_Proveedores($data)
    {
        try {
            $empresa = strtoupper($data['empresa']);
            $cedula = "%" . $data['busqueda'] . "%";
            $nombre = "%" . $data['busqueda'] . "%";
            $sql = "SELECT 
            ID, 
            Nombre as proveedor,
            Código as proveedor_codigo,
            Ruc as cedula
            from " . $empresa . "..ACR_ACREEDORES
            where Anulado = 0 and Nombre like :nombre or Ruc like :ruc";

            $params = [
                ':nombre' => $nombre,
                ':ruc' => $cedula
            ];
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

    function Cargar_TipoGastos()
    {
        try {
            $sql = "SELECT * from SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO WHERE estado = 1";
            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo tipos de gastos: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo tipos de gastos: ' . $e->getMessage()
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

    function GetImpuestoIva()
    {
        try {
            $sql = "SELECT valor FROM CARTIMEX..SIS_PARAMETROS 
            WHERE código = 'IMP-IVA-15'
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


    function Cargar_Responsables($data)
    {
        try {
            $empresa = "%" . strtoupper($data['empresa']) . "%";
            $tipogasto = "%" . $data['tipo_gasto'] . "%";
            $busqueda = "%" . $data['busqueda'] . "%";
            $busqueda_nombre = "%" . $data['busqueda'] . "%";
            $superamonto = $data['superamontoAprobacion'] ? 1 : 0;
            $extra = "";
            if ($superamonto == 1) {
                $extra = " AND ua.aprobacionpormonto = 1";
            }

            $sql = "SELECT
                ua.usuario_id as usuario_id,
                RTRIM(LTRIM(u.usuario)) as usuario,
                RTRIM(LTRIM(u.nombre)) as nombre,
                em.email as email_empresa,
				em.email_personal
                from CARTIMEX..SGO_USUARIO_APROBADORES ua
                left join CARTIMEX..SERIESUSR u 
                on u.usrid = ua.usuario_id
                left join EMP_EMPLEADOS em
				on em.ID = u.EmpleadoID
                where empresa like :empresa
                and tipogastos like :tipogasto
                and (u.usuario like :busqueda or u.nombre like :busqueda_nombre)
                and ua.estado = 1
                $extra";
            $params = [
                ':empresa' => $empresa,
                ':tipogasto' => $tipogasto,
                ':busqueda' => $busqueda,
                ':busqueda_nombre' => $busqueda_nombre
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo responsables: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error obteniendo responsables: ' . $e->getMessage()
            ];
        }
    }

    function ValidarSecuenciaAutorizacion($data)
    {
        try {
            $secuencia = $data['secuencia'];
            $autorizacion = $data['autorizacion'];
            $empresa = $data['empresa'];

            $sql = "SELECT * FROM SGO_PROV_BANCOS_FACTURAS_SUBIDAS WHERE secuencia = :secuencia AND autorizacion = :autorizacion AND empresa = :empresa";
            $params = [
                ':secuencia' => $secuencia,
                ':autorizacion' => $autorizacion,
                ':empresa' => $empresa
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error validando secuencia y autorizacion: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error validando secuencia y autorizacion: ' . $e->getMessage()
            ];
        }
    }

    function ValidarTipoGasto($data)
    {
        try {
            $tipoGasto = $data['tipoGasto'];

            $sql = "SELECT * FROM SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO WHERE id = :tipoGasto";
            $params = [
                ':tipoGasto' => $tipoGasto,
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error validando tipo de gasto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error validando tipo de gasto: ' . $e->getMessage()
            ];
        }
    }

    function GuardarFactura($data)
    {
        try {

            $secuencia = $data['secuencia'];
            $detalle = "doc # " . $secuencia . " - " . $data['detalle'];
            $fecha_factura = date('Ymd', strtotime($data['fechaFactura']));
            $fecha_vencimiento = date('Ymd', strtotime($data['fechaVencimiento']));
            $autorizacion = $data['autorizacion'];
            $archivo = $data['documentos'][0]["nombre"];
            $proveedor = $data['proveedor']['proveedor'];
            $ID_proveedor = $data['proveedor']['ID'];
            $subtotal_12 = $data['subtotal15'];
            $subtotal_0 = $data['subtotal0'];
            $IVA = $data['iva'];
            $total = $data['total'];
            $estado = 0;
            $creado_por = $data['userdata']['usrid'];
            $buzon = $data['responsable']['usuario_id'];
            $empresa = $data['empresa'];
            $canje = $data['canje'] == true ? 1 : 0;
            $TIPO = $data['tipoGasto'];
            $CedBeneficiario = $data['cedulaBeneficiario'];
            $preaprobacion = $data['preaprobacion'];


            $sql = "INSERT INTO SGO_PROV_BANCOS_FACTURAS_SUBIDAS (
                secuencia,
                detalle,
                fecha_factura,
                fecha_vencimiento,
                autorizacion,
                archivo,
                fecha_creado,
                proveedor,
                ID_proveedor,
                subtotal_12,
                subtotal_0,
                IVA,
                total,
                estado,
                creado_por,
                buzon,
                empresa,
                canje,
                TIPO,
                CedBeneficiario,
                PREAPROBACION   
            ) VALUES (
                :secuencia,
                :detalle,
                :fecha_factura,
                :fecha_vencimiento,
                :autorizacion,
                :archivo,
                GETDATE(),
                :proveedor,
                :ID_proveedor,
                :subtotal_12,
                :subtotal_0,
                :IVA,
                :total,
                :estado,
                :creado_por,
                :buzon,
                :empresa,
                :canje,
                :TIPO,
                :CedBeneficiario,
                :preaprobacion
            )";
            $params = [
                ':secuencia' => $secuencia,
                ':detalle' => $detalle,
                ':fecha_factura' => $fecha_factura,
                ':fecha_vencimiento' => $fecha_vencimiento,
                ':autorizacion' => $autorizacion,
                ':archivo' => $archivo,
                ':proveedor' => $proveedor,
                ':ID_proveedor' => $ID_proveedor,
                ':subtotal_12' => $subtotal_12,
                ':subtotal_0' => $subtotal_0,
                ':IVA' => $IVA,
                ':total' => $total,
                ':estado' => $estado,
                ':creado_por' => $creado_por,
                ':buzon' => $buzon,
                ':empresa' => $empresa,
                ':canje' => $canje,
                ':TIPO' => $TIPO,
                ':CedBeneficiario' => $CedBeneficiario,
                ':preaprobacion' => $preaprobacion
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error guardando factura: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error guardando factura: ' . $e->getMessage()
            ];
        }
    }


    //*** SOLICITADAS */

    function Cargar_FacturasSolicitadas()
    {
        try {
            $sql = "SELECT * from SGO_PROV_BANCOS_FACTURAS_SUBIDAS WITH (NOLOCK) ORDER BY fecha_creado DESC";
            $stmt = $this->db->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error cargando facturas solicitadas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error cargando facturas solicitadas: ' . $e->getMessage()
            ];
        }
    }
}
