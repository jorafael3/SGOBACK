<?php
require_once __DIR__ . '../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class Opciones extends Controller
{
    /**
     * Constructor de la clase Empresa
     * Inicializa el modelo de empresa
     **/

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica'; // Especifica la carpeta donde está el modelo
        $this->loadModel('opciones'); // Cargar el modelo correcto
    }

    function GetSucursalesFacturacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetSucursalesFacturacion();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener las sucursales de facturación',
                "data" => $result
            ], 200);
        }
    }

    function GetListaSucursales()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $tipos = [
            array("id" => "ONLINE", "nombre" => "ONLINE"),
            array("id" => "DROPSHIP", "nombre" => "DROPSHIP"),
        ];
        $result = $this->model->getListaSucursales();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                "tipos" => $tipos
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener las sucursales de facturación',
                "data" => $result
            ], 200);
        }
    }

    function SetEditarSucursalFacturacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        // Obtener los datos del cuerpo de la solicitud
        $data = $this->getJsonInput();


        $result = $this->model->SetEditarSucursalFacturacion($data);
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al editar la sucursal de facturación',
                "data" => $result
            ], 200);
        }
    }

    function GetListaBodegas()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $tipos = [
            array("id" => "ONLINE", "nombre" => "ONLINE"),
            array("id" => "DROPSHIP", "nombre" => "DROPSHIP"),
        ];
        $result = $this->model->getListaBodegas();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                "tipos" => $tipos
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener las sucursales de facturación',
                "data" => $result
            ], 200);
        }
    }

    function GetListaBodegasDropShipping()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $tipos = [
            array("id" => "ONLINE", "nombre" => "ONLINE"),
            array("id" => "DROPSHIP", "nombre" => "DROPSHIP"),
        ];
        $result = $this->model->getListaBodegasDropshipping();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                "tipos" => $tipos
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener las sucursales de facturación',
                "data" => $result
            ], 200);
        }
    }

    function SetSaveBodegaFacturacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        // Obtener los datos del cuerpo de la solicitud
        $data = $this->getJsonInput();

        $result = $this->model->SetSaveBodegaFacturacion($data);
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al guardar la bodega de facturación',
                "data" => $result
            ], 200);
        }
    }

    function SetEditarBodegaFacturacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        // Obtener los datos del cuerpo de la solicitud
        $data = $this->getJsonInput();

        $result = $this->model->SetEditarBodegaFacturacion($data);
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al guardar la bodega de facturación',
                "data" => $result
            ], 200);
        }
    }
}
