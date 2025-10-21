<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class GuiasPickup extends Controller
{
    /**
     * Constructor de la clase Empresa
     * Inicializa el modelo de empresa
     */

    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('guiaspickup'); // Cargar el modelo correcto
    }

    function GetFacturasGuiasPickup()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        
        $data = $this->getJsonInput();
        $usuario = $data['usrid'] ?? null;
        if (!$usuario) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Usuario no proporcionado'
            ], 400);
            return;
        }
        
        $result = $this->model->getFacturasGuiasPickup($data);
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener facturas guías pickup',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function GetTransporteGuiasPickup()
    {
        $jwtData = $this->authenticateAndConfigureModel(1); // 1 = GET/POST opcional
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        
        $result = $this->model->getTransporteGuiasPickup();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener transporte guías pickup',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function GuardarGuiasPickup()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        
        $data = $this->getJsonInput();

        $BODEGAS = explode(',', $data['bodegas'][0] ?? null)   ?? null;
        $id_unico = date('YmdHis') . rand(1000, 9999);
        $data['id_unico'] = $id_unico;
        $data['usrid'] = $data['userdata']["usrid"] ?? null;

        $this->model->db->beginTransaction();


        $ERRORESFACTURAS = [];
        $ERRORESGUIA = [];
        foreach ($BODEGAS as $bodega) {
            $data['bodega'] = $bodega;
            $FACTURASLI = $this->model->ActualizarFacturasListas($data);
            if (!$FACTURASLI) {
                $ERRORESFACTURAS[] = $bodega;
            }
        }

        foreach ($data['guiasPorBodega'] as $guia) {
            $guia['factura'] = $data["factura"];
            $guia['usrid'] = $data["usrid"];
            $FACTURAS = $this->model->GuardarListaGuias($guia);
            if (!$FACTURAS) {
                $ERRORESGUIA[] = $guia;
            }
        }

        if (count($ERRORESFACTURAS) + count($ERRORESGUIA) > 0) {
            $this->model->db->rollback();
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al actualizar las facturas en las bodegas: ' . implode(', ', $ERRORESFACTURAS),
            ], 500);
            return;
        }

        $this->jsonResponse([
            'success' => true,
            'message' => 'Datos guardados correctamente',
            'data_recibida' => $data
        ], 200);
    }
}
