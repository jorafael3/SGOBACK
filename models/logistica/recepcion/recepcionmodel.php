<?php
// require_once __DIR__ . '/../../../libs/JwtHelper.php';


class Recepcionmodel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
    }



    function BuscarBodegas($data = [])
    {
        try {


            $sql = "SELECT  Nombre  , ID  , Código as Codigo FROM CARTIMEX..INV_BODEGAS   WHERE Sucursal = :sucursal";

            $params = [

                ':sucursal' => $data['sucursal'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en BuscarBodegas: " . $e->getMessage());
            error_log("Exception in BuscarBodegas: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    function BuscarTransferenciasDestino($data = [])
    {
        try {


            $sql = "EXEC CARTIMEX..SGO_CARGAR_PENDIENTES_RECIBIR @BODEGA_DESTINO = :bodegaId , @EMPRESA = :empresa";

            $params = [

                ':empresa' => $data['empresa'] ?? null,
                ':bodegaId' => $data['bodegaId'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en BuscarTransferenciasDestino: " . $e->getMessage());
            error_log("Exception in BuscarTransferenciasDestino: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function MarcarComoRecibido($data = [])
    {
        try {

            $sql = "UPDATE CARTIMEX..FACTURASLISTAS 
            SET FECHA_RECIBIDO = GETDATE() , RECIBIDO_POR = :usuario
            WHERE Factura = :numero";

            $params = [
                ':numero' => $data['numero'] ?? null,
                ':usuario' => $data['usuario'] ?? null,
            ];

            // Use execute() for UPDATE queries instead of query()
            $result = $this->db->execute($sql, $params);

            return $result;

        } catch (Exception $e) {
            $this->logError("Error en MarcarComoRecibido: " . $e->getMessage());
            error_log("Exception in MarcarComoRecibido: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function cargarDetalleTransferencia($data = [])
    {
        try {


            $sql = "EXEC CARTIMEX..SGO_LOG_RECIBIR_DETALLE_TRASFERENCIA
           
            @TransferenciaID = :TransferenciaID
            
            ";

            $params = [

                ':TransferenciaID' => $data['TransferenciaID'] ?? null

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en cargarDetalleTransferencia: " . $e->getMessage());
            error_log("Exception in cargarDetalleTransferencia: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    function GuardarCantidad($data = [])
    {
        try {
            $sql = "EXEC COMPUTRONSA..SGO_RECIBIR_TRANSFERENCIAS_GUARDAR_POR_CANTIDAD
            @TransferenciaID = :TransferenciaID,
            @PRODUCTO_ID = :PRODUCTO_ID,
            @CANTIDAD = :CANTIDAD,
            @USUARIO = :USUARIO,
            @DTIDCART = :DTIDCART,
            @DTID = :DTID";

            $params = [
                ':TransferenciaID' => $data['TransferenciaID'] ?? null,
                ':PRODUCTO_ID' => $data['PRODUCTO_ID'] ?? null,
                ':CANTIDAD' => $data['CANTIDAD'] ?? null,
                ':USUARIO' => $data['USUARIO'] ?? null,
                ':DTIDCART' => $data['DTIDCART'] ?? null,
                ':DTID' => $data['DTID'] ?? null,
            ];

            // Use execute() for stored procedures that don't return data
            $result = $this->db->execute($sql, $params);

            return $result;

        } catch (Exception $e) {
            $this->logError("Error en GuardarCantidad: " . $e->getMessage());
            error_log("Exception in GuardarCantidad: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function GuardarSeries($data = [])
    {
        try {
            // Validar que SERIES sea un array
            if (!isset($data['SERIES']) || !is_array($data['SERIES'])) {
                return [
                    'success' => false,
                    'error' => 'El parámetro SERIES debe ser un array'
                ];
            }

            $series = $data['SERIES'];
            $transferencia_id = $data['TransferenciaID'] ?? null;
            $producto_id = $data['PRODUCTO_ID'] ?? null;
            $dtid = $data['DTID'] ?? null;
            $dtidcart = $data['DTIDCART'] ?? null;
            $usuario = $data['USUARIO'] ?? null;

            // Validar que haya al menos una serie
            if (empty($series)) {
                return [
                    'success' => false,
                    'error' => 'Debe proporcionar al menos una serie'
                ];
            }

            // PASO 1: Validar todas las series primero
            $seriesInvalidas = [];
            foreach ($series as $serie) {
                $sqlValidar = "SELECT T.TrasferenciaID , T.Serie_inventario , p.Código FROM 
                COMPUTRONSA..SGO_TRASFERENCIAS_SERIES_COMPUTRON T 
                inner join INV_PRODUCTOS p ON T.ProductoID = P.ID
                WHERE serie_inventario = :SERIES 
                AND ProductoID = :PRODUCTO_ID ";

                $paramsValidar = [
                 
                    ':PRODUCTO_ID' => $producto_id,
                    ':SERIES' => $serie,
                ];

                $resultValidar = $this->query($sqlValidar, $paramsValidar);

                // Si la serie no existe o la consulta falló
                if (!$resultValidar || !$resultValidar['success'] || empty($resultValidar['data'])) {
                    $seriesInvalidas[] = $serie;
                }
            }

            // Si hay series inválidas, devolver error
            if (!empty($seriesInvalidas)) {
                return [
                    'success' => false,
                    'error' => 'Las siguientes series no existen en esta transferencia para este producto',
                    'seriesInvalidas' => $seriesInvalidas,
                    'mensaje' => 'Serie(s) no encontrada(s): ' . implode(', ', $seriesInvalidas)
                ];
            }

            // PASO 2: Si todas las series son válidas, proceder a enviarlas
            $resultadosEnvio = [];
            $errores = [];

            foreach ($series as $serie) {
                $sqlEnviar = "EXEC COMPUTRONSA..SGO_RECIBIR_TRANSFERENCIAS_GUARDAR_POR_SERIE_RECEPCION
                    @TRANSFERENCIA_ID = :TransferenciaID,
                    @PRODUCTO_ID = :PRODUCTO_ID,
                    @SERIE = :SERIES,
                    @DTID = :DTID,
                    @DTIDCART = :DTIDCART,
                    @USUARIO = :USUARIO";

                $paramsEnviar = [
                    ':TransferenciaID' => $transferencia_id,
                    ':PRODUCTO_ID' => $producto_id,
                    ':SERIES' => $serie,
                    ':DTID' => $dtid,
                    ':DTIDCART' => $dtidcart,
                    ':USUARIO' => $usuario,
                ];

                // Use execute() for stored procedures that don't return data
                $resultEnviar = $this->db->execute($sqlEnviar, $paramsEnviar);

                if ($resultEnviar && $resultEnviar['success']) {
                    $resultadosEnvio[] = [
                        'serie' => $serie,
                        'status' => 'enviada'
                    ];
                } else {
                    $errores[] = [
                        'serie' => $serie,
                        'error' => $resultEnviar['error'] ?? 'Error desconocido'
                    ];
                }
            }

            // Si hubo errores al enviar, devolver los detalles
            if (!empty($errores)) {
                return [
                    'success' => false,
                    'error' => 'Error al enviar algunas series',
                    'seriesEnviadas' => $resultadosEnvio,
                    'errores' => $errores
                ];
            }

            // Todo salió bien
            return [
                'success' => true,
                'message' => 'Todas las series fueron enviadas correctamente',
                'seriesEnviadas' => $resultadosEnvio,
                'totalEnviadas' => count($resultadosEnvio)
            ];

        } catch (Exception $e) {
            $this->logError("Error en enviarSeriesTransferencia: " . $e->getMessage());
            error_log("Exception in enviarSeriesTransferencia: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    
    function FinalizarRecepcion($data = [])
    {
        try {
            // Validar que exista el array de transferencias
            if (!isset($data['transferencias']) || !is_array($data['transferencias'])) {
                return [
                    'success' => false,
                    'error' => 'El parámetro transferencias debe ser un array'
                ];
            }

            $transferencias = $data['transferencias'];
            $usuario = $data['usuario'] ?? null;
            $bodegaDestino = $data['BodegaID_Destino'] ?? null;

            // Validar que haya al menos una transferencia
            if (empty($transferencias)) {
                return [
                    'success' => false,
                    'error' => 'Debe proporcionar al menos una transferencia'
                ];
            }

            // Iniciar transacción
            $this->db->beginTransaction();

            $resultadosProcesados = [];
            $totalProcesadas = 0;

            // Procesar cada transferencia
            foreach ($transferencias as $index => $transferencia) {
                // PASO 1: Ejecutar el primer procedimiento almacenado
                $sql1 = "EXEC COMPUTRONSA..SGO_INV_TransferenciasDT_Insert_kardex
                @TransferenciaID = :TransferenciaID,
                @ProductoID = :ProductoID,
                @Cantidad = :Cantidad,
                @SucursalID = :SucursalID,
                @CreadoPor = :CreadoPor,
                @BODEGA_DESTINO = :BODEGA_DESTINO";

                $params1 = [
                    ':TransferenciaID' => $transferencia['TransferenciaID'] ?? null,
                    ':ProductoID' => $transferencia['ProductoID'] ?? null,
                    ':Cantidad' => $transferencia['Cantidad'] ?? null,
                    ':SucursalID' => $transferencia['SucursalID'] ?? null,
                    ':CreadoPor' => $usuario,
                    ':BODEGA_DESTINO' => $bodegaDestino,
                ];

                $result1 = $this->db->execute($sql1, $params1);

                // Verificar si el primer procedimiento falló
                if (!$result1 || !$result1['success']) {
                    $this->db->rollback();
                    return [
                        'success' => false,
                        'error' => "Error al ejecutar SGO_INV_TransferenciasDT_Insert_kardex para la transferencia #" . ($index + 1),
                        'transferencia' => $transferencia,
                        'details' => $result1
                    ];
                }

                // PASO 2: Ejecutar el segundo procedimiento almacenado
                $sql2 = "EXEC COMPUTRONSA..SGO_INV_TransferenciasDT_Insert_kardex_cartimex
                @TransferenciaID = :TransferenciaID,
                @ProductoID = :ProductoID,
                @Cantidad = :Cantidad,
                @SucursalID = :SucursalID,
                @CreadoPor = :CreadoPor,
                @Consignación = :Consignacion,
                @DTIDCART = :DTIDCART";

                $params2 = [
                    ':TransferenciaID' => $transferencia['TransferenciaID'] ?? null,
                    ':ProductoID' => $transferencia['ProductoID'] ?? null,
                    ':Cantidad' => $transferencia['Cantidad'] ?? null,
                    ':SucursalID' => $transferencia['SucursalID'] ?? null,
                    ':CreadoPor' => $usuario,
                    ':Consignacion' => $transferencia['Consignacion'] ?? '0',
                    ':DTIDCART' => $transferencia['DTIDCART'] ?? null,
                ];

                $result2 = $this->db->execute($sql2, $params2);

                // Verificar si el segundo procedimiento falló
                if (!$result2 || !$result2['success']) {
                    $this->db->rollback();
                    return [
                        'success' => false,
                        'error' => "Error al ejecutar SGO_INV_TransferenciasDT_Insert_kardex_cartimex para la transferencia #" . ($index + 1),
                        'transferencia' => $transferencia,
                        'details' => $result2
                    ];
                }

                // Registrar el resultado exitoso
                $resultadosProcesados[] = [
                    'index' => $index + 1,
                    'TransferenciaID' => $transferencia['TransferenciaID'],
                    'ProductoID' => $transferencia['ProductoID'],
                    'status' => 'procesada'
                ];

                $totalProcesadas++;
            }

            // Si todas las transferencias fueron procesadas exitosamente, hacer commit
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Recepción finalizada correctamente',
                'totalProcesadas' => $totalProcesadas,
                'transferencias' => $resultadosProcesados
            ];

        } catch (Exception $e) {
            // Si hay cualquier error, hacer rollback
            if ($this->db) {
                $this->db->rollback();
            }

            $this->logError("Error en FinalizarRecepcion: " . $e->getMessage());
            error_log("Exception in FinalizarRecepcion: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error en la transacción: ' . $e->getMessage()
            ];
        }
    }

}
