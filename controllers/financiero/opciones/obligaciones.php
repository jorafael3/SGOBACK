<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class obligaciones extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'financiero/opciones'; // Especifica la carpeta donde está el modelo
        $this->loadModel('obligaciones'); // Cargar el modelo correcto
    }

    function Cargar_Tipos_Obligaciones()
    {
        $result = $this->model->Cargar_Tipos_Obligaciones();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener tipos de obligaciones',
                "respuesta" => $result
            ], 200);
        }
    }
    
    function Cargar_ACC_CuentasGastos()
    {
        $result = $this->model->Cargar_ACC_CuentasGastos();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener tipos de obligaciones',
                "respuesta" => $result
            ], 200);
        }
    }
    
    function Cargar_ACC_CuentasPasivos_Provision()
    {
        $result = $this->model->Cargar_ACC_CuentasPasivos_Provision();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener tipos de obligaciones',
                "respuesta" => $result
            ], 200);
        }
    }

    function Actualizar_cuentas_obligaciones()
    {
        $items = $this->getJsonInput();
        if (!is_array($items)) {
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Formato inválido'
            ], 500);
        }
        foreach ($items as $item) {
            $result = $this->model->Actualizar_cuentas_obligaciones($item);
        }
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al actualizar las cuentas de obligaciones',
                "respuesta" => $result
            ], 500);
        }
    }

    function Borrar_Tipo_Obligacion()
    {
        $item = $this->getJsonInput();
        $result = $this->model->Borrar_Tipo_Obligacion($item);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al eliminar el tipo de obligación',
                "respuesta" => $result
            ], 200);
        }
    }
}