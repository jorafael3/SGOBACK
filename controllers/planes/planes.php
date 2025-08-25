<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class Planes extends Controller
{
    /**
     * Constructor de la clase Empresa
     * Inicializa el modelo de empresa
     */
    public function __construct()
    {
        parent::__construct();
        // $this->loadModel('empresa');
    }

    public function getPlanes()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);
        if (!JwtHelper::validateJwt($jwt)) {
            $this->jsonResponse(["success" => false, 'error' => 'Token JWT inválido o expirado'], 401);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(["success" => false, 'error' => 'Método no permitido'], 405);
            return;
        }
        $data = $this->getJsonInput();
        $result = $this->model->getPlanes();
        if ($result) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse($result, 200);
        }
    }

}
