<?php

// require_once __DIR__ . '/../logsmodel.php';


class BandejaModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);

        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("GuiasPickupModel conectado a: " . $this->empresaCode);
        }
    }

    function GetFacturasAprobacion($params)
    {
        try {
			$inicio = date('Ym01');
			$fin = date('Ymd');

            $sql = "SELECT 
				ID_factura,
				factura_secuencia = secuencia,
				factura_autorizacion = Autorizacion,
				factura_detalle =  Detalle,
				factura_proveedor = Proveedor,
				factura_fecha_solicitud = creado_date,
				factura_fecha_factura = Fecha_factura,
				factura_Fecha_Vencimiento = Fecha_Vencimiento,
				valor_subtotal_12  =  Subtotal_12,
				valor_subtotal_0  =  Subtotal_0,
				valor_iva  =  IVA,
				valor_total = f.Total,
				f.Estado,
				Estado_texto = CASE 
							WHEN f.Estado = 0 then 'Pendiente'
							WHEN f.Estado = 1 then 'Aprobado'
							WHEN f.Estado = 2 then 'Rechazado'
							ELSE ''
							END,
				solicitado_por_usuario = u.usuario,
				solicitado_por_usuario_nombre = u.nombre,
				Buzon = u2.usuario,
				Buzon_nombre = u2.nombre,
				Empresa,
				Tipo_gasto = t.tipo_nombre,
				--
				aprobado_por = isnull(aprobado_por,''),
				aprobado_nota = isnull(Nota_aprobado,''),
				aprobado_fecha = isnull(Fecha_aprobacion,''),
				rechazado_por = isnull(rechazado_por,''),
				rechazado_nota  =  isnull(comentario_rechazo,''),
				rechazado_fecha  =  isnull(fecha_rechazo,''),
				isnull(ds.total,0) as doc_es_total,
				f.CedBeneficiario,
				isnull(f.PREAPROBACION,0) as PREAPROBACION,
				isnull(f.PREAPROBADA,0) as PREAPROBADA,
				isnull(f.PREAPROBADA_POR,'') as PREAPROBADA_POR,
				isnull(f.PREAPROBADA_FECHA,'') as PREAPROBADA_FECHA,
				isnull(f.PREAPROBADA_COMENTARIO,'') as PREAPROBADA_COMENTARIO
				from SGO_PROV_BANCOS_FACTURAS_SUBIDAS f
				left join SERIESUSR u
				on u.usrid = f.Creado_por
				left join SERIESUSR u2
				on u2.usrid = f.Buzon
				left join SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO t
				on t.id = f.TIPO
				LEFT JOIN(
					select  
					sum(Subtotal_0 + Subtotal_12) as total,
					CedBeneficiario
					from SGO_PROV_BANCOS_FACTURAS_SUBIDAS
					where Fecha_creado between '$inicio' and '$fin'
					group by CedBeneficiario
				) as ds on ds.CedBeneficiario = f.CedBeneficiario
				--update SGO_PROV_BANCOS_FACTURAS_SUBIDAS set Buzon = 'KTOMALA'
				where buzon = :buzon
                AND empresa = :empresa
				and f.Estado = :estado
				order by creado_date desc";

            $params = [
                ":buzon" => $params['userdata']["usrid"],
                ":empresa" => strtoupper($params['empresa']),
				":estado" => $params['estado']
            ];
            $result = $this->query($sql, $params);
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error en la consulta: ' . $e->getMessage()
            ];
        }
    }

	function GetFacturasDocumentosEspeciales($cedula){

		try {
			$sql = "SELECT
				d.empleado_id,
				d.valor,
				d.empresa,
				d.estado,
				em.Nombre,
				em.Código,
				u.usuario,
				u.nombre as usuario_nombre
				from 
				SGO_PROV_BANCOS_FACTURAS_DOCUMENTOS_ESPECIALES d
				left join EMP_EMPLEADOS em
				on em.id = d.empleado_id
				left join SERIESUSR u
				on u.usrid = d.created_by
				where Código = :cedula
				and estado = 1";

			$params = [
				":cedula" => $cedula
			];
			$result = $this->query($sql, $params);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta: ' . $e->getMessage()
			];
		}

	}
}
