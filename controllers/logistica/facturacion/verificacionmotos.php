<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';

class VerificacionMotos extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/';
        $this->loadModel('verificacionmotos');
    }

    public function FACTURAS_PENDIENTES_VERIFICAR()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();
        $result = $this->model->FACTURAS_PENDIENTES_VERIFICAR($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener facturas pendientes',
                'respuesta' => $result
            ], 200);
        }
    }

    public function FACTURAS_PENDIENTES_VERIFICAR_DETALLE()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();
        $facturaId = $data['factura_id'] ?? null;

        if (!$facturaId) {
            $this->jsonResponse(['success' => false, 'error' => 'ID de factura no proporcionado'], 400);
            return;
        }

        $result = $this->model->FACTURAS_PENDIENTES_VERIFICAR_DETALLE($facturaId);
        $this->jsonResponse($result, 200);
    }

    public function Mostrar_Series_Disponibles()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();
        $datosFactura = $data['DATOS'] ?? null;

        if (!$datosFactura || !isset($datosFactura[0])) {
            $this->jsonResponse(['success' => false, 'error' => 'Datos no proporcionados'], 400);
            return;
        }

        $result = $this->model->Mostrar_Series_Disponibles($datosFactura[0]);
        $this->jsonResponse($result, 200);
    }

    public function Mostrar_Tiendas_retiro()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();
        $datosFactura = $data['DATOS'] ?? null;

        if (!$datosFactura || !isset($datosFactura[0])) {
            $this->jsonResponse(['success' => false, 'error' => 'Datos no proporcionados'], 400);
            return;
        }

        $result = $this->model->Mostrar_Tiendas_retiro($datosFactura[0]);
        $this->jsonResponse($result, 200);
    }

    public function Guardar_Datos_Serie()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData) return;

            $data = $this->getJsonInput();
            
            if (!isset($data['DATOS_FACTURA']) || !isset($data['DATOS_SERIE'])) {
                $this->jsonResponse(['success' => false, 'error' => 'Datos incompletos'], 400);
                return;
            }

            $data['usuario'] = $jwtData['usuario'] ?? $jwtData['usrid'] ?? null;
            $this->model->db->beginTransaction();

            $result = $this->model->Guardar_Datos_Serie($data);

            if ($result && $result['success']) {
                $this->model->db->commit();
                $this->jsonResponse($result, 200);
            } else {
                $this->model->db->rollback();
                $this->jsonResponse(['success' => false, 'error' => $result['mensaje'] ?? 'Error', 'detalles' => $result], 200);
            }
        } catch (Exception $e) {
            try { $this->model->db->rollback(); } catch (Exception $ex) {}
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function Recibir_moto()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData) return;

            $data = $this->getJsonInput();
            
            if (!isset($data['ARRAY_DATOS_RECIBIR']['FacturaID'])) {
                $this->jsonResponse(['success' => false, 'error' => 'Datos incompletos'], 400);
                return;
            }

            $data['usuario'] = $jwtData['usuario'] ?? $jwtData['usrid'] ?? null;
            $result = $this->model->Recibir_moto($data);
            $this->jsonResponse($result, 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function Entregar_moto()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData) return;

            $data = $this->getJsonInput();
            
            if (!isset($data['ARRAY_DATOS_ENTREGAR']['FacturaID'])) {
                $this->jsonResponse(['success' => false, 'error' => 'Datos incompletos'], 400);
                return;
            }

            $data['usuario'] = $jwtData['usuario'] ?? $jwtData['usrid'] ?? null;
            $result = $this->model->Entregar_moto($data);
            $this->jsonResponse($result, 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }

    public function Motos_por_recibir()
    {
        try {
            $jwtData = $this->authenticateAndConfigureModel(2);
            if (!$jwtData) return;

            $data = $this->getJsonInput();
            $data['usuario'] = $jwtData['usuario'] ?? $jwtData['usrid'] ?? null;

            $result = $this->model->Motos_por_recibir($data);
            $this->jsonResponse($result, 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => 'Error inesperado', 'message' => $e->getMessage()], 500);
        }
    }
}