<?php

// require_once __DIR__ . '/../logsmodel.php';
require_once __DIR__ . '/../../../libs/database.php';


class GuiasPickupModel extends Model
{
    private $mysqlDb;

    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
        $this->mysqlDb = MySQLConnection::getInstance();

        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("GuiasPickupModel conectado a: " . $this->empresaCode);
        }
    }

    /**
     * Ejemplo: Obtener datos de MySQL (base de datos SISCO)
     * Este método muestra cómo usar MySQLConnection desde un modelo
     */
    function getDatosSisco($secuencia = null)
    {
        try {
            // Obtener instancia de MySQLConnection
            $sql = "SELECT a.*, p.bodega as bodegaret, 
                    c.sucursalid as sucursalret , 
                    d.sucursalid as sucursalfact , 
                    cr.doc1, cr.doc2, cr.doc3, cr.doc4, cr.doc5
                    FROM covidsales a 
                    inner join sisco.covidciudades d on a.bodega= d.almacen 
                    left outer join covidpickup p on p.orden= a.secuencia 
                    left outer join covidcredito cr on cr.transaccion= a.secuencia
                    left outer join sisco.covidciudades c on p.bodega= c.almacen 
                    where a.secuencia = '$secuencia'  and a.anulada<> '1'";
            $resultado = $this->mysqlDb->query($sql, []);
            return $resultado;
        } catch (Exception $e) {
            $this->logError("Error obteniendo datos de MySQL: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    function getDatosDropShipping($factura_id = null)
    {
        try {
            $sql = "SELECT s.Nombre as TIENDA_RETIRO_NOMBRE, * from Cli_Direccion_Dropshipping c
				left join SIS_SUCURSALES s
				on c.tienda_retiro = s.ID
				where c.Facturaid = :factura_id";
            $params = [
                ":factura_id" => $factura_id
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo facturas guías pickup: " . $e->getMessage());
            return false;
        }
    }


    function getFacturasGuiasPickup($data = [])
    {
        try {
            $sql = "EXECUTE SGO_LOG_GUIAS_PIKUP_FACTURAS_2 @usuario = :usuario";
            $params = [
                ":usuario" => $data['usrid']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo facturas guías pickup: " . $e->getMessage());
            return false;
        }
    }
    function getFacturasGuiasPickupDropShipping($data = [])
    {
        try {
            $sql = "EXECUTE SGO_LOG_GUIAS_PIKUP_DROPSHIPPING @usuario = :usuario";
            $params = [
                ":usuario" => $data['usrid']
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo facturas guías pickup: " . $e->getMessage());
            return false;
        }
    }

    function getTransporteGuiasPickup()
    {
        try {
            $sql = "select id, Código as Codigo  from SIS_PARAMETROS where PadreID='0000000090' order by 2";

            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo transporte guías pickup: " . $e->getMessage());
            return false;
        }
    }

    function getTiendasRetiroGuiasPickup()
    {
        try {
            $sql = "SELECT top 5 ID,Nombre,Código as codigo from SIS_SUCURSALES
			where Región != 'OMNICANAL'
			and Anulado = 0
			and TipoNegocio = 'COMPUTRON'
			and ID not in ('0000000067','0000000066')

			UNION ALL

			SELECT ID,Nombre,Código as codigo from SIS_SUCURSALES
			where ID in ('0000000001')";

            $stmt = $this->query($sql);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo transporte guías pickup: " . $e->getMessage());
            return false;
        }
    }

    function ActualizarFacturasListas($datos)
    {
        try {
            $sql = "UPDATE FACTURASLISTAS 
                SET 
                    Estado='INGRESADAGUIA',
                   -- id_unico = :id_unico,
                    GUIA = :GUIA,
                    FECHAGUIA = GETDATE(),
                    GUIAPOR = :usrid
                WHERE Factura = :factura_id
                AND bodegaID = :bodega_id
                ";

            $params = [
                // ":id_unico" => $datos['id_unico'],
                ":factura_id" => $datos['factura'],
                ":bodega_id" => $datos['bodega'],
                ":GUIA" => $datos['numeroGuia'],
                ":usrid" => $datos['usrid']
            ];

            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error actualizando facturas listas: " . $e->getMessage());
            return false;
        }
    }

    function ActualizarFacturasListasParaConsolidacion($datos)
    {
        try {
            $sql = "UPDATE FACTURASLISTAS 
                SET 
                    Estado='CONSOLIDADA',
                    id_unico = :id_unico
                WHERE Factura = :factura_id
                AND bodegaID = :bodega_id
                ";

            $params = [
                ":id_unico" => $datos['id_unico'],
                ":factura_id" => $datos['factura'],
                ":bodega_id" => $datos['bodega']
            ];

            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error actualizando facturas listas: " . $e->getMessage());
            return false;
        }
    }


    function GuardarListaGuias($datos)
    {
        try {
            $sql = "INSERT INTO SGO_LOG_GUIASPICKUP_GUIAS
            (
                factura_id,
                bodega,
                guia,
                creado_por,
                id_unico
            ) VALUES (
                :factura_id,
                :bodega,
                :guia,
                :creado_por,
                :id_unico
            )";

            $params = [
                ":factura_id" => $datos['factura'],
                ":bodega" => $datos['bodega'],
                ":id_unico" => $datos['id_unico'],
                ":guia" => $datos['numeroGuia'],
                ":creado_por" => $datos['usrid']
            ];

            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error guardando lista de guías: " . $e->getMessage());
            return false;
        }
    }

    function GuardarCambioTipoPedido($datos)
    {
        try {
            $sql = "INSERT INTO SGO_FACTURASLISTAS_LOG_TIPOPEDIDOSFACTURA
            (
                factura_id,
                tipo,
                creado_por
            ) VALUES (
                :factura_id,
                :tipo,
                :creado_por
            )";

            $params = [
                ":factura_id" => $datos['factura'],
                ":tipo" => $datos['tipo'],
                ":creado_por" => $datos['usrid']
            ];

            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error guardando lista de guías: " . $e->getMessage());
            return false;
        }
    }

    //** GUARDAR COMPUTRON */

    function GuardarDatosDespachoComputron($data)
    {
        try {
            $sql = "INSERT INTO SGO_LOG_GUIAS_DATOS_DESPACHO
            (
                factura_id,
                bodega_id,
                forma_despacho,
                tienda_retiro,
                enviar_cliente,
                guia,
                bultos,
                comentario,
                peso,
                creado_por
            ) VALUES (
                :factura_id,
                :bodega_id,
                :forma_despacho,
                :tienda_retiro,
                :enviar_cliente,
                :guia,
                :bultos,
                :comentario,
                :peso,
                :creado_por
            )";

            $params = [
                ":factura_id" => $data['factura'],
                ":bodega_id" => $data['bodega'],
                ":forma_despacho" => $data['formaDespachoId'],
                ":tienda_retiro" => $data['tiendaRetiroId'],
                ":enviar_cliente" => $data['envioACliente'] ? 1 : 0,
                ":guia" => $data['numeroGuia'],
                ":bultos" => $data['numeroBultos'],
                ":comentario" => $data['comentarios'],
                ":peso" => $data['peso'],
                ":creado_por" => $data['userdata']['usrid']
            ];

            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error guardando lista de guías: " . $e->getMessage());
            return false;
        }
    }

    function ActualizarFacturasListasComputron($datos)
    {
        try {
            $sql = "UPDATE FACTURASLISTAS 
                SET 
                    Estado='INGRESADAGUIA',
                    GUIA = :GUIA,
                    FECHAGUIA = GETDATE(),
                    GUIAPOR = :usrid,
                    TRANSPORTE = :TRANSPORTE
                WHERE Factura = :factura_id
                AND bodegaID = :bodega_id
                ";

            $params = [
                ":factura_id" => $datos['factura'],
                ":bodega_id" => $datos['bodega'],
                ":GUIA" => $datos['numeroGuia'],
                ":TRANSPORTE" => $datos['formaDespachoId'],
                ":usrid" => $datos['userdata']["usrid"]
            ];

            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            $this->logError("Error actualizando facturas listas: " . $e->getMessage());
            return false;
        }
    }
}
