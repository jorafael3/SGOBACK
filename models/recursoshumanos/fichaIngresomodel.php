<?php

class FichaIngresoModel extends Model
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

    function guardarFichaIngreso($param)
    {
        try {
            // return $param;

            // DATOS PERSONALES
            $nombreApellido = ($param['datosPersonales']['nombres'] ?? '') . ' ' . ($param['datosPersonales']['apellidos'] ?? '');
            $cedula = $param['datosPersonales']['cedula'] ?? null;
            $direccion = $param['datosPersonales']['direccion'] ?? null;
            $telefonoConvencional = $param['datosPersonales']['telefono'] ?? null;
            $celular = $param['datosPersonales']['celular'] ?? null;
            $email = $param['datosPersonales']['correoElectronico'] ?? null;
            $fechaNacimiento = $param['datosPersonales']['fechaNacimiento'] ?? null;
            $edad = $param['datosPersonales']['edad'] ?? null;
            $lugarNacimiento = $param['datosPersonales']['lugarNacimiento'] ?? null;
            $nacionalidad = $param['datosPersonales']['nacionalidad'] ?? null;
            $genero = $param['datosPersonales']['genero'] ?? null;
            $parentescoTrabajador = $param['datosPersonales']['parentescoTrabajador'] ?? null;
            $referidoPor = $param['datosPersonales']['referidoPor'] ?? null;
            $cargoAplica = $param['datosPersonales']['cargoAplica'] ?? null;

            // PERSONA A CARGO (DISCAPACIDAD RESPONSABILIDAD)
            $personaACargo = $param['datosPersonales']['discapacidadResponsabilidad'] ?? null;
            $tipoDiscapacidadACargo = $param['datosPersonales']['tipoDiscapacidadResponsabilidad'] ?? null;
            $parentescoACargo = $param['datosPersonales']['relacionDiscapacidadResponsabilidad'] ?? null;

            // DISCAPACIDAD PERSONAL
            $tieneDiscapacidad = $param['datosPersonales']['discapacidadPersonal'] ?? null;
            $tipoDiscapacidad = $param['datosPersonales']['tipoDiscapacidadPersonal'] ?? null;
            $gradoDiscapacidad = $param['datosPersonales']['gradoDiscapacidadPersonal'] ?? null;

            $Estado_civil = $param['datosPersonales']['estadoCivil'] ?? null;
            $Etnica = $param['datosPersonales']['etnia'] ?? null;
            $Tipo_contrato = $param['datosPersonales']['tipoContrato'] ?? null;
            $decimos = $param['datosPersonales']['decimosAcumulados'] ?? null;

            // Nombres separados
            $nombre = $param['datosPersonales']['nombres'] ?? null;
            $Apellido = $param['datosPersonales']['apellidos'] ?? null;


            // INSTRUCCIÓN LABORAL
            $nivelInstruccion = $param['instruccionLaboral']['nivel'] ?? null;
            $institucionEducativa = $param['instruccionLaboral']['institucion'] ?? null;
            $tituloObtenido = $param['instruccionLaboral']['titulo'] ?? null;
            $fechaTitulacion = $param['instruccionLaboral']['fechaTitulacion'] ?? null;
            $lugarTrabajo = $param['instruccionLaboral']['lugarTrabajo'] ?? null;
            $telefonoTrabajo = $param['instruccionLaboral']['trabajoTelefono'] ?? null;
            $cargoTrabajo = $param['instruccionLaboral']['cargo'] ?? null;
            $rolDesempenado = $param['instruccionLaboral']['rol'] ?? null;
            $fechaDesde = $param['instruccionLaboral']['desde'] ?? null;
            $fechaHasta = $param['instruccionLaboral']['hasta'] ?? null;
            $totalTiempo = $param['instruccionLaboral']['tiempo'] ?? null;
            $razonSalida = $param['instruccionLaboral']['razonSalida'] ?? null;


            // DATOS MÉDICOS
            $enfermedad = $param['datosMedicos']['emfermedad'] ?? null;
            $motivoEnfermedad = $param['datosMedicos']['emfermedadDetalle'] ?? null;

            $gestacion = $param['datosMedicos']['gestacion'] ?? null;
            $motivoGestacion = $param['datosMedicos']['gestacionDetalle'] ?? null;

            $lactancia = $param['datosMedicos']['lactancia'] ?? null;
            $motivoLactancia = $param['datosMedicos']['lactanciaDetalle'] ?? null;

            $operacion = $param['datosMedicos']['operacion'] ?? null;
            $motivoOperacion = $param['datosMedicos']['operacionDetalle'] ?? null;

            $alergia = $param['datosMedicos']['alergia'] ?? null;
            $motivoAlergia = $param['datosMedicos']['alergiaDetalle'] ?? null;

            $esfuerzoFisico = $param['datosMedicos']['esfuerzoFisico'] ?? null;
            $motivoEsfuerzo = $param['datosMedicos']['esfuerzoFisicoDetalle'] ?? null;


            // CONTACTO DE EMERGENCIA (primer contacto)
            $emergencia  = $param['contactoEmergencia'] ?? [];
            $contacto = $param['contactoEmergencia'][0]['parentesco'] ?? null;
            $celular_contacto = $param['contactoEmergencia'][0]['celular'] ?? null;
            $nombre_contacto = $param['contactoEmergencia'][0]['nombresApellidos'] ?? null;
            $parentesco_contacto = $param['contactoEmergencia'][0]['parentesco'] ?? null;


            // FIRMA
            $firmaAceptada = $param['firmaAceptada'] ?? null;

            $sql = "INSERT INTO Empleados (
                NombreApellido, Cedula, Direccion, TelefonoConvencional, Celular, Email, FechaNacimiento, Edad, LugarNacimiento, Nacionalidad, Genero, ParentescoTrabajador, ReferidoPor, CargoAplica, PersonaACargo, TipoDiscapacidadACargo, ParentescoACargo, TieneDiscapacidad, TipoDiscapacidad, GradoDiscapacidad, 
                NivelInstruccion, InstitucionEducativa, TituloObtenido, FechaTitulacion, LugarTrabajo, TelefonoTrabajo, CargoTrabajo, RolDesempenado, FechaDesde, FechaHasta, TotalTiempo, RazonSalida, 
                Enfermedad, MotivoEnfermedad, Gestacion, MotivoGestacion, Lactancia, MotivoLactancia, Operacion, MotivoOperacion, Alergia, MotivoAlergia, EsfuerzoFisico, MotivoEsfuerzo,
                firmado, 
                Estado_civil , Etnica, Nombre, Apellido , EMPRESA , contacto , celular_contacto , nombre_contacto , parentesco_contacto , Tipo_contrato, decimos
            ) VALUES (
                :nombreApellido, :cedula, :direccion, :telefonoConvencional, :celular, :email, :fechaNacimiento, :edad, :lugarNacimiento, :nacionalidad, :genero, :parentescoTrabajador, :referidoPor, :cargoAplica, :personaACargo, :tipoDiscapacidadACargo, :parentescoACargo, :tieneDiscapacidad, :tipoDiscapacidad, :gradoDiscapacidad, 
                :nivelInstruccion, :institucionEducativa, :tituloObtenido, :fechaTitulacion, :lugarTrabajo, :telefonoTrabajo, :cargoTrabajo, :rolDesempenado, :fechaDesde, :fechaHasta, :totalTiempo, :razonSalida, 
                :enfermedad, :motivoEnfermedad, :gestacion, :motivoGestacion, :lactancia, :motivoLactancia, :operacion, :motivoOperacion, :alergia, :motivoAlergia, :esfuerzoFisico, :motivoEsfuerzo , 
                :firmaAceptada , :Estado_civil, :Etnica , :nombre, :Apellido , 'CARTIMEX' , :contacto  , :celular_contacto , :nombre_contacto , :parentesco_contacto ,:Tipo_contrato, :decimos
            );
                ";
            $params = [
                ":nombreApellido" => $nombreApellido,
                ":cedula" => $cedula,
                ":direccion" => $direccion,
                ":telefonoConvencional" => $telefonoConvencional,
                ":celular" => $celular,
                ":email" => $email,
                ":fechaNacimiento" => $fechaNacimiento,
                ":edad" => $edad,
                ":lugarNacimiento" => $lugarNacimiento,
                ":nacionalidad" => $nacionalidad,
                ":genero" => $genero,
                ":parentescoTrabajador" => $parentescoTrabajador,
                ":referidoPor" => $referidoPor,
                ":cargoAplica" => $cargoAplica,
                ":personaACargo" => $personaACargo,
                ":tipoDiscapacidadACargo" => $tipoDiscapacidadACargo,
                ":parentescoACargo" => $parentescoACargo,
                ":tieneDiscapacidad" => $tieneDiscapacidad,
                ":tipoDiscapacidad" => $tipoDiscapacidad,
                ":gradoDiscapacidad" => $gradoDiscapacidad,
                ":nivelInstruccion" => $nivelInstruccion,
                ":institucionEducativa" => $institucionEducativa,
                ":tituloObtenido" => $tituloObtenido,
                ":fechaTitulacion" => $fechaTitulacion,
                ":lugarTrabajo" => $lugarTrabajo,
                ":telefonoTrabajo" => $telefonoTrabajo,
                ":cargoTrabajo" => $cargoTrabajo,
                ":rolDesempenado" => $rolDesempenado,
                ":fechaDesde" => $fechaDesde,
                ":fechaHasta" => $fechaHasta,
                ":totalTiempo" => $totalTiempo,
                ":razonSalida" => $razonSalida,
                ":enfermedad" => $enfermedad,
                ":motivoEnfermedad" => $motivoEnfermedad,
                ":gestacion" => $gestacion,
                ":motivoGestacion" => $motivoGestacion,
                ":lactancia" => $lactancia,
                ":motivoLactancia" => $motivoLactancia,
                ":operacion" => $operacion,
                ":motivoOperacion" => $motivoOperacion,
                ":alergia" => $alergia,
                ":motivoAlergia" => $motivoAlergia,
                ":esfuerzoFisico" => $esfuerzoFisico,
                ":motivoEsfuerzo" => $motivoEsfuerzo,
                ":firmaAceptada" => $firmaAceptada,
                ":Estado_civil" => $Estado_civil,
                ":Etnica" => $Etnica,
                ":nombre" => $nombre,
                ":Apellido" => $Apellido,
                ":contacto" => $contacto,
                ":celular_contacto" => $celular_contacto,
                ":nombre_contacto" => $nombre_contacto,
                ":parentesco_contacto" => $parentesco_contacto,
                ":Tipo_contrato" => $Tipo_contrato,
                ":decimos" => $decimos
            ];

            $query = $this->db->execute($sql, $params);
            // return $query;

            if ($query) {
                $EmpleadoID = $this->db->lastInsertId();
                if (!$EmpleadoID) {
                    return ['success' => false];
                }

                // Guardar Familiares
                $FAM = $this->GRABAR_FAMILIARES($param["familiares"], $EmpleadoID, $cedula);
                if ($FAM[0] != 1) {
                    $this->db->rollBack();
                    exit();
                }

                // Guardar Referencias Laborales
                $REF = $this->GRABAR_REFERENCIAS($param["referenciasPersonales"], $EmpleadoID, $cedula);

                if ($REF[0] != 1) {
                    $this->db->rollBack();
                    exit();
                }

                // Guardar contacto laboral
                $CONT = $this->GRABAR_CONTACTOS($param["contactoEmergencia"], $EmpleadoID, $cedula);

                if ($CONT[0] != 1) {
                    $this->db->rollBack();
                    exit();
                }
            }
            return $query;
        } catch (Exception $e) {
            return ['error' => $e];
        }
    }

    function GRABAR_FAMILIARES($familiares, $empleadoID, $cedula)
    {
        try {
            $sql = "INSERT INTO FamiliaresEmpleado (
                EmpleadoID_SGO, Parentesco, Cedula, Nombre, fecha_nacimiento, genero, Edad, cedula_familiar
            ) VALUES (
                :EmpleadoID_SGO, :Parentesco, :Cedula, :nombres, :fechanacimiento, :genero, :edad, :cedula_familiar
            );";
            $val = 0;
            $errores = [];
            foreach ($familiares as $item) {
                if (trim($item["nombresApellidos"] ?? '') === '')
                    continue;
                $params = [
                    ":EmpleadoID_SGO" => $empleadoID,
                    ":Parentesco" => $item['parentesco'],
                    ":Cedula" => $item['cedula'],
                    ":nombres" => $item['nombresApellidos'],
                    ":fechanacimiento" => $item['fechaNacimiento'],
                    ":genero" => $item['genero'],
                    ":edad" => $item['edad'],
                    ":cedula_familiar" => $cedula
                ];
                if ($query = $this->db->execute($sql, $params)) {
                    $val++;
                } else {
                    $errores[] = $query;
                }
            }
            if ($val >= 0) {
                return [1, "Familiares guardados correctamente"];
            } else {
                return [1, "No se insertaron familiares (ninguno cumplía la condición)"];
            }
        } catch (PDOException $e) {
            return [0, "Error guardando familiares: " . $e->getMessage()];
        }
    }

    function GRABAR_REFERENCIAS($referencias, $empleadoID, $cedula)
    {
        try {
            $sql = "INSERT INTO ReferenciasLaborales (
                EmpleadoID_SGO, Nombre, Trabajo, Telefono , Cedula_padre
            ) VALUES (
                :EmpleadoID_SGO, :Nombre, :Trabajo, :Telefono , :cedulaEmpleado
            );";
            $val = 0;
            foreach ($referencias as $item) {
                if (trim($item["nombresApellidos"] ?? '') === '')
                    continue;
                $params = [
                    ":EmpleadoID_SGO" => $empleadoID,
                    ":Nombre" => $item['nombresApellidos'],
                    ":Trabajo" => $item['lugarTrabajo'],
                    ":Telefono" => $item['telefono'],
                    ":cedulaEmpleado" => $cedula
                ];
                if ($query = $this->db->execute($sql, $params)) {
                    $val++;
                } else {
                    $errores[] = $query;
                }
            }

            if ($val > 0) {
                return [1, "Referencias laborales guardadas correctamente"];
            } else {
                return [1, "No se insertaron referencias (ninguna cumplía la condición)"];
            }
        } catch (PDOException $e) {
            return [0, "Error guardando referencias: " . $e->getMessage()];
        }
    }

    function GRABAR_CONTACTOS($referencias, $empleadoID, $cedula)
    {
        try {
            $sql = "INSERT INTO ContactosEmpleados (
                        EmpleadoID_SGO, Nombre, Parentesco, Telefono, Cedula_padre, FechaCreacion
                    ) VALUES (
                    :EmpleadoID_SGO, :nombre, :parentesco, :telefono, :cedulaEmpleado, GETDATE()
                    )";
            $val = 0;
            foreach ($referencias as $item) {
                if (trim($item["nombresApellidos"] ?? '') === '')
                    continue;

                $params = [
                    ":EmpleadoID_SGO" => $empleadoID,
                    ":nombre" => $item['nombresApellidos'],
                    ":parentesco" => $item['parentesco'],
                    ":telefono" => $item['celular'],
                    ":cedulaEmpleado" => $cedula
                ];
                if ($query = $this->db->execute($sql, $params)) {
                    $val++;
                } else {
                    $errores[] = $query;
                }
            }
            if ($val > 0) {
                return [1, "Contactos laborales guardadas correctamente"];
            } else {
                return [1, "No se insertaron referencias (ninguna cumplía la condición)"];
            }
        } catch (PDOException $e) {
            return [0, "Error guardando referencias: " . $e->getMessage()];
        }
    }

    function actualizarDoc($pdf, $params){
        $documento = $pdf ?? null;
        $cedula = $params['datosPersonales']['cedula'] ?? null;
        $empresa = $params['userdata']['empleado_empresa'] ?? null;
        $sql = "UPDATE Empleados
        SET Documento = :documento
        WHERE Cedula = :cedula AND EMPRESA = :empresa";
        $param = [
            ":documento" => $documento,
            ":cedula" => $cedula,
            ":empresa" => $empresa
        ];
        $query = $this->db->execute($sql, $param);
        return $query;
    }
}
