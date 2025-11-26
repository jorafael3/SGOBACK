<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class parametros extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'financiero/opciones'; // Especifica la carpeta donde está el modelo
        $this->loadModel('parametros'); // Cargar el modelo correcto
    }
}