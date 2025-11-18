ALTER PROCEDURE [dbo].[SGO_LOG_PREPARAR_FACTURAS]
@usuario VARCHAR(20) = '0000000442'
AS
BEGIN
    SET ANSI_NULLS ON
    SET QUOTED_IDENTIFIER ON
    SET NOCOUNT ON

    DECLARE 
        @fecha DATE = '20250901',
        @is_admin BIT = 0,
        @lugartrabajo VARCHAR(20),
        @sucursal_usuario VARCHAR(20),
        @bodega_usuario VARCHAR(20)

    -- Obtener datos del usuario en una sola consulta
    SELECT TOP 1
        @lugartrabajo = lugartrabajo, 
        @is_admin = is_admin 
    FROM SERIESUSR WITH (NOLOCK)
    WHERE usrid = @usuario

    IF @lugartrabajo IS NULL
    BEGIN
        SELECT 'ERROR: Usuario no encontrado' AS mensaje
        RETURN
    END

    -- Obtener sucursal
    SELECT TOP 1 @sucursal_usuario = Código 
    FROM SIS_SUCURSALES WITH (NOLOCK)
    WHERE id = @lugartrabajo

    -- Obtener bodega principal
    SELECT TOP 1 @bodega_usuario = id 
    FROM INV_BODEGAS WITH (NOLOCK)
    WHERE sucursal = @sucursal_usuario 
    AND Anulado = 0 
    AND venta = 1 
    AND tipo = 'STOCK' 
    ORDER BY Código DESC;

    -- CTE para facturas no procesadas (cachear una sola vez)
    WITH CTE_FACTURAS_EXCLUIDAS AS (
        SELECT DISTINCT Factura
        FROM FACTURASLISTAS WITH (NOLOCK)
        WHERE tipo = 'VEN-FA'
    ),
    CTE_SUCURSALES_ACTIVAS AS (
        SELECT DISTINCT sucursal
        FROM SGO_LOG_SUCURSALES_FACTURACION WITH (NOLOCK)
        WHERE estado = 1
    ),
    -- CTE para contar bodegas por factura (evitar subconsulta correlacionada)
    CTE_MULTIBODEGA AS (
        SELECT 
            dt2.FacturaID,
            COUNT(DISTINCT dt2.BodegaID) AS num_bodegas
        FROM VEN_FACTURAS_DT dt2 WITH (NOLOCK)
        GROUP BY dt2.FacturaID
    ),
    -- CTE principal con condiciones base
    CTE_FACTURAS_BASE AS (
        SELECT
            f.SucursalID,
            f.ID,
            f.Secuencia,
            f.Detalle,
            CONVERT(CHAR(10), f.fecha, 103) AS fecha_formateada,
            f.fecha,
            c.Nombre AS cliente_nombre,
            bo.Nombre AS bodega_nombre,
            bo.Código AS bodega_codigo,
            dt.BodegaID,
            COUNT(*) AS total_productos,
            CASE WHEN mb.num_bodegas > 1 THEN 'SI' ELSE 'NO' END AS multibodega
        FROM VEN_FACTURAS F WITH (NOLOCK)
        INNER JOIN VEN_FACTURAS_DT DT WITH (NOLOCK) 
            ON DT.FACTURAID = F.ID
        INNER JOIN INV_PRODUCTOS p WITH (NOLOCK)
            ON p.id = dt.ProductoID
        INNER JOIN CLI_CLIENTES c WITH (NOLOCK)
            ON c.ID = f.ClienteID
        INNER JOIN INV_BODEGAS bo WITH (NOLOCK)
            ON bo.ID = dt.BodegaID
        INNER JOIN CTE_MULTIBODEGA mb 
            ON mb.FacturaID = f.ID
        WHERE 
            F.Fecha >= @fecha
            AND F.Anulado = 0
            AND DT.Devuelto = 0
            AND p.GrupoID NOT IN ('0000000158')
            AND ISNULL(dt.BodegaID, '') <> ''
            AND f.SucursalID IN (SELECT sucursal FROM CTE_SUCURSALES_ACTIVAS)
            AND f.ID NOT IN (SELECT Factura FROM CTE_FACTURAS_EXCLUIDAS)
            -- Filtro por bodega según permisos
            AND dt.BodegaID = CASE 
                WHEN @is_admin = 1 THEN dt.BodegaID 
                ELSE @bodega_usuario 
            END
        GROUP BY 
            f.SucursalID, f.ID, f.Secuencia, f.Detalle, f.fecha,
            c.Nombre, bo.Nombre, bo.Código, dt.BodegaID, mb.num_bodegas
    )
    SELECT
        SucursalID AS FACTURA_SUCURSAL,
        ID AS FACTURA_ID,
        Secuencia AS FACTURA_SECUENCIA,
        Detalle AS FACTURA_DETALLE,
        fecha_formateada AS FACTURA_FECHA,
        cliente_nombre AS CLIENTE_NOMBRE,
        '' AS ORDEN_TIPO_PEDIDO,
        '' AS departamento_nombre,
        bodega_nombre AS BODEGAS,
        bodega_codigo AS CODIGOS_BODEGA,
        BodegaID AS ID_BODEGA,
        multibodega AS MULTIBODEGA,
        total_productos
    FROM CTE_FACTURAS_BASE
    ORDER BY Secuencia, fecha DESC

END
GO
