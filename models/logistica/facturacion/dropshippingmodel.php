<?php

// require_once __DIR__ . '/../logsmodel.php';


class DropshippingModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);

        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("DropshippingModel conectado a: " . $this->empresaCode);
        }
    }

    function getFacturasDropshipping($data = [])
    {
        try {
            $sql = "EXECUTE [SGO_LOG_FACTURAS_DROP_SHIPPING] @usuario = :usuario";

            $params = [
                ":usuario" => $data['usrid']
            ];

            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo facturas dropshipping: " . $e->getMessage());
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

    function getFacturasDetalle($factura_id, $bodega)
    {
        try {
            $sql = "EXECUTE SGO_LOG_FACTURAS_DETALLE @factura_id = :factura_id, @bodega = :bodega";

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

    function getTiendasDespacho()
    {
        try {
            $sql = "SELECT ID,Nombre,Código as codigo from SIS_SUCURSALES
			where Región != 'OMNICANAL'
			and Anulado = 0
			and TipoNegocio = 'COMPUTRON'
			and ID not in ('0000000067','0000000066')";

            $params = [];

            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

     function validarDropshipping($data)
    {
        try {
            $sql = "SELECT * from Cli_Direccion_Dropshipping where Facturaid = :factura ";

            $params = [
                ":factura" => $data['factura']
            ];

            // Usar la conexión automática basada en el JWT
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }

    function guardarDropshipping($data)
    {
        try {
            $sql = "Log_facturaslistas_dropshipping_insert 
            @facturaid=:factura ,
            @bodegaid=:bodegaid, 
            @direccion=:direccion, 
            @referencia=:referencia, 
            @telefono=:telefono , @Email=:Email, 
            @usuario=:usuario ,@TPedido=:TPedido,
            @Tienda_retiro = :Tienda_retiro,
            @comentario = :comentario 
            ";

            $params = [
                ":factura" => $data['factura'],
                ":bodegaid" => $data['bodegas'][0],
                ":direccion" => $data['direccion'],
                ":referencia" => $data['referencia'],
                ":telefono" => $data['telefono'],
                ":Email" => $data['email'],
                ":usuario" => $data['userdata']["usrid"],
                ":TPedido" => $data['tipo'],
                ":Tienda_retiro" => $data['tienda'],
                ":comentario" => $data['comentario']
            ];

            // Usar la conexión automática basada en el JWT
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }
}
