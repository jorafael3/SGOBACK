<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';

class BuscarSeries extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/';
        $this->loadModel('buscarseries');
    }

    /**
     * Búsqueda principal de series
     * Consulta en 4 tablas diferentes:
     * 1. Inventario (SGO_Inv_buscar_series)
     * 2. Productos (SGO_Inv_buscar_series_productos)
     * 3. RMA (RMA_PRODUCTOS)
     * 4. Stock (SGO_BUSCAR_STOCK_SERIE)
     * 
     * Endpoint: POST /buscarseries/Buscar_series
     */
    public function Buscar_series()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData) return;

            $data = $this->getJsonInput();
            
            // Validar datos requeridos
            $serie = $data['Serie'] ?? null;

            if (!$serie) {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Serie no proporcionada'
                ], 400);
                return;
            }

            $result = $this->model->Buscar_series($data);

            if ($result && $result['success']) {
                $this->jsonResponse($result, 200);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error al buscar series',
                    'respuesta' => $result
                ], 200);
            }

        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error inesperado',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar código de producto por serie
     * Endpoint: POST /buscarseries/buscar_codigo
     */
    public function buscar_codigo()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData) return;

            $data = $this->getJsonInput();
            $serie = $data['Serie'] ?? null;

            if (!$serie) {
                $this->jsonResponse(['success' => false, 'error' => 'Serie no proporcionada'], 400);
                return;
            }

            $result = $this->model->buscar_codigo($data);
            $this->jsonResponse($result, 200);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cargar bodega por sucursal
     * Endpoint: POST /buscarseries/cargar_bodega_sucursal
     */
    public function cargar_bodega_sucursal()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData) return;

            $data = $this->getJsonInput();
            $sucursalId = $data['SucursalID'] ?? null;

            if (!$sucursalId) {
                $this->jsonResponse(['success' => false, 'error' => 'SucursalID no proporcionado'], 400);
                return;
            }

            $result = $this->model->cargar_bodega_sucursal($data);
            $this->jsonResponse($result, 200);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Grabar serie en RMA
     * Endpoint: POST /buscarseries/grabar_series
     */
    public function grabar_series()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData) return;

            $data = $this->getJsonInput();
            $productoId = $data['ProductoID'] ?? null;
            $serie = $data['Serie'] ?? null;

            if (!$productoId || !$serie) {
                $this->jsonResponse(['success' => false, 'error' => 'ProductoID y Serie son requeridos'], 400);
                return;
            }

            // Agregar usuario de sesión
            $data['usuario'] = $jwtData['usuario'] ?? $jwtData['usrid'] ?? null;

            $result = $this->model->grabar_series($data);
            $this->jsonResponse($result, 200);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Inserción automática de serie
     * Endpoint: POST /buscarseries/insert_automatico
     */
    public function insert_automatico()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData) return;

            $data = $this->getJsonInput();
            $productoId = $data['ProductoID'] ?? null;
            $serie = $data['Serie'] ?? null;

            if (!$productoId || !$serie) {
                $this->jsonResponse(['success' => false, 'error' => 'ProductoID y Serie son requeridos'], 400);
                return;
            }

            // Agregar usuario de sesión
            $data['usuario'] = $jwtData['usuario'] ?? $jwtData['usrid'] ?? null;

            $result = $this->model->insert_automatico($data);
            $this->jsonResponse($result, 200);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Devolver series
     * Endpoint: POST /buscarseries/DEVOLVER_SERIES
     */
    public function DEVOLVER_SERIES()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData) return;

            $data = $this->getJsonInput();
            $productoId = $data['ProductoID'] ?? null;
            $serie = $data['Serie'] ?? null;
            $facturaId = $data['FacturaID'] ?? null;

            if (!$productoId || !$serie || !$facturaId) {
                $this->jsonResponse(['success' => false, 'error' => 'ProductoID, Serie y FacturaID son requeridos'], 400);
                return;
            }

            $result = $this->model->DEVOLVER_SERIES($data);
            $this->jsonResponse($result, 200);

        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }
}
