<?php

// require_once __DIR__ . '/../logsmodel.php';


class InformePagosModel extends Model
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

	function GetFacturasPorPagar($params)
	{
		try {
			$inicio = date('Ym01');
			$fin = date('Ymd');

			$fecha_inicio = $params['fecha_inicio'] ?? $inicio;
			$fecha_fin = $params['fecha_fin'] ?? $fin;
			$estado = $params['estado'] ?? null;
			$empresa = $params['empresa'] ?? null;
			$tipo = $params['tipo'] ?? null;

			$sqlpendientes = "SELECT
				--acr.*,
				ACR_ID = acr.ID,
				ACR_ACREEDOR_NOMRBRE = cre.Nombre,
				ACR_DETALLE  = acr.Detalle,
				ACR_SALDO  = acr.Saldo,
				ACR_TIPO = acr.Tipo,
				ACR_SGO_SOLICITADA_POR = isnull(acr.SGO_ASIGNADA_POR,''),
				ACR_SGO_ABONO = isnull(acr.SGO_ABONO,0),
				ACR_SGO_FECHA_SOLICITADO = isnull(acr.SGO_FECHA_SOLICITADO,''),
				ACR_SGO_COMENTARIO = isnull(acr.SGO_COMENTARIO_PENDIENTE,''),
				ACR_VENCIMIENTO = acr.Vencimiento,
				--
				FACT_ID = hd.documento_id,
				FACT_SECUENCIA = isnull(hd.acrSerie+'-'+hd.acrSecuencia,''),
				FACT_DETALLE = hd.documento_detalle,
				FACT_SOLICITADO_FECHA = hd.solicitado_fecha,
				FACT_SOLICITADO_POR = isnull(hd.solicitado_por,''),
				FACT_APROBADO = isnull(hd.Aprobado,''),
				FACT_APROBADO_POR = isnull(hd.aprobado_por,''),
				FACT_APROBADO_FECHA = isnull(hd.Fecha_aprobacion,''),
				FACT_APROBADO_COMEMTARIO = isnull(hd.Nota_aprobado,''),
				FACT_TIPO_GASTO = isnull(hd.tipo_gasto,''),
				FACT_ARCHIVO = hd.Archivo ,
				FACT_DOCUMENTO_PREAPROBADO = hd.documento_preaprobado,
				FACT_DOCUMENTO_PREAPROBADO_POR = isnull(hd.documento_preaprobado_por,'')	,
				FACT_DOCUMENTO_PREAPROBADO_FECHA = isnull(hd.documento_preaprobado_fecha,''),
				FACT_DOCUMENTO_PREAPROBADO_COMENTARIO = isnull(hd.documento_preaprobado_comentario,''),
				
				--
				DOCUMENTO_ABONO = CASE WHEN isnull(acr.SGO_ABONO,'') = '' THEN acr.Saldo ELSE acr.SGO_ABONO END,
				DOCUMENTO_SOLICITADO_POR = CASE WHEN isnull(acr.SGO_ASIGNADA_POR,'') != '' THEN acr.SGO_ASIGNADA_POR ELSE hd.solicitado_por END,
				DOCUMENTO_TABLA = 'ACR_ACREEDORES_DEUDAS',
				--
				PAGO_ESTADO = acr.SGO_PAGO_APROBACION,
				PAGO_APROBADO_POR = isnull(us.usuario,''),
				PAGO_APROBADO_FECHA = isnull(acr.SGO_PAGO_APROBACION_FECHA,''),
				PAGO_APROBADO_COMENTARIO = isnull(acr.SGO_PAGO_APROBACION_COMENTARIO,''),

				PAGO_DESCARTAR = isnull(acr.DESCARTAR,0),
				PAGO_DESCARTAR_POR = isnull(acr.SGO_PAGO_DESCARTADO_POR,''),
				PAGO_DESCARTAR_FECHA = isnull(acr.SGO_PAGO_DESCARTADO_FECHA,''),
				PAGO_DESCARTAR_COMENTARIO = isnull(acr.SGO_PAGO_DESCARTADO_COMENTARIO,''),

				
				PAGO_GENERADO = isnull(acr.SGO_PAGO_GENERADO,0),
				PAGO_GENERADO_POR = isnull(us2.usuario,''),
				PAGO_GENERADO_FECHA = isnull(acr.SGO_PAGO_GENERADO_FECHA,''),
				PAGO_GENERADO_COMENTARIO = isnull(acr.SGO_PAGO_GENERADO_COMENTARIO,''),
				PAGO_AGRUPADO = isnull(acr.SGO_PAGO_AGRUPADO,0),
				PAGO_AGRUPADO_ID = isnull(acr.SGO_PAGO_AGRUPADO_ID,'')

				from " . $empresa . "..ACR_ACREEDORES_DEUDAS acr with(NOLOCK)
				left outer join " . $empresa . "..ACR_ACREEDORES cre  WITH (NOLOCK) on cre.ID = acr.AcreedorID
				INNER JOIN (
					SELECT 
					DocumentoID = F.ID, 
					F.Tipo, 
					F.acrSerie,
					F.acrSecuencia,
					F.Credito_TributarioID,
					F.Tipo_ComprobanteID,
					u2.usuario as solicitado_por,
					FS.Fecha_creado as solicitado_fecha,
					FS.Fecha_aprobacion,
					u.usuario as aprobado_por,
					FS.Nota_aprobado,
					Aprobado = fs.Estado,
					tg.tipo_nombre as tipo_gasto,
					fs.Archivo as Archivo,
					fs.Detalle as documento_detalle,
					fs.ID_factura as documento_id,
					fs.PREAPROBADA as documento_preaprobado,
					fs.PREAPROBADA_POR as documento_preaprobado_por,
					fs.PREAPROBADA_FECHA as documento_preaprobado_fecha,
					fs.PREAPROBADA_COMENTARIO as documento_preaprobado_comentario
					FROM " . $empresa . "..PRV_FACTURAS F WITH (NOLOCK)
					INNER JOIN CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS FS
						on LTRIM(RTRIM(FS.secuencia)) = LTRIM(RTRIM(F.acrSerie))+'-'+LTRIM(RTRIM(F.acrSecuencia))
						AND LTRIM(RTRIM(FS.Autorizacion)) = LTRIM(RTRIM(F.acrAutorización))
					LEFT JOIN CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO tg on tg.id = FS.TIPO
					LEFT JOIN  SERIESUSR u
					ON (
							(ISNUMERIC(fs.aprobado_por) = 1 
							AND u.usrid = fs.aprobado_por)
							OR
							(ISNUMERIC(fs.aprobado_por) = 0 
							AND u.usuario = fs.aprobado_por)
						)
					LEFT JOIN  SERIESUSR u2
					ON (
							(ISNUMERIC(fs.Creado_por) = 1 
							AND u2.usrid = fs.Creado_por)
							OR
							(ISNUMERIC(fs.Creado_por) = 0 
							AND u2.usuario = fs.Creado_por)
						)
					WHERE fs.Estado = 1
					UNION ALL
					SELECT 
					DocumentoID = F.ID, 
					F.Tipo, 
					F.acrSerie,
					F.acrSecuencia,
					F.Credito_TributarioID,
					F.Tipo_ComprobanteID,
					FS.CreadoPor as solicitado_por,
					FS.CreadoDate as solicitado_fecha,
					CASE WHEN fs.aprobado_fecha is null THEN ''
						WHEN ISDATE(fs.aprobado_fecha) = 1 THEN CONVERT(DATETIME, fs.aprobado_fecha)
						ELSE '' END as Fecha_aprobacion,
					FS.aprobado_por,
					FS.aprobado_comentario as Nota_aprobado,
					Aprobado = fs.Aprobado,
					'' as tipo_gasto,
					fs.archivo as Archivo,
					fs.Detalle as documento_detalle,
					fs.ID as documento_id,
					0 as documento_preaprobado,
					'' as documento_preaprobado_por,
					'' as documento_preaprobado_fecha,
					'' as documento_preaprobado_comentario
					FROM " . $empresa . "..COM_FACTURAS F WITH (NOLOCK)
					LEFT OUTER JOIN " . $empresa . "..COM_ORDENES FS WITH (NOLOCK) ON FS.ID = F.OrdenID
					WHERE fs.Aprobado = 1
				) HD on HD.DocumentoID = acr.DocumentoID AND HD.Tipo = acr.Tipo
				LEFT OUTER JOIN " . $empresa . "..SRI_CREDITO_TRIBUTARIO CR WITH (NOLOCK) ON CR.ID = HD.Credito_TributarioID
				LEFT OUTER JOIN " . $empresa . "..SRI_TIPOCOMPROBANTE CO WITH (NOLOCK) ON CO.ID = HD.Tipo_ComprobanteID
				LEFT JOIN " . $empresa . "..SERIESUSR us WITH (NOLOCK) on us.usrid = acr.SGO_PAGO_APROBACION_POR
				LEFT JOIN " . $empresa . "..SERIESUSR us2 WITH (NOLOCK) on us2.usrid = acr.SGO_PAGO_GENERADO_POR
				where 
				acr.Débito = 0 
				and acr.Anulado = 0 
				and acr.Saldo > 0
				and acr.SGO_CONFIRMAR = 1
				and acr.DESCARTAR = '" . ($estado == 2 ? '1' : '0') . "'
				and acr.AcreedorID NOT IN ( '0000002922', '1000000618')
				and hd.Tipo = '" . $tipo . "'
				and acr.SGO_PAGO_GENERADO = 0
				and acr.SGO_PAGO_APROBACION = " . ($estado == 2 ? "0" : "$estado") . "
				and (
						CR.Código+'-'+CO.Código in ('02-01','02-02','02-2','08-03') 
						OR CO.Código IN ('12','41','02') 
						or acr.Retenido = 1
						OR cre.Ruc IN ('0908663818001','0991331859001') 
						OR CR.Código in ('02','00') 
					)
				ORDER BY ACR_DETALLE
				";

			$params = [
				// ":empresa" => strtoupper($params['empresa']),
				// ":estado" => $params['estado']
			];
			$sql = $sqlpendientes;
			$result = $this->query($sql, $params);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta: ' . $e->getMessage()
			];
		}
	}

	function AprobarFacturas($data, $empresa, $comentario, $usuario, $agrupado_id = '', $agrupado = 0)
	{
		try {
			$ACR_ID = $data['ACR_ID'] ?? [];
			$tabla = $data['DOCUMENTO_TABLA'] ?? [];
			$sql = "UPDATE " . $empresa . "..ACR_ACREEDORES_DEUDAS
						SET SGO_PAGO_APROBACION = 1,
							SGO_PAGO_APROBACION_FECHA = GETDATE(),
							SGO_PAGO_APROBACION_POR = :aprobado_por,
							SGO_PAGO_APROBACION_COMENTARIO = :comentario,
							SGO_PAGO_AGRUPADO = :agrupado,
							SGO_PAGO_AGRUPADO_ID = :agrupado_id
						WHERE ID = :acr_id";
			$params = [
				":aprobado_por" => $usuario,
				":comentario" => $comentario ?? '',
				":acr_id" => $ACR_ID,
				":agrupado" => $agrupado,
				":agrupado_id" => $agrupado_id
			];
			return $this->db->execute($sql, $params);
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error al aprobar facturas: ' . $e->getMessage()
			];
		}
	}

	function DescartarFacturas($data, $empresa, $comentario, $usuario)
	{
		try {
			$ACR_ID = $data['ACR_ID'] ?? [];
			$tabla = $data['DOCUMENTO_TABLA'] ?? [];
			$sql = "UPDATE " . $empresa . "..ACR_ACREEDORES_DEUDAS
						SET DESCARTAR = 1,
							SGO_PAGO_APROBACION = 0,
							SGO_PAGO_DESCARTADO_FECHA = GETDATE(),
							SGO_PAGO_DESCARTADO_POR = :aprobado_por,
							SGO_PAGO_DESCARTADO_COMENTARIO = :comentario
						WHERE ID = :acr_id";
			$params = [
				":aprobado_por" => $usuario,
				":comentario" => $comentario ?? '',
				":acr_id" => $ACR_ID
			];
			return $this->db->execute($sql, $params);
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error al aprobar facturas: ' . $e->getMessage()
			];
		}
	}

	//** PAGOS POR REALIZAR */

	function GetFacturasPagosAprobados($params)
	{
		try {
			$inicio = date('Ym01');
			$fin = date('Ymd');

			$fecha_inicio = $params['fecha_inicio'] ?? $inicio;
			$fecha_fin = $params['fecha_fin'] ?? $fin;
			$estado = $params['estado'] ?? null;
			$empresa = $params['empresa'] ?? null;
			$tipo = trim($params['tipo']) ?? null;

			$sqlpendientes = "SELECT
				--acr.*,
				ACR_ID = acr.ID,
				ACR_ACREEDOR_NOMRBRE = cre.Nombre,
				ACR_DETALLE  = acr.Detalle,
				ACR_SALDO  = acr.Saldo,
				ACR_TIPO = acr.Tipo,
				ACR_SGO_SOLICITADA_POR = isnull(acr.SGO_ASIGNADA_POR,''),
				ACR_SGO_ABONO = isnull(acr.SGO_ABONO,0),
				ACR_SGO_FECHA_SOLICITADO = isnull(acr.SGO_FECHA_SOLICITADO,''),
				ACR_SGO_COMENTARIO = isnull(acr.SGO_COMENTARIO_PENDIENTE,''),
				ACR_VENCIMIENTO = acr.Vencimiento,
				--
				FACT_ID = hd.documento_id,
				FACT_SECUENCIA = isnull(hd.acrSerie+'-'+hd.acrSecuencia,''),
				FACT_DETALLE = hd.documento_detalle,
				FACT_SOLICITADO_FECHA = hd.solicitado_fecha,
				FACT_SOLICITADO_POR = isnull(hd.solicitado_por,''),
				FACT_APROBADO = isnull(hd.Aprobado,''),
				FACT_APROBADO_POR = isnull(hd.aprobado_por,''),
				FACT_APROBADO_FECHA = isnull(hd.Fecha_aprobacion,''),
				FACT_APROBADO_COMEMTARIO = isnull(hd.Nota_aprobado,''),
				FACT_TIPO_GASTO = isnull(hd.tipo_gasto,''),
				FACT_ARCHIVO = hd.Archivo ,
				FACT_DOCUMENTO_PREAPROBADO = hd.documento_preaprobado,
				FACT_DOCUMENTO_PREAPROBADO_POR = isnull(hd.documento_preaprobado_por,'')	,
				FACT_DOCUMENTO_PREAPROBADO_FECHA = isnull(hd.documento_preaprobado_fecha,''),
				FACT_DOCUMENTO_PREAPROBADO_COMENTARIO = isnull(hd.documento_preaprobado_comentario,''),
				
				--
				DOCUMENTO_ABONO = CASE WHEN isnull(acr.SGO_ABONO,'') = '' THEN acr.Saldo ELSE acr.SGO_ABONO END,
				DOCUMENTO_SOLICITADO_POR = CASE WHEN isnull(acr.SGO_ASIGNADA_POR,'') != '' THEN acr.SGO_ASIGNADA_POR ELSE hd.solicitado_por END,
				DOCUMENTO_TABLA = 'ACR_ACREEDORES_DEUDAS',
				--
				PAGO_ESTADO = acr.SGO_PAGO_APROBACION,
				PAGO_APROBADO_POR = isnull(us.usuario,''),
				PAGO_APROBADO_FECHA = isnull(acr.SGO_PAGO_APROBACION_FECHA,''),
				PAGO_APROBADO_COMENTARIO = isnull(acr.SGO_PAGO_APROBACION_COMENTARIO,''),

				PAGO_DESCARTAR = isnull(acr.DESCARTAR,0),
				PAGO_DESCARTAR_POR = isnull(acr.SGO_PAGO_DESCARTADO_POR,''),
				PAGO_DESCARTAR_FECHA = isnull(acr.SGO_PAGO_DESCARTADO_FECHA,''),
				PAGO_DESCARTAR_COMENTARIO = isnull(acr.SGO_PAGO_DESCARTADO_COMENTARIO,''),

				PAGO_GENERADO = isnull(acr.SGO_PAGO_GENERADO,0),
				PAGO_GENERADO_POR = isnull(us2.usuario,''),
				PAGO_GENERADO_FECHA = isnull(acr.SGO_PAGO_GENERADO_FECHA,''),
				PAGO_GENERADO_COMENTARIO = isnull(acr.SGO_PAGO_GENERADO_COMENTARIO,''),
				PAGO_AGRUPADO = isnull(acr.SGO_PAGO_AGRUPADO,0),
				PAGO_AGRUPADO_ID = isnull(acr.SGO_PAGO_AGRUPADO_ID,'')

				from " . $empresa . "..ACR_ACREEDORES_DEUDAS acr with(NOLOCK)
				left outer join " . $empresa . "..ACR_ACREEDORES cre  WITH (NOLOCK) on cre.ID = acr.AcreedorID
				INNER JOIN (
					SELECT 
					DocumentoID = F.ID, 
					F.Tipo, 
					F.acrSerie,
					F.acrSecuencia,
					F.Credito_TributarioID,
					F.Tipo_ComprobanteID,
					u2.usuario as solicitado_por,
					FS.Fecha_creado as solicitado_fecha,
					FS.Fecha_aprobacion,
					u.usuario as aprobado_por,
					FS.Nota_aprobado,
					Aprobado = fs.Estado,
					tg.tipo_nombre as tipo_gasto,
					fs.Archivo as Archivo,
					fs.Detalle as documento_detalle,
					fs.ID_factura as documento_id,
					fs.PREAPROBADA as documento_preaprobado,
					fs.PREAPROBADA_POR as documento_preaprobado_por,
					fs.PREAPROBADA_FECHA as documento_preaprobado_fecha,
					fs.PREAPROBADA_COMENTARIO as documento_preaprobado_comentario
					FROM " . $empresa . "..PRV_FACTURAS F WITH (NOLOCK)
					INNER JOIN CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS FS
						on LTRIM(RTRIM(FS.secuencia)) = LTRIM(RTRIM(F.acrSerie))+'-'+LTRIM(RTRIM(F.acrSecuencia))
						AND LTRIM(RTRIM(FS.Autorizacion)) = LTRIM(RTRIM(F.acrAutorización))
					LEFT JOIN CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO tg on tg.id = FS.TIPO
					LEFT JOIN  SERIESUSR u
					ON (
							(ISNUMERIC(fs.aprobado_por) = 1 
							AND u.usrid = fs.aprobado_por)
							OR
							(ISNUMERIC(fs.aprobado_por) = 0 
							AND u.usuario = fs.aprobado_por)
						)
					LEFT JOIN  SERIESUSR u2
					ON (
							(ISNUMERIC(fs.Creado_por) = 1 
							AND u2.usrid = fs.Creado_por)
							OR
							(ISNUMERIC(fs.Creado_por) = 0 
							AND u2.usuario = fs.Creado_por)
						)
					WHERE fs.Estado = 1
					UNION ALL
					SELECT 
					DocumentoID = F.ID, 
					F.Tipo, 
					F.acrSerie,
					F.acrSecuencia,
					F.Credito_TributarioID,
					F.Tipo_ComprobanteID,
					FS.CreadoPor as solicitado_por,
					FS.CreadoDate as solicitado_fecha,
					CASE WHEN fs.aprobado_fecha is null THEN ''
						WHEN ISDATE(fs.aprobado_fecha) = 1 THEN CONVERT(DATETIME, fs.aprobado_fecha)
						ELSE '' END as Fecha_aprobacion,
					FS.aprobado_por,
					FS.aprobado_comentario as Nota_aprobado,
					Aprobado = fs.Aprobado,
					'' as tipo_gasto,
					fs.archivo as Archivo,
					fs.Detalle as documento_detalle,
					fs.ID as documento_id,
					0 as documento_preaprobado,
					'' as documento_preaprobado_por,
					'' as documento_preaprobado_fecha,
					'' as documento_preaprobado_comentario
					FROM " . $empresa . "..COM_FACTURAS F WITH (NOLOCK)
					LEFT OUTER JOIN " . $empresa . "..COM_ORDENES FS WITH (NOLOCK) ON FS.ID = F.OrdenID
					WHERE fs.Aprobado = 1
				) HD on HD.DocumentoID = acr.DocumentoID AND HD.Tipo = acr.Tipo
				LEFT OUTER JOIN " . $empresa . "..SRI_CREDITO_TRIBUTARIO CR WITH (NOLOCK) ON CR.ID = HD.Credito_TributarioID
				LEFT OUTER JOIN " . $empresa . "..SRI_TIPOCOMPROBANTE CO WITH (NOLOCK) ON CO.ID = HD.Tipo_ComprobanteID
				LEFT JOIN " . $empresa . "..SERIESUSR us WITH (NOLOCK) on us.usrid = acr.SGO_PAGO_APROBACION_POR
				LEFT JOIN " . $empresa . "..SERIESUSR us2 WITH (NOLOCK) on us2.usrid = acr.SGO_PAGO_GENERADO_POR
				where 
				acr.Débito = 0 
				and acr.Anulado = 0 
				and acr.Saldo > 0
				and acr.SGO_CONFIRMAR = 1
				and acr.DESCARTAR = 0
				and acr.AcreedorID NOT IN ( '0000002922', '1000000618')
				-- and hd.Tipo = '" . $tipo . "'
				and acr.SGO_PAGO_APROBACION = 1
				and acr.SGO_PAGO_GENERADO = '" . $estado . "'
				and (
						CR.Código+'-'+CO.Código in ('02-01','02-02','02-2','08-03') 
						OR CO.Código IN ('12','41','02') 
						or acr.Retenido = 1
						OR cre.Ruc IN ('0908663818001','0991331859001') 
						OR CR.Código in ('02','00') 
					)
				ORDER BY ACR_DETALLE
				";

			$params = [
				// ":empresa" => strtoupper($params['empresa']),
				// ":estado" => $params['estado']
			];
			$sql = $sqlpendientes;
			$result = $this->query($sql, $params);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta: ' . $e->getMessage()
			];
		}
	}

	function MarcarPagosRealizados($data)
	{
		try {
			$ACR_ID = $data['factura'][0]["ACR_ID"] ?? [];
			$empresa = $data['empresa'] ?? null;
			$comentario = $data['comentario'] ?? null;
			$usuario = $data['userdata']['usrid'] ?? null;

			$sql = "UPDATE " . $empresa . "..ACR_ACREEDORES_DEUDAS
						SET SGO_PAGO_GENERADO = 1,
							SGO_PAGO_GENERADO_FECHA = GETDATE(),
							SGO_PAGO_GENERADO_POR = :generado_por,
							SGO_PAGO_GENERADO_COMENTARIO = :comentario
						WHERE ID = :acr_id";
			$params = [
				":generado_por" => $usuario,
				":comentario" => $comentario ?? '',
				":acr_id" => $ACR_ID
			];
			return $this->db->execute($sql, $params);
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error al marcar pagos realizados: ' . $e->getMessage()
			];
		}
	}

	//*** CHEQUES */

	function GetChequesEmitidos($params)
	{
		try {
			$inicio = date('Ym01');
			$fin = date('Ymd');

			$fecha_inicio = $params['fecha_inicio'] ?? $inicio;
			$fecha_fin = $params['fecha_fin'] ?? $fin;
			$estado = $params['estado'] ?? null;
			$empresa = $params['empresa'] ?? null;
			$tipo = trim($params['tipo']) ?? null;

			$sql = "SELECT 
				E.Fecha, E.Número, E.Tipo,  E.Detalle, E.Beneficiario, E.Valor,E.CreadoPor ,
				Banco = B.Nombre, E.Cheque, Empres = '" . $empresa . "', E.Nota, e.ID,
				e.SGO_FIRMADO,
				E.SGO_FIRMADO_FECHA,
				e.SGO_FIRMADO_POR,
				E.SGO_ENTREGADO_CAJA,
				e.SGO_ENTREGADO_CAJA_FECHA,
				E.SGO_ENTREGADO_CAJA_POR,
				e.SGO_ENTREGADO,
				E.SGO_FECHA_ENTREGADO,
				E.SGO_ENTREGADO_POR,
				E.SGO_ENTREGADO_COMENTARIO,
				e.Ruta,
				e.SGO_CIUDAD,
				e.SGO_PROVINCIA,
				e.fecha_cheque,
				'0' as postfechado,
				'EGRESO' as TABLA,
				e.USUARIO_FIRMANTE,
				e.anulado,
				us.nombre as USUARIO_FIRMA_NOMBRE,
				AU.ID_factura as FS_FACTURA_ID,
				ftp.tipo_nombre as FS_TIPO
				FROM " . $empresa . "..BAN_EGRESOS E WITH (NOLOCK) 
				INNER JOIN " . $empresa . "..BAN_BANCOS B WITH (NOLOCK)  ON B.ID = E.BancoID
				left join " . $empresa . "..SERIESUSR us
				on us.usrid = e.USUARIO_FIRMANTE
				left join " . $empresa . "..ACR_ACREEDORES_DEUDAS acr
				on acr.DocumentoID = E.ID
					LEFT OUTER JOIN (
						SELECT DocumentoID = ID, Tipo, OrdenSGOID ,acrAutorización,TérminoID,Credito_TributarioID,Tipo_ComprobanteID,acrSerie,acrSecuencia FROM " . $empresa . "..COM_FACTURAS WITH (NOLOCK) WHERE Anulado = 0 UNION ALL
						SELECT DocumentoID = ID, Tipo, OrdenSGOID, acrAutorización,TérminoID,Credito_TributarioID,Tipo_ComprobanteID,acrSerie,acrSecuencia FROM " . $empresa . "..PRV_FACTURAS WITH (NOLOCK) WHERE Anulado = 0   
				) HD ON HD.DocumentoID = acr.DocumentoID AND HD.Tipo = acr.Tipo 
				LEFT OUTER JOIN CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS AU WITH (NOLOCK) ON AU.Autorizacion = hd.acrAutorización
					and AU.secuencia = HD.acrSerie+'-'+HD.acrSecuencia
				left join CARTIMEX..SGO_PROV_BANCOS_FACTURAS_SUBIDAS_TIPOS_GASTO ftp
					on ftp.id = isnull(AU.TIPO,1)
				WHERE 
				B.GrupoID = '0000000006' 
				AND Cheque != ''
				and e.Transferencia = 0
				and E.Cheque not like '%deb%' and E.Cheque not like '%tra%' 
				and CONVERT(DATE,E.Fecha) BETWEEN '" . $fecha_inicio . "' AND '" . $fecha_fin . "'
				and E.Anulado = 0  
				group by
				E.Fecha, E.Número, E.Tipo,  E.Detalle, E.Beneficiario, E.Valor,E.CreadoPor ,
				B.Nombre, E.Cheque, E.Nota, e.ID,
				e.SGO_FIRMADO,
				E.SGO_FIRMADO_FECHA,
				e.SGO_FIRMADO_POR,
				E.SGO_ENTREGADO_CAJA,
				e.SGO_ENTREGADO_CAJA_FECHA,
				E.SGO_ENTREGADO_CAJA_POR,
				e.SGO_ENTREGADO,
				E.SGO_FECHA_ENTREGADO,
				E.SGO_ENTREGADO_POR,
				E.SGO_ENTREGADO_COMENTARIO,
				e.Ruta,
				e.SGO_CIUDAD,
				e.SGO_PROVINCIA,
				e.fecha_cheque,
				e.USUARIO_FIRMANTE,
				e.anulado,
				us.nombre ,
				AU.ID_factura ,
				ftp.tipo_nombre 
			UNION ALL
				SELECT 
				E.Fecha, E.Número, E.Tipo,  E.Detalle, E.Beneficiario, E.Valor,E.CreadoPor ,
				Banco = B.Nombre, E.Cheque, Empres = '" . $empresa . "', E.Nota, e.ID,
				e.SGO_FIRMADO,
				E.SGO_FIRMADO_FECHA,
				e.SGO_FIRMADO_POR,
				E.SGO_ENTREGADO_CAJA,
				e.SGO_ENTREGADO_CAJA_FECHA,
				E.SGO_ENTREGADO_CAJA_POR,
				e.SGO_ENTREGADO,
				E.SGO_FECHA_ENTREGADO,
				E.SGO_ENTREGADO_POR,
				E.SGO_ENTREGADO_COMENTARIO,
				e.Ruta,
				e.SGO_CIUDAD,
				e.SGO_PROVINCIA,
				e.fecha_cheque,
				'1' as postfechado,
				'RECIBOS' as TABLA,
				e.USUARIO_FIRMANTE,
				e.anulado,
				us.nombre as USUARIO_FIRMA_NOMBRE,
				'' as FS_FACTURA_ID,
				'' as FS_TIPO
				FROM 
				" . $empresa . "..ACR_RECIBOS E WITH (NOLOCK) 
				INNER JOIN " . $empresa . "..BAN_BANCOS B WITH (NOLOCK)  ON B.ID = E.BancoID
				left join " . $empresa . "..SERIESUSR us
				on us.usrid = e.USUARIO_FIRMANTE
				WHERE 
				B.GrupoID = '0000000006' AND Cheque != ''
					and E.Cheque not like '%deb%' and E.Cheque not like '%tra%' 
					and CONVERT(DATE,E.Fecha) BETWEEN '" . $fecha_inicio . "' AND '" . $fecha_fin . "'
				and E.Anulado = 0
				group by
				E.Fecha, 
				E.Número, 
				E.Tipo,  
				E.Detalle, 
				E.Beneficiario, 
				E.Valor,
				E.CreadoPor ,
				B.Nombre, 
				E.Cheque, 
				E.Nota, 
				e.ID,
				e.SGO_FIRMADO,
				E.SGO_FIRMADO_FECHA,
				e.SGO_FIRMADO_POR,
				E.SGO_ENTREGADO_CAJA,
				e.SGO_ENTREGADO_CAJA_FECHA,
				E.SGO_ENTREGADO_CAJA_POR,
				e.SGO_ENTREGADO,
				E.SGO_FECHA_ENTREGADO,
				E.SGO_ENTREGADO_POR,
				E.SGO_ENTREGADO_COMENTARIO,
				e.Ruta,
				e.SGO_CIUDAD,
				e.SGO_PROVINCIA,
				e.fecha_cheque,
				e.USUARIO_FIRMANTE,
				e.anulado,
				us.nombre
				UNION ALL
				SELECT 
				E.Fecha, E.Número, E.Tipo,  E.Detalle, 
				Beneficiario = (select Em.Nombre from EMP_ROLES_EMPLEADOS er inner join EMP_EMPLEADOS em
								on er.EmpleadoID = em.ID
								where er.RolID = e.ID), 
				E.Total as Valor,E.CreadoPor ,
				Banco = B.Nombre, E.Cheque, Empres = '" . $empresa . "', E.Nota, e.ID,
				e.SGO_FIRMADO,
				E.SGO_FIRMADO_FECHA,
				e.SGO_FIRMADO_POR,
				E.SGO_ENTREGADO_CAJA,
				e.SGO_ENTREGADO_CAJA_FECHA,
				E.SGO_ENTREGADO_CAJA_POR,
				e.SGO_ENTREGADO,
				E.SGO_FECHA_ENTREGADO,
				E.SGO_ENTREGADO_POR,
				E.SGO_ENTREGADO_COMENTARIO,
				e.Ruta,
				e.SGO_CIUDAD,
				e.SGO_PROVINCIA,
				e.fecha_cheque,
				'1' as postfechado,
				'ROLES' as TABLA,
				e.USUARIO_FIRMANTE,
				e.anulado,
				us.nombre as USUARIO_FIRMA_NOMBRE,
				'' as FS_FACTURA_ID,
				'' as FS_TIPO
				FROM 
				" . $empresa . "..EMP_ROLES E WITH (NOLOCK) 
				INNER JOIN " . $empresa . "..BAN_BANCOS B WITH (NOLOCK)  ON B.ID = E.BancoID
				left join " . $empresa . "..SERIESUSR us
				on us.usrid = e.USUARIO_FIRMANTE
				WHERE 
				B.GrupoID = '0000000006' AND Cheque != ''
					and E.Cheque not like '%deb%' and E.Cheque not like '%tra%' 
					and CONVERT(DATE,E.Fecha) BETWEEN '" . $fecha_inicio . "' AND '" . $fecha_fin . "'
					group by 
					E.Fecha, 
					E.Número, 
					E.Tipo,  
					E.Detalle, 
				E.Total,
				B.Nombre, 
				E.Cheque, 
				E.Nota,
				e.ID,
				e.SGO_FIRMADO,
				E.SGO_FIRMADO_FECHA,
				e.SGO_FIRMADO_POR,
				E.SGO_ENTREGADO_CAJA,
				e.SGO_ENTREGADO_CAJA_FECHA,
				E.SGO_ENTREGADO_CAJA_POR,
				e.SGO_ENTREGADO,
				E.SGO_FECHA_ENTREGADO,
				E.SGO_ENTREGADO_POR,
				E.SGO_ENTREGADO_COMENTARIO,
				e.Ruta,
				e.SGO_CIUDAD,
				e.SGO_PROVINCIA,
				e.fecha_cheque,
				e.USUARIO_FIRMANTE,
				e.anulado,
				E.CreadoPor,
				us.nombre
				ORDER by 9,1,8";

			$result = $this->query($sql);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta de cheques: ' . $e->getMessage()
			];
		}
	}

	//*** CHEQUES POSTFECHADOS */

	function GetChequesPosFechados()
	{

		try {
			$sql = "SGO_BAN_Informe_ChequesPosfechado";
			$result = $this->query($sql);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta de cheques: ' . $e->getMessage()
			];
		}
	}

	function GetChequesGiradosNoCobrados($data)
	{

		try {
			$sql = "SGO_BAN_Informe_ChequesGirados_NoCobrados @empresa = :empresa";
			$params = ['empresa' => $data['empresa']];
			$result = $this->query($sql, $params);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta de cheques: ' . $e->getMessage()
			];
		}
	}

	function GetChequesPosFechadosClientes($data)
	{

		try {
			$sql = "SGO_CLI_Informe_ChequesPosfechado @fecha = :fecha, @Todos = :todos";
			$params = [
				'fecha' => $data['fecha'],
				'todos' => $data['todo']
			];
			$result = $this->query($sql, $params);
			return $result;
		} catch (Exception $e) {
			return [
				'success' => false,
				'error' => 'Error en la consulta de cheques: ' . $e->getMessage()
			];
		}
	}
}
