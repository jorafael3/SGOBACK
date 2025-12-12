<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class crearact extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'ImportCompras/proteccionMarcas'; // Especifica la carpeta donde está el modelo
        $this->loadModel('crearact'); // Cargar el modelo correcto
    }

    function Guardar_datos()
    {
        try {
            $param = $this->getJsonInput();
            $param['usrid'] = $param['userdata']['usuario'] ?? null;
            $result = $this->model->Guardar_datos($param);
            if ($result) {
                $this->jsonResponse( $result, 200);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'No se pudieron guardar los datos', 'respuesta' => $result], 500);
            }
                // $this->jsonResponse(['success' => true, 'data' => $result], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    function Cargar_Tipos_Marcas()
    {
        try {
            $result = $this->model->Cargar_Tipos_Marcas();
            if ($result && $result['success']) {
                $this->jsonResponse($result, 200);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'No se pudieron cargar los tipos de marcas', 'respuesta' => $result], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    function Cargar_Marcas()
    {
        try {
            $param = $this->getJsonInput();
            $result = $this->model->Cargar_Marcas($param);
            if ($result && $result['success']) {
                $this->jsonResponse($result, 200);
            } else {
                $this->jsonResponse(['success' => false, 'error' => 'No se pudieron cargar las marcas', 'respuesta' => $result], 500);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}