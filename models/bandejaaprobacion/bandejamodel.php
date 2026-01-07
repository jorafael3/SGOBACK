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

	//** APROBACION_FACTURAS */

	function GetFacturasAprobacion($params)
	{
		try {
			$inicio = date('Ym01');
			$fin = date('Ymd');

			$sql = "
				DECLARE @monto_aprobacion DECIMAL(18,2);
				SELECT  @monto_aprobacion = valor FROM CARTIMEX..SIS_PARAMETROS 
            	WHERE código = 'SGO_PROV_MONTO_APROBACION'
			
			
				SELECT 
				ID_factura,
				factura_secuencia = secuencia,
				factura_autorizacion = Autorizacion,
				factura_detalle =  Detalle,
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
				solicitado_por_usuario = u.usuario,
				solicitado_por_usuario_nombre = u.nombre,
				Buzon = u2.usuario,
				Buzon_nombre = u2.nombre,
				Empresa,
				Tipo_gasto = t.tipo_nombre,
				Tipo_gasto_necesita_preaprobacion = t.preaprobacion,
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
				CASE WHEN f.subtotal_0 + f.subtotal_12 >= @monto_aprobacion then 1 else 0 end as PREAPROBACION_MONTO,
				isnull(f.PREAPROBADA,0) as PREAPROBADA,
				isnull(u3.usuario,'') as PREAPROBADA_POR,
				isnull(f.PREAPROBADA_FECHA,'') as PREAPROBADA_FECHA,
				isnull(f.PREAPROBADA_COMENTARIO,'') as PREAPROBADA_COMENTARIO
				from SGO_PROV_BANCOS_FACTURAS_SUBIDAS f
				left join SERIESUSR u
				on u.usrid = f.Creado_por
				left join SERIESUSR u2
				on u2.usrid = f.Buzon
				left join SERIESUSR u3
				on u3.usrid = f.PREAPROBADA_POR
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
				order by Fecha_Creado desc";

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

	function GetFacturasDocumentosEspeciales($cedula)
	{

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

	function GetUsuariosSgo()
	{
		try {
			$sql = "
				SELECT
				u.usrid,
				LTRIM(RTRIM(u.Nombre)) as nombre,
				LTRIM(RTRIM(u.email_sgo)) as email_sgo
				FROM SERIESUSR u
				inner join EMP_EMPLEADOS em
				on em.id = u.EmpleadoID
				where u.anulado = 0 
				and em.Anulado = 0
				and em.Nombre not like '%**%'
				and em.Clase = '01'
				and em.GrupoID in ('0000000007','0000000006')
				and isnull(email_sgo,'') != ''
				order by em.nombre 
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

	function GetUsuariosAprobadores()
	{
		try {
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
                where ua.estado = 1
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

	function GuardarAprobacionRegular($params)
	{

		try {
			$sql = "UPDATE CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS
					SET
						estado = 1,
						aprobado_por = :usuario_id,
						Nota_aprobado = :nota,
						Fecha_aprobacion = GETDATE()
					WHERE ID_factura = :factura_id
			";
			$params = [
				":factura_id" => $params['factura']["ID_factura"],
				":usuario_id" => $params['userdata']["usrid"],
				":nota" => $params['comentario'] ?? ''
			];
			$result = $this->db->execute($sql, $params);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta: ' . $e->getMessage()
			];
		}
	}

	function GuardarPreaprobacion($params)
	{

		try {
			$sql = "UPDATE SGO_PROV_BANCOS_FACTURAS_SUBIDAS
                    SET
                        PREAPROBADA = :PREAPROBADA,
                        PREAPROBADA_POR = :PREAPROBADA_POR,
                        PREAPROBADA_FECHA = GETDATE(),
                        PREAPROBADA_COMENTARIO = :PREAPROBADA_COMENTARIO,
                        preaprobacion_monto = :MONTO,
                        Buzon = :Buzon
                    WHERE ID_factura = :ID_factura
			";
			$params = [
				":ID_factura" => $params['factura']["ID_factura"],
				":PREAPROBADA" => 1,
				":PREAPROBADA_POR" => $params['userdata']["usrid"],
				":PREAPROBADA_COMENTARIO" => $params['comentario'] ?? '',
				":MONTO" => $params["factura"]['PREAPROBACION_MONTO'] ?? 0,
				":Buzon" => $params['usuarioPreaprobacion']["usuario_id"],
			];
			$result = $this->db->execute($sql, $params);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta: ' . $e->getMessage()
			];
		}
	}

	//********************************** */
}
