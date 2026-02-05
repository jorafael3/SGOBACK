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

    function Cargar_FacturasSolicitadas($data)
    {
        $filtro = $data['estado'] == "" ? '' : ($data["estado"] == 0 ? " AND f.Estado = 0 " : ($data["estado"] == 1 ? " AND f.Estado = 1 " : ($data["estado"] == 2 ? " AND f.Estado = 2 " : '')));
        try {
            $sql = "
                    DECLARE @monto_aprobacion DECIMAL(18,2);
					SELECT  @monto_aprobacion = valor FROM CARTIMEX..SIS_PARAMETROS 
            		WHERE código = 'SGO_PROV_MONTO_APROBACION';
            
            WITH BAN_EG as (
                            SELECT 
                            acr.DeudaID,
                            e.Cheque,
                            e.SGO_ENTREGADO,
                            e.SGO_FECHA_ENTREGADO,
                            e.SGO_ENTREGADO_POR,
                            e.SGO_FIRMADO,
                            e.SGO_FIRMADO_FECHA,
                            e.SGO_FIRMADO_POR,
                            e.SGO_ENTREGADO_CAJA,
                            e.SGO_ENTREGADO_CAJA_FECHA,
                            e.SGO_ENTREGADO_CAJA_POR
                            FROM BAN_EGRESOS e WITH(NOLOCK)
                            LEFT JOIN ACR_ACREEDORES_DEUDAS acr WITH(NOLOCK)
                            on acr.DocumentoID = e.ID --and acr.Tipo = 'BAN-EG'
                            where e.Anulado = 0
                            union all
                            SELECT 
                            acr.DeudaID,
                            e.Cheque,
                            e.SGO_ENTREGADO,
                            e.SGO_FECHA_ENTREGADO,
                            e.SGO_ENTREGADO_POR,
                            e.SGO_FIRMADO,
                            e.SGO_FIRMADO_FECHA,
                            e.SGO_FIRMADO_POR,
                            e.SGO_ENTREGADO_CAJA,
                            e.SGO_ENTREGADO_CAJA_FECHA,
                            e.SGO_ENTREGADO_CAJA_POR
                            FROM ACR_RECIBOS e WITH(NOLOCK)
                            LEFT JOIN ACR_ACREEDORES_DEUDAS acr WITH(NOLOCK)
                            on acr.DocumentoID = e.ID --and acr.Tipo = 'BAN-EG'
                            where e.Anulado = 0
                            union all
                            SELECT 
                            acr.DeudaID,
                            e.Cheque,
                            e.SGO_ENTREGADO,
                            e.SGO_FECHA_ENTREGADO,
                            e.SGO_ENTREGADO_POR,
                            e.SGO_FIRMADO,
                            e.SGO_FIRMADO_FECHA,
                            e.SGO_FIRMADO_POR,
                            e.SGO_ENTREGADO_CAJA,
                            e.SGO_ENTREGADO_CAJA_FECHA,
                            e.SGO_ENTREGADO_CAJA_POR
                            FROM EMP_ROLES e WITH(NOLOCK)
                            LEFT JOIN ACR_ACREEDORES_DEUDAS acr WITH(NOLOCK)
                            on acr.DocumentoID = e.ID --and acr.Tipo = 'BAN-EG'
                            where e.Anulado = 0
                        )
                SELECT
                FACT_ID = f.ID_factura,
                FACT_SECUENCIA = f.secuencia,
                FACT_DETALLE = f.Detalle,
                FACT_FECHA_FACTURA = f.Fecha_factura,
                FACT_FECHA_VENCIMIENTO = f.Fecha_Vencimiento,

                FACT_AUTORIZACION = f.Autorizacion,
                FACT_ARCHIVO = f.Archivo,
                FACT_FECHA_CREADO = f.Fecha_creado,
                FACT_PROVEEDOR = f.Proveedor,
                FACT_SUBTOTAL_15 = f.Subtotal_12,
                FACT_SUBTOTAL_0 = f.Subtotal_0,
                FACT_IVA = f.IVA,
                FACT_TOTAL = f.Total,
                FACT_RETENIDO = acr.Retenido,
                FACT_ESTADO = f.Estado,
                FACT_CREADO_POR = u.usuario,
                FACT_BUZON = u2.usuario,

                FACT_APROBADO_POR = f.aprobado_por,
                FACT_APROBADO_FECHA = f.Fecha_aprobacion,
                FACT_APROBADO_COMENTARIO = f.Nota_aprobado,
                FACT_RECHAZADO_POR = f.rechazado_por,
                FACT_RECHAZADO_FECHA = f.fecha_rechazo,
                FACT_RECHAZADO_COMENTARIO = f.comentario_rechazo,
                FACT_EMPRESA = f.Empresa,

                FACT_TIPO_GASTO = t.tipo_nombre,
				FACT_TIPO_GASTO_NECESITA_PREAPROBACION = t.preaprobacion,

                isnull(f.PREAPROBACION,0) as PREAPROBACION,
				CASE WHEN f.subtotal_0 + f.subtotal_12 >= @monto_aprobacion then 1 else 0 end as FACT_DOCUMENTO_PREAPROBADO_MONTO,
                FACT_DOCUMENTO_PREAPROBADO = f.PREAPROBADA,
                FACT_DOCUMENTO_PREAPROBADO_POR = isnull(f.PREAPROBADA_POR,'')	,
                FACT_DOCUMENTO_PREAPROBADO_FECHA = isnull(f.PREAPROBADA_FECHA,''),
                FACT_DOCUMENTO_PREAPROBADO_COMENTARIO = isnull(f.PREAPROBADA_COMENTARIO,''),

                FACT_INGRESADA = CASE WHEN isnull(pr.ID,'') = '' THEN 0 ELSE 1 END,
                FACT_INGRESADA_ID = isnull(pr.ID,''),
                FACT_INGRESADA_FECHA = pr.CreadoDate,
                FACT_INGRESADA_POR = pr.CreadoPor,

                PAGO_APROBADO = isnull(acr.SGO_PAGO_APROBACION,0),
                PAGO_APROBADO_POR = acr.SGO_PAGO_APROBACION_POR,
                PAGO_APROBADO_FECHA = acr.SGO_PAGO_APROBACION_FECHA,
                PAGO_APROBADO_COMENTARIO = acr.SGO_PAGO_APROBACION_COMENTARIO,

                PAGO_RECHAZADO = isnull(acr.DESCARTAR,0),
                PAGO_RECHAZADO_POR = acr.SGO_PAGO_DESCARTADO_POR,
                PAGO_RECHAZADO_FECHA = acr.SGO_PAGO_DESCARTADO_FECHA,
                PAGO_RECHAZADO_COMENTARIO = acr.SGO_PAGO_DESCARTADO_COMENTARIO,

                PAGO_GENERADO = isnull(acr.SGO_PAGO_GENERADO,0),
                PAGO_GENERADO_POR = acr.SGO_PAGO_GENERADO_POR,
                PAGO_GENERADO_FECHA = acr.SGO_PAGO_GENERADO_FECHA,
                PAGO_GENERADO_COMENTARIO = acr.SGO_PAGO_GENERADO_COMENTARIO,

                SGO_FIRMADO = isnull(eg.SGO_FIRMADO,0),
                SGO_FIRMADO_POR = eg.SGO_FIRMADO_POR,
                SGO_FIRMADO_FECHA = eg.SGO_FIRMADO_FECHA,

                SGO_ENTREGADO_CAJA = isnull(eg.SGO_ENTREGADO_CAJA,0),
                eg.SGO_ENTREGADO_CAJA_POR,
                eg.SGO_ENTREGADO_CAJA_FECHA,

                SGO_ENTREGADO = isnull(eg.SGO_ENTREGADO,0),
                eg.SGO_ENTREGADO_POR,
                SGO_ENTREGADO_FECHA = eg.SGO_FECHA_ENTREGADO

                FROM SGO_PROV_BANCOS_FACTURAS_SUBIDAS f with(nolock)
                LEFT JOIN SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO t with(nolock)
				on t.id = f.TIPO
                LEFT JOIN PRV_FACTURAS pr  with(nolock)
                    on pr.acrSerie+'-'+pr.acrSecuencia = f.secuencia
                    and pr.acrAutorización = f.Autorizacion
                left join ACR_ACREEDORES_DEUDAS acr  with(nolock)
                    on acr.DocumentoID = pr.ID
                left join BAN_EG eg
                    on eg.DeudaID = acr.ID
                left join SERIESUSR u with(nolock)
                    ON (
                        (ISNUMERIC(f.Creado_por) = 1 
                        AND u.usrid = f.Creado_por)
                        OR
                        (ISNUMERIC(f.Creado_por) = 0 
                        AND u.usuario = f.Creado_por)
                    )
                 left join SERIESUSR u2 with(nolock)
                    ON (
                        (ISNUMERIC(f.buzon) = 1 
                        AND u2.usrid = f.buzon)
                        OR
                        (ISNUMERIC(f.buzon) = 0 
                        AND u2.usuario = f.buzon)
                    )
                where 
                    1=1
                    " . $filtro . "
                    and (
                    f.Creado_por = '" . $data['userdata']['usuario'] . "'
                    or f.Creado_por = '" . $data['userdata']['usrid'] . "')

                ORDER by f.Fecha_creado desc    
                ";
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
