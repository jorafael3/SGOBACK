<?php

// require_once __DIR__ . '/../logsmodel.php';


class ModificarSeriesModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);

        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("VerificarFacturasModel conectado a: " . $this->empresaCode);
        }
    }

    function getFacturasCab($secuencia)
    {
        try {
            $sql = "EXECUTE LOG_BUSQUEDA_FACTURA @secuencia = :secuencia";

            $params = [
                ":secuencia" => $secuencia
            ];

            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getFacturaRmaCab($factura_id)
    {
        try {
            $sql = "SELECT * FROM RMA_FACTURAS with(NOLOCK) WHERE facturaid = :factura_id";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":factura_id" => $factura_id
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getFacturaRmaDet($RMACABID, $FATID)
    {
        try {
            $sql = "DECLARE 
            @factura_id varchar(10) = '$FATID',
            @rmaid varchar(10)

            select @rmaid = ID from RMA_FACTURAS where FacturaID = @factura_id ;

            WITH DetallesEnumerados AS (
                            SELECT 
                                dt.*,
                                p.Código,
                                p.Nombre,
                                p.registroSeries,
                                ROW_NUMBER() OVER (PARTITION BY dt.ProductoID ORDER BY dt.ID) AS rn
                            FROM VEN_FACTURAS_DT dt
                            left join INV_PRODUCTOS p
                            on p.ID = dt.ProductoID
                            WHERE dt.FacturaID = @factura_id
                            and p.registroSeries = 1
                        ),
                        SeriesEnumeradas AS (
                            SELECT 
                                r.ID,
                                rdt.ProductoID,
                                rdt.Serie,
                                rdt.id as RMADT_ID,
                                ROW_NUMBER() OVER (PARTITION BY rdt.ProductoID ORDER BY rdt.ID) AS rn
                            FROM RMA_FACTURAS r WITH(NOLOCK)
                            JOIN RMA_FACTURAS_DT rdt ON r.ID = rdt.FacturaID
                            WHERE r.FacturaID = @factura_id
                        ),
                        CabeceraRMA AS (
                            SELECT 
                                r.FacturaID,
                                r.ID AS RMAID
                            FROM RMA_FACTURAS r WITH(NOLOCK)
                            WHERE r.FacturaID = @factura_id
                        )
                        SELECT 
                            isnull(rma.RMAID,'') as RMAID,
                            ProductoId=  d.productoid,
                            CopProducto = d.código, 
                            Detalle= d.Nombre, 
                            RegistaSerie= d.registroseries,
                            serie = isnull(s.Serie,''),
                            serie_anterior = isnull(s.Serie,''),
                            Factura_id = d.FacturaID,
                            LTRIM(RTRIM(isnull(s.RMADT_ID,''))) as RMADT_ID,
                            estado = case when isnull(s.Serie,'') = '' then 0 else 1 end
                        FROM DetallesEnumerados d
                        LEFT JOIN SeriesEnumeradas s
                        ON d.ProductoID = s.ProductoID AND d.rn = s.rn
                        LEFT JOIN CabeceraRMA rma
                        ON 1 = 1  
                        ORDER BY d.ID";
            // Usar la conexión automática basada en el JWT
            $params = [];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function validarSeries_SERIESCOMPRAS($data)
    {
        $sql = "SELECT * FROM RMA_FACTURAS_DT WHERE Serie = @serie AND ProductoID = @producto_id";
        $params = [
            ['@producto_id', $data['ProductoId'], PDO::PARAM_STR],
            ['@serie', $data['serie'], PDO::PARAM_STR]
        ];
        $stmt = $this->query($sql, $params);
        return $stmt;
    }

    function validarSeries_RMADT($data)
    {
        $sql = "SELECT * FROM RMA_FACTURAS_DT WHERE Serie = :serie AND ProductoID = :producto_id";
        $params = [
            ":producto_id" => $data['ProductoId'],
            ":serie" => $data['serie']
        ];
        $stmt = $this->query($sql, $params);
        return $stmt;
    }

    function getFacturaIngresadaSeriesModificar($factura_id)
    {
        try {
            $sql = "DECLARE 
                @RMAID varchar(10) = '$factura_id',
                @FACTURAID varchar(10)

                SELECT @FACTURAID = FacturaID from RMA_FACTURAS WHERE ID = @RMAID

                SELECT Secuencia FROM VEN_FACTURAS WHERE ID = @FACTURAID";
            // Usar la conexión automática basada en el JWT
            $params = [
                // ":factura_id" => $factura_id
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getRMAProductos($data)
    {
        try {
            $sql = "SELECT Serie,Estado,FacturaID FROM RMA_PRODUCTOS WHERE ProductoID = :producto_id AND Serie = :serie";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":producto_id" => $data['ProductoId'],
                ":serie" => $data['serie']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getSeriesCompras($data)
    {
        try {
            $sql = "SELECT Serie FROM CARTIMEX..INV_PRODUCTOS_SERIES_COMPRAS WHERE ProductoID = :producto_id AND Serie = :serie";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":producto_id" => $data['ProductoId'],
                ":serie" => $data['serie']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function InsertarRmaDt($data)
    {
        try {
            $sql = "WEB_RMA_Ventas_Insert_DT 
                    @serie=:serie,
                    @ProductoID=:productoid, 
                    @RmaFacturaID= :rmafacturaid, 
                    @creadopor=:creadopor";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":productoid" => $data['ProductoId'],
                ":serie" => $data['serie'],
                ":rmafacturaid" => $data['RMAID'],
                ":creadopor" => $data['userdata']["usrid"]
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function ActualizaRmaDt($data)
    {
        try {
            $sql = "UPDATE RMA_FACTURAS_DT
                SET
                    Serie = :Serie
                WHERE
                    ID = :ID";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":Serie" => $data['serie'],
                ":ID" => $data['RMADT_ID']
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }


    function Nuevo_RMAProductos($data)
    {
        try {
            $sql = "INSERT INTO RMA_PRODUCTOS
                ( 
                    Serie,
                    FacturaID,
                    Estado ,
                    ProductoID,
                    CREADOPOR,
                    CREADODATE,
                    RMADTID
                ) 
                VALUES
                (
                    :Serie,
                    :FacturaID,
                    'INVENTARIO',
                    :ProductoID,
                    :CREADOPOR,
                    GETDATE(),
                    :RMADTID
                )";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":Serie" => $data['serie'],
                ":FacturaID" => $data['Factura_id'],
                ":ProductoID" => $data['ProductoId'],
                ":CREADOPOR" => $data['userdata']["usrid"],
                ":RMADTID" => $data['RMADT_ID']
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function Actualizar_RMAProductos($data)
    {
        try {
            $estado = 'VENDIDO';
            $sql = "UPDATE COMPUTRONSA..RMA_PRODUCTOS 
                SET 
                    FACTURAID=:FacturaID, 
                    ESTADO=:estado,
                    RMADTID = :RMADTID
                    WHERE PRODUCTOID=:ProductoID 
                        and SERIE=:Serie";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":Serie" => $data['serie'],
                ":FacturaID" => $data['Factura_id'],
                ":ProductoID" => $data['ProductoId'],
                ":RMADTID" => $data['RMADT_ID'],
                ":estado" => $estado
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function Actualizar_RMAProductos_Inventario($data)
    {
        try {
            $estado = 'INVENTARIO';
            $sql = "UPDATE COMPUTRONSA..RMA_PRODUCTOS 
                SET 
                    FACTURAID='', 
                    ESTADO=:estado,
                    RMADTID = ''
                    WHERE PRODUCTOID=:ProductoID 
                        and SERIE=:Serie";
            // Usar la conexión automática basada en el JWT
            $params = [
                ":Serie" => $data['serie_anterior'],
                ":ProductoID" => $data['ProductoId'],
                ":estado" => $estado
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }
}
