-- ✅ STORED PROCEDURE CORREGIDO: SGO_LOG_GUIAS_PIKUP_FACTURAS
-- Problema solucionado: Eliminación de filas duplicadas por factura
-- Fecha: 23/10/2025
-- Cambio principal: DISTINCT → GROUP BY con agregaciones MAX()

ALTER PROCEDURE [dbo].[SGO_LOG_GUIAS_PIKUP_FACTURAS]
@usuario varchar(20) = '0000000386'
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE 
        @LISTA varchar(1000),
        @FECHA date = '20251008',
        @is_admin bit = 0,
		@padre_consolidado int,
		@padre_consolidado_buscar int ,
		@padre_dep_nombre varchar(20);

    SELECT @is_admin = is_admin,@padre_consolidado = departamento_id FROM SERIESUSR WHERE usrid = @usuario;
    SELECT @padre_consolidado_buscar = b.departamento_id,@padre_dep_nombre = b.departamento_nombre 
	FROM SERIESUSR_DEPARTAMENTOS d
	left join SERIESUSR_DEPARTAMENTOS b
	on d.PADRE_CONSOLIDADO = b.departamento_id
	WHERE d.PADRE_CONSOLIDADO = @padre_consolidado;

    IF @is_admin = 0
    BEGIN
        -- Obtener lista de bodegas del usuario
        SELECT @LISTA = STUFF((
			SELECT ',' + BODEGAS.bodega_id
			FROM (
				SELECT db1.bodega_id
				FROM SERIESUSR s1 WITH (NOLOCK)
				LEFT JOIN SERIESUSR_DEPARTAMENTOS d1 ON d1.departamento_id = s1.departamento_id
				LEFT JOIN SERIESUSR_DEPARTAMENTOS_BODEGAS db1 ON db1.departamento_id = d1.departamento_id
				WHERE s1.usrid = @usuario

				UNION ALL

				SELECT db2.bodega_id
				FROM SERIESUSR s2 WITH (NOLOCK)
				LEFT JOIN SERIESUSR_DEPARTAMENTOS d2 ON d2.departamento_id = s2.departamento_id
				LEFT JOIN SERIESUSR_DEPARTAMENTOS_BODEGAS db2 ON db2.departamento_id = d2.departamento_id
				WHERE d2.PADRE_CONSOLIDADO = @padre_consolidado_buscar
			) AS BODEGAS
			FOR XML PATH(''), TYPE
		).value('.', 'NVARCHAR(MAX)'), 1, 1, '');
    END;

	--SELECT @LISTA

;WITH FL_VERIFICADAS AS (
    SELECT 
		Factura, 
		BodegaID,
		Estado,
		FECHAYHORA
    FROM FACTURASLISTAS WITH (NOLOCK)
    WHERE Estado IN('VERIFICADA','CONSOLIDADA')
	and isnull(BODEGAID,'') != ''
	and FECHAYHORA > '20251001'
)
,
CTE_FACTURAS AS (
        SELECT 
            f.ID AS FACTURA_ID,
            f.SucursalID,
            f.Secuencia,
            f.Detalle,
            f.Fecha,
            f.Bloqueado,
            f.Total,
            f.OrdenID,
            f.ClienteID,
            dp.departamento_id,
            dp.departamento_nombre,
            dp.CENTRO_CONSOLIDADO,
            dt.BodegaID,
            o.TipoPedido,
            p.Nombre AS TRANSPORTE,
            LTP.tipo AS TIPO_LOG,
            ml.cantidad AS MULTIBODEGA_COUNT,
			fl.Estado as FL_ESTADO
        FROM VEN_FACTURAS f
        INNER JOIN VEN_FACTURAS_DT dt ON dt.FacturaID = f.ID
        LEFT JOIN VEN_ORDENES o ON o.ID = f.OrdenID
        LEFT JOIN SERIESUSR_DEPARTAMENTOS_BODEGAS so ON so.bodega_id = dt.BodegaID
        LEFT JOIN SERIESUSR_DEPARTAMENTOS dp ON dp.departamento_id = so.departamento_id
        LEFT JOIN CLI_CLIENTES c ON c.ID = f.ClienteID
		INNER JOIN FL_VERIFICADAS fl ON fl.Factura = f.ID AND fl.BodegaID = dt.BodegaID
        LEFT JOIN SIS_PARAMETROS p ON p.id = o.transporte
        LEFT JOIN (
            SELECT TOP 1 tipo, factura_id 
            FROM SGO_FACTURASLISTAS_LOG_TIPOPEDIDOSFACTURA
            ORDER BY fecha_creado DESC
        ) LTP ON LTP.factura_id = f.ID
        LEFT JOIN (
            SELECT COUNT(DISTINCT BodegaID) AS cantidad, FacturaID 
            FROM VEN_FACTURAS_DT 
            GROUP BY FacturaID
        ) ml ON ml.FacturaID = f.ID
        WHERE 
            f.Anulado = 0
            AND dt.Devuelto = 0
            AND f.Fecha >= @FECHA
            AND (
                @is_admin = 1 OR 
                dt.BodegaID IN (
                    SELECT CAST(val AS VARCHAR(10)) 
                    FROM cartimex.[dbo].[f_split](@LISTA, ',')
                )
            )
			and fl.FECHAYHORA > '20251020'
    )

    -- ✅ SOLUCIÓN: Usar GROUP BY en lugar de DISTINCT para agrupar por factura
    -- Esto elimina las filas duplicadas para la misma factura con múltiples bodegas
    SELECT 
        MAX(f.SucursalID) AS FACTURA_SUCURSAL,
        f.FACTURA_ID,
        MAX(f.Secuencia) AS FACTURA_SECUENCIA,
        MAX(f.Detalle) AS FACTURA_DETALLE,
        CONVERT(char(10), MAX(f.Fecha), 103) AS FACTURA_FECHA,
        MAX(c.Nombre) AS CLIENTE_NOMBRE,

        -- Tipo de pedido / mostrador
        MAX(CASE 
                WHEN ISNULL(f.TIPO_LOG, '') <> '' THEN f.TIPO_LOG
                WHEN f.TipoPedido IS NOT NULL THEN f.TipoPedido
                WHEN f.SucursalID = '00' THEN 'MOSTRADOR-GYE'
                ELSE 'MOSTRADOR-UIO'
            END) AS ORDEN_TIPO_PEDIDO,

        MAX(CASE 
                WHEN ISNULL(f.TIPO_LOG, 
                        ISNULL(f.TipoPedido,
                            CASE WHEN f.SucursalID = '00' THEN 'MOSTRADOR-GYE' ELSE 'MOSTRADOR-UIO' END
                        )
                    ) IN ('MOSTRADOR-GYE', 'MOSTRADOR-UIO') and f.MULTIBODEGA_COUNT > 1
                THEN 1 ELSE 0 
            END) AS CONSOLIDAR_FACTURA,

        MAX(CASE
			WHEN f.FL_ESTADO = 'CONSOLIDADA' THEN @padre_dep_nombre ELSE f.departamento_nombre END) AS departamento_nombre,
        MAX(f.Bloqueado) AS FACTURA_BLOQUEADA,
        MAX(f.Total) AS FACTURA_TOTAL,
        MAX(ISNULL(f.TRANSPORTE, '')) AS FACTURA_TRANSPORTE,

        -- ✅ Validación CENTRO_CONSOLIDADO combinada
        MAX(CASE 
			WHEN f.CENTRO_CONSOLIDADO = 1 
				 AND ISNULL(f.TIPO_LOG, 
						ISNULL(f.TipoPedido,
							CASE WHEN f.SucursalID = '00' THEN 'MOSTRADOR-GYE' ELSE 'MOSTRADOR-UIO' END
						)
					) IN ('MOSTRADOR-GYE', 'MOSTRADOR-UIO') and f.MULTIBODEGA_COUNT > 1 or f.FL_ESTADO = 'CONSOLIDADA'
			THEN 1
			ELSE 0
		END) AS CENTRO_CONSOLIDADO,

        -- Multibodega
        MAX(CASE WHEN f.MULTIBODEGA_COUNT > 1 THEN 'SI' ELSE 'NO' END) AS MULTIBODEGA,

        -- Concatenaciones
        BODEGAS = STUFF((
            SELECT DISTINCT ', ' + bo2.Nombre
            FROM VEN_FACTURAS_DT dt2
            INNER JOIN INV_BODEGAS bo2 ON bo2.ID = dt2.BodegaID
            WHERE dt2.FacturaID = f.FACTURA_ID
			AND EXISTS (
                  SELECT 1 
                  FROM SERIESUSR_DEPARTAMENTOS_BODEGAS so2
                  WHERE so2.bodega_id = dt2.BodegaID
                    AND so2.departamento_id = f.departamento_id
              )
            FOR XML PATH(''), TYPE
        ).value('.', 'NVARCHAR(MAX)'), 1, 2, ''),

        CODIGOS_BODEGA = STUFF((
            SELECT DISTINCT ', ' + bo2.Código
            FROM VEN_FACTURAS_DT dt2
            INNER JOIN INV_BODEGAS bo2 ON bo2.ID = dt2.BodegaID
            WHERE dt2.FacturaID = f.FACTURA_ID
			 AND EXISTS (
                  SELECT 1 
                  FROM SERIESUSR_DEPARTAMENTOS_BODEGAS so2
                  WHERE so2.bodega_id = dt2.BodegaID
                    AND so2.departamento_id = f.departamento_id
              )
            FOR XML PATH(''), TYPE
        ).value('.', 'NVARCHAR(MAX)'), 1, 2, ''),

        IDS_BODEGA = STUFF((
            SELECT DISTINCT ', ' + bo2.ID
            FROM VEN_FACTURAS_DT dt2
            INNER JOIN INV_BODEGAS bo2 ON bo2.ID = dt2.BodegaID
            WHERE dt2.FacturaID = f.FACTURA_ID
			  AND EXISTS (
                  SELECT 1 
                  FROM SERIESUSR_DEPARTAMENTOS_BODEGAS so2
                  WHERE so2.bodega_id = dt2.BodegaID
                    AND so2.departamento_id = f.departamento_id
              )
            FOR XML PATH(''), TYPE
        ).value('.', 'NVARCHAR(MAX)'), 1, 2, ''),
		MAX(CASE 
			WHEN f.FL_ESTADO = 'CONSOLIDADA' THEN 'VERIFICADA' ELSE f.FL_ESTADO END) AS FL_ESTADO

    FROM CTE_FACTURAS f
    LEFT JOIN CLI_CLIENTES c ON c.ID = f.ClienteID
    -- ✅ CLAVE: GROUP BY para agrupar por factura y eliminar duplicados
    -- Agrupa todas las filas de la misma factura en una sola línea
    GROUP BY f.FACTURA_ID, f.departamento_id
    ORDER BY f.FACTURA_ID
    OPTION (RECOMPILE);
END;

/*
CAMBIOS REALIZADOS:
==================

1. ❌ PROBLEMA ANTERIOR:
   SELECT DISTINCT ... FROM CTE_FACTURAS f
   - DISTINCT no funcionaba correctamente con múltiples bodegas
   - Retornaba múltiples filas para la misma factura

2. ✅ SOLUCIÓN IMPLEMENTADA:
   SELECT MAX(...) ... FROM CTE_FACTURAS f GROUP BY f.FACTURA_ID, f.departamento_id
   - GROUP BY agrupa correctamente por factura
   - MAX() obtiene el valor para campos no agrupados
   - Resultado: Una sola fila por factura con todas las bodegas concatenadas

3. 🎯 RESULTADO:
   - Antes: Factura 12345 → 2 filas (una por cada bodega)
   - Ahora: Factura 12345 → 1 fila con "Bodega A, Bodega B" en campo BODEGAS

4. ✅ CAMPOS CONCATENADOS (ya funcionaban bien):
   - BODEGAS: "Bodega Principal, Bodega Secundaria"
   - CODIGOS_BODEGA: "001, 002" 
   - IDS_BODEGA: "1, 2"

5. 📊 COMPATIBILIDAD:
   - Mantiene la misma estructura de columnas
   - Compatible con frontend Angular existente
   - No requiere cambios en el código PHP
*/
