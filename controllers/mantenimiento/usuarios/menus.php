<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class Menus extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'mantenimiento/usuarios/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('menus'); // Cargar el modelo correcto
    }
    

    function GetMenus()
    {
         echo  json_encode("HOLAAA");
        exit;

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetMenusLista($data);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener los datos de los menús',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function CrearMenu()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        $tipo = $data['Type'] ?? '';
        // echo json_encode($data);
        // exit;

        if ($tipo == "sub") {
            $result = $this->model->CrearSubMenu($data);
        }

        if ($tipo == "link") {
            $result = $this->model->CrearEnlace($data);
        }

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al crear el menú',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }
}
