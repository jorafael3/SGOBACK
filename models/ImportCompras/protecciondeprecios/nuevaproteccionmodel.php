<?php

class NuevaProteccionModel extends Model
{
    public function __construct($empresaCode = null)
    {
        parent::__construct($empresaCode);

        if (DEBUG) {
            error_log("NuevaProteccionModel conectado a: " . $this->empresaCode);
        }
    }

    /**
     * Cargar tipos (establecimientos/parámetros)
     * Usa SIS_PARAMETROS igual que el legacy
     */
    public function Cargar_Tipos()
    {
        try {
            $sql = "SELECT ID, Código, Nombre
                    FROM SIS_PARAMETROS
                    WHERE PadreID = '0000018531'
                    AND Anulado = 0";

            $result = $this->db->query($sql);
            return $result['data'] ?? [];
        } catch (Exception $e) {
            error_log("Error en Cargar_Tipos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar producto por código o nombre
     * SP: WEB_INV_Producto_SeeKID (NOMBRE, TIPO)
     */
    public function Consultar_Productos($data)
    {
        try {
            $nombre = $data['NOMBRE'];
            $tipo = $data['TIPO'] ?? 0;

            $sql = "EXEC WEB_INV_Producto_SeeKID :nombre, :tipo";

            $params = [
                'nombre' => $nombre,
                'tipo' => $tipo
            ];

            $result = $this->db->query($sql, $params);
            return $result;
        } catch (Exception $e) {
            error_log("Error en Consultar_Productos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar cliente por cédula o RUC para exclusión/cliente específico
     * SP: WEB_INV_Producto_ProteccionCliente_SeeKID (CEDULA)
     */
    public function Consultar_Exclusion($data)
    {
        try {
            $cedula = $data['CEDULA'];

            $sql = "EXEC WEB_INV_Producto_ProteccionCliente_SeeKID :cedula";

            $params = [
                'cedula' => $cedula
            ];

            $result = $this->db->query($sql, $params);
            return $result['data'] ?? [];
        } catch (Exception $e) {
            error_log("Error en Consultar_Exclusion: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Guardar protección completa usando stored procedures del legacy
     * SP Cabecera: WEB_INV_Productos_Proteccion_insert (8 params)
     * SP Detalle:  WEB_INV_Productos_Proteccion_DT_insert (18 params)
     * SP Excluidos: WEB_INV_Producto_ProteccionCliente_Insert (2 params)
     */
    public function Guardar_Proteccion($cabecera, $detalle, $excluidos)
    {
        try {
            $connection = $this->db->connect();

            // 1. INSERT CABECERA via SP
            $stmtCabecera = $connection->prepare(
                '{CALL WEB_INV_Productos_Proteccion_insert (?,?,?,?,?,?,?,?)}'
            );

            $DESCRIPCION    = $cabecera['DESCRIPCION'];
            $CREADO_POR     = $cabecera['CREADO_POR'];
            $CREADO_DATE    = $cabecera['CREADO_DATE'];
            $REFERENCIA     = $cabecera['REFERENCIA'];
            $ESTABLECIMIENTO = $cabecera['ESTABLECIMIENTO'];
            $ID_CLIENTE     = $cabecera['ID_CLIENTE'] ?? '';
            $SUCURSAL       = $cabecera['SUCURSAL'] ?? '';
            $TIPO_EXCLUIDO  = $cabecera['TIPO_EXCLUIDO'] ?? '';

            $stmtCabecera->bindParam(1, $DESCRIPCION, \PDO::PARAM_STR);
            $stmtCabecera->bindParam(2, $CREADO_POR, \PDO::PARAM_STR);
            $stmtCabecera->bindParam(3, $CREADO_DATE, \PDO::PARAM_STR);
            $stmtCabecera->bindParam(4, $REFERENCIA, \PDO::PARAM_STR);
            $stmtCabecera->bindParam(5, $ESTABLECIMIENTO, \PDO::PARAM_STR);
            $stmtCabecera->bindParam(6, $ID_CLIENTE, \PDO::PARAM_STR);
            $stmtCabecera->bindParam(7, $SUCURSAL, \PDO::PARAM_STR);
            $stmtCabecera->bindParam(8, $TIPO_EXCLUIDO, \PDO::PARAM_STR);

            if (!$stmtCabecera->execute()) {
                return [
                    'success' => false,
                    'message' => 'Error al insertar cabecera: ' . implode(', ', $stmtCabecera->errorInfo())
                ];
            }

            $result = $stmtCabecera->fetchAll(\PDO::FETCH_ASSOC);
            $proteccionId = $result[0]['PromiciónID'] ?? null;

            if (!$proteccionId) {
                return [
                    'success' => false,
                    'message' => 'No se pudo obtener el ID de la protección'
                ];
            }

            // 2. INSERT DETALLE via SP (loop)
            $detalleCount = 0;

            foreach ($detalle as $item) {
                $stmtDetalle = $connection->prepare(
                    '{CALL WEB_INV_Productos_Proteccion_DT_insert (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)}'
                );

                $PromociónID   = $proteccionId;
                $PRODUCTO_ID   = $item['PRODUCTO_ID'];
                $PROTECCION    = $item['PROTECCION'];
                $PRECIO        = $item['PRECIO'];
                $COSTOV        = $item['COSTOV'];
                $UNIDADES      = $item['UNIDADES'];
                $StartDate     = $item['StartDate'];
                $EndDate       = $item['EndDate'];
                $DT_CREADO_POR = $CREADO_POR;
                $DT_CREADO_DATE = $CREADO_DATE;
                $ANULADO       = $item['ANULADO'] ?? 0;
                $DT_EST        = $ESTABLECIMIENTO;
                $BODEGA        = $SUCURSAL;
                $op1           = $item['op1'] ?? '';
                $op2           = $item['op2'] ?? '';
                $op3           = $item['op3'] ?? '';
                $op4           = $item['op4'] ?? '';
                $Es_combo      = $item['Es_combo'] ?? '';

                $stmtDetalle->bindParam(1, $PromociónID, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(2, $PRODUCTO_ID, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(3, $PROTECCION, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(4, $PRECIO, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(5, $COSTOV, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(6, $UNIDADES);
                $stmtDetalle->bindParam(7, $StartDate, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(8, $EndDate, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(9, $DT_CREADO_POR, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(10, $DT_CREADO_DATE, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(11, $ANULADO, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(12, $DT_EST, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(13, $BODEGA, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(14, $op1, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(15, $op2, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(16, $op3, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(17, $op4, \PDO::PARAM_STR);
                $stmtDetalle->bindParam(18, $Es_combo, \PDO::PARAM_STR);

                if ($stmtDetalle->execute()) {
                    $detalleCount++;
                } else {
                    error_log("Error detalle SP: " . implode(', ', $stmtDetalle->errorInfo()));
                }
            }

            // 3. INSERT EXCLUIDOS via SP (si hay)
            $excCount = 0;
            if ($ESTABLECIMIENTO == 0 && !empty($excluidos)) {
                foreach ($excluidos as $cliente) {
                    $stmtExc = $connection->prepare(
                        '{CALL WEB_INV_Producto_ProteccionCliente_Insert (?,?)}'
                    );

                    $EXC_PROT_ID = $proteccionId;
                    $EXC_CLIENTE = $cliente['ClienteID'];

                    $stmtExc->bindParam(1, $EXC_PROT_ID, \PDO::PARAM_STR);
                    $stmtExc->bindParam(2, $EXC_CLIENTE, \PDO::PARAM_STR);

                    if ($stmtExc->execute()) {
                        $excCount++;
                    } else {
                        error_log("Error excluido SP: " . implode(', ', $stmtExc->errorInfo()));
                    }
                }
            }

            return [
                'success' => true,
                'data' => [
                    'CABECERA' => 1,
                    'DETALLE' => $detalleCount,
                    'EXCLUIDOS' => $excCount
                ],
                'proteccionId' => $proteccionId
            ];

        } catch (Exception $e) {
            error_log("Error en Guardar_Proteccion: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al guardar la protección: ' . $e->getMessage()
            ];
        }
    }
}
