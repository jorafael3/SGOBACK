<?php
require_once __DIR__ . '../../../../libs/JwtHelper.php';
// require_once __DIR__ . '/../models/empresamodel.php';

class obligacionesbancarias extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'financiero/bancos/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('obligacionesbancarias'); // Cargar el modelo correcto
    }

    function buscar_proveedor()
    {
        $params = $this->getJsonInput();
        $result = $this->model->Buscar_proveedores($params);
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
    
    function Buscar_Bancos()
    {
        $result = $this->model->Buscar_Bancos();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }
    function Cargar_Tipos_Obligaciones()
    {
        $result = $this->model->Cargar_Tipos_Obligaciones();
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

    function Calcular_Amortizacion()
    {


        try {
            $params = $this->getJsonInput();
            $capital = floatval($params['capital']);
            $tasa = floatval($params['tasa']);
            $plazo = intval($params['plazo']);
            $tipo_pago = isset($params['tipo_pago']) ? intval($params['tipo_pago']) : 12; // Default: mensual
            $tipo_amortizacion = isset($params['tipo_amortizacion']) ? intval($params['tipo_amortizacion']) : 1; // Default: francés
            $fecha_primer_pago = isset($params['fecha_primer_pago']) ? $params['fecha_primer_pago'] : date('Y-m-d');
            $otros = isset($params['otros']) ? floatval($params['otros']) : ($capital * 0.005); // Default: 0.5% del capital
            $cuotafija = isset($params['cuotafija']) ? floatval($params['cuotafija']) : 0;
            $taza_mensual = isset($params['taza_mensual']) ? floatval($params['taza_mensual']) : 0;

            // Si es tipo francés
            $tabla = [];
            if ($tipo_amortizacion == 1) {
                $tabla =  $this->generar_tabla_francesa($capital, $tasa, $plazo, $tipo_pago, $fecha_primer_pago, $otros, $cuotafija, $taza_mensual);
            } else {
                // Si es tipo alemán
                $tabla =  $this->generar_tabla_alemana($capital, $tasa, $plazo, $tipo_pago, $fecha_primer_pago, $otros);
            }

            echo json_encode([
                'success' => true,
                'data' => $tabla,
                "parametros" => $params
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    private function generar_tabla_francesa($capital, $tasa, $plazo, $tipo_pago, $fecha_primer_pago, $otros = 0, $cuota_fija = 0, $tasa_mensual = 0)
    {
        // Calculamos la tasa periódica con mayor precisión
        $tasaPeriodo = ($tasa / 100) / $tipo_pago; // Tasa por periodo

        // Si se proporciona una cuota fija, la usamos; si no, la calculamos
        // Usamos BC Math para mayor precisión en el cálculo de la cuota
        if ($cuota_fija > 0) {
            $cuota = $cuota_fija;
        } else {
            // Fórmula de la cuota para el sistema francés: C = P * i / (1 - (1 + i)^-n)
            $numerador = $capital * $tasaPeriodo;
            $denominador = 1 - pow(1 + $tasaPeriodo, -$plazo);
            $cuota = $numerador / $denominador;
        }

        // Redondeamos la cuota a 2 decimales y la mantenemos constante
        $cuota = round($cuota, 2);

        // Mantenemos el capital original para posibles referencias
        $capitalOriginal = $capital;
        $saldo = $capital;
        $tabla = [];

        // Calculamos el total a pagar para verificar ajustes
        $totalAPagar = $cuota * $plazo;

        // Calcular fechas de pago basadas en el tipo de pago
        $fechas = $this->calcular_fechas_pago($fecha_primer_pago, $plazo, $tipo_pago);

        // Realizamos el cálculo con mayor precisión
        for ($i = 1; $i <= $plazo; $i++) {
            // Calculamos interés sobre el saldo pendiente (con 6 decimales para mayor precisión)
            $interesPreciso = $saldo * $tasaPeriodo;
            $interes = round($interesPreciso, 2);

            // Calculamos el abono a capital como la diferencia entre cuota e interés
            $abonoCapital = $cuota - $interes;

            // Ajuste final en la última cuota
            if ($i === $plazo) {
                $abonoCapital = $saldo; // El último pago debe ser exactamente el saldo restante
                $cuota = $interes + $abonoCapital; // Ajustamos la cuota final
                $saldo = 0; // Saldo final debe ser exactamente cero
            } else {
                // Usamos mayor precisión para el saldo y luego redondeamos para mostrar
                $saldo -= $abonoCapital;
                $saldo = round($saldo, 2); // Redondeamos el saldo a 2 decimales
            }

            // Redondeamos los valores finales para mostrar
            $abonoCapital = round($abonoCapital, 2);
            $cuotaMostrar = round($cuota, 2);

            $tabla[] = [
                "cuota" => $i,
                "fechaPago" => $fechas[$i - 1],
                "pagoTotal" => number_format($cuotaMostrar, 2, ".", ""),
                "interes" => number_format($interes, 2, ".", ""),
                "abonoCapital" => number_format($abonoCapital, 2, ".", ""),
                "saldo" => number_format($saldo, 2, ".", ""),
                "otros" => number_format(0, 2, ".", "")
            ];
        }
        return $tabla;
    }

    /**
     * Genera una tabla de amortización con el sistema alemán
     */
    private function generar_tabla_alemana($capital, $tasa, $plazo, $tipo_pago, $fecha_primer_pago, $otros = 0)
    {
        // Calculamos la tasa periódica con mayor precisión
        $tasaPeriodo = ($tasa / 100) / $tipo_pago; // Tasa por periodo

        // En el sistema alemán el abono a capital es fijo (excepto la última cuota)
        $abonoCapital = $capital / $plazo; // No redondeamos aún para mantener precisión
        $abonoCapitalNormal = round($abonoCapital, 2); // Para mostrar

        // Mantenemos el capital original para posibles referencias
        $capitalOriginal = $capital;
        $saldo = $capital;
        $tabla = [];

        // Calcular fechas de pago basadas en el tipo de pago
        $fechas = $this->calcular_fechas_pago($fecha_primer_pago, $plazo, $tipo_pago);

        // Acumulador para verificar que amortizamos exactamente el capital
        $capitalAmortizado = 0;

        for ($i = 1; $i <= $plazo; $i++) {
            // Calculamos interés sobre el saldo pendiente (con mayor precisión)
            $interes = round($saldo * $tasaPeriodo, 2);

            // Determinamos el abono a capital de esta cuota
            if ($i === $plazo) {
                // El último pago debe cubrir exactamente el saldo restante
                $abonoCapital = $saldo;
            } else {
                $abonoCapital = $abonoCapitalNormal;
                $capitalAmortizado += $abonoCapital;
            }

            // El pago total es la suma del abono a capital más los intereses
            $pagoTotal = $abonoCapital + $interes;

            // Actualizamos el saldo
            if ($i === $plazo) {
                $saldo = 0; // Forzar saldo final a 0.00
            } else {
                $saldo = $saldo - $abonoCapital;
                $saldo = round($saldo, 2); // Redondeamos el saldo a 2 decimales
            }

            $tabla[] = [
                "cuota" => $i,
                "fechaPago" => $fechas[$i - 1],
                "pagoTotal" => number_format($pagoTotal, 2, ".", ""),
                "interes" => number_format($interes, 2, ".", ""),
                "abonoCapital" => number_format($abonoCapital, 2, ".", ""),
                "saldo" => number_format($saldo, 2, ".", ""),
                "otros" => number_format(0, 2, ".", "") // Solo el cuota 0 tiene valor "otros"
            ];
        }

        echo json_encode([
            'success' => true,
            'data' => $tabla
        ]);
        exit;
    }

    private function calcular_fechas_pago($fecha_inicial, $plazo, $tipo_pago)
    {
        $fechas = [];
        $fecha = new DateTime($fecha_inicial);

        for ($i = 0; $i < $plazo; $i++) {
            // Siempre avanzamos desde la primera cuota (incluso para i=0)
            // La fecha inicial es la fecha del préstamo (cuota 0)
            // La fecha de la primera cuota es un periodo después
            switch ($tipo_pago) {
                case 30: // Días
                    $fecha->modify('+30 days');
                    break;
                case 12: // Mensual
                    $fecha->modify('+1 month');
                    break;
                case 4: // Trimestral
                    $fecha->modify('+3 months');
                    break;
                case 2: // Semestral
                    $fecha->modify('+6 months');
                    break;
                case 1: // Anual
                    $fecha->modify('+1 year');
                    break;
            }

            $fechas[] = $fecha->format('Y-m-d');
        }

        return $fechas;
    }

    /**
     * Guarda un modelo de amortización en la base de datos
     */
    function guardar_modelo_amortizacion()
    {

        $jwtData = $this->authenticateAndConfigureModel(2); // 1 = GET/POST opcional
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }
        $params = $this->getJsonInput();

        try {
            $this->model->db->beginTransaction();

            $cab = $this->model->Guardar_Modelo_Amortizacion($params);

            if ($cab['success'] === false) {
                $this->model->db->rollBack();
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error al guardar el modelo de amortización',
                    'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                    "respuesta" => $cab
                ], 200);
                return;
            }

            $DETALLE = isset($params['datos']) ? $params['datos'] : [];
            $DETERRO = [];
            $DETERROACR = [];

            for ($i = 0; $i < count($DETALLE); $i++) {
                $ACRID = "";
                if ($i > 0) {
                    $DETALLE_ACR = $this->model->Guardar_Acreedores_Amortizacion($DETALLE[$i], $cab['insertId'], $params);
                    if ($DETALLE_ACR['success'] === false) {
                        $DETERROACR[] = $DETALLE_ACR;
                    }
                    $ACRID = $DETALLE_ACR["data"][0]['ACRID'];
                }

                $DETALLE_DT = $this->model->Guardar_Detalle_Amortizacion($DETALLE[$i], $cab['insertId'], $ACRID);
                if ($DETALLE_DT['success'] === false) {
                    $DETERRO[] = $DETALLE_DT;
                }
            }

            if (count($DETERRO) === 0) {
                // Todo ok, hacer commit
                $this->model->db->commit();
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Modelo de amortización guardado correctamente',
                    'data' => $cab,
                    'acr' => $DETERROACR
                ], 200);
            } else {
                // Hubo errores en los detalles, hacer rollback
                $this->model->db->rollBack();
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error al guardar los detalles de amortización',
                    'errors' => $DETERRO,
                    'errors_acr' => $DETERROACR,
                    "respuesta" => $cab
                ], 500);
            }
        } catch (Exception $e) {
            $this->model->db->rollBack();
            $this->jsonResponse([
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
    /**
     * Lista los modelos de amortización guardados
     */
    function cargar_amortizaciones()
    {
        $result = $this->model->Cargar_Amortizaciones();
        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener amortizaciones',
                "respuesta" => $result
            ], 200);
        }
    }

    function guardar_reajuste()
    {

        try {

            $param = $this->getJsonInput();


            $CAB = $this->model->Guardar_reajuste_cabecera($param);

            if (!$CAB['success']) {
                $this->jsonResponse($CAB, 200);
                return;
            }

            $DET = $this->model->Guardar_reajuste_detalle($param);

            if ($DET && $DET['success'] && count($DET['errors']) === 0) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Reajuste guardado correctamente',
                    'data' => [
                        'cabecera' => $CAB,
                        'detalle' => $DET
                    ]
                ], 200);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Error al obtener transporte guías pickup',
                    'empresa_actual' => $jwtData['empresa'] ?? 'N/A',
                ], 500);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}
