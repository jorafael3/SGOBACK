<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
require_once __DIR__ . '../../../../libs/EmailService.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class InformePagos extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'proveeduria/facturas/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('informepagos'); // Cargar el modelo correcto
    }

    function GetFacturasPorPagar()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);    
        // exit();

        $result = $this->model->GetFacturasPorPagar($data);
        $result["ruta_archivos"] = constant("DOWNLOAD_PATH") . "Cartimex/proveeduria/ingresos_facturas/facturas/";

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                'ruta_archivos' => $result["ruta_archivos"],
                "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function AprobarFacturas()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $facturas = $data['factura'];
        $empresa = $data['empresa'];
        $comentario = $data['comentario'];
        $usuario = $data['userdata']['usrid'];

        if ($empresa == null || $empresa == '') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'La empresa es requerida para aprobar facturas.',
            ], 200);
            return;
        }

        if (count($facturas) == 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No hay facturas para aprobar.',
            ], 200);
            return;
        }

        $this->model->db->beginTransaction();



        $cantidadFacturas = count($facturas);
        $insertadas = 0;
        $errores = [];
        $idgrupacion = $cantidadFacturas > 1 ? uniqid() : '';
        $agrupar = $cantidadFacturas > 1 ? 1 : 0;

        for ($i = 0; $i < $cantidadFacturas; $i++) {
            $result = $this->model->AprobarFacturas($facturas[$i], $empresa, $comentario, $usuario, $idgrupacion, $agrupar);
            if ($result['success']) {
                $insertadas++;
            } else {
                $errores[] = $result;
            }
        }
        if ($insertadas == $cantidadFacturas) {

            $this->model->db->commit();
            $this->jsonResponse([
                'success' => true,
                'message' => 'Facturas aprobadas correctamente.',
            ], 200);
        } else {
            $this->model->db->rollBack();
            $this->jsonResponse([
                'success' => false,
                'message' => 'No se aprobaron todas las facturas.',
                'errors' => $errores,
            ], 200);
        }
    }

    function DescartarFacturas()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $facturas = $data['factura'];
        $empresa = $data['empresa'];
        $comentario = $data['comentario'];
        $usuario = $data['userdata']['usrid'];

        // echo json_encode($data);
        // exit();

        if ($empresa == null || $empresa == '') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'La empresa es requerida para aprobar facturas.',
            ], 200);
            return;
        }

        if (count($facturas) == 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No hay facturas para aprobar.',
            ], 200);
            return;
        }

        $this->model->db->beginTransaction();




        $insertadas = 0;
        $errores = [];
        for ($i = 0; $i < count($facturas); $i++) {
            $result = $this->model->DescartarFacturas($facturas[$i], $empresa, $comentario, $usuario);
            if ($result['success']) {
                $insertadas++;
            } else {
                $errores[] = $result;
            }
        }
        if ($insertadas == count($facturas)) {

            $this->model->db->commit();
            $this->jsonResponse([
                'success' => true,
                'message' => 'Facturas aprobadas correctamente.',
            ], 200);
        } else {
            $this->model->db->rollBack();
            $this->jsonResponse([
                'success' => false,
                'message' => 'No se aprobaron todas las facturas.',
                'errors' => $errores,
            ], 200);
        }
    }

    //** PAGOS POR REALIZAR */

    function GetFacturasPagosAprobados()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);    
        // exit();

        $result = $this->model->GetFacturasPagosAprobados($data);
        $result["ruta_archivos"] = constant("DOWNLOAD_PATH") . "Cartimex/proveeduria/ingresos_facturas/facturas/";

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                'ruta_archivos' => $result["ruta_archivos"],
                "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function MarcarPagosRealizados()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $facturas = $data['factura'];
        $empresa = $data['empresa'];
        $comentario = $data['comentario'];
        $usuario = $data['userdata']['usrid'];

        if ($empresa == null || $empresa == '') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'La empresa es requerida para aprobar facturas.',
            ], 200);
            return;
        }

        if (count($facturas) == 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'No hay facturas para aprobar.',
            ], 200);
            return;
        }

        // echo json_encode($data);
        // exit();


        $result = $this->model->MarcarPagosRealizados($data);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result,
                "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    //** CHEQUES */

    function GetChequesEmitidos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);    
        // exit();

        $result = $this->model->GetChequesEmitidos($data);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }


    //** CHEQUES POSTFECHADOS */

    function GetChequesPosFechados()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);    
        // exit();

        $result = $this->model->GetChequesPosFechados($data);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function GetChequesGiradosNoCobrados()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);    
        // exit();

        $result = $this->model->GetChequesGiradosNoCobrados($data);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }

    function GetChequesPosFechadosClientes()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);    
        // exit();

        $result = $this->model->GetChequesPosFechadosClientes($data);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'data' => $result["data"],
                "params" => $data,
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => $result,
            ], 200);
        }
    }
}
