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

        for ($i=0; $i <count($result["data"]) ; $i++) { 
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
}
