<?php

class NuevaProteccion extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'ImportCompras/protecciondeprecios';
        $this->loadModel('nuevaproteccion');
    }

    /**
     * Cargar tipos de clientes para dropdown de exclusiones
     */
    public function Cargar_Tipos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $result = $this->model->Cargar_Tipos();

        $this->jsonResponse([
            'success' => true,
            'data' => $result
        ], 200);
    }

    /**
     * Buscar producto por código o nombre
     */
    public function Consultar_Productos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();

        if (empty($data['NOMBRE'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'El código o nombre del producto es requerido'
            ], 400);
            return;
        }

        $result = $this->model->Consultar_Productos($data);

        $this->jsonResponse([
            'success' => true,
            'data' => $result,
            "parametros" => $data
        ], 200);
    }

    /**
     * Buscar cliente por cédula o RUC para exclusión o cliente específico
     */
    public function Consultar_Exclusion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();

        if (empty($data['CEDULA'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'La cédula o RUC es requerida'
            ], 400);
            return;
        }

        $result = $this->model->Consultar_Exclusion($data);

        $this->jsonResponse([
            'success' => true,
            'data' => $result
        ], 200);
    }

    /**
     * Guardar protección completa (cabecera + detalle + excluidos)
     */
    public function Guardar_Proteccion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $data = $this->getJsonInput();

        if (empty($data['cabecera']) || empty($data['detalle'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Datos de cabecera y detalle son requeridos'
            ], 400);
            return;
        }

        $cabecera = $data['cabecera'];
        $detalle = $data['detalle'];
        $excluidos = $data['excluidos'] ?? [];

        // Inyectar usuario del JWT
        $cabecera['CREADO_POR'] = $jwtData['username'] ?? $jwtData['usrid'] ?? 'SISTEMA';

        $result = $this->model->Guardar_Proteccion($cabecera, $detalle, $excluidos);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result['data'],
                'proteccionId' => $result['proteccionId'],
                'message' => 'Protección guardada correctamente'
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result['message'] ?? 'Error al guardar la protección'
            ], 200);
        }
    }
}
