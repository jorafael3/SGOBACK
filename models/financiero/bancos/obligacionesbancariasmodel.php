<?php

// require_once __DIR__ . '/../logsmodel.php';


class ObligacionesBancariasModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);

        // Debug: mostrar qué empresa estás usando
        if (DEBUG) {
            error_log("GuiasPickupModel conectado a: " . $this->empresaCode);
        }
    }


    function Buscar_proveedores($data = [])
    {
        try {
            if ($data['nombre'] == "") {
                $sql = "SELECT id,Nombre,Código as codigo  from ACR_ACREEDORES
                    where Anulado = '0'
                    and GrupoID = '0000000001'
                    ";
                $params = [];
            } else {
                $sql = "SELECT id,Nombre,Código as codigo  from ACR_ACREEDORES
                    where Anulado = '0'
                    and GrupoID = '0000000001'
                    and nombre like :nombre
                    ";
                $params = [
                    ":nombre" => '%' . $data['nombre'] . '%',
                ];
            }
            $stmt = $this->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            $this->logError("Error obteniendo facturas guías pickup: " . $e->getMessage());
            return false;
        }
    }

    function Cargar_Tipos_Obligaciones()
    {
        try {
            $sql = "SELECT * FROM SGO_AMORTIZACION_TIPOS WHERE estado=1";
            $query = $this->db->query($sql, []);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Guardar_Modelo_Amortizacion($params)
    {

        $nombre = $params['nombre'];
        $capital = floatval($params['capital']);
        $tasa = floatval($params['tasa']);
        $plazo = intval($params['plazo']);
        $tipo_pago = isset($params['tipo_pago']) ? intval($params['tipo_pago']) : 12;
        $tipo_amortizacion = isset($params['tipo_amortizacion']) ? intval($params['tipo_amortizacion']) : 1;
        $fecha_primer_pago = isset($params['fecha_primer_pago']) ? $params['fecha_primer_pago'] : date('Y-m-d');
        $cuota_fija = isset($params['cuotafija']) ? floatval($params['cuotafija']) : 0;
        $tasa_mensual = isset($params['tasaMensual']) ? floatval($params['tasaMensual']) : 0;
        $otros = isset($params['otros']) ? floatval($params['otros']) : 0;
        $referencia = isset($params['referencia']) ? $params['referencia'] : '';
        $tipo_obligacion = isset($params['tipo_obligacion']) ? intval($params['tipo_obligacion']) : 0;
        $TIPO_GENERAL = "COMPUESTA";
        $PROVEEDOR = isset($params['proveedor']) ? $params['proveedor']["id"] : '';

        $usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'sistema';
        $DETALLE = isset($params['datos']) ? $params['datos'] : [];

        $sql = "INSERT INTO SGO_AMORTIZACION_CAB 
            (
                detalle,
                capital,
                tasa,
                plazo,
                tipo_pago,
                tipo_amortizacion,
                creado_por,
                cuota_fija,
                tasa_mensual,
                fecha_inicio,
                otros_valores,
                referencia,
                tipo_obligacion,
                TIPO_GENERAL,
                proveedorid
            )VALUES
            (
                :detalle,
                :capital,
                :tasa,
                :plazo,
                :tipo_pago,
                :tipo_amortizacion,
                :creado_por,
                :cuota_fija,
                :tasa_mensual,
                :fecha_inicio,
                :otros_valores,
                :referencia,
                :tipo_obligacion,
                :TIPO_GENERAL,
                :proveedorid
            )";
        $params = array(
            ':detalle' => $nombre,
            ':capital' => $capital,
            ':tasa' => $tasa,
            ':plazo' => $plazo,
            ':tipo_pago' => $tipo_pago,
            ':tipo_amortizacion' => $tipo_amortizacion,
            ':creado_por' => $usuario,
            ':cuota_fija' => $cuota_fija,
            ':tasa_mensual' => $tasa_mensual,
            ':fecha_inicio' => $fecha_primer_pago,
            ':otros_valores' => $otros,
            ':referencia' => $referencia,
            ':tipo_obligacion' => $tipo_obligacion,
            ':TIPO_GENERAL' => $TIPO_GENERAL,
            ':proveedorid' => $PROVEEDOR
        );
        $result = $this->db->execute($sql, $params);
        $detallet = $this->Guardar_Detalle_Amortizacion($DETALLE, $this->db->lastInsertId());
        $result['insertId'] = $this->db->lastInsertId();
        $result['detallet'] = $detallet;
        return $result;
    }

    function Guardar_Detalle_Amortizacion($DETALLE, $AMORTIZACION)
    {

        try {

            $ERR = [];

            for ($i = 0; $i < count($DETALLE); $i++) {
                $n_cuota = $DETALLE[$i]['cuota'];
                $fecha_pago = $DETALLE[$i]['fechaPago'];
                $abono_capital = $DETALLE[$i]['abonoCapital'];
                $interes = $DETALLE[$i]['interes'];
                $cuota = $DETALLE[$i]['pagoTotal'];
                $saldo = $DETALLE[$i]['saldo'];
                $otros = $DETALLE[$i]['otros'];

                $sql = "INSERT INTO SGO_AMORTIZACION_DT
                    (
                        cabecera_id,
                        n_cuota,
                        fecha_pago,
                        abono_capital,
                        interes,
                        cuota,
                        saldo,
                        otros
                    )VALUES
                    (
                        :cabecera_id,
                        :n_cuota,
                        :fecha_pago,
                        :abono_capital,
                        :interes,
                        :cuota,
                        :saldo,
                        :otros
                    )";

                $params = [
                    ':cabecera_id' => $AMORTIZACION,
                    ':n_cuota' => $n_cuota,
                    ':fecha_pago' => $fecha_pago,
                    ':abono_capital' => $abono_capital,
                    ':interes' => $interes,
                    ':cuota' => $cuota,
                    ':saldo' => $saldo,
                    ':otros' => $otros
                ];

                if ($this->db->execute($sql, $params)) {
                } else {
                    $err = $this->db->errorInfo();
                    $ERR[] = 'Error al guardar el detalle: ' . $err[2];
                }
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar el detalle: ' . $e->getMessage()
            ]);
            exit();
        }
    }

    function Cargar_Amortizaciones()
    {

        $sql = "SELECT 
            id,
            detalle,
            capital,
            tasa,
            plazo,
            tipo_pago,
            tipo_amortizacion,
            cuota_fija,
            tasa_mensual,
            fecha_inicio,
            otros_valores,
            creado_date
            from SGO_AMORTIZACION_CAB ORDER BY id DESC";
        $query = $this->db->query($sql, []);
        $result = $query['data'];

        for ($i = 0; $i < count($result); $i++) {
            $capital = floatval($result[$i]['capital']);
            $tasa = floatval($result[$i]['tasa']);
            $plazo = intval($result[$i]['plazo']);
            $tipo_pago = isset($result[$i]['tipo_pago']) ? intval($result[$i]['tipo_pago']) : 12; // Default: mensual
            $tipo_amortizacion = isset($result[$i]['tipo_amortizacion']) ? intval($result[$i]['tipo_amortizacion']) : 1; // Default: francés
            $fecha_primer_pago = isset($result[$i]['fecha_inicio']) ? $result[$i]['fecha_inicio'] : date('Y-m-d');
            $otros = isset($params['otros_valores']) ? floatval($params['otros_valores']) : ($capital * 0.005); // Default: 0.5% del capital
            $cuotafija = isset($params['cuota_fija']) ? floatval($params['cuota_fija']) : 0;
            $taza_mensual = isset($params['tasa_mensual']) ? floatval($params['tasa_mensual']) : 0;
            $tabla =  $this->generar_tabla_francesa($capital, $tasa, $plazo, $tipo_pago, $fecha_primer_pago, $otros, $cuotafija, $taza_mensual);
            $result[$i]["amortizacion"] = $tabla;
            $result[$i]["amortizacion_detalle"] = $this->Cargar_Detalle_Amortizacion($result[$i]['id']);
        }

        $res = [
            'success' => true,
            'data' => $result
        ];

        return $res;
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

    function Cargar_Detalle_Amortizacion($ID)
    {
        try {
            $sql = "SELECT 
            id,
            cabecera_id,
            n_cuota,
            fecha_pago,
            abono_capital,
            interes,
            cuota,
            saldo,
            otros,
            isnull(fila_reajuste,0)as fila_reajuste
            from SGO_AMORTIZACION_DT 
            WHERE cabecera_id = :cabecera_id 
            and dt_activo=1
            ORDER BY n_cuota ASC";
            $query = $this->db->query($sql, [":cabecera_id" => $ID]);
            return $query['data'];
        } catch (Exception $e) {
            return [];
        }
    }

    function Guardar_reajuste_cabecera($param)
    {
        try {

            $CUOTAS_REAJUSTE = $param['ARRAYCUOTASREAJUSTE'];
            $DATOS_REAJUSTE = $param['ARRAY_DATOS_REAJUSTE'];
            $amortizacion_id = $DATOS_REAJUSTE['id'];
            $detalle = floatval($DATOS_REAJUSTE['detalle']);
            $capital = floatval($DATOS_REAJUSTE['capital']);
            $tasa_ajustada = floatval($DATOS_REAJUSTE['tasa']);
            $plazo = floatval($DATOS_REAJUSTE['plazo']);
            $n_cuota_reajuste = floatval($CUOTAS_REAJUSTE[0]);
            $tasa_mensual = floatval($DATOS_REAJUSTE['tasa_mensual']);
            $cuota_fija = floatval($DATOS_REAJUSTE['cuota_fija']);
            $creado_por = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'sistema';

            $sql = "INSERT INTO SGO_AMORTIZACION_CAB_REAJUSTE
                    (
                        cabecera_id,
                        detalle,
                        capital,
                        tasa,
                        plazo,
                        n_cuota_reajuste,
                        cuota_fija,
                        tasa_mensual,
                        creado_por
                    ) VALUES (
                        :cabecera_id,
                        :detalle,
                        :capital,
                        :tasa,
                        :plazo,
                        :n_cuota_reajuste,
                        :cuota_fija,
                        :tasa_mensual,
                        :creado_por
                    )";
            $params = [
                ":cabecera_id" => $amortizacion_id,
                ":detalle" => $detalle,
                ":capital" => $capital,
                ":tasa" => $tasa_ajustada,
                ":plazo" => $plazo,
                ":n_cuota_reajuste" => $n_cuota_reajuste,
                ":cuota_fija" => $cuota_fija,
                ":tasa_mensual" => $tasa_mensual,
                ":creado_por" => $creado_por
            ];
            $result = $this->db->execute($sql, $params);
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    function Guardar_reajuste_detalle($param)
    {

        $DATOS_REAJUSTE = $param['ARRAY_DATOS_REAJUSTE'];
        $amortizacion_id = $DATOS_REAJUSTE['id'];
        $GUARDARDOS = [];
        $ERR = [];

        for ($i = 0; $i < count($DATOS_REAJUSTE["amortizacion_detalle"]); $i++) {
            $n_cuota = $DATOS_REAJUSTE["amortizacion_detalle"][$i]['n_cuota'];
            $fecha_pago = $DATOS_REAJUSTE["amortizacion_detalle"][$i]['fecha_pago'];
            $abono_capital = $DATOS_REAJUSTE["amortizacion_detalle"][$i]['abono_capital'];
            $interes = $DATOS_REAJUSTE["amortizacion_detalle"][$i]['interes'];
            $cuota = $DATOS_REAJUSTE["amortizacion_detalle"][$i]['cuota'];
            $saldo = $DATOS_REAJUSTE["amortizacion_detalle"][$i]['saldo'];
            $otros = $DATOS_REAJUSTE["amortizacion_detalle"][$i]['otros'];
            $fila_reajuste = $DATOS_REAJUSTE["amortizacion_detalle"][$i]['fila_reajuste'];

            $sql = "INSERT INTO SGO_AMORTIZACION_DT
                            (
                                cabecera_id,
                                n_cuota,
                                fecha_pago,
                                abono_capital,
                                interes,
                                cuota,
                                saldo,
                                otros,
                                fila_reajuste
                            )VALUES
                            (
                                :cabecera_id,
                                :n_cuota,
                                :fecha_pago,
                                :abono_capital,
                                :interes,
                                :cuota,
                                :saldo,
                                :otros,
                                :fila_reajuste
                            )";
            $params = [
                ':cabecera_id' => $amortizacion_id,
                ':n_cuota' => $n_cuota,
                ':fecha_pago' => $fecha_pago,
                ':abono_capital' => $abono_capital,
                ':interes' => $interes,
                ':cuota' => $cuota,
                ':saldo' => $saldo,
                ':otros' => $otros,
                ':fila_reajuste' => $fila_reajuste
            ];
            $result = $this->db->execute($sql, $params);
            if ($result) {
            } else {
                $err = $this->db->errorInfo();
                $ERR[] = 'Error al guardar el detalle: ' . $err[2];
            }
        }

        return ['success' => true, 'errors' => $ERR];
    }
}
