CREATE PROCEDURE [dbo].[SGO_LOG_VERIFICAR_FACTURAS]
@usuario VARCHAR(20)  = '0000000442'
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

select @lugartrabajo = lugartrabajo, @is_admin = is_admin from SERIESUSR WHERE usrid = @usuario
select @sucursal_usuario = Código from SIS_SUCURSALES where id = @lugartrabajo
select @bodega_usuario = id from INV_BODEGAS where sucursal = @sucursal_usuario and Anulado = 0 and venta = 1 and tipo = 'STOCK' order by Código desc

--SELECT @lugartrabajo
--SELECT @sucursal_usuario
--SELECT @bodega_usuario

IF @is_admin = 1
BEGIN

    SELECT
        f.SucursalID as FACTURA_SUCURSAL,
        f.ID AS FACTURA_ID,
        f.Secuencia AS FACTURA_SECUENCIA,
        f.Detalle AS FACTURA_DETALLE,
        CONVERT(char(10), f.fecha, 103) AS FACTURA_FECHA,
        c.Nombre AS CLIENTE_NOMBRE,
        '-' AS ORDEN_TIPO_PEDIDO,
        su.Nombre AS departamento_nombre,
        BODEGAS = bo.Nombre,
        CODIGOS_BODEGA = bo.Código,
        IDS_BODEGA = dt.BodegaID,
        IDS_PREPARADAS = (SELECT Factura FROM FACTURASLISTAS WHERE tipo = 'VEN-FA' AND factura = f.ID AND BodegaID = DT.BodegaID AND estado = 'PREPARADA'),
        MULTIBODEGA = CASE 
                WHEN (
                    SELECT COUNT(DISTINCT dt2.BodegaID)
                    FROM VEN_FACTURAS_DT dt2
                    --INNER JOIN SERIESUSR_DEPARTAMENTOS_BODEGAS so2 ON so2.bodega_id = dt2.BodegaID
                    WHERE dt2.FacturaID = f.ID
                    --AND so2.departamento_id = dp.departamento_id
                ) > 1 THEN 'SI'
                ELSE 'NO'
            END,
        COUNT(*) AS total_productos
    FROM VEN_FACTURAS F
        INNER join VEN_FACTURAS_DT DT ON DT.FACTURAID = F.ID
        INNER join INV_PRODUCTOS p on p.id = dt.ProductoID
        INNER JOIN CLI_CLIENTES c ON c.ID = f.ClienteID
        INNER JOIN INV_BODEGAS bo on bo.ID = dt.BodegaID
        INNER JOIN SIS_SUCURSALES su ON su.Código = bo.Sucursal
    WHERE F.Fecha >= CONVERT( char(10),@fecha, 103)
        AND F.Anulado = 0
        and DT.Devuelto = 0
        and p.GrupoID not in ('0000000158')
        and ISNULL(dt.BodegaID, '') <> ''
        -- and substring(f.Secuencia,1,7) = '022-002' 
        --AND F.Secuencia = '060-002-000000963'
        and f.Sucursalid in (select sucursal
        from SGO_LOG_SUCURSALES_FACTURACION
        where estado = 1)
        and f.ID IN (select factura
        from FACTURASLISTAS WITH (NOLOCK)
        WHERE tipo = 'VEN-FA' and BodegaID = DT.BodegaID and estado = 'PREPARADA')
    GROUP BY F.SECUENCIA , F.SucursalID , DT.Devuelto, DT.BodegaID,f.ID,f.Detalle,f.Fecha,c.Nombre,bo.Nombre,bo.Código,su.Nombre
    ORDER BY F.Secuencia, f.Fecha asc
    --AND DT.Devuelto = 0
END
ELSE
BEGIN
    
    SELECT
        f.SucursalID as FACTURA_SUCURSAL,
        f.ID AS FACTURA_ID,
        f.Secuencia AS FACTURA_SECUENCIA,
        f.Detalle AS FACTURA_DETALLE,
        CONVERT(char(10), f.fecha, 103) AS FACTURA_FECHA,
        c.Nombre AS CLIENTE_NOMBRE,
        '-' AS ORDEN_TIPO_PEDIDO,
        su.Nombre AS departamento_nombre,
        BODEGAS = bo.Nombre,
        CODIGOS_BODEGA = bo.Código,
        IDS_BODEGA = dt.BodegaID,
        IDS_PREPARADAS = (SELECT Factura FROM FACTURASLISTAS WHERE tipo = 'VEN-FA' AND factura = f.ID AND BodegaID = DT.BodegaID AND estado = 'PREPARADA'),
        MULTIBODEGA = CASE 
                WHEN (
                    SELECT COUNT(DISTINCT dt2.BodegaID)
                    FROM VEN_FACTURAS_DT dt2
                    --INNER JOIN SERIESUSR_DEPARTAMENTOS_BODEGAS so2 ON so2.bodega_id = dt2.BodegaID
                    WHERE dt2.FacturaID = f.ID
                    --AND so2.departamento_id = dp.departamento_id
                ) > 1 THEN 'SI'
                ELSE 'NO'
            END,
        COUNT(*) AS total_productos
    FROM VEN_FACTURAS F
        INNER join VEN_FACTURAS_DT DT ON DT.FACTURAID = F.ID
        INNER join INV_PRODUCTOS p on p.id = dt.ProductoID
        INNER JOIN CLI_CLIENTES c ON c.ID = f.ClienteID
        INNER JOIN INV_BODEGAS bo on bo.ID = dt.BodegaID
        INNER JOIN SIS_SUCURSALES su ON su.Código = bo.Sucursal
    WHERE F.Fecha >= CONVERT( char(10),@fecha, 103)
        AND F.Anulado = 0
        and DT.Devuelto = 0
        and p.GrupoID not in ('0000000158')
        and ISNULL(dt.BodegaID, '') <> ''
        and dt.BodegaID in (@bodega_usuario)
        -- and substring(f.Secuencia,1,7) = '022-002' 
        and f.Sucursalid in (select sucursal
        from SGO_LOG_SUCURSALES_FACTURACION
        where estado = 1)
        and f.ID IN (select factura
        from FACTURASLISTAS WITH (NOLOCK)
        WHERE tipo = 'VEN-FA' and BodegaID = DT.BodegaID and estado = 'PREPARADA')
    GROUP BY F.SECUENCIA , F.SucursalID , DT.Devuelto, DT.BodegaID,f.ID,f.Detalle,f.Fecha,c.Nombre,bo.Nombre,bo.Código, su.Nombre
    ORDER BY F.Secuencia, f.Fecha asc
    --AND DT.Devuelto = 0
END
END


