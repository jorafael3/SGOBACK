<?php

class BuscarSeriesModel extends Model
{
    public function __construct($empresaCode = null)
    {
        parent::__construct($empresaCode);

        if (DEBUG) {
            error_log("BuscarSeriesModel conectado a: " . $this->empresaCode);
        }
    }

    /**
     * Búsqueda principal de series en 4 tablas
     * @param array $param Datos con Serie, SucursalID, ProductoID, BodegaID
     * @return array Resultado con 4 conjuntos de datos
     */
    public function Buscar_series($param)
    {
        try {
            $serie = $param["Serie"];
            $sucursalId = $param["SucursalID"] ?? null;
            $productoId = $param["ProductoID"] ?? null;
            $bodegaId = $param["BodegaID"] ?? null;

            // Resultado 1: Búsqueda en inventario
            $sql1 = "EXEC SGO_Inv_buscar_series @Serie = :Serie";
            $result1 = $this->query($sql1, [':Serie' => $serie]);

            // Resultado 2: Búsqueda en productos
            $sql2 = "EXEC COMPUTRONSA..SGO_Inv_buscar_series_productos @Serie = :Serie";
            $result2 = $this->query($sql2, [':Serie' => $serie]);

            // Resultado 3: Búsqueda en RMA
            $sql3 = "SELECT Pr.Serie, Código, P.Nombre, Pr.Estado, p.id as ProductoID 
                     FROM COMPUTRONSA..RMA_PRODUCTOS Pr 
                     INNER JOIN INV_PRODUCTOS P WITH (NOLOCK) ON P.ID = Pr.ProductoID
                     WHERE Pr.Serie = :Serie AND NOT GrupoID = '0000000158'";
            $result3 = $this->query($sql3, [':Serie' => $serie]);

            // Resultado 4: Búsqueda de stock (solo si hay ProductoID y BodegaID)
            $result4 = ['success' => true, 'data' => []];
            if ($productoId && $bodegaId) {
                $sql4 = "EXEC SGO_BUSCAR_STOCK_SERIE 
                         @Serie = :Serie, 
                         @BodegaID = :BodegaID, 
                         @ProductoID = :ProductoID";
                $result4 = $this->query($sql4, [
                    ':Serie' => $serie,
                    ':BodegaID' => $bodegaId,
                    ':ProductoID' => $productoId
                ]);
            }

            return [
                'success' => true,
                'data' => [
                    'result1' => $result1['data'] ?? [],
                    'result2' => $result2['data'] ?? [],
                    'result3' => $result3['data'] ?? [],
                    'result4' => $result4['data'] ?? []
                ]
            ];

        } catch (Exception $e) {
            $this->logError("Error en Buscar_series: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al buscar series: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Buscar código de producto por serie
     * @param array $param Datos con Serie
     * @return array Resultado con ID del producto
     */
    public function buscar_codigo($param)
    {
        try {
            $serie = $param["Serie"];

            $sql = "SELECT Código, ID 
                    FROM INV_PRODUCTOS_SERIES_COMPRAS C 
                    INNER JOIN INV_PRODUCTOS p WITH (NOLOCK) ON C.ProductoID = p.ID
                    WHERE C.Serie = :Serie";

            $result = $this->query($sql, [':Serie' => $serie]);

            if ($result && $result['success'] && count($result['data']) > 0) {
                return [
                    'success' => true,
                    'ID' => $result['data'][0]['ID'],
                    'Codigo' => $result['data'][0]['Código']
                ];
            }

            return [
                'success' => true,
                'ID' => null,
                'Codigo' => null
            ];

        } catch (Exception $e) {
            $this->logError("Error en buscar_codigo: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al buscar código: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cargar bodega por sucursal
     * @param array $param Datos con SucursalID
     * @return array Resultado con ID de bodega
     */
    public function cargar_bodega_sucursal($param)
    {
        try {
            $sucursalId = $param["SucursalID"];

            $sql = "SELECT Código, ID 
                    FROM INV_BODEGAS 
                    WHERE Sucursal = :SucursalID";

            $result = $this->query($sql, [':SucursalID' => $sucursalId]);

            if ($result && $result['success'] && count($result['data']) > 0) {
                return [
                    'success' => true,
                    'ID' => $result['data'][0]['ID'],
                    'Codigo' => $result['data'][0]['Código']
                ];
            }

            return [
                'success' => true,
                'ID' => null,
                'Codigo' => null
            ];

        } catch (Exception $e) {
            $this->logError("Error en cargar_bodega_sucursal: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al cargar bodega: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Grabar serie en RMA
     * @param array $param Datos con ProductoID, Serie, usuario
     * @return array Resultado de la operación
     */
    public function grabar_series($param)
    {
        try {
            $productoId = $param["ProductoID"];
            $serie = $param["Serie"];
            $usuario = $param["usuario"];

            $sql = "EXEC COMPUTRONSA..SGO_INV_INSERT_SERIES_RMA 
                    @Serie = :Serie, 
                    @ProductoID = :ProductoID, 
                    @CreadoPor = :CreadoPor";

            $params = [
                ':Serie' => $serie,
                ':ProductoID' => $productoId,
                ':CreadoPor' => $usuario
            ];

            $result = $this->query($sql, $params);

            if ($result && $result['success']) {
                return [
                    'success' => true,
                    'SerieGuardada' => $serie,
                    'Result' => $result['data'] ?? [],
                    'message' => 'Serie guardada correctamente'
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al guardar serie'
            ];

        } catch (Exception $e) {
            $this->logError("Error en grabar_series: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al guardar serie: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Inserción automática de serie
     * @param array $param Datos con ProductoID, Serie, usuario
     * @return array Resultado de la operación
     */
    public function insert_automatico($param)
    {
        try {
            $productoId = $param["ProductoID"];
            $serie = $param["Serie"];
            $usuario = $param["usuario"];

            $sql = "EXEC COMPUTRONSA..SGO_INV_INSERT_SERIES_RMA 
                    @Serie = :Serie, 
                    @ProductoID = :ProductoID, 
                    @CreadoPor = :CreadoPor";

            $params = [
                ':Serie' => $serie,
                ':ProductoID' => $productoId,
                ':CreadoPor' => $usuario
            ];

            $result = $this->query($sql, $params);

            if ($result && $result['success']) {
                return [
                    'success' => true,
                    'SerieGuardada' => $serie,
                    'Result' => $result['data'] ?? [],
                    'message' => 'Serie insertada automáticamente'
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al insertar serie'
            ];

        } catch (Exception $e) {
            $this->logError("Error en insert_automatico: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al insertar serie: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Devolver series
     * @param array $param Datos con ProductoID, Serie, FacturaID
     * @return array Resultado de la operación
     */
    public function DEVOLVER_SERIES($param)
    {
        try {
            $productoId = $param["ProductoID"];
            $serie = $param["Serie"];
            $facturaId = $param["FacturaID"];

            $sql = "EXEC DEVOLVER_SERIES_SGO_IMFORME 
                    @ProductoID = :ProductoID, 
                    @Serie = :Serie, 
                    @FacturaID = :FacturaID";

            $params = [
                ':ProductoID' => $productoId,
                ':Serie' => $serie,
                ':FacturaID' => $facturaId
            ];

            $result = $this->query($sql, $params);

            if ($result && $result['success']) {
                return [
                    'success' => true,
                    'Result' => $result['data'] ?? [],
                    'message' => 'Serie devuelta correctamente'
                ];
            }

            return [
                'success' => false,
                'message' => 'Error al devolver serie'
            ];

        } catch (Exception $e) {
            $this->logError("Error en DEVOLVER_SERIES: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al devolver serie: ' . $e->getMessage()
            ];
        }
    }
}
