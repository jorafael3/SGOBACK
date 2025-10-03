<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class Pr extends Controller
{
    /**
     * Constructor de la clase Empresa
     * Inicializa el modelo de empresa
     */
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'ejemplos/pr/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('pr'); // Cargar el modelo de empresa
    }

    function p()
    {
        // echo json_encode("asdasdasd");
        // exit();
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $result = $this->model->getAllEmpresas();
    }
}
