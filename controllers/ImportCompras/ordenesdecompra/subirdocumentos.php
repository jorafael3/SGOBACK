<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
require_once __DIR__ . '../../../../libs/EmailService.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class SubirDocumentos extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'ordenesdecompra/subirdocumentos/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('subirdocumentos'); // Cargar el modelo correcto
    }


    function GetOrdenesPorSubirDocumentos()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);    
        // exit();
        $result = $this->model->GetOrdenesPorSubirDocumentos($data);
        $result["ruta_archivos"] = constant("DOWNLOAD_PATH") . "Cartimex/Compras/ordenes_documentos/";

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                'ruta_archivos' => $result["ruta_archivos"],
                "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function GetOrdenesPorSubirDocumentosDetalle()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);    
        // exit();
        $result = $this->model->GetOrdenesPorSubirDocumentosDetalles($data);
        $result["ruta_archivos"] = constant("DOWNLOAD_PATH") . "Cartimex/Compras/ordenes_documentos/";

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                'ruta_archivos' => $result["ruta_archivos"],
                "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function SaveDocumentoOrdenCompra()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);
        // exit();

        $fileData = base64_decode($data['documento']);
        $fileName = $data['nombre'];

        $result = $this->model->SaveDocumentoOrdenCompra($data);

        if ($result['success']) {
            $uploadDir = '';
            if (!empty($fileData) && !empty($fileName)) {
                $uploadDir = UPLOAD_PATH . 'Cartimex/Compras/ordenes_documentos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $filePath = $uploadDir . $fileName;
                if (file_put_contents($filePath, $fileData) === false) {
                    error_log("Error guardando archivo: " . $filePath);
                }
            }

            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                "uploadDir" => $uploadDir,
                "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }
}
