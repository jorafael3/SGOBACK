<?php
require_once __DIR__ . '../../../libs/JwtHelper.php';
require_once __DIR__ . '../../../libs/EmailService.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class Notificaciones extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'notificaciones/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('notificaciones'); // Cargar el modelo correcto
    }


    function GetNotificaciones()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);    
        // exit();
        $result = $this->model->GetNotificaciones($data);

        // Verificar que la respuesta sea exitosa y tenga datos
        if (!$result['success'] || !isset($result['data'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => $result['message'] ?? 'Error al obtener notificaciones',
            ], 200);
            return;
        }

        $notificaciones = array_map(function ($n) {
            // Mapear tipo a border_color
            $border_color = 'primary';
            switch ($n['tipo']) {
                case 'success':
                    $border_color = 'success';
                    break;
                case 'warning':
                    $border_color = 'warning';
                    break;
                case 'error':
                    $border_color = 'danger';
                    break;
                case 'info':
                    $border_color = 'primary';
                    break;
            }

            return [
                'id' => (int)$n['id'],                    // Convertir a número
                'usuario_id' => $n['usuario_id'],
                'empresa' => $n['empresa'],
                'tipo' => $n['tipo'],
                'titulo' => $n['titulo'],
                'mensaje' => $n['mensaje'],
                'message' => $n['mensaje'],                // Alias para retrocompatibilidad
                'url' => $n['url'],
                'fecha' => $n['fecha'],
                'leida' => (bool)(int)$n['leida'],        // Convertir a boolean
                'activo' => (bool)(int)$n['activo'],
                'border_color' => $border_color            // Agregar border_color
            ];
        }, $result['data']);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $notificaciones,
                // "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function MarcarComoLeida()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);    
        // exit();
        $result = $this->model->MarcarComoLeida($data);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                // "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function InsertarNotificacion($data)
    {
        $this->model->setEmpresa("produccion_cartimex");

        $result = $this->model->InsertarNotificacion($data);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                // "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }
}
