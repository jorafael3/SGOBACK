<?php

require_once __DIR__ . '/../../libs/JwtHelper.php';

class Fondoreservas extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'recursoshumanos/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('fondoreservas'); // Cargar el modelo correcto
    }


    function GuardarDatosFondoReservas()
    {
        
        
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $input = $this->getJsonInput();

        // El frontend envía: {empresa: "X", datos: [...]}
        // Extraer el array de datos
        $empleados = isset($input['datos']) ? $input['datos'] : $input;
        
        // Verificar si hay datos válidos
        if (!is_array($empleados) || empty($empleados)) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'No se recibieron datos válidos',
                'received' => $input
            ], 400);
            return;
        }

        // Usar empresa del frontend si existe, sino del JWT
        $empresa = $input['empresa'] ?? $jwtData['empresa'];
        $resultados = [];
        $errores = [];
        
        foreach ($empleados as $index => $empleado) {
            try {
                // Preparar datos para el stored procedure
                $empleadoData = [
                    'empresa' => $empresa,
                    'cedula' => $empleado['cedula'] ?? null,
                    'VISTO' => $empleado['VISTO'] ?? 0
                ];
                
                // Llamar al modelo para cada empleado
                $result = $this->model->guardarFondoReserva($empleadoData);
                
                if ($result && $result['success']) {
                    $resultados[] = [
                        'cedula' => $empleado['cedula'],
                        'nombre' => $empleado['nombre'] ?? '',
                        'status' => 'success',
                        'db_output' => $result['stored_procedure_output'] ?? null,
                        'visto_enviado' => $empleado['VISTO']
                    ];
                } else {
                    $errores[] = [
                        'cedula' => $empleado['cedula'],
                        'nombre' => $empleado['nombre'] ?? '',
                        'error' => $result['error'] ?? 'Error desconocido'
                    ];
                }
            } catch (Exception $e) {
                $errores[] = [
                    'cedula' => $empleado['cedula'] ?? 'N/A',
                    'nombre' => $empleado['nombre'] ?? '',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Responder con el resumen
        $this->jsonResponse([
            'success' => count($errores) === 0,
            'total' => count($empleados),
            'procesados' => count($resultados),
            'errores_count' => count($errores),
            'resultados' => $resultados,
            'errores' => $errores
        ], 200);
    }
}
