<?php


class Principal extends Controller
{

    function __construct()
    {

        parent::__construct();
        //$this->view->render('principal/index');
        //echo "nuevo controlaodr";
    }

    // function render()
    // {
    //     $this->view->render('principal/principal');
    // }

    function Cargar_Series()
    {
        $array = json_decode(file_get_contents("php://input"), true);
        $Ventas =  $this->model->Cargar_Series($array);
        //$this->CrecimientoCategoriasIndex();
    }
}
