<?php
require_once __DIR__ . '/../libs/JwtHelper.php';
require_once __DIR__ . '/../models/planmodel.php';

class Plan extends Controller
{
    /**
     * Crea un nuevo plan
     * Requiere JWT y método POST
     * Recibe datos por JSON
     */
    public function create()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        $jwt = str_replace('Bearer ', '', $authHeader);
        if (!JwtHelper::validateJwt($jwt)) {
            $this->jsonResponse(['error' => 'Token JWT inválido o expirado'], 401);
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Método no permitido'], 405);
            return;
        }
        $data = $this->getJsonInput();
        $nombre = $data['nombre'] ?? null;
        $descripcion = $data['descripcion'] ?? null;
        $max_usuarios = $data['max_usuarios'] ?? 0;
        $max_documentos = $data['max_documentos'] ?? 0;
        $max_almacenamiento = $data['max_almacenamiento'] ?? 0;
        $precio_mensual = $data['precio_mensual'] ?? 0.00;
        $precio_anual = $data['precio_anual'] ?? 0.00;
        $duracion_meses = $data['duracion_meses'] ?? 1;
        $estado = $data['estado'] ?? 'A';
        if (empty($nombre)) {
            $this->jsonResponse(['error' => 'Faltan datos obligatorios'], 400);
            return;
        }
        $result = $this->model->createPlan([
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'max_usuarios' => $max_usuarios,
            'max_documentos' => $max_documentos,
            'max_almacenamiento' => $max_almacenamiento,
            'precio_mensual' => $precio_mensual,
            'precio_anual' => $precio_anual,
            'duracion_meses' => $duracion_meses,
            'estado' => $estado
        ]);
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => 'Plan creado exitosamente'], 201);
        } else {
            $this->jsonResponse(['error' => 'Error al crear plan'], 500);
        }
    }
}
