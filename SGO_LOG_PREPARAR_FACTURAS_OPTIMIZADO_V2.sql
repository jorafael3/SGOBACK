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

    -- Obtener datos del usuario (optimizado con NOLOCK y TOP 1)
    SELECT TOP 1
        @lugartrabajo = lugartrabajo, 
        @is_admin = is_admin 
    FROM SERIESUSR WITH (NOLOCK)
    WHERE usrid = @usuario

    IF @lugartrabajo IS NULL
    BEGIN
        RETURN -- Usuario no encontrado, salir
    END

    -- Obtener sucursal (optimizado con NOLOCK y TOP 1)
    SELECT TOP 1 @sucursal_usuario = Código 
    FROM SIS_SUCURSALES WITH (NOLOCK)
    WHERE id = @lugartrabajo

    -- Obtener bodega principal (optimizado con NOLOCK y TOP 1)
    SELECT TOP 1 @bodega_usuario = id 
    FROM INV_BODEGAS WITH (NOLOCK)
    WHERE sucursal = @sucursal_usuario 
    AND Anulado = 0 
    AND venta = 1 
    AND tipo = 'STOCK' 
    ORDER BY Código DESC;

    -- CTE para eliminar subconsultas lentas
    WITH 
    -- Pre-calcular facturas excluidas (una sola vez)
    CTE_FACTURAS_EXCLUIDAS AS (
        SELECT DISTINCT Factura, BodegaID
        FROM FACTURASLISTAS WITH (NOLOCK)
        WHERE tipo = 'VEN-FA'
    ),
    -- Pre-calcular sucursales activas (una sola vez)
    CTE_SUCURSALES_ACTIVAS AS (
        SELECT DISTINCT sucursal
        FROM SGO_LOG_SUCURSALES_FACTURACION WITH (NOLOCK)
        WHERE estado = 1
    ),
    -- Pre-calcular conteo de bodegas por factura (elimina subconsulta correlacionada)
    CTE_MULTIBODEGA AS (
        SELECT 
            dt2.FacturaID,
            CASE WHEN COUNT(DISTINCT dt2.BodegaID) > 1 THEN 'SI' ELSE 'NO' END AS es_multibodega
        FROM VEN_FACTURAS_DT dt2 WITH (NOLOCK)
        GROUP BY dt2.FacturaID
    )
    -- QUERY PRINCIPAL OPTIMIZADO
    SELECT
        f.SucursalID AS FACTURA_SUCURSAL,
        f.ID AS FACTURA_ID,
        f.Secuencia AS FACTURA_SECUENCIA,
        f.Detalle AS FACTURA_DETALLE,
        CONVERT(CHAR(10), f.fecha, 103) AS FACTURA_FECHA,
        c.Nombre AS CLIENTE_NOMBRE,
        '' AS ORDEN_TIPO_PEDIDO,
        su.Nombre AS departamento_nombre,
        bo.Nombre AS BODEGAS,
        bo.Código AS CODIGOS_BODEGA,
        dt.BodegaID AS ID_BODEGA,
        ISNULL(mb.es_multibodega, 'NO') AS MULTIBODEGA,
        COUNT(*) AS total_productos
    FROM VEN_FACTURAS F WITH (NOLOCK)
    INNER JOIN VEN_FACTURAS_DT DT WITH (NOLOCK) 
        ON DT.FACTURAID = F.ID
    INNER JOIN INV_PRODUCTOS p WITH (NOLOCK)
        ON p.id = dt.ProductoID
    INNER JOIN CLI_CLIENTES c WITH (NOLOCK)
        ON c.ID = f.ClienteID
    INNER JOIN INV_BODEGAS bo WITH (NOLOCK)
        ON bo.ID = dt.BodegaID
    LEFT JOIN CTE_MULTIBODEGA mb 
        ON mb.FacturaID = f.ID
    INNER JOIN SIS_SUCURSALES su ON su.Código = bo.Sucursal
    WHERE 
        F.Fecha >= @fecha  -- SIN CONVERT, más rápido
        AND F.Anulado = 0
        AND DT.Devuelto = 0
        AND p.GrupoID NOT IN ('0000000158')
        AND ISNULL(dt.BodegaID, '') <> ''
        -- Filtro por bodega según permisos (consolidado en una condición)
        AND (@is_admin = 1 OR dt.BodegaID = @bodega_usuario)
        -- Usar EXISTS en lugar de IN para sucursales
        AND EXISTS (SELECT 1 FROM CTE_SUCURSALES_ACTIVAS s WHERE s.sucursal = f.SucursalID)
        -- Usar NOT EXISTS en lugar de NOT IN para mejor rendimiento
        AND NOT EXISTS (
            SELECT 1 FROM CTE_FACTURAS_EXCLUIDAS ex 
            WHERE ex.Factura = f.ID AND ex.BodegaID = DT.BodegaID
        )
    GROUP BY 
        F.SECUENCIA, F.SucursalID, DT.Devuelto, DT.BodegaID, f.ID, f.Detalle, 
        f.Fecha, c.Nombre, bo.Nombre, bo.Código, mb.es_multibodega,su.Nombre
    ORDER BY f.Fecha desc,f.id
END
