<?php

// require_once __DIR__ . '/../logsmodel.php';


class listactModel extends Model
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

    // Consolidados

    function marca_creada($data)
    {
        try {

            $FECHAINI = $data["FECHAINI"];
            $FECHAFIN = $data["FECHAFIN"];

            $sql = 'EXECUTE SGO_ACTIVIDADES_MARCAS_CARGAR_ACTIVIDADES_CONSOLIDADAS @FECHAINI = :FECHAINI, @FECHAFIN = :FECHAFIN';
            $param = [
                ':FECHAINI' => $FECHAINI,
                ':FECHAFIN' => $FECHAFIN,
            ];

            $response = $this->db->query($sql, $param);

            $datos_consolidados = $response['data'];

            for ($i = 0; $i < count($datos_consolidados); $i++) {
                $consolidado_id = $datos_consolidados[$i]["CONSOLIDADO_ID"];
                $datos_consolidados[$i]["_detalle_actividades"] = $this->Cargar_Actividades_por_Consolidado($consolidado_id);
                $GASTOS = $this->Cargar_Gastos_Por_Consolidado($consolidado_id);
                $datos_consolidados[$i]["_detalle_gastos"] = $GASTOS;
                $SUMA_GASTOS = 0;
                $SUMA_PAGOS = 0;
                for ($j = 0; $j < count($GASTOS); $j++) {
                    if ($GASTOS[$j]["TIPO_DOC"] == "GASTO") {
                        // Asegurarse de que el valor sea numérico antes de sumar
                        $SUMA_GASTOS += floatval($GASTOS[$j]["CON_VALOR_APLICADO"]);
                    }
                    if ($GASTOS[$j]["TIPO_DOC"] == "PAGO") {
                        $SUMA_PAGOS += floatval($GASTOS[$j]["PAGO_APLICADO"]);
                    }
                }
                $datos_consolidados[$i]["_SUMA_GASTOS"] = $SUMA_GASTOS;
                $datos_consolidados[$i]["_SUMA_PAGOS"] = $SUMA_PAGOS;
                $presupuesto = floatval($datos_consolidados[$i]["ACTIVIDAD_PRESUPUESTO"]);
                $datos_consolidados[$i]["_SALDO"] = $presupuesto - $SUMA_GASTOS;
            }

            $res = array(
                "success" => true,
                "consolidadas" => $datos_consolidados,
                "individuales" => $this->Actividades_Individuales($param),
            );
            return $res;
            // return $datos_consolidados;
            return $response;
        } catch (PDOException $e) {
            return [];
        }
    }

    function Cargar_Actividades_por_Consolidado($data)
    {
        try {
            $SQL = "SELECT 
            m.ID_Marca as ACTIVIDAD_ID,
            m.creado_por as ACTIVIDAD_CREADO_POR,
            m.tipo as ACTIVIDAD_TIPO,
            mt.nombre as ACTIVIDAD_TIPO_NOMBRE,
            m.Marca as ACTIVIDAD_MARCA,
            m.MarcaNombre as ACTIVIDAD_MARCA_NOMBRE,
            m.Referencia as ACTIVIDAD_REFERENCIA,
            m.Fecha as ACTIVIDAD_FECHA,
            m.periodo as ACTIVIDAD_PERIODO,
            m.concepto as ACTIVIDAD_CONCEPTO,
            m.valor as ACTIVIDAD_VALOR,
            m.Fecha_actividad as ACTIVIDAD_FECHA_ACTIVIDAD
            FROM SGO_Actividades_Marcas m
            LEFT JOIN SGO_ACTIVIDADES_MARCAS_TIPOS mt ON mt.id = m.Tipo
            WHERE m.consolidado_id = :CONSOLIDADO_ID
            and m.consolidado = 1";

            $params = [
                ':CONSOLIDADO_ID' => $data,
            ];
            $query = $this->db->query($SQL, $params);
            return $query['data'];
        } catch (PDOException $e) {
            return [];
        }
    }

    function Cargar_Gastos_Por_Consolidado($data)
    {
        try {
            $SQL = "EXECUTE SGO_ACTIVIDADES_MARCAS_CARGAR_ACTIVIDADES_CONSOLIDADOS_GASTOS_PAGOS @CONSOLIDADO = :CONSOLIDADO_ID";
            $params = [
                ':CONSOLIDADO_ID' => $data
            ];
            $query = $this->db->query($SQL, $params);
            return $query['data'];
        } catch (PDOException $e) {
            return [];
        }
    }



    // Actividades Individuales

    function Actividades_Individuales($data)
    {
        try {

            $sql = 'EXECUTE SGO_ACTIVIDADES_MARCAS_CARGAR_ACTIVIDADES_INDIVIDUALES @FECHAINI = :FECHAINI ,@FECHAFIN = :FECHAFIN';
            $query = $this->db->query($sql, $data);
            return $query['data'];
        } catch (Exception $e) {
            return [];
        }
    }

    function marca_creada2()
    {
        try {
            $sql = "SELECT 
                d.ID_Marca,
                d.Marca,
                d.Fecha,
                si.Nombre AS nombre_marca,
                d.Referencia,
                d.Concepto,
                d.valor,
                d.Fecha_actividad,
                d.Periodo,
                d.Tipo,
                d.Tipo_marca,
                d.MarcaNombre,
                --mc.Creado_por,
                mc.ActividadID,
                mt.nombre AS tipo,
                (po.valor_aplicado) AS Pago,
                SUM(ISNULL(mc.valor,0)) AS valor2,
                SUM(po.valor_aplicado) AS pago,
                COUNT(mc.ActividadID) AS cantidad_facturas,
                CASE WHEN mc.Tipo_Documento not in ('NOTA DE CREDITO','FACTURA') then SUM(mc.valor) else 0 end as PROMESA,
				CASE WHEN mc.Tipo_Documento in ('NOTA DE CREDITO','FACTURA') then SUM(mc.valor) else 0 end as REAL
                FROM SGO_Actividades_Marcas D 
                LEFT JOIN SGO_Actividades_Marcas_Creadas mc ON mc.ActividadID = d.ID_Marca AND mc.activa = 1
                LEFT JOIN SIS_PARAMETROS si ON d.Marca = si.ID
                LEFT JOIN SGO_ACTIVIDADES_MARCAS_TIPOS mt ON mt.id = d.Tipo
                LEFT JOIN SGO_ACTIVIDADES_MARCAS_PAGOS po ON po.Actividad_ID = D.ID_Marca
                WHERE d.Estado = 1
                GROUP BY
                d.ID_Marca,
                d.Marca,
                si.Nombre,
                mt.nombre,
                d.Referencia,
                d.Concepto,
                d.Fecha,
                d.valor,
                --mc.Creado_por,
                d.Fecha_actividad,
                d.Periodo,
                d.Tipo,
                d.Tipo_marca,
                po.valor_aplicado,
                mc.ActividadID,
                d.MarcaNombre,
                mc.Tipo_Documento
                ORDER BY 
                CASE
                    WHEN d.Fecha = CONVERT(DATE, GETDATE()) THEN 0 -- Fecha actual
                    ELSE 1 -- Fechas anteriores
                END,
                d.Fecha DESC;";
            $query = $this->db->query($sql);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Facturas_marcas($data)
    {
        try {
            $Consolidado_id = $data['Consolidado_id'];
            $sql = 'EXECUTE SGO_ACTIVIDADES_MARCAS_CARGAR_ACTIVIDADES_CONSOLIDADOS_GASTOS_PAGOS @CONSOLIDADO = :CONSOLIDADO';
            $param = [
                ':CONSOLIDADO' => $Consolidado_id
            ];
            $query = $this->db->query($sql, $param);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Actualizar_actividad($data)
    {
        try {
            $Creado_por = $data["usrid"];
            $Tipo = $data["Tipo"];
            $MarcaID = $data["MarcaID"];
            $MarcaNombre = $data["MarcaNombre"];
            $Referencia = $data["Referencia"];
            $valor = $data["valor"];
            $Concepto = $data["Concepto"];
            $Periodo = $data["Periodo"];
            $Actividad_ID = $data["Actividad_ID"];
            $Fecha_actividad = $data["Fecha_actividad"];
            $Cheek_marca = $data["Cheek_marca"];
            $sql = 'UPDATE SGO_Actividades_Marcas
                SET creado_por = :usuario, Tipo = :tipo, Marca = :MarcaID, MarcaNombre = :marcaNombre, Referencia = :referencia , valor = :valor , 
                Concepto = :concepto , Fecha_actividad = :Fecha_actividad , Tipo_marca = :Cheek_marca , Periodo = :Periodo
                WHERE ID_Marca = :ID_Marca';
            $param = [
                ':usuario' => $Creado_por,
                ':MarcaID' => $MarcaID,
                ':marcaNombre' => $MarcaNombre,
                ':tipo' => $Tipo,
                ':referencia' => $Referencia,
                ':valor' => $valor,
                ':concepto' => $Concepto,
                ':Fecha_actividad' => $Fecha_actividad,
                ':ID_Marca' => $Actividad_ID,
                ':Cheek_marca' => $Cheek_marca,
                ':Periodo' => $Periodo,
            ];
            $query = $this->db->execute($sql, $param);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Cargar_Tipos_Marcas()
    {
        try {
            $sql = "SELECT * FROM SGO_ACTIVIDADES_MARCAS_TIPOS";
            $query = $this->db->query($sql, []);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Cargar_Marcas_Editar()
    {
        try {
            $sql = "SELECT ID,Nombre,'0' as tipo FROM SIS_PARAMETROS WITH(NOLOCK) WHERE PadreID = '0000000026'
                    union ALL 
                    SELECT ID,Nombre,'1' as tipo FROM ACR_ACREEDORES WITH(NOLOCK) WHERE Anulado = '0'";
            $query = $this->db->query($sql);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Buscar_Proteccion($data)
    {
        try {
            $id = $data['id'];
            $sql = "SELECT 
                PromociónID,
                sum(Cantidad - Devuelto) as cantidad, 
                sum((Cantidad-Devuelto)*(Promoción) )  as valor,
                pr.Referencia,
                pr.CreadoDate,
                pr.Descripción
                from INV_PRODUCTOS_PROMOCIONES_PRECIO PR
                LEFT OUTER JOIN VEN_FACTURAS_DT DT with(NOLOCK) ON PR.PromiciónID = dt.PromociónID
                LEFT OUTER JOIN VEN_FACTURAS F WITH (NOLOCK) on F.ID = dt.FacturaID AND F.Anulado = 0 
                where PromociónID  = :PromicionID
                AND CASE ISNULL(PR.ClienteID,'') WHEN '' THEN ISNULL(PR.ClienteID,'') ELSE F.ClienteID END = ISNULL(PR.ClienteID,'')
                group by PromociónID,pr.Referencia,
                pr.CreadoDate,
                pr.Descripción";
            $param = [
                ':PromicionID' => $id,
            ];
            $query = $this->db->query($sql, $param);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Consolidar_Actividades($datos)
    { #TODO: Corregir cuando se crea la actividad llama a este model
        try {
            $val = 0;
            $err = 0;
            $Creado_por = $datos["usrid"];
            $DATOS = $datos["DATOS"] ?? [];
            $CONSOLIDADO_ID = $datos["CONSOLIDADO_ID"] ?? null;
            $CONSOLIDADO_NUEVO = $datos["CONSOLIDADO_NUEVO"] ?? [];
            $DESCRIPCION = $datos["DESCRIPCION"] ?? [];
            $REFERENCIA = $datos["REFERENCIA"] ?? [];
            $ACTUALIZADO_ERRO = [];
            if ($CONSOLIDADO_NUEVO == 0) {
                for ($i = 0; $i < count($DATOS); $i++) {
                    $ACTIVIDAD_ID = $DATOS[$i]["ACTIVIDAD_ID"];
                    $SQLU = "UPDATE SGO_Actividades_Marcas SET
                                consolidado = 1,
                                consolidado_id = :consolidado_id
                            WHERE ID_Marca = :ID_Marca";
                    $param = [
                        ':consolidado_id' => $CONSOLIDADO_ID,
                        ':ID_Marca' => $ACTIVIDAD_ID,
                    ];
                    $query = $this->db->execute($SQLU, $param);
                    if (!$query) {
                        $err = $query->errorInfo();
                        array_push($ACTUALIZADO_ERRO, [$DATOS[$i], $err]);
                    }
                }
                return $query;
            } else {
                $SQL_INSERT = "INSERT INTO SGO_Actividades_Marcas_Consolidados (Descripcion, Referencia, Creado_Por)
                   VALUES (:Descripcion, :Referencia, :Creado_Por)";
                $param = [
                    ':Descripcion' => $DESCRIPCION,
                    ':Referencia' => $REFERENCIA,
                    ':Creado_Por' => $Creado_por,
                ];
                $query = $this->db->execute($SQL_INSERT, $param);
                if ($query) {
                    // $result = $query->fetchAll(PDO::FETCH_ASSOC);
                    $lastId = $this->db->lastInsertId();
                    $Consolidado_Id = str_pad($lastId, 10, '0', STR_PAD_LEFT);

                    $ACTUALIZADO_ERRO = [];
                    #TODO: Aqui se usa el ACTIVIDAD_ID desde el crear
                    for ($i = 0; $i < count($DATOS); $i++) {
                        $ACTIVIDAD_ID = $DATOS[$i]["ACTIVIDAD_ID"] ?? $datos["ACTIVIDAD_ID"];
                        $SQLU = "UPDATE SGO_Actividades_Marcas SET
                                consolidado = 1,
                                consolidado_id = :consolidado_id
                            WHERE ID_Marca = :ID_Marca";
                        $param = [
                            ':consolidado_id' => $Consolidado_Id,
                            ':ID_Marca' => $ACTIVIDAD_ID,
                        ];
                        $query = $this->db->execute($SQLU, $param);
                        if (!$query) {
                            $err = $query->errorInfo();
                            array_push($ACTUALIZADO_ERRO, [$DATOS[$i], $err]);
                        }
                    }
                    return $query;
                } else {
                    return ['Error'];
                }
            }
        } catch (PDOException $e) {
            $e = $e->getMessage();
            echo json_encode($e);
            exit();
        }
    }

    function GUARDAR_DATOS($data)
    {
        try {
            $Creado_por = $data["usrid"];
            $ARRAY_DOCUMENTOS_SELECCIONADOS = $data["ARRAY_DOCUMENTOS_SELECCIONADOS"][0];

            $Consolidado_id = $data["Consolidado_id"];
            $Valor_Aplicar = $data["Valor_Aplicar"];
            $ACR_ID = $ARRAY_DOCUMENTOS_SELECCIONADOS["ACR_ID"];
            $ACR_TIPO = trim($ARRAY_DOCUMENTOS_SELECCIONADOS["ACR_TIPO"]);
            $ACR_VALOR_DOCUMENTO = trim($ARRAY_DOCUMENTOS_SELECCIONADOS["ACR_VALOR_DOCUMENTO"]);
            $ID_UNICO = date("YmdHis") . rand(1000, 9999);

            $sql = 'EXECUTE SGO_ACTIVIDADES_MARCAS_GUARDAR 
            @documento = :documento,
            @actividad = :actividad,
            @valor = :valor ,
            @creado_por = :creado_por,
            @tipo = :tipo,
            @documento_id = :documento_id,
            @valor_doc = :valor_doc,
            @id_unico= :id_unico,
            ';
            $param = [
                ':documento' => $ACR_ID,
                ':actividad' => $Consolidado_id,
                ':valor' => $Valor_Aplicar,
                ':creado_por' => $Creado_por,
                ':tipo' => $ACR_TIPO,
                ':documento_id' => $ACR_ID,
                ':valor_doc' => $ACR_VALOR_DOCUMENTO,
                ':id_unico' => $ID_UNICO
            ];
            $query = $this->db->execute($sql, $param);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function GUARDAR_NOTA_CREDITO($data)
    {
        try {
            $Creado_por = $data["usrid"];
            $ACTIVIDAD_ID = $data["ACTIVIDAD_ID"];
            $tipo = $data["tipo"];
            $ARRAY_DATOS = $data["DATA_ARRAY"];

            for ($i = 0; $i < count($ARRAY_DATOS); $i++) {
                $DOCUMENTO = $ARRAY_DATOS[$i]["DEUDA_DOCUMENTO_ID"];
                $DOCUMENTO_ID = $ARRAY_DATOS[$i]["DEUDA_ID"];
                $Valor = $ARRAY_DATOS[$i]["ABONO"];
                $Valor_doc = $ARRAY_DATOS[$i]["VALOR_NOTACREDITO"];
                $currentDateTime = date("YmdHis");

                $sql = 'EXECUTE SGO_ACTIVIDADES_MARCAS_GUARDAR 
                        @documento = :documento,
                        @actividad = :actividad,
                        @valor = :valor ,
                        @creado_por = :creado_por,
                        @tipo = :tipo,
                        @documento_id = :documento_id,
                        @valor_doc = :valor_doc,
                        @id_unico= :id_unico,
                        ';
                $param = [
                    ':documento' => $DOCUMENTO_ID,
                    ':actividad' => $ACTIVIDAD_ID,
                    ':valor' => $Valor,
                    ':creado_por' => $Creado_por,
                    ':tipo' => $tipo,
                    ':documento_id' => $DOCUMENTO,
                    ':valor_doc' => $Valor_doc,
                    ':id_unico' => $currentDateTime
                ];

                $query = $this->db->execute($sql, $param);
            }
            return $query;
        } catch (PDOException $e) {
            return [];
        }
    }

    function Eliminar_Actividad($data)
    {
        try {
            $ActividadID = $data["actividad_id"];
            $Documento = $data["documento"];
            $id_unico = $data["id_unico"];
            $sql = 'UPDATE
            SGO_Actividades_Marcas_Creadas
            SET
                activa = 0
            WHERE
                ActividadID = :ActividadID AND
                DocumentoID = :DocumentoID
                AND id_unico = :id_unico';
            $param = [
                ':ActividadID' => $ActividadID,
                ':DocumentoID' => $Documento,
                ':id_unico' => $id_unico
            ];
            $query = $this->db->execute($sql, $param);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Eliminar_actividad_creada($param)
    {
        try {
            $Eliminado_por = $param["usrid"];
            $ID_Marca = $param["ID_Marca"];
            $fecha_Eliminado = date("Y-m-d");

            $sql = 'EXEC SGO_ACTIVIDADES_MARCAS_ACTUALIZAR_ACTIVIDAD @ID_Marca = :ID_Marca, @Eliminado_por = :Eliminado_por, @fecha_Eliminado = :fecha_Eliminado';
            $param = [
                ':ID_Marca' => $ID_Marca,
                ':Eliminado_por' => $Eliminado_por,
                ':fecha_Eliminado' => $fecha_Eliminado
            ];
            $query = $this->db->execute($sql, $param);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Buscar_Documentos($data)
    {
        try {
            $Tipo_Documento = $data["Tipo_Documento"];
            $Dt_Documento = $data["Dt_Documento"];

            $sql = 'EXEC SGO_ACTIVIDADES_MARCAS_BUSCAR_DOCUMENTOS 
                @Tipo_Documento = :Tipo_Documento,
                @Dt_Documento = :Dt_Documento';
            $param = [
                ':Tipo_Documento' => $Tipo_Documento,
                ':Dt_Documento' => $Dt_Documento
            ];
            $query = $this->db->query($sql, $param);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Validar_Pagos($data)
    {
        try {
            $PG_NUMERO_DOCUMENTO = $data["PG_NUMERO_DOCUMENTO"];
            $DOCUMENTO = $data["DOCUMENTO"];
            $empresa = $data["empresa"];
            $tipoDocumentoValor = $data["tipoDocumentoValor"];

            $sql = "SELECT 
                d.ID as DOC_ID,
                d.Número,
                d.Tipo as DOC_TIPO,
                d.Detalle as DOC_DETALLE,
                d.Valor_Base as DOC_VALOR,
				isnull(d.Valor_Base - t.VALOR_APLICADO,d.Valor_Base) as SALDO,
                'CARTIMEX' as DOC_EMPRESA
                from ACR_DEBITOS d with(nolock)
				left outer join (
					select Documento_ID, SUM(p.valor_aplicado) as VALOR_APLICADO from SGO_ACTIVIDADES_MARCAS_PAGOS p
					where p.Estado = 1
					group by p.Documento_ID
				) as t on t.Documento_ID =d.ID
                where d.ID = :DOCUMENTO
                and d.anulado = 0
                group by
                d.ID,d.Valor_Base,d.Detalle,d.Fecha,d.Tipo,d.Número,t.VALOR_APLICADO";
            if ($DOCUMENTO == "INGRESO") {
                $sql = "EXEC SGO_PROTECCION_MARCAS_PAGOS_DOCUMENTOS 
                    @Documento = :DOCUMENTO,
                    @tipo = :tipo,
                    @empresa = :empresa
                ";
            }
            $param = [
                ':DOCUMENTO' => $PG_NUMERO_DOCUMENTO,
                ':tipo' => $tipoDocumentoValor,
                ':empresa' => $empresa
            ];
            $query = $this->db->query($sql, $param);
            return $query;
        } catch (Exception $e) {
            return [];
        }
    }

    function Agregar_Pago($data)
    {
        try {
            $Creado_por = $data["usrid"];
            $Valor_Aplicar = $data["Valor_Aplicar"];
            $ARRAY_PAGOS_SELECCIONADOS = $data["ARRAY_PAGOS_SELECCIONADOS"][0];
            $CONSOLIDADOS_SELECCIONADOS = $data["CONSOLIDADOS_SELECCIONADOS"];

            $DOC_ID = $ARRAY_PAGOS_SELECCIONADOS["DOC_ID"];
            $DOC_TIPO = $ARRAY_PAGOS_SELECCIONADOS["DOC_TIPO"];
            $DOC_EMPRESA = $ARRAY_PAGOS_SELECCIONADOS["DOC_EMPRESA"];
            $Valor_Aplicar = $data["Valor_Aplicar"];

            $ERRORES = [];

            for ($i = 0; $i < count($CONSOLIDADOS_SELECCIONADOS); $i++) {

                $CONSOLIDADO_ID = $CONSOLIDADOS_SELECCIONADOS[$i]["CONSOLIDADO_ID"];

                $sql = "INSERT INTO SGO_ACTIVIDADES_MARCAS_PAGOS(Actividad_ID,Documento_ID,valor_aplicado,tipo,Creado_por,empresa)
                    VALUES(:Actividad_ID,:Documento_ID,:valor_aplicado,:tipo,:Creado_por,:empresa)";

                $param = [
                    ":Actividad_ID" => $CONSOLIDADO_ID,
                    ":Documento_ID" => $DOC_ID,
                    ":valor_aplicado" => $Valor_Aplicar,
                    ":tipo" => $DOC_TIPO,
                    ":Creado_por" => $Creado_por,
                    ":empresa" => $DOC_EMPRESA
                ];
                $query = $this->db->execute($sql, $param);
            }
            return $query;
        } catch (PDOException $e) {
            $e = $e->getMessage();
            echo json_encode($e);
            exit();
        }
    }

}