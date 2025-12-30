<?php

class VerificacionMotosModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);

        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("VerificacionMotosModel conectado a: " . $this->empresaCode);
        }
    }

    /**
     * Obtiene las facturas pendientes de verificación de motos
     * @param array $data Datos con información de sesión
     * @return array Resultado con success, data, message
     */
    public function FACTURAS_PENDIENTES_VERIFICAR($data)
    {
        try {
            // Extraer datos de sesión
            $usuario = $data["userdata"]['usrid'] ?? null;

            // Ejecutar stored procedure
            $sql = "EXECUTE SGO_LOG_FACTURAS_PENDIENTES_MOTOS 
                    @usuario = :usuario";

            $params = [':usuario' => $usuario];

            // Usar el método query heredado de Model
            $stmt = $this->query($sql, $params);
            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error obteniendo facturas pendientes: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener facturas pendientes: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene el detalle de una factura específica
     * @param string $facturaId ID de la factura
     * @return array Resultado con success, data, message
     */
    public function FACTURAS_PENDIENTES_VERIFICAR_DETALLE($facturaId)
    {
        try {
            $sql = "EXECUTE SGO_FACTURAS_PENDIENTES_MOTOS_DETALLE
                    @FacturaID = :FacturaID";

            $params = [':FacturaID' => $facturaId];

            $stmt = $this->query($sql, $params);
            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error obteniendo detalle de factura: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener detalle de factura: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene las series disponibles para un producto
     * @param array $datosFactura Datos de la factura con ProductoID, Código, ES_VIRTUAL, etc.
     * @return array Resultado con success, data, message
     */
    public function Mostrar_Series_Disponibles($datosFactura)
    {
        try {
            $productoId = $datosFactura['ProductoID'] ?? null;
            $codigo = $datosFactura['Código'] ?? null;
            $esVirtual = $datosFactura['ES_VIRTUAL'] ?? 0;
            $sucursalNombre = $datosFactura['SUCURSAL_NOMBRE'] ?? '';
            $sucursalId = $datosFactura['SUCURSAL_ID'] ?? '';

            if (!$productoId) {
                return [
                    'success' => false,
                    'message' => 'ProductoID no proporcionado'
                ];
            }

            // Construir SQL dinámicamente según si es virtual o no
            $sqlExtra = "";
            if ($esVirtual == 0) {
                $sqlExtra = " AND Bodega_Destino = :Bodega_Destino";
            }

            $sql = "SELECT * FROM CARTIMEX..INV_PRODUCTOS_SERIES_COMPRAS_MOTOS
                    WHERE ProductoID = :ProductoID
                    AND Estado_Serie = 'INVENTARIO'
                    AND Virtual = :virtual" . $sqlExtra;

            $params = [
                ':ProductoID' => $productoId,
                ':virtual' => $esVirtual
            ];

            if ($esVirtual == 0) {
                $params[':Bodega_Destino'] = $codigo;
            }

            $stmt = $this->query($sql, $params);

            // Agregar información adicional a la respuesta
            if ($stmt && $stmt['success']) {
                $stmt['VIRTUAL'] = $esVirtual;
                $stmt['CODIGO'] = $codigo;
                $stmt['SUCURSAL_NOMBRE'] = $sucursalNombre;
                $stmt['SUCURSAL_ID'] = $sucursalId;
            }

            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error obteniendo series disponibles: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener series disponibles: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene las tiendas de retiro disponibles
     * @param array $datosFactura Datos de la factura con ES_VIRTUAL, BodegaID
     * @return array Resultado con success, data, message
     */
    public function Mostrar_Tiendas_retiro($datosFactura)
    {
        try {
            $esVirtual = $datosFactura['ES_VIRTUAL'] ?? 0;
            $bodegaId = $datosFactura['BodegaID'] ?? null;

            if ($esVirtual == 0) {
                // Si no es virtual, obtener la tienda específica de la bodega
                $sql = "SELECT b.ID, s.Código, s.Nombre, s.Tienda_retiro_coordinacion 
                        FROM INV_BODEGAS b
                        LEFT JOIN SIS_SUCURSALES s ON s.Código = b.Sucursal
                        WHERE b.id = :bodega";

                $params = [':bodega' => $bodegaId];
            } else {
                // Si es virtual, obtener todas las tiendas de retiro
                $sql = "SELECT ID, Código, Nombre, Tienda_retiro_coordinacion 
                        FROM COMPUTRONSA..SIS_SUCURSALES
                        WHERE Tienda_retiro = 1";

                $params = [];
            }

            $stmt = $this->query($sql, $params);
            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error obteniendo tiendas de retiro: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener tiendas de retiro: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Proceso completo de guardado de serie con transacciones
     * Orquesta los 6 pasos necesarios para asignar una serie a una factura
     * @param array $param Datos completos con DATOS_FACTURA, DATOS_SERIE, DATOS_RETIRO, usuario
     * @return array Resultado con success y mensaje
     */
    public function Guardar_Datos_Serie($param)
    {
        try {
            // Paso 1: Validar que la serie esté disponible
            $validarSerie = $this->Validar_Serie_Disponible($param);
            if (!$validarSerie['success']) {
                return [
                    'success' => false,
                    'mensaje' => 'La serie no se encuentra disponible, seleccionar otra',
                    'detalles' => $validarSerie
                ];
            }

            // Paso 2: Preparar factura (insertar en facturaslistas)
            $prepararFactura = $this->Prepara_Factura($param);
            if (!$prepararFactura['success']) {
                return [
                    'success' => false,
                    'mensaje' => 'Error al preparar factura',
                    'detalles' => $prepararFactura
                ];
            }

            // Paso 3: Verificar factura (crear/actualizar RMA)
            $verificarFactura = $this->Verificar_Factura($param);
            if (!$verificarFactura['success']) {
                return [
                    'success' => false,
                    'mensaje' => 'Error al verificar factura',
                    'detalles' => $verificarFactura
                ];
            }

            $rmaDtId = $verificarFactura['RMADTID'];

            // Paso 4: Actualizar detalle extendido
            $actualizarDtex = $this->Actualizar_VEN_FDT_DETEX($param);
            if (!$actualizarDtex['success']) {
                return [
                    'success' => false,
                    'mensaje' => 'Error al actualizar detalle extendido',
                    'detalles' => $actualizarDtex
                ];
            }

            // Paso 5: Actualizar tabla de series de motos
            $actualizarSeries = $this->Actualizar_Tabla_Series_motos($param, $rmaDtId);
            if (!$actualizarSeries['success']) {
                return [
                    'success' => false,
                    'mensaje' => 'Error al actualizar tabla de series',
                    'detalles' => $actualizarSeries
                ];
            }

            // Paso 6: Actualizar estado en facturaslistas
            $actualizarFacturasListas = $this->Actualizar_Facturas_listas($param);
            if (!$actualizarFacturasListas['success']) {
                return [
                    'success' => false,
                    'mensaje' => 'Error al actualizar estado de factura',
                    'detalles' => $actualizarFacturasListas
                ];
            }

            // Todo exitoso
            return [
                'success' => true,
                'mensaje' => 'Datos guardados correctamente',
                'detalles' => [
                    'PREPARAR_FACT' => $prepararFactura,
                    'VERIFICAR_FACT' => $verificarFactura,
                    'ACTUALIZAR_DTEX' => $actualizarDtex,
                    'ACTUALIZAR_SERIES' => $actualizarSeries,
                    'ACTUALIZAR_FACTURAS_LISTAS' => $actualizarFacturasListas
                ]
            ];

        } catch (Exception $e) {
            $this->logError("Error en Guardar_Datos_Serie: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error inesperado al guardar datos de serie',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Valida que la serie esté disponible en inventario
     */
    private function Validar_Serie_Disponible($param)
    {
        try {
            $datosFactura = $param["DATOS_FACTURA"];
            $datosSerie = $param["DATOS_SERIE"];

            $productoId = $datosFactura[0]["ProductoID"];
            $serie = $datosSerie[0]["Serie"];

            $sql = "SELECT * FROM CARTIMEX..INV_PRODUCTOS_SERIES_COMPRAS_MOTOS
                    WHERE ProductoID = :ProductoID
                    AND Serie = :Serie";

            $params = [
                ':ProductoID' => $productoId,
                ':Serie' => $serie
            ];

            $result = $this->query($sql, $params);

            if ($result && $result['success'] && count($result['data']) > 0) {
                $estado = $result['data'][0]["Estado_Serie"];
                if ($estado != "INVENTARIO") {
                    return [
                        'success' => false,
                        'message' => 'Serie no disponible, estado: ' . $estado
                    ];
                }
                return ['success' => true, 'message' => 'Serie disponible'];
            }

            return [
                'success' => false,
                'message' => 'Serie no encontrada'
            ];

        } catch (Exception $e) {
            $this->logError("Error validando serie: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al validar serie: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Inserta registro en facturaslistas
     */
    private function Prepara_Factura($param)
    {
        try {
            $datosFactura = $param["DATOS_FACTURA"];
            $facturaId = $datosFactura[0]["FacturaID"];
            $bodegaId = "";  // Vacío para motos
            $usuario = $param['usuario'];
            $esVirtual = $datosFactura[0]["ES_VIRTUAL"];
            $tipo = 'VEN-FA';

            $sql = "EXECUTE Log_facturaslistas_preparando_insert_MOTOS
                    @id = :facturaid,
                    @usuario = :usuario,
                    @tipo = :tipo,
                    @bodegaid = :bodegaid,
                    @es_virtual = :es_virtual";

            $params = [
                ':facturaid' => $facturaId,
                ':usuario' => $usuario,
                ':tipo' => $tipo,
                ':bodegaid' => $bodegaId,
                ':es_virtual' => $esVirtual
            ];

            $result = $this->db->execute($sql, $params);

            return [
                'success' => $result['success'] ?? false,
                'message' => 'Factura preparada'
            ];

        } catch (Exception $e) {
            $this->logError("Error preparando factura: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al preparar factura: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crea o actualiza RMA de la factura
     */
    private function Verificar_Factura($param)
    {
        try {
            $datosFactura = $param["DATOS_FACTURA"];
            $facturaId = $datosFactura[0]["FacturaID"];
            $secuencia = $datosFactura[0]["Secuencia"];
            $cliente = $datosFactura[0]["cliente"];
            $usuario = $param['usuario'];

            // Verificar si ya existe RMA para esta factura
            $sqlCheck = "SELECT * FROM RMA_FACTURAS WHERE FacturaID = :FacturaID";
            $resultCheck = $this->query($sqlCheck, [':FacturaID' => $facturaId]);

            $rmaId = null;

            if ($resultCheck && $resultCheck['success'] && count($resultCheck['data']) > 0) {
                // Ya existe RMA, usar el ID existente
                $rmaId = $resultCheck['data'][0]["ID"];
            } else {
                // Crear nuevo RMA
                $detalle = "Factura de Venta Nro: " . $secuencia . " Cliente: " . $cliente;
                $fecha = date('Ymd');

                $sqlInsert = "EXECUTE WEB_RMA_Ventas_Insert
                              @facturaid = :facturaid,
                              @fecha = :fecha,
                              @detalle = :detalle,
                              @creadopor = :creadopor";

                $paramsInsert = [
                    ':facturaid' => $facturaId,
                    ':fecha' => $fecha,
                    ':detalle' => $detalle,
                    ':creadopor' => $usuario
                ];

                $resultInsert = $this->query($sqlInsert, $paramsInsert);

                if ($resultInsert && $resultInsert['success'] && count($resultInsert['data']) > 0) {
                    $rmaId = $resultInsert['data'][0]["RMAID"];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Error al crear RMA'
                    ];
                }
            }

            // Guardar detalle de RMA
            $guardarDt = $this->Guardar_Rma_Dt($param, $rmaId);

            if ($guardarDt['success']) {
                return [
                    'success' => true,
                    'message' => 'RMA verificado',
                    'RMADTID' => $guardarDt['RMADTID']
                ];
            }

            return $guardarDt;

        } catch (Exception $e) {
            $this->logError("Error verificando factura: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al verificar factura: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Guarda el detalle del RMA
     */
    private function Guardar_Rma_Dt($param, $rmaId)
    {
        try {
            $datosFactura = $param["DATOS_FACTURA"];
            $datosSerie = $param["DATOS_SERIE"];

            $productoId = $datosFactura[0]["ProductoID"];
            $serie = $datosSerie[0]["Serie"];
            $usuario = $param['usuario'];

            $sql = "EXECUTE WEB_RMA_Ventas_Insert_DT
                    @serie = :serie,
                    @ProductoID = :productoid,
                    @RmaFacturaID = :rmafacturaid,
                    @creadopor = :creadopor";

            $params = [
                ':serie' => $serie,
                ':productoid' => $productoId,
                ':rmafacturaid' => $rmaId,
                ':creadopor' => $usuario
            ];

            $result = $this->query($sql, $params);

            if ($result && $result['success'] && count($result['data']) > 0) {
                return [
                    'success' => true,
                    'message' => 'RMA DT guardado',
                    'RMADTID' => $result['data'][0]["RMAIDDT"]
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al guardar RMA DT'
            ];

        } catch (Exception $e) {
            $this->logError("Error guardando RMA DT: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al guardar RMA DT: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza el detalle extendido de la factura con información de la moto
     */
    private function Actualizar_VEN_FDT_DETEX($param)
    {
        try {
            $datosFactura = $param["DATOS_FACTURA"];
            $datosSerie = $param["DATOS_SERIE"];

            $facturaId = $datosFactura[0]["FacturaID"];
            $productoId = $datosFactura[0]["ProductoID"];
            $serie = $datosSerie[0]["Serie"];

            // Obtener datos de la moto
            $sqlMoto = "SELECT p.nombre as nombre_producto, m.* 
                        FROM CARTIMEX..INV_PRODUCTOS_SERIES_COMPRAS_MOTOS m
                        LEFT JOIN CARTIMEX..inv_productos p ON p.ID = m.ProductoID
                        WHERE m.ProductoID = :ProductoID
                        AND m.Serie = :Serie";

            $resultMoto = $this->query($sqlMoto, [
                ':ProductoID' => $productoId,
                ':Serie' => $serie
            ]);

            if (!$resultMoto || !$resultMoto['success'] || count($resultMoto['data']) == 0) {
                return [
                    'success' => false,
                    'message' => 'No se encontraron datos de la moto'
                ];
            }

            $moto = $resultMoto['data'][0];

            // Obtener precio de la factura
            $sqlFactDt = "SELECT * FROM VEN_FACTURAS_DT
                          WHERE FacturaID = :FacturaID
                          AND ProductoID = :ProductoID";

            $resultFactDt = $this->query($sqlFactDt, [
                ':FacturaID' => $facturaId,
                ':ProductoID' => $productoId
            ]);

            if (!$resultFactDt || !$resultFactDt['success'] || count($resultFactDt['data']) == 0) {
                return [
                    'success' => false,
                    'message' => 'No se encontró detalle de factura'
                ];
            }

            $factDt = $resultFactDt['data'][0];

            // Construir detalle extendido
            $detex = "SERIE:" . $moto["Serie"] . 
                     ",MARCA:" . $moto["Marca"] . 
                     ",MODELO:" . $moto["Modelo"] . 
                     ",AÑO_MODELO:" . $moto["AÑO_MODELO"] . 
                     ",CLASE:" . $moto["Clases"] . 
                     ",MOTOR:" . $moto["Motor"] . 
                     ",COLOR:" . $moto["Color"] . 
                     ",CHASIS:" . $moto["Chasis"] . 
                     ",PRECIO:" . $factDt["Total"] . 
                     ",ESTADO:" . $moto["estado"] . 
                     ",CAPACIDAD_PERSONAS: " . $moto["capacidad"] . 
                     ",TONELAJE:" . $moto["Tonelaje"] . 
                     ",CILINDRAJE:" . $moto["Cilindraje"] . 
                     ", RAMV:" . $moto["Ramv"] . 
                     ",TIPO:" . trim($moto["Tipo"]) . 
                     ",ORIGEN:" . $moto["PAIS_DE_ORIGEN"] . 
                     ",TIPO_COMBUSTIBLE:" . $moto["TIPO_DE_COMBUSTIBLE"] . 
                     ",EJES:" . $moto["EJES"] . 
                     ",RUEDAS:" . $moto["EJES"];

            // Actualizar VEN_FACTURAS_DT
            $sqlUpdate = "UPDATE VEN_FACTURAS_DT 
                          SET Detalle_Ex = :Detalle_Ex
                          WHERE FacturaID = :FacturaID 
                          AND ProductoID = :ProductoID";

            $result = $this->db->execute($sqlUpdate, [
                ':FacturaID' => $facturaId,
                ':ProductoID' => $productoId,
                ':Detalle_Ex' => trim($detex)
            ]);

            return [
                'success' => $result['success'] ?? false,
                'message' => 'Detalle extendido actualizado'
            ];

        } catch (Exception $e) {
            $this->logError("Error actualizando detalle extendido: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar detalle extendido: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza la tabla de series de motos marcándola como vendida
     */
    private function Actualizar_Tabla_Series_motos($param, $rmaDtId)
    {
        try {
            $datosFactura = $param["DATOS_FACTURA"];
            $datosSerie = $param["DATOS_SERIE"];

            $facturaId = $datosFactura[0]["FacturaID"];
            $productoId = $datosFactura[0]["ProductoID"];
            $serie = $datosSerie[0]["Serie"];
            $usuario = $param['usuario'];
            $estado = 'VENDIDO';

            $sql = "UPDATE CARTIMEX..INV_PRODUCTOS_SERIES_COMPRAS_MOTOS 
                    SET Facturaid = :facturaid,
                        Estado_Serie = :estado,
                        RmaDtId = :RmaDtId,
                        Estado_serie_fecha = GETDATE(),
                        Estado_serie_por = :CreadoPor
                    WHERE ProductoID = :productoid 
                    AND Serie = :serie";

            $params = [
                ':facturaid' => $facturaId,
                ':estado' => $estado,
                ':RmaDtId' => $rmaDtId,
                ':CreadoPor' => $usuario,
                ':productoid' => $productoId,
                ':serie' => $serie
            ];

            $result = $this->db->execute($sql, $params);

            return [
                'success' => $result['success'] ?? false,
                'message' => 'Tabla de series actualizada'
            ];

        } catch (Exception $e) {
            $this->logError("Error actualizando tabla de series: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar tabla de series: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza el estado de la factura en facturaslistas
     */
    private function Actualizar_Facturas_listas($param)
    {
        try {
            $datosFactura = $param["DATOS_FACTURA"];
            $facturaId = $datosFactura[0]["FacturaID"];
            $usuario = $param['usuario'];
            $tiendaRetiro = $param["DATOS_RETIRO"] ?? '';
            $tipo = 'VEN-FA';

            $sql = "UPDATE facturaslistas 
                    SET verificado = :usuario,
                        fechaVerificado = GETDATE(),
                        ESTADO = 'VERIFICADA',
                        tienda_Retiro = :tienda_Retiro
                    WHERE factura = :facturaid 
                    AND Tipo = :tipo 
                    AND es_moto = 1";

            $params = [
                ':facturaid' => $facturaId,
                ':usuario' => $usuario,
                ':tipo' => $tipo,
                ':tienda_Retiro' => $tiendaRetiro
            ];

            $result = $this->db->execute($sql, $params);

            return [
                'success' => $result['success'] ?? false,
                'message' => 'Estado de factura actualizado'
            ];

        } catch (Exception $e) {
            $this->logError("Error actualizando facturas listas: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al actualizar facturas listas: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Marca una moto como recibida
     */
    public function Recibir_moto($param)
    {
        try {
            $datosFactura = $param["ARRAY_DATOS_RECIBIR"];
            $facturaId = $datosFactura["FacturaID"];
            $comentario = $param["comentario"] ?? '';
            $usuario = $param['usuario'];

            // Verificar si ya fue recibida
            $sqlCheck = "SELECT recibido FROM facturaslistas WHERE factura = :facturaid";
            $resultCheck = $this->query($sqlCheck, [':facturaid' => $facturaId]);

            if ($resultCheck && $resultCheck['success'] && count($resultCheck['data']) > 0) {
                if ($resultCheck['data'][0]["recibido"] == 1) {
                    return [
                        'success' => false,
                        'mensaje' => 'Ya fue recibida, refrescar la página'
                    ];
                }
            }

            // Actualizar como recibida
            $sql = "UPDATE facturaslistas 
                    SET recibido = 1,
                        recibido_por = :recibido_por,
                        recibido_fecha = GETDATE(),
                        recibido_comentario = :comentario
                    WHERE factura = :facturaid";

            $params = [
                ':facturaid' => $facturaId,
                ':comentario' => $comentario,
                ':recibido_por' => $usuario
            ];

            $result = $this->db->execute($sql, $params);

            return [
                'success' => $result['success'] ?? false,
                'mensaje' => 'Moto recibida correctamente'
            ];

        } catch (Exception $e) {
            $this->logError("Error recibiendo moto: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error al recibir moto: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Marca una moto como entregada al cliente
     */
    public function Entregar_moto($param)
    {
        try {
            $datosFactura = $param["ARRAY_DATOS_ENTREGAR"];
            $facturaId = $datosFactura["FacturaID"];
            $comentario = $param["comentario"] ?? '';
            $usuario = $param['usuario'];

            // Verificar si ya fue entregada
            $sqlCheck = "SELECT entregado_cliente FROM facturaslistas WHERE factura = :facturaid";
            $resultCheck = $this->query($sqlCheck, [':facturaid' => $facturaId]);

            if ($resultCheck && $resultCheck['success'] && count($resultCheck['data']) > 0) {
                if ($resultCheck['data'][0]["entregado_cliente"] == 1) {
                    return [
                        'success' => false,
                        'mensaje' => 'Ya fue entregada, refrescar la página'
                    ];
                }
            }

            // Actualizar como entregada
            $sql = "UPDATE COMPUTRONSA..facturaslistas 
                    SET entregado_cliente = 1,
                        entregado_cliente_por = :entregado_por,
                        entregado_cliente_fecha = GETDATE(),
                        entregado_cliente_comentario = :comentario
                    WHERE factura = :facturaid 
                    AND ISNULL(FECHAYHORA,'') = ''";

            $params = [
                ':facturaid' => $facturaId,
                ':comentario' => $comentario,
                ':entregado_por' => $usuario
            ];

            $result = $this->db->execute($sql, $params);

            return [
                'success' => $result['success'] ?? false,
                'mensaje' => 'Moto entregada correctamente'
            ];

        } catch (Exception $e) {
            $this->logError("Error entregando moto: " . $e->getMessage());
            return [
                'success' => false,
                'mensaje' => 'Error al entregar moto: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene las motos pendientes de recibir
     */
    public function Motos_por_recibir($param)
    {
        try {
            $sql = "SELECT * FROM facturaslistas 
                    WHERE es_moto = 1 
                    AND ESTADO = 'VERIFICADA'
                    AND (recibido IS NULL OR recibido = 0)
                    ORDER BY fechaVerificado DESC";

            $result = $this->query($sql, []);

            return $result;

        } catch (Exception $e) {
            $this->logError("Error obteniendo motos por recibir: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al obtener motos por recibir: ' . $e->getMessage()
            ];
        }
    }
}
