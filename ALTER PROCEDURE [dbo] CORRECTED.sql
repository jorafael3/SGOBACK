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
            (
                SELECT TOP 1 Estado
                FROM FL_VERIFICADAS
                WHERE Factura = f.ID AND BodegaID = dt.BodegaID AND FECHAYHORA > '20241001'
            ) as FL_ESTADO
        FROM VEN_FACTURAS f
        INNER JOIN VEN_FACTURAS_DT dt ON dt.FacturaID = f.ID
        LEFT JOIN VEN_ORDENES o ON o.ID = f.OrdenID
        LEFT JOIN SERIESUSR_DEPARTAMENTOS_BODEGAS so ON so.bodega_id = dt.BodegaID
        LEFT JOIN SERIESUSR_DEPARTAMENTOS dp ON dp.departamento_id = so.departamento_id
    LEFT JOIN CLI_CLIENTES c ON c.ID = f.ClienteID
    -- Optimización: usar EXISTS en vez de INNER JOIN para FL_VERIFICADAS
        LEFT JOIN SIS_PARAMETROS p ON p.id = o.transporte
        LEFT JOIN (
            SELECT factura_id, tipo
            FROM (
                SELECT factura_id, tipo,
                       ROW_NUMBER() OVER (PARTITION BY factura_id ORDER BY fecha_creado DESC) AS rn
                FROM SGO_FACTURASLISTAS_LOG_TIPOPEDIDOSFACTURA
            ) t
            WHERE t.rn = 1
        ) LTP ON LTP.factura_id = f.ID
        LEFT JOIN (
            SELECT COUNT(DISTINCT BodegaID) AS cantidad, FacturaID 
            FROM VEN_FACTURAS_DT 
            GROUP BY FacturaID
        ) ml ON ml.FacturaID = f.ID
        WHERE 
            f.Anulado = 0
            AND dt.Devuelto = 0
            AND f.Fecha >= '20241001' -- Ampliar rango para más registros
            AND (
                @is_admin = 1 OR 
                dt.BodegaID IN (
                    SELECT CAST(val AS VARCHAR(10)) 
                    FROM cartimex.[dbo].[f_split](@LISTA, ',')
                )
            )
            AND EXISTS (
                SELECT 1 FROM FL_VERIFICADAS fl
                WHERE fl.Factura = f.ID AND fl.BodegaID = dt.BodegaID AND fl.FECHAYHORA > '20241001'
            )
    )

    SELECT DISTINCT
        f.SucursalID AS FACTURA_SUCURSAL,
        f.FACTURA_ID,
        f.Secuencia AS FACTURA_SECUENCIA,
        f.Detalle AS FACTURA_DETALLE,
        CONVERT(char(10), f.Fecha, 103) AS FACTURA_FECHA,
        c.Nombre AS CLIENTE_NOMBRE,

        -- Tipo de pedido / mostrador
        ORDEN_TIPO_PEDIDO = 
            CASE 
                WHEN ISNULL(f.TIPO_LOG, '') <> '' THEN f.TIPO_LOG
                WHEN f.TipoPedido IS NOT NULL THEN f.TipoPedido
                WHEN f.SucursalID = '00' THEN 'MOSTRADOR-GYE'
                ELSE 'MOSTRADOR-UIO'
            END,

        CONSOLIDAR_FACTURA =
            CASE 
                WHEN ISNULL(f.TIPO_LOG, 
                        ISNULL(f.TipoPedido,
                            CASE WHEN f.SucursalID = '00' THEN 'MOSTRADOR-GYE' ELSE 'MOSTRADOR-UIO' END
                        )
                    ) IN ('MOSTRADOR-GYE', 'MOSTRADOR-UIO') and f.MULTIBODEGA_COUNT > 1
                THEN 1 ELSE 0 
            END,

        departamento_nombre = CASE
			WHEN f.FL_ESTADO = 'CONSOLIDADA' THEN @padre_dep_nombre ELSE f.departamento_nombre END ,
        f.Bloqueado AS FACTURA_BLOQUEADA,
        f.Total AS FACTURA_TOTAL,
        ISNULL(f.TRANSPORTE, '') AS FACTURA_TRANSPORTE,

        -- ✅ Validación CENTRO_CONSOLIDADO combinada
        CENTRO_CONSOLIDADO =
		CASE 
			WHEN f.CENTRO_CONSOLIDADO = 1 
				 AND ISNULL(f.TIPO_LOG, 
						ISNULL(f.TipoPedido,
							CASE WHEN f.SucursalID = '00' THEN 'MOSTRADOR-GYE' ELSE 'MOSTRADOR-UIO' END
						)
					) IN ('MOSTRADOR-GYE', 'MOSTRADOR-UIO') and f.MULTIBODEGA_COUNT > 1 or f.FL_ESTADO = 'CONSOLIDADA'
			THEN 1
			ELSE 0
		END,

        -- Multibodega
        MULTIBODEGA = CASE WHEN f.MULTIBODEGA_COUNT > 1 THEN 'SI' ELSE 'NO' END,

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
		FL_ESTADO = f.FL_ESTADO

    FROM CTE_FACTURAS f
    LEFT JOIN CLI_CLIENTES c ON c.ID = f.ClienteID
    ORDER BY f.FACTURA_ID
    OPTION (RECOMPILE);
END;
