<?php
require_once __DIR__ . '../../../libs/JwtHelper.php';

class transferencias extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'transferencias/';
        $this->loadModel('transferencias');
    }

    public function gettransferenciaselect()
    {
        $jwtData = $this->authenticateAndConfigureModel(0); // permitir cualquier método para pruebas
        if (!$jwtData) return;

        $result = $this->model->gettransferenciascargar();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result && is_array($result),
            'data'    => $result ?: [],
            'error'   => !$result ? 'Error al obtener transferencias' : null
        ]);
        exit;
    }
}
