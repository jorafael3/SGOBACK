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
        // $this->view->render('principal/index');
        die('Controlador Principal funcionando');
    }
  
}
