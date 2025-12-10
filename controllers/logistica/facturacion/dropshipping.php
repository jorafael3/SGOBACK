<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class Dropshipping extends Controller
{
    /**
     * Constructor de la clase Empresa
     * Inicializa el modelo de empresa
     */
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'logistica/facturacion/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('prepararfacturas'); // Cargar el modelo de empresa
    }

    function GetFacturasDropshipping()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $data = $this->getJsonInput();
        $usuario = $data['usrid'] ?? null;

        // echo json_encode($data);
        // exit;   

        if (!$usuario) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Usuario no proporcionado'
            ], 400);
            return;
        }

        $result = $this->model->getFacturasDropshipping($data);

        // Agrupar por FACTURA_SECUENCIA y combinar bodegas
        if ($result && $result['success'] && count($result['data']) > 0) {
            $facturasAgrupadas = [];
            
            foreach ($result['data'] as $factura) {
                $secuencia = $factura['FACTURA_SECUENCIA'];
                
                if (!isset($facturasAgrupadas[$secuencia])) {
                    // Primera vez que vemos esta factura
                    $facturasAgrupadas[$secuencia] = $factura;
                    $facturasAgrupadas[$secuencia]['ID_BODEGA'] = [$factura['ID_BODEGA']];
                    $facturasAgrupadas[$secuencia]['CODIGOS_BODEGA'] = [$factura['CODIGOS_BODEGA']];
                } else {
                    // Ya existe esta factura, agregar bodega si no está
                    if (!in_array($factura['ID_BODEGA'], $facturasAgrupadas[$secuencia]['ID_BODEGA'])) {
                        $facturasAgrupadas[$secuencia]['ID_BODEGA'][] = $factura['ID_BODEGA'];
                    }
                    if (!in_array($factura['CODIGOS_BODEGA'], $facturasAgrupadas[$secuencia]['CODIGOS_BODEGA'])) {
                        $facturasAgrupadas[$secuencia]['CODIGOS_BODEGA'][] = $factura['CODIGOS_BODEGA'];
                    }
                }
            }
            
            // Convertir arrays de bodegas a strings separados por comas
            $facturasFinales = [];
            foreach ($facturasAgrupadas as $factura) {
                $factura['ID_BODEGA'] = implode(',', $factura['ID_BODEGA']);
                $factura['CODIGOS_BODEGA'] = implode(',', $factura['CODIGOS_BODEGA']);
                $facturasFinales[] = $factura;
            }
            
            $result['data'] = $facturasFinales;
        }

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos de empresas',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function GetFacturasDatosDropshipping()
    {
        // echo json_encode("asdasdasd");
        // exit();

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();
        // $usuario = $data['usrid'] ?? null;
        $secuencia = $data['secuencia'] ?? null;
        // $bodega = explode(',', $data['bodega'] ?? '') ?? null;
        $bodega = $data['bodega'] ?? null;


        if (!$secuencia) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Secuencia no proporcionada'
            ], 400);
            return;
        }
        if (!$bodega) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Bodega no proporcionada'
            ], 400);
            return;
        }
        // if (!$usuario) {
        //     $this->jsonResponse([
        //         'success' => false,
        //         'error' => 'Usuario no proporcionado'
        //     ], 400);
        //     return;
        // }

        $cab = $this->model->getFacturasCab($secuencia);
        if (!$cab || !$cab['success']) {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos de la cabecera de la factura'
            ], 500);
            return;
        }
        //   echo json_encode($cab);
        //         exit();

        $det = [];
        // for ($i = 0; $i < count($bodega); $i++) {
        //     $ListaDet = $this->model->getFacturasDetalle($cab["data"][0]['Id'], trim($bodega[$i]));
        //     if ($ListaDet && $ListaDet['success'] && count($ListaDet['data']) > 0) {
        //         array_push($det, $ListaDet['data']);
        //     }
        // }
        // $det["data"] = array_merge(...$det);
        $det = $this->model->getFacturasDetalle($cab["data"][0]['Id'], trim($bodega));
        // echo json_encode($data);
        // exit();


        $tieneServicios = 0;
        $sinservicios = 0;
        $BODEGAS = [];
        for ($i = 0; $i < count($det["data"]); $i++) {
            if ($det["data"][$i]['Clase'] === '02') {
                $tieneServicios++;
            } else {
                $sinservicios++;
            }
            if (!in_array($det["data"][$i]['BodegaID'], $BODEGAS)) {
                $BODEGAS[] = $det["data"][$i]['BodegaID'];
            }
            $ubicacion = $this->model->getFacturasDetalleUbicacion($det["data"][$i]['ProductoId'], $det["data"][$i]['BodegaID']);
            $det["data"][$i]['ubi1'] = $ubicacion['data'][0]["ubi1"] ?? '';
            $det["data"][$i]['ubi2'] = $ubicacion['data'][0]["ubi2"] ?? '';
            // $det[$i]['clase'] = 'valor'; // Modificar según sea necesario
        }

        if (count($det["data"]) > 0) {
            // echo json_encode($det);
            // exit();
            $cab['data'][0]['SoloServicios'] = $tieneServicios > 0 && $sinservicios == 0 ? 1 : 0;

            // $cab['data'][0]['sinServicios'] = $sinservicios;
            $result = [
                'success' => true,
                'cabecera' => [$cab['data'][0]] ?? null,
                'detalle' => $det["data"] ?? [],
                'BODEGAS' => $BODEGAS
            ];
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos de empresas',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A'
            ], 200);
        }
    }

    function GetInfoDespacho()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        $forma_Despacho = [
            array(
                "id" => "1",
                "nombre" => "Entrega en Tienda"
            ),
            array(
                "id" => "2",
                "nombre" => "Entrega en otra Tienda"
            ),
            array(
                "id" => "3",
                "nombre" => "Envio"
            )
        ];

        $result = $this->model->getTiendasDespacho();

        if ($result && $result['success']) {
            $this->jsonResponse([
                "success" => true,
                'forma_Despacho' => $forma_Despacho,
                'tiendas' => $result['data'] ?? []
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos de empresas',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }

    function GuardarDropshipping()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $data = $this->getJsonInput();

        // echo json_encode($data);
        // exit();

        $result = $this->model->guardarDropshipping($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar dropshipping',
                'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                "respuesta" => $result
            ], 200);
        }
    }
}
