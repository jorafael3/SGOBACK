<?php


// =====================================================
// ARCHIVO: controllers/principal.php
// =====================================================
/**
 * Controlador Principal
 */
class Principal extends Controller 
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function render()
    {
        $this->view->render('principal/index');
    }
    
    public function cargarSeries()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
                return;
            }
            
            $input = $this->getJsonInput();
            
            if ($input === null) {
                $this->jsonResponse(['error' => 'JSON inválido'], 400);
                return;
            }
            
            $result = $this->model->cargarSeries($input);
            
            if ($result['success']) {
                $this->jsonResponse($result, 200);
            } else {
                $this->jsonResponse($result, 400);
            }
            
        } catch (Exception $e) {
            error_log("Error en cargarSeries: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del servidor'], 500);
        }
    }
    
    public function buscarSeries()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
                return;
            }
            
            $input = $this->getJsonInput();
            
            if ($input === null) {
                $this->jsonResponse(['error' => 'JSON inválido'], 400);
                return;
            }
            
            $result = $this->model->buscarSeries($input);
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            error_log("Error en buscarSeries: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del servidor'], 500);
        }
    }
    
    public function listarSeries()
    {
        try {
            $page = (int)$this->getGet('page', 1);
            $perPage = (int)$this->getGet('per_page', 20);
            
            $filtros = [];
            if ($this->getGet('pedido')) {
                $filtros['pedido'] = $this->getGet('pedido');
            }
            if ($this->getGet('estado')) {
                $filtros['estado'] = $this->getGet('estado');
            }
            
            $result = $this->model->obtenerSeriesPaginadas($page, $perPage, $filtros);
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            error_log("Error en listarSeries: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del servidor'], 500);
        }
    }
}
