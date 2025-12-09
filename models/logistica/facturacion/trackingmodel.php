<?php

// require_once __DIR__ . '/../logsmodel.php';


class TrackingModel extends Model
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

    function getFacturasDetCartimex($factura_id)
    {
        try {
            $sql = "SELECT
                p.Código as producto_codigo,
                p.Nombre as producto_nombre,
                dt.Cantidad as factura_cantidad,
                dt.Subtotal as factura_subtoal,
                dt.Impuesto as factura_iva,
                dt.Descuento as factura_descuento,
                dt.Total as factura_total,
                b.Nombre as bodega_nombre,
                b.Código as bodega_codigo,
                isnull(fl.Estado,'-') as fl_estado,
                --isnull(fl.PREPARADOPOR,'-') as fl_preparadapor,
                CASE
                    WHEN isnull(fl.PREPARADOPOR ,'') = '' THEN '-'
                    WHEN ISNUMERIC(fl.PREPARADOPOR) = 1 THEN (SELECT usuario FROM SERIESUSR where usrid = fl.PREPARADOPOR)
                    ELSE isnull(fl.PREPARADOPOR ,'') END as fl_preparadapor,
                isnull(fl.FECHAYHORA,'') as fl_preparadafecha,
                CASE
                    WHEN isnull(fl.Verificado ,'') = '' THEN '-'
                    WHEN ISNUMERIC(fl.Verificado) = 1 THEN (SELECT usuario FROM SERIESUSR where usrid = fl.Verificado)
                    ELSE isnull(fl.Verificado ,'') END as fl_verificadapor,
                fl.FechaVerificado as fl_verificadafecha,
                ISNULL((SELECT guia from SGO_LOG_GUIASPICKUP_GUIAS where factura_id = fl.Factura and bodega = fl.BODEGAID),'') as fl_guiaconsolidado,
                ISNULL((SELECT fecha_creado from SGO_LOG_GUIASPICKUP_GUIAS where factura_id = fl.Factura and bodega = fl.BODEGAID),'') as fl_guiaconsolidadofecha,
                ISNULL((SELECT u.usuario from SGO_LOG_GUIASPICKUP_GUIAS g
                        left join SERIESUSR u on u.usrid= g.creado_por
                        where factura_id = fl.Factura and bodega = fl.BODEGAID),'') as fl_guiaconsolidadopor,
                ISNULL(fl.GUIA,'-') as fl_guiadespachofinal,
                ISNULL(fl.FECHAGUIA,'') as fl_guiadespachofinalfecha,
                CASE
                    WHEN isnull(fl.GUIA ,'') = '' THEN '-'
                    WHEN ISNUMERIC(fl.GUIA) = 1 THEN (SELECT usuario FROM SERIESUSR where usrid = fl.Verificado)
                    ELSE isnull(fl.GUIA ,'') END as fl_guiadespachofinalpor
                from VEN_FACTURAS_DT dt
                left join INV_PRODUCTOS p
                on p.id = dt.ProductoID
                left join INV_BODEGAS b
                on b.ID = dt.BodegaID
                left join FACTURASLISTAS fl
                on fl.Factura = dt.FacturaID and fl.BODEGAID = dt.BodegaID
                where dt.FacturaID = :factura_id";

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

    function getFacturasDetComputron($factura_id)
    {
        try {
            $sql = "SELECT
                p.Código as producto_codigo,
                p.Nombre as producto_nombre,
                dt.Cantidad as factura_cantidad,
                dt.Subtotal as factura_subtoal,
                dt.Impuesto as factura_iva,
                dt.Descuento as factura_descuento,
                dt.Total as factura_total,
                b.Nombre as bodega_nombre,
                b.Código as bodega_codigo,
                isnull(fl.Estado,'-') as fl_estado,
                --isnull(fl.PREPARADOPOR,'-') as fl_preparadapor,
                CASE
                    WHEN isnull(fl.PREPARADOPOR ,'') = '' THEN '-'
                    WHEN ISNUMERIC(fl.PREPARADOPOR) = 1 THEN (SELECT usuario FROM SERIESUSR where usrid = fl.PREPARADOPOR)
                    ELSE isnull(fl.PREPARADOPOR ,'') END as fl_preparadapor,
                isnull(fl.FECHAYHORA,'') as fl_preparadafecha,
                CASE
                    WHEN isnull(fl.Verificado ,'') = '' THEN '-'
                    WHEN ISNUMERIC(fl.Verificado) = 1 THEN (SELECT usuario FROM SERIESUSR where usrid = fl.Verificado)
                    ELSE isnull(fl.Verificado ,'') END as fl_verificadapor,
                fl.FechaVerificado as fl_verificadafecha,
                --ISNULL((SELECT guia from SGO_LOG_GUIASPICKUP_GUIAS where factura_id = fl.Factura and bodega = fl.BODEGAID),'') as fl_guiaconsolidado,
                --ISNULL((SELECT fecha_creado from SGO_LOG_GUIASPICKUP_GUIAS where factura_id = fl.Factura and bodega = fl.BODEGAID),'') as fl_guiaconsolidadofecha,
                --ISNULL((SELECT u.usuario from SGO_LOG_GUIASPICKUP_GUIAS g left join SERIESUSR u on u.usrid= g.creado_por where factura_id = fl.Factura and bodega = fl.BODEGAID),'') as fl_guiaconsolidadopor,
                ISNULL(fl.GUIA,'-') as fl_guiadespachofinal,
                ISNULL(fl.FECHAGUIA,'') as fl_guiadespachofinalfecha,
                CASE
                    WHEN isnull(fl.GUIA ,'') = '' THEN '-'
                    WHEN ISNUMERIC(fl.GUIA) = 1 THEN (SELECT usuario FROM SERIESUSR where usrid = fl.Verificado)
                    ELSE isnull(fl.GUIAPOR ,'') END as fl_guiadespachofinalpor
                from VEN_FACTURAS_DT dt
                left join INV_PRODUCTOS p
                on p.id = dt.ProductoID
                left join INV_BODEGAS b
                on b.ID = dt.BodegaID
                left join FACTURASLISTAS fl
                on fl.Factura = dt.FacturaID and fl.BODEGAID = dt.BodegaID
                where dt.FacturaID = :factura_id";

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

    function getfacturasSeries($facturaid)
    {
        try {
            $sql = "declare @facturaid varchar(10)
                select @facturaid = id from RMA_FACTURAS
                where FacturaID = :facturaid

                select
                p.Nombre as producto_nombre,
                p.Código as producto_codigo,
                dt.Serie
                from RMA_FACTURAS_DT dt
                left join INV_PRODUCTOS p
                on p.id = dt.ProductoID
                where FacturaID = @facturaid";
            $params = [
                ":facturaid" => $facturaid
            ];
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo empresas: " . $e->getMessage());
            return false;
        }
    }
}
