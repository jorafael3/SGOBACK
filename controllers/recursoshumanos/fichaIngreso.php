<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';
require_once __DIR__ . '/../../libs/EmailService.php';
// require_once __DIR__ . '/../models/empresamodel.php';

require('fpdf/fpdf.php');
require_once('tcpdf/tcpdf.php');

class fichaIngreso extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'recursoshumanos/'; // Especifica la carpeta donde está el modelo
        $this->loadModel('fichaIngreso'); // Cargar el modelo correcto
    }


    function guardarFichaIngreso()
    {
        $jwtData = $this->authenticateAndConfigureModel(2); // 2 = POST requerido
        if (!$jwtData) {
            return; // La respuesta de error ya fue enviada automáticamente
        }

        $params = $this->getJsonInput();
        $params['usrid'] = $params['userdata']['usuario'] ?? null;

        $result = $this->model->guardarFichaIngreso($params);
        if ($result && $result['success']) {
            try {
                $pdf = $this->Crear_pdf($params);
                if ($pdf['success']) {
                    $update = $this->model->actualizarDoc($pdf['data'], $params);
                }
            } catch (Exception $e) {
                error_log("Error: " . $e->getMessage());
            }
            $this->jsonResponse([
                "success" => true,
                "message" => "Ficha de ingreso guardada correctamente.",
                // "data" => $result
                "data" => $result,
                "pdf" => $pdf
            ], 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                "respuesta" => $result
            ], 200);
        }
    }

    function Crear_pdf($param)
    {
        // return $params;
        $p = $param["datosPersonales"];
        $i = $param["instruccionLaboral"];
        $m = $param["datosMedicos"];
        $familiares = $param["familiares"] ?? [];
        $contacto = $param["contactoEmergencia"] ?? [];
        $referencias = $param["referenciasPersonales"] ?? [];
        $EMPRESA = $param["userdata"]["empleado_empresa"] ?? "CARTIMEX";

        // Simular estructura $row[0] igual que tu función original
        $row = [[
            "NombreApellido"       => $p["nombres"] . " " . $p["apellidos"],
            "Cedula"               => $p["cedula"],
            "Email"                => $p["correoElectronico"],
            "FechaNacimiento"      => $p["fechaNacimiento"],
            "LugarNacimiento"      => $p["lugarNacimiento"],
            "Nacionalidad"         => $p["nacionalidad"],
            "Genero"               => $p["genero"],
            "NivelInstruccion"     => $i["nivel"],
            "TituloObtenido"       => $i["titulo"],
            "FechaTitulacion"      => $i["fechaTitulacion"],
            "LugarTrabajo"         => $i["lugarTrabajo"],
            "TelefonoTrabajo"      => $i["trabajoTelefono"],
            "CargoTrabajo"         => $i["cargo"],
            "RolDesempenado"       => $i["rol"],
            "FechaDesde"           => $i["desde"],
            "FechaHasta"           => $i["hasta"],
            "Estado_civil"         => $p["estadoCivil"],
            "TieneDiscapacidad"    => $p["discapacidadPersonal"],
            "Discapacidad"    => $p["tipoDiscapacidadPersonal"],
            "CargoAplica"          => $p["cargoAplica"],
            "ParentescoTrabajador" => $p["parentescoTrabajador"],
            "ReferidoPor"          => $p["referidoPor"],
            "TotalTiempo"          => $i["tiempo"],
            "RazonSalida"          => $i["razonSalida"],
            "EMPRESA"              => $EMPRESA,
            "Enfermedad"           => $m["emfermedad"],
            "MotivoEnfermedad"     => $m["emfermedadDetalle"],
            "Operacion"            => $m["operacion"],
            "MotivoOperacion"      => $m["operacionDetalle"],
            "Gestacion"            => $m["gestacion"],
            "MotivoGestacion"      => $m["gestacionDetalle"],
            "Lactancia"            => $m["lactancia"],
            "MotivoLactancia"      => $m["lactanciaDetalle"],
            "Alergia"              => $m["alergia"],
            "MotivoAlergia"        => $m["alergiaDetalle"],
            "EsfuerzoFisico"       => $m["esfuerzoFisico"],
            "MotivoEsfuerzo"       => $m["esfuerzoFisicoDetalle"],
            "Direccion"            => $p["direccion"],
            "Celular"              => $p["celular"],
            "Edad"                 => $p["edad"],
            "PersonaACargo"        => $p["discapacidadResponsabilidad"],
            "parentesco_contacto"  => $contacto[0]["parentesco"] ?? "",
            "celular_contacto"     => $contacto[0]["celular"] ?? "",
            "nombre_contacto"      => $contacto[0]["nombresApellidos"] ?? ""
        ]];


        $pdf = new TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();

        $logo = ($EMPRESA === 'CARTIMEX')
            ? 'https://www.cartimex.com/assets/img/logo200.png'
            : 'https://www.computron.com.ec/wp-content/uploads/2023/08/computron-tienda-online.png';
        $pdf->Image($logo, 15, 10, 35);
        $pdf->Ln(20);

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Ficha de Ingreso del Empleado', 0, 1, 'C');
        $pdf->Ln(10);

        function fila($pdf, $label, $value)
        {
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(60, 8, $label . ':', 0, 0);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 8, $value, 0, 1);
        }

        // === Información Personal ===
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Información Personal', 0, 1);

          foreach ([
            "Nombres y Apellidos" => "NombreApellido",
            "Cédula" => "Cedula",
            "Email" => "Email",
            "Direccion" => "Direccion",
            "Celular" => "Celular",
            "Fecha de Nacimiento" => "FechaNacimiento",
            "Edad" => "Edad",
            "Lugar de Nacimiento" => "LugarNacimiento",
            "Nacionalidad" => "Nacionalidad",
            "Género" => "Genero",
            "Estado Civil" => "Estado_civil",
            "Cargo al que Aplica" => "CargoAplica",
            "Referido Por" => "ReferidoPor"
        ] as $label => $key) {
            fila($pdf, $label, $row[0][$key]);
        }

        fila($pdf, "Parentesco con Trabajador", $row[0]["ParentescoTrabajador"] ? "Sí" : "No");

         fila($pdf, "Persona con discapacidad a cargo", $row[0]["PersonaACargo"] ? "Sí" : "No");
        $pdf->Ln(5);

        // === Formación Académica ===
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Formación Académica', 0, 1);
        fila($pdf, "Nivel de Instrucción", $row[0]["NivelInstruccion"]);
        fila($pdf, "Título Obtenido", $row[0]["TituloObtenido"]);
        fila($pdf, "Fecha de Titulación", $row[0]["FechaTitulacion"]);
        $pdf->Ln(5);

        // === Experiencia Laboral ===
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Experiencia Laboral', 0, 1);
        fila($pdf, "Cargo Actual", $row[0]['CargoTrabajo']);
        fila($pdf, "Rol Desempeñado", $row[0]['RolDesempenado']);
        fila($pdf, "Lugar de Trabajo", $row[0]['LugarTrabajo']);
        fila($pdf, "Fecha Desde", $row[0]['FechaDesde']);
        fila($pdf, "Fecha Hasta", $row[0]['FechaHasta']);
        fila($pdf, "Total Tiempo Laborado", $row[0]['TotalTiempo']);
        fila($pdf, "Razón de Salida", $row[0]['RazonSalida']);
        // fila($pdf, "Empresa", $row[0]['EMPRESA']);
        $pdf->Ln(5);

        // === Información Médica ===
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Información Médica', 0, 1);
        function condicion_medica($pdf, $label, $valor, $motivo)
        {
            fila($pdf, $label, $valor ? "Sí" : "No");
            if ($valor && !empty($motivo))
                fila($pdf, "Motivo", $motivo);
        }
        condicion_medica($pdf, "Discapacidad", $row[0]['TieneDiscapacidad'], $row[0]['Discapacidad']);
        condicion_medica($pdf, "Enfermedad", $row[0]['Enfermedad'], $row[0]['MotivoEnfermedad']);
        condicion_medica($pdf, "Operación", $row[0]['Operacion'], $row[0]['MotivoOperacion']);
        condicion_medica($pdf, "Gestación", $row[0]['Gestacion'], $row[0]['MotivoGestacion']);
        condicion_medica($pdf, "Lactancia", $row[0]['Lactancia'], $row[0]['MotivoLactancia']);
        condicion_medica($pdf, "Alergia", $row[0]['Alergia'], $row[0]['MotivoAlergia']);
        condicion_medica($pdf, "Esfuerzo Físico", $row[0]['EsfuerzoFisico'], $row[0]['MotivoEsfuerzo']);
        $pdf->Ln(5);

        // === Registro Familiar ===
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Cargas Familiares', 0, 1);
         if (count($familiares) == 0) {
            $pdf->Cell(0, 8, "No registra familiares.", 0, 1);
        } else {
            foreach ($familiares as $f) {
                fila($pdf, "Nombre", $f["nombresApellidos"]);
                fila($pdf, "Cédula", $f["cedula"]);
                fila($pdf, "Parentesco", $f['parentesco']);
                fila($pdf, "Fecha Nacimiento", $f["fechaNacimiento"]);
                fila($pdf, "Edad", $f["edad"]);
                fila($pdf, "Género", $f["genero"]);
                $pdf->Ln(5);
            }
        }
        $pdf->Ln(5);


        // === Contactos Laborales emergencia ===
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Contactos de Emergencia', 0, 1);

         if (count($contacto) == 0) {
            $pdf->Cell(0, 8, "No registra contacto.", 0, 1);
        } else {
            $c = $contacto[0];
            fila($pdf, "Nombre", $c["nombresApellidos"]);
            fila($pdf, "Parentesco", $c["parentesco"]);
            fila($pdf, "Celular", $c["celular"]);
        }
        $pdf->Ln(5);


        // === Referencias Laborales ===
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Referencias Laborales', 0, 1);
        if (count($referencias) == 0) {
            $pdf->Cell(0, 8, "No registra referencias.", 0, 1);
        } else {
            foreach ($referencias as $r) {
                fila($pdf, "Nombre", $r["nombresApellidos"]);
                fila($pdf, "Trabajo", $r["lugarTrabajo"]);
                fila($pdf, "Teléfono", $r["telefono"]);
                $pdf->Ln(5);
            }
        }

        // === Declaración final y firma ===
        $pdf->Ln(15);
        $declaracion = "Yo " . $row[0]['NombreApellido'] . " con C.I " . $row[0]['Cedula'] . ", certifico que todos los datos indicados en esta solicitud son verdaderos y no he ocultado ningún información. Es responsabilidad de cada trabajador proporcionar toda información de manera clara, oportuna y transparente; y, en los casos que esta información sea ocultada, modificada, restringida, manipulada o adulterada, será considerado como una FALTA GRAVE dando derecho a la terminación de la relación laboral previo trámite de visto bueno.";
        $pdf->MultiCell(0, 10, $declaracion, 0, 'C');
        $pdf->Ln(20);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Ln(10);
        $pdf->Cell(0, 0, '___________________________', 0, 1, 'C');
        $pdf->Ln(3);
        $pdf->Cell(0, 8, 'FIRMA DEL TRABAJADOR', 0, 1, 'C');


        // === Guardar PDF ===
        $nombre_archivo = $row[0]['Cedula'] . '.pdf';

        $SO = PHP_OS;
        // echo $SO;
        if ($SO != "Linux") {
            $ruta_pdf = 'C:/xampp/htdocs/sgo_docs/Cartimex/fichaempleados/fichas_empleados/';
        } else {
            $ruta_pdf = '/var/www/html/sgo_docs/Cartimex/fichaempleados/fichas_empleados/';
        }
        if (!file_exists($ruta_pdf)) {
            mkdir($ruta_pdf, 0777, true); // Crea la carpeta si no existe
        }
        $pdf->Output($ruta_pdf . $nombre_archivo, 'F'); // Guarda el PDF en la carpeta creada
        return ['success' => true, 
        'data' => $nombre_archivo];
    }

}
