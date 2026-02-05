<?php

// require_once __DIR__ . '/../logsmodel.php';


class PresupuestoModel extends Model
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

    // ALTER TABLE PBI_PRESUPUESTO_CARTIMEX_CREADOS_SGO
// ADD presupuesto_Estimado NUMERIC(18, 2) NULL;
    function Cargar_Presupuestos_creados()
    {
        try {
            $sql = "SELECT * FROM PBI_PRESUPUESTO_CARTIMEX_CREADOS_SGO";
            $stmt = $this->db->query($sql);
            return $stmt;
        } catch (Exception $e) {
            return false;
            // return [
            //     'success'      => false,
            //     'querymessage' => 'Error obteniendo proveedores',
            //     'data'         => []
            // ];
        }
    }

    function Nuevo_Presupuesto($param)
    {
        try {
            $year = $param['year'];
            $presupuesto = $param['presupuesto'];
            $creado_por = $param['usrid'];
            $params = [
                ":year" => $year,
                ":presupuesto" => $presupuesto,
                ':creado_por' => $creado_por
            ];
            $sql = "INSERT INTO PBI_PRESUPUESTO_CARTIMEX_CREADOS_SGO
            (year, presupuestoEstimado, creado_por) VALUES (:year, :presupuesto, :creado_por)";
            $ok = $this->db->execute($sql, $params);
            if (!$ok) {
                return false;
            }
            $meses = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre'
            ];
            $sqlMes = "INSERT INTO PBI_PRESUPUESTO_CARTIMEX_VENDEDORES_TOTAL_MESES (ANIO, MES, VALOR, ORDEN)
                    VALUES (CAST(:year AS varchar(10)), :mes, CAST(0 AS money), :orden)";

            foreach ($meses as $orden => $mes) {
                $paramsMes = [
                    ":year" => $year,
                    ":mes" => $mes,
                    ":orden" => $orden
                ];
                $okMes = $this->db->execute($sqlMes, $paramsMes);
                if (!$okMes) {
                    return false;
                }
            }
            return $ok;
        } catch (Exception $e) {
            return false;
            // return [
            //     'success'      => false,
            //     'querymessage' => 'Error obteniendo proveedores',
            //     'data'         => []
            // ];
        }
    }

    // Vendedores
    function BuscarVendedor()
    {
        try {
            $sql = "SELECT ID, Código, Nombre
                FROM EMP_EMPLEADOS
                WHERE CLASE = 2
                  AND Nombre NOT LIKE '%*%';";
            $stmt = $this->db->query($sql);
            return $stmt;
        } catch (Exception $e) {
            return false;
            // return [
            //     'success'      => false,
            //     'querymessage' => 'Error obteniendo proveedores',
            //     'data'         => []
            // ];
        }
    }
    function AgregarVendedor($params)
    {// select * from PBI_PRESUPUESTO_CARTIMEX_VENDEDORES
        try {
            $year = $params['year'];
            $vendedorId = $params['ID'];
            $sqlExiste = "SELECT 1
            FROM dbo.PBI_PRESUPUESTO_CARTIMEX_VENDEDORES
            WHERE [Year] = :year
              AND VendedorID = :vendedorId";

            $param = [
                ':year' => $year,
                ':vendedorId' => $vendedorId,
            ];
            $stmtExiste = $this->db->query($sqlExiste, $param);
            if (!empty($stmtExiste['data'])) {
                return [
                    'success' => false,
                    'querymessage' => 'Ya existe (Year, VendedorID)',
                    'data' => [
                        'exists' => true,
                        'inserted' => false
                    ]
                ];
            }
            $sqlInsert = "INSERT INTO dbo.PBI_PRESUPUESTO_CARTIMEX_VENDEDORES
            (
                [Year], VendedorID,
                Enero, Febrero, Marzo, Abril, Mayo, Junio, Julio, Agosto, Septiembre, Octubre, Noviembre, Diciembre
            )
            VALUES
            (
                :year, :vendedorId,
                0,0,0,0,0,0,0,0,0,0,0,0
            )";

            $stmtIns = $this->db->execute($sqlInsert, $param);
            return $stmtIns;
        } catch (Exception $e) {
            return false;
            // return [
            //     'success'      => false,
            //     'querymessage' => 'Error obteniendo proveedores',
            //     'data'         => []
            // ];
        }
    }

    function Cargar_Vendedores($params)
    {
        try {
            $mes = $params['mes'];
            $year = $params['year'];

            $sql = "SELECT e.Nombre, g.Nombre as grupo, g.ID, p.vendedorID, $mes, VALOR_INGRESADO = $mes
            from [PBI_PRESUPUESTO_CARTIMEX_VENDEDORES] p
            left join EMP_EMPLEADOS e
            on e.ID = p.VendedorID
            left join EMP_GRUPOS g
            on g.ID = e.GrupoID
            where p.year = :year
            order by e.Nombre";
            $params = [
                ":year" => $year
            ];
            $stmt = $this->db->query($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            return false;
            // return [
            //     'success'      => false,
            //     'querymessage' => 'Error obteniendo proveedores',
            //     'data'         => []
            // ];
        }
    }

    function Actualizar_Presupuesto_Vendedor($param)
    {
        try {
            $year = $param['year'];
            $mes = $param['mes'];
            $valor = $param['valor'];
            $vendedor = $param['vendedor'];
            $sql = "UPDATE PBI_PRESUPUESTO_CARTIMEX_VENDEDORES
            SET " . $mes . " = :valor
            WHERE vendedorID = :vendedor
            and year = :year";
            $params = [
                ":vendedor" => $vendedor,
                ":valor" => $valor,
                ":year" => $year
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            return false;
            // return [
            //     'success'      => false,
            //     'querymessage' => 'Error obteniendo proveedores',
            //     'data'         => []
            // ];
        }
    }

    // Categorias
    function Subir_Presupuesto_Categorias($param)
    {
        try {
            $year = $param['year'];
            $sql = "";
            $params = [
                "" => $year
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            return false;
        }
    }

    function Cargar_Presupuesto_Categorias($param)
    {
        try {
            $year = $param['year'];
            // $year = isset($param['year']) ? (int)$param['year'] : 0;
            // return $year;
            $sql = "SELECT  i.Código, i.ID, i.Nombre,
        REPLACE(i.Orden, 'General\', '') AS Orden,
        VALOR_INGRESADO = c.PresupuestoAnual
        FROM INV_CATEGORIAS i
INNER JOIN PBI_PRESUPUESTO_CARTIMEX_CATEGORIAS c
    ON i.ID = c.CategoriaID
   AND c.Year = ".$year."
WHERE (LEN(i.Código) - LEN(REPLACE(i.Código, '.', ''))) = 1
ORDER BY i.Código";
            $params = [
                ":CatYear" => $year
            ];
            // $stmt = $this->db->query($sql, $params);
            $stmt = $this->db->query($sql);
            return $stmt;
        } catch (Exception $e) {
            return false;
        }
    }

    function Actualizar_Presupuesto_Categorias($param)
    {
        try {
            $year = $param['year'];
            $valor = $param['valor'];
            $categoria = $param['categoriaID'];
            $sql = "UPDATE PBI_PRESUPUESTO_CARTIMEX_CATEGORIAS
            SET PresupuestoAnual = :valor
            WHERE CategoriaID = :categoria
            and year = :year";
            $params = [
                ":valor" => $valor,
                ":categoria" => $categoria,
                ":year" => $year
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            return false;
            // return [
            //     'success'      => false,
            //     'querymessage' => 'Error obteniendo proveedores',
            //     'data'         => []
            // ];
        }
    }

    // Meses
    function Cargar_Presupuesto_Meses($params)
    {
        try {
            $year = $params['year'];
            $queryMeses = "SELECT * FROM PBI_PRESUPUESTO_CARTIMEX_VENDEDORES_TOTAL_MESES
            WHERE ANIO = :year";
            $params = [
                ":year" => $year,
            ];
            $stmtMes = $this->db->query($queryMeses, $params); //2,688731
            $queryTotales = "SELECT 
                    SUM(Enero)      AS Enero,
                    SUM(Febrero)    AS Febrero,
                    SUM(Marzo)      AS Marzo,
                    SUM(Abril)      AS Abril,
                    SUM(Mayo)       AS Mayo,
                    SUM(Junio)      AS Junio,
                    SUM(Julio)      AS Julio,
                    SUM(Agosto)     AS Agosto,
                    SUM(Septiembre) AS Septiembre,
                    SUM(Octubre)    AS Octubre,
                    SUM(Noviembre)  AS Noviembre,
                    SUM(Diciembre)  AS Diciembre
                FROM PBI_PRESUPUESTO_CARTIMEX_VENDEDORES
                WHERE [Year] = :year";

            $stmtTot = $this->db->query($queryTotales, $params);
            if ($stmtMes && $stmtTot) {
                return [
                    'success' => true,
                    'TotalxMeses' => $stmtTot['data'],
                    'Meses' => $stmtMes['data']
                ];
            }
        } catch (Exception $e) {
            return false;
            // return [
            //     'success'      => false,
            //     'querymessage' => 'Error obteniendo proveedores',
            //     'data'         => []
            // ];
        }
    }
    function Actualizar_Presupuesto_Mes($param)
    {
        try {
            $year = $param['year'];
            $mes = $param['mes'];
            $valor = $param['valor'];
            $sql = "UPDATE PBI_PRESUPUESTO_CARTIMEX_VENDEDORES_TOTAL_MESES
            SET VALOR = :valor
            WHERE MES = :mes
            and ANIO = :year";
            $params = [
                ":valor" => $valor,
                ":mes" => $mes,
                ":year" => $year
            ];
            $stmt = $this->db->execute($sql, $params);
            return $stmt;
        } catch (Exception $e) {
            return false;
            // return [
            //     'success'      => false,
            //     'querymessage' => 'Error obteniendo proveedores',
            //     'data'         => []
            // ];
        }
    }


}
