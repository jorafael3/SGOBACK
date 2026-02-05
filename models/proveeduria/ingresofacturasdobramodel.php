<?php

// require_once __DIR__ . '/../logsmodel.php';


class ingresofacturasdobramodel extends Model
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

	function GetFacturasPorIngresar($params)
	{
		try {
			$inicio = date('Ym01');
			$fin = date('Ymd');

			$fecha_inicio = isset($params['fecha_inicio']) ? (new DateTime($params['fecha_inicio']))->format('Ymd') : $inicio;
			$fecha_fin = isset($params['fecha_fin']) ? (new DateTime($params['fecha_fin']))->format('Ymd') : $fin;

			$estado = $params['estado'] ?? null;
			$empresa = $params['empresa'] ?? null;

			$sqlpendientes = "
                DECLARE @monto_aprobacion DECIMAL(18,2);
					SELECT  @monto_aprobacion = valor FROM CARTIMEX..SIS_PARAMETROS 
            		WHERE código = 'SGO_PROV_MONTO_APROBACION'
			
			
				SELECT 
				ID_factura,
				factura_secuencia = f.secuencia,
				factura_autorizacion = Autorizacion,
				factura_detalle =  f.Detalle,
				factura_proveedor = Proveedor,
				factura_fecha_solicitud = Fecha_Creado,
				factura_fecha_factura = Fecha_factura,
				factura_Fecha_Vencimiento = Fecha_Vencimiento,
				factura_archivo = Archivo,
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
				solicitado_por_usuarioid = u.usrid,
				solicitado_por_usuario = u.usuario,
				solicitado_por_usuario_nombre = LTRIM(RTRIM(u.nombre)),
				solicitado_por_usuario_email = isnull(em.email,u.email_sgo),

				Empresa,
				Tipo_gasto = t.tipo_nombre,
				Tipo_gasto_necesita_preaprobacion = t.preaprobacion,
				--
				aprobado_por = isnull(u4.usuario,''),
				aprobado_nota = isnull(Nota_aprobado,''),
				aprobado_fecha = isnull(Fecha_aprobacion,''),
				rechazado_por = isnull(u5.usuario,''),
				rechazado_nota  =  isnull(comentario_rechazo,''),
				rechazado_fecha  =  isnull(fecha_rechazo,''),
				isnull(ds.total,0) as doc_es_total,
				f.CedBeneficiario,
				isnull(f.PREAPROBACION,0) as PREAPROBACION,
				CASE WHEN f.subtotal_0 + f.subtotal_12 >= @monto_aprobacion then 1 else 0 end as PREAPROBACION_MONTO,
				isnull(f.PREAPROBADA,0) as PREAPROBADA,
				isnull(u3.usuario,'') as PREAPROBADA_POR,
				isnull(f.PREAPROBADA_FECHA,'') as PREAPROBADA_FECHA,
				isnull(f.PREAPROBADA_COMENTARIO,'') as PREAPROBADA_COMENTARIO,

				fa.CreadoDate as fecha_ingresada_factura_dobra,
                case when fa.acrAutorización is null then f.ingresado_estado else 1 end as ingresado_estado,
                case when fa.acrAutorización is null then f.orden_dobra else fa.ID end as orden_dobra,
                case when fa.acrAutorización is null then f.ingresada_por else fa.CreadoPor end as ingresada_por,
                case when fa.acrAutorización is null then CONVERT(VARCHAR(20),f.ingresada_fecha,23) else 'DOBRA' end as ingresada_fecha_por_dobra

				from CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS f
				left join " . $empresa . "..SERIESUSR u
				ON (
					(ISNUMERIC(f.Creado_por) = 1 
					 AND u.usrid = f.Creado_por)
					OR
					(ISNUMERIC(f.Creado_por) = 0 
					 AND u.usuario = f.Creado_por)
				   )
				left join " . $empresa . "..SERIESUSR u3
				ON (
					(ISNUMERIC(f.PREAPROBADA_POR) = 1 
					 AND u3.usrid = f.PREAPROBADA_POR)
					OR
					(ISNUMERIC(f.PREAPROBADA_POR) = 0 
					 AND u3.usuario = f.PREAPROBADA_POR)
				  )
				left join " . $empresa . "..SERIESUSR u4
				ON (
					(ISNUMERIC(f.aprobado_por) = 1 
					 AND u4.usrid = f.aprobado_por)
					OR
					(ISNUMERIC(f.aprobado_por) = 0 
					 AND u4.usuario = f.aprobado_por)
				  )
				left join " . $empresa . "..SERIESUSR u5
				on u5.usrid = f.rechazado_por
				left join CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO t
				on t.id = f.TIPO
				LEFT JOIN(
					select  
					sum(Subtotal_0 + Subtotal_12) as total,
					CedBeneficiario
					from SGO_PROV_BANCOS_FACTURAS_SUBIDAS
					where Fecha_creado between '$fecha_inicio' and '$fecha_fin'
					group by CedBeneficiario
				) as ds on ds.CedBeneficiario = f.CedBeneficiario
				LEFT JOIN " . $empresa . "..emp_empleados em
				on em.id = u.EmpleadoID
				LEFT join PRV_FACTURAS fa on 
				case when len(f.Autorizacion) > 11 then fa.acrAutorización  else
				fa.acrAutorización +'-'+fa.acrSerie end =
				case when len(f.Autorizacion) > 11 then  f.Autorizacion else
				f.Autorizacion+'-'+f.secuencia end
				and f.ID_Proveedor = fa.ProveedorID
				where 
                f.empresa = '$empresa'
				and f.Estado = 1
				and f.Fecha_creado between '$fecha_inicio' and '$fecha_fin'
				and isnull(fa.acrAutorización,'') = ''
				order by Fecha_Creado desc";


			$sqlingresadas = "
				DECLARE @monto_aprobacion DECIMAL(18,2);
				SELECT  @monto_aprobacion = valor FROM CARTIMEX..SIS_PARAMETROS 
            	WHERE código = 'SGO_PROV_MONTO_APROBACION'

				SELECT 
				ID_factura,
				factura_secuencia = f.secuencia,
				factura_autorizacion = Autorizacion,
				factura_detalle =  f.Detalle,
				factura_proveedor = Proveedor,
				factura_fecha_solicitud = Fecha_Creado,
				factura_fecha_factura = Fecha_factura,
				factura_Fecha_Vencimiento = Fecha_Vencimiento,
				factura_archivo = Archivo,
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
				solicitado_por_usuarioid = u.usrid,
				solicitado_por_usuario = u.usuario,
				solicitado_por_usuario_nombre = LTRIM(RTRIM(u.nombre)),
				solicitado_por_usuario_email = isnull(em.email,u.email_sgo),

				Empresa,
				Tipo_gasto = t.tipo_nombre,
				Tipo_gasto_necesita_preaprobacion = t.preaprobacion,
				--
				aprobado_por = isnull(u4.usuario,''),
				aprobado_nota = isnull(Nota_aprobado,''),
				aprobado_fecha = isnull(Fecha_aprobacion,''),
				rechazado_por = isnull(u5.usuario,''),
				rechazado_nota  =  isnull(comentario_rechazo,''),
				rechazado_fecha  =  isnull(fecha_rechazo,''),
				isnull(ds.total,0) as doc_es_total,
				f.CedBeneficiario,
				isnull(f.PREAPROBACION,0) as PREAPROBACION,
				CASE WHEN f.subtotal_0 + f.subtotal_12 >= @monto_aprobacion then 1 else 0 end as PREAPROBACION_MONTO,
				isnull(f.PREAPROBADA,0) as PREAPROBADA,
				isnull(u3.usuario,'') as PREAPROBADA_POR,
				isnull(f.PREAPROBADA_FECHA,'') as PREAPROBADA_FECHA,
				isnull(f.PREAPROBADA_COMENTARIO,'') as PREAPROBADA_COMENTARIO,

				fa.CreadoDate as fecha_ingresada_factura_dobra,
                case when fa.acrAutorización is null then f.ingresado_estado else 1 end as ingresado_estado,
                case when fa.acrAutorización is null then f.orden_dobra else fa.ID end as orden_dobra,
                case when fa.acrAutorización is null then f.ingresada_por else fa.CreadoPor end as ingresada_por,
                case when fa.acrAutorización is null then CONVERT(VARCHAR(20),f.ingresada_fecha,23) else 'DOBRA' end as ingresada_fecha_por_dobra

				from CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS f
				left join " . $empresa . "..SERIESUSR u
				ON (
					(ISNUMERIC(f.Creado_por) = 1 
					 AND u.usrid = f.Creado_por)
					OR
					(ISNUMERIC(f.Creado_por) = 0 
					 AND u.usuario = f.Creado_por)
				   )
				left join " . $empresa . "..SERIESUSR u3
				ON (
					(ISNUMERIC(f.PREAPROBADA_POR) = 1 
					 AND u3.usrid = f.PREAPROBADA_POR)
					OR
					(ISNUMERIC(f.PREAPROBADA_POR) = 0 
					 AND u3.usuario = f.PREAPROBADA_POR)
				  )
				left join " . $empresa . "..SERIESUSR u4
				ON (
					(ISNUMERIC(f.aprobado_por) = 1 
					 AND u4.usrid = f.aprobado_por)
					OR
					(ISNUMERIC(f.aprobado_por) = 0 
					 AND u4.usuario = f.aprobado_por)
				  )
				left join " . $empresa . "..SERIESUSR u5
				on u5.usrid = f.rechazado_por
				left join CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO t
				on t.id = f.TIPO
				LEFT JOIN(
					select  
					sum(Subtotal_0 + Subtotal_12) as total,
					CedBeneficiario
					from CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS
					where Fecha_creado between '$fecha_inicio' and '$fecha_fin'
					group by CedBeneficiario
				) as ds on ds.CedBeneficiario = f.CedBeneficiario
				LEFT JOIN " . $empresa . "..emp_empleados em
				on em.id = u.EmpleadoID
				inner join " . $empresa . "..PRV_FACTURAS fa on 
				case when len(f.Autorizacion) > 11 then fa.acrAutorización  else
				fa.acrAutorización +'-'+fa.acrSerie end =
				case when len(f.Autorizacion) > 11 then  f.Autorizacion else
				f.Autorizacion+'-'+f.secuencia end
				and f.ID_Proveedor = fa.ProveedorID
				where 
                f.empresa = '$empresa'
				and f.Estado = 1
				and f.Fecha_creado between '$fecha_inicio' and '$fecha_fin'
				order by Fecha_Creado desc
			
			";

			$params = [
				// ":empresa" => strtoupper($params['empresa']),
				// ":estado" => $params['estado']
			];
			if ($estado == 1) {
				$sql = $sqlpendientes;
			} else {
				$sql = $sqlingresadas;
			}
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
