<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
require_once __DIR__ . '../../../../libs/EmailService.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class ingresofacturasdobra extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'proveeduria/facturas/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('ingresofacturasdobra'); // Cargar el modelo correcto
    }

    function GetFacturasPorIngresar()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetFacturasPorIngresar($data);
        $result["ruta_archivos"] = constant("DOWNLOAD_PATH") . "Cartimex/proveeduria/ingresos_facturas/facturas/";

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                'ruta_archivos' => $result["ruta_archivos"],
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    // function Apro
}
