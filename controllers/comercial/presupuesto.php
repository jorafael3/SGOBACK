<?php
require_once __DIR__ . '../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class presupuesto extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'comercial/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('presupuesto'); // Cargar el modelo correcto
    }

    function Cargar_Presupuestos_creados()
    {
        $result = $this->model->Cargar_Presupuestos_creados();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }

    function Nuevo_Presupuesto()
    {
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Nuevo_Presupuesto($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }



    // Vendedores

    function BuscarVendedor()
    {
        $result = $this->model->BuscarVendedor();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }
    function AgregarVendedor()
    {
        $params = $this->getJsonInput();
        $result = $this->model->AgregarVendedor($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }
    function Cargar_Vendedores()
    {
        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;
        $result = $this->model->Cargar_Vendedores($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }

    function Actualizar_Presupuesto_Vendedor()
    {
        $params = $this->getJsonInput();
        $result = $this->model->Actualizar_Presupuesto_Vendedor($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }

    // Categorias
    function Subir_Presupuesto_Categorias()
    {
        $params = $this->getJsonInput();
        $result = $this->model->Subir_Presupuesto_Categorias($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result,
                "params" => $params

            ], 200);
        }
    }
    
    function Cargar_Presupuesto_Categorias()
    {
        $params = $this->getJsonInput();
        $result = $this->model->Cargar_Presupuesto_Categorias($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result,
                "params" => $params

            ], 200);
        }
    }
    function Actualizar_Presupuesto_Categorias()
    {
        $params = $this->getJsonInput();
        $result = $this->model->Actualizar_Presupuesto_Categorias($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result,
                "params" => $params
            ], 200);
        }
    }

    // Meses
    function Cargar_Presupuesto_Meses()
    {
        $params = $this->getJsonInput();
        $result = $this->model->Cargar_Presupuesto_Meses($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }
    function Actualizar_Presupuesto_Mes()
    {
        $params = $this->getJsonInput();
        $result = $this->model->Actualizar_Presupuesto_Mes($params);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }
}
