<?php

// require_once __DIR__ . '/../logsmodel.php';


class VerificarFacturasModel extends Model
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

    function getFacturasPorVerificar($data = [])
    {
        try {
            $sql = "EXECUTE SGO_LOG_VERIFICAR_FACTURAS @usuario = :usuario";

            $params = [
                ":usuario" => $data['usrid']
            ];
            // $stmt = $this->query($sql);
            // return $stmt;
            // Estando conectado a Cartimex, consultar datos de Computron
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
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

    function getFacturasDetalleVerificar($factura_id, $bodega)
    {
        try {
            $sql = "EXECUTE SGO_LOG_FACTURAS_DETALLE_VERIFICAR @factura_id = :factura_id, @bodega = :bodega";

            $params = [
                ":factura_id" => $factura_id,
                ":bodega" => $bodega
            ];
            
            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function getFacturasDetalleUbicacion($factura_id, $bodega)
    {
        try {
            $sql = "EXECUTE LOG_BUSCAR_MEJOR_UBICACION @ProductoId = :ProductoId, @bodega = :bodega";

            $params = [
                ":ProductoId" => $factura_id,
                ":bodega" => $bodega
            ];
            
            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function GetSeriesRmaProductos($producto_id, $serie = null)
    {
        try {
            // Alternativa 1: Usar interpolación directa (solo para testing)
            // IMPORTANTE: Esto es solo para debug, no usar en producción por seguridad
            $sql = "SELECT serie,productoId,estado FROM RMA_PRODUCTOS WITH(NOLOCK) 
            WHERE LTRIM(RTRIM(serie)) = :serie
            AND ProductoId = :producto_id";

            $params = [
                ":serie" => $serie,
                ":producto_id" => $producto_id
            ];

            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo series RMA productos: " . $e->getMessage());
            return false;
        }
    }

    function GuardarSGLSeries($datos)
    {
        try {
            $sql = "INSERT INTO SERIESSGLCARTIMEX ( FACTURAID, SERIE ,FECHA, PRODUCTO, grabar,creado_por ) 
            VALUES(:FACTURAID,:SERIE,GETDATE(),:PRODUCTO,:grabar,:creado_por)";

            $params = [
                ":FACTURAID" => $datos['factura_id'],
                ":SERIE" => $datos['serie'],
                ":PRODUCTO" => $datos['producto'],
                ":grabar" => 1,
                ":creado_por" => $datos['usuario']
            ];

            // Usar la conexión automática basada en el JWT para INSERT
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error guardando series SGL: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error guardando series SGL: " . $e->getMessage()
            ];
        }
    }

    function ValidarTieneRmaFacturasCab($factura_id)
    {
        try {
            $sql = "SELECT TOP 1 * FROM RMA_FACTURAS WITH(NOLOCK) 
            WHERE FacturaID = :factura_id";

            $params = [
                ":factura_id" => $factura_id
            ];

            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo series RMA productos: " . $e->getMessage());
            return false;
        }
    }

    function GuardarRmaFacturasCab($datos)
    {
        try {
            $detalle = "Factura de Venta Nro: " . $datos['factura'] . " Cliente: " . $datos['cliente'];
            $sql = "WEB_RMA_Ventas_Insert 
                @facturaid=:facturaid, 
                @detalle= :detalle,   
                @creadopor=:creadopor";

            $params = [
                ":facturaid" => $datos['factura'],
                ":detalle" => $detalle,
                ":creadopor" => $datos['usrid']
            ];

            // Usar la conexión automática basada en el JWT para SP
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error guardando series SGL: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error guardando series SGL: " . $e->getMessage()
            ];
        }
    }

    function GuardarRmaFacturasDet($datos)
    {
        try {
            $sql = "WEB_RMA_Ventas_Insert_DT 
                    @serie=:serie,
                    @ProductoID=:productoid, 
                    @RmaFacturaID= :rmafacturaid, 
                    @creadopor=:creadopor";

            $params = [
                ":serie" => $datos['serie'],
                ":productoid" => $datos['producto'],
                ":rmafacturaid" => $datos['rmafacturaid'],
                ":creadopor" => $datos['usuario']
            ];

            // Usar la conexión automática basada en el JWT para SP
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error guardando series SGL: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error guardando series SGL: " . $e->getMessage()
            ];
        }
    }
    function ActualizarRMAProductos($datos,$RMADTID){
        try {
            $estado = 'VENDIDO';
            $sql = "UPDATE RMA_PRODUCTOS 
                    SET 
                        FACTURAID=:facturaid, 
                        ESTADO=:estado,
                        RmaDtId = :RmaDtId,
                        CreadoDate = GETDATE(),
                        CreadoPor = :CreadoPor 
                WHERE serie = :serie
                AND ProductoId = :producto";
            $params = [
                ":facturaid" => $datos['factura_id'],
                ":estado" => $estado,
                ":RmaDtId" => $RMADTID,
                ":CreadoPor" => $datos['usuario'],
                ":serie" => $datos['serie'],
                ":producto" => $datos['producto']
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error guardando series SGL: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error guardando series SGL: " . $e->getMessage()
            ];
        }
    }

    function ActualizarFacturasListas($datos)
    {
        try {

            $sql = "UPDATE VEN_FACTURAS SET Procesado_RMA= 1 WHERE ID= :facturaid";
            $params = [
                ":facturaid" => $datos['factura_id']
            ];
            $stmt = $this->db->execute($sql, $params);
            $sql2 = "UPDATE facturaslistas 
                SET
                    Estado= 'VERIFICADA',
                    verificado = :verificado,
                    FechaVerificado = GETDATE()
				WHERE Factura=:factura and LTRIM(RTRIM(bodegaid)) = :bodega";
            $params2 = [
                ":verificado" => $datos['usuario'],
                ":factura" => $datos['factura_id'],
                // ":tipo" => $datos['tipo'],
                ":bodega" => $datos['bodega']
            ];

            // Usar la conexión automática basada en el JWT para UPDATE
            $stmt = $this->db->execute($sql2, $params2);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error guardando series SGL: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error guardando series SGL: " . $e->getMessage()
            ];
        }
    }
}
