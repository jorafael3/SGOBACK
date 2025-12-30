<?php
require_once __DIR__ . '../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class Opciones extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'proveeduria'; // Especifica la carpeta donde está el modelo
        $this->loadModel('opciones'); // Cargar el modelo correcto
    }

    // ** RUBROS CENTROS DE COSTO */


    function GetRubros()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }


        $result = $this->model->GetRubros();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }

    function SetGuardarRubro()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        // echo json_encode($data);
        // die();
        $result = $this->model->SetGuardarRubro($data);
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                "message" => "Rubro guardado exitosamente",
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }

    //** CENTROS DE COSTO */


    function GetCentrosCostos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetCentrosCostos();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }

    function Getdivisiones()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetDivisiones();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }

    function GetRubrosCentrosCostos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetRubrosCentrosCostos();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }

    function SetGuardarCentroCostos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        // echo json_encode($data);
        // die();
        $result = $this->model->SetGuardarCentroCostos($data);
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                "message" => "Centro de costos guardado exitosamente",
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    //*** APROBADORES */

    function GetUsuariosAprobadores()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetUsuariosAprobadores();
        $monto = $this->model->GetMontoAprobacion();



        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                'monto' => $monto["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }

    function SetEditarMontoAprobacion() {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        // echo json_encode($data);
        // die();
        $result = $this->model->SetEditarMontoAprobacion($data);
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                "message" => "Monto de aprobacion editado exitosamente",
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function GetUsuariosSgo()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetUsuariosSgo();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }

    function GetTipoGastos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetTipoGastos();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }

    function GetEmpresas()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = [array(
            "id_empresa" => "CARTIMEX",
            "nombre_empresa" => "CARTIMEX"
        ), array(
            "id_empresa" => "COMPUTRON",
            "nombre_empresa" => "COMPUTRON"
        ), array(
            "id_empresa" => "XTRATECH",
            "nombre_empresa" => "XTRATECH"
        )];
        $this->jsonResponse([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    function GuardarAprobador()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $validar = $this->model->validarAprobador($data);
        if (count($validar["data"]) > 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => "El aprobador ya esta registrado",
            ], 200);
            return;
        }


        $result = $this->model->GuardarAprobador($data);
        // echo json_encode($data);
        // die();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                "message" => "Aprobador guardado exitosamente",
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function EditarAprobador()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        // echo json_encode($data);
        // die();
        $result = $this->model->EditarAprobador($data);
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                "message" => "Aprobador editado exitosamente",
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    //*** PROVEEDORESA ESPESCIALES */

    function GetProveedoresEspeciales()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetProveedoresEspeciales();
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result["message"],
            ], 200);
        }
    }
}
