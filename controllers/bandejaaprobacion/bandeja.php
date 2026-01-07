<?php
require_once __DIR__ . '../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class Bandeja extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'bandejaaprobacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('bandeja'); // Cargar el modelo correcto
    }

    function GetFacturasAprobacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) return;

        $params = $this->getJsonInput();

        // echo json_encode($params);
        // exit();

        $result = $this->model->GetFacturasAprobacion($params);
        $result["ruta_archivos"] = constant("DOWNLOAD_PATH") . "Cartimex/proveeduria/ingresos_facturas/facturas/";


        for ($i = 0; $i < count($result["data"]); $i++) {
            $d = $this->model->GetFacturasDocumentosEspeciales($result["data"][$i]['CedBeneficiario']);
            $result["data"][$i]['documentos_especiales'] = $d["data"];
        }

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }

    function GetUsuariosEmail()
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

    function GetUsuariosAprobadores()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $result = $this->model->GetUsuariosAprobadores();

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

    function GuardarAprobacionRegular()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->GuardarAprobacionRegular($data);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                "message" => "Aprobación guardada correctamente",
                'data' => $result,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "message" => "Error al guardar la aprobación",
                'data' => $result,
            ], 200);
        }
    }

    function GuardarPreaprobacion()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->GuardarPreaprobacion($data);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                "message" => "Aprobación guardada correctamente",
                'data' => $result,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "message" => "Error al guardar la aprobación",
                'data' => $result,
            ], 200);
        }
    }
}
