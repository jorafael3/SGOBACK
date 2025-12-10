<?php
require_once __DIR__ . '/../../libs/JwtHelper.php';




class AdministradorModel extends Model
{
    public function __construct($empresaCode = null)
    {
        // Usar la empresa por defecto del sistema si no se especifica
        parent::__construct($empresaCode);
    }




    function GetEmpleadosPorEmpresa($data = [])
    {
        try {


            $sql = "EXEC SGO_EMP_CARGAR_EMPLEADOS  @empresa = :empresa";

            $params = [

                ':empresa' => $data['empresa'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetEmpleadosPorEmpresa: " . $e->getMessage());
            error_log("Exception in GetEmpleadosPorEmpresa: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function getEmpleadoIndividual($data = [])
    {
        try {


            $sql = "EXEC SGO_EMP_CARGAR_EMPLEADOS_INDIVIDUAL  @empresa = :empresa ,  @cedula = :cedula";

            $params = [

                ':empresa' => $data['empresa'] ?? null,
                ':cedula' => $data['cedula'] ?? null,


            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en getEmpleadoIndividual: " . $e->getMessage());
            error_log("Exception in getEmpleadoIndividual: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


    function GetSolicitudesCargasFamiliares($data = [])
    {
        try {


            $sql = "SELECT E.Nombre as Solicitado_por , c.Nombres as carga , c.Cedula , c.Edad , c.Sexo
            , c.FechaNacimiento ,c.Archivo_Cedula  , c.EmpleadoID , c.TipoCarga
            FROM EMP_EMPLEADOS_CARGAS_PREVIA C
            inner join EMP_EMPLEADOS E ON C.EmpleadoID = E.ID
            WHERE Estado = 0";

            $params = [


            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetSolicitudesCargasFamiliares: " . $e->getMessage());
            error_log("Exception in GetSolicitudesCargasFamiliares: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function AprobarCargaFamiliar($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("AprobarCargaFamiliar - Datos recibidos: " . json_encode($data));

            $sql = "EXEC SGO_EMP_CARGAR_INSERT_APROBAR
                @empleadoId       = :empleadoId,
                @cedula           = :cedula,
                @edad             = :edad,
                @fechaNacimiento  = :fechaNacimiento,
                @nombres          = :nombres,
                @sexo             = :sexo,
                @usuario  = :usuario,
                @TipoCarga  = :TipoCarga";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':cedula' => $data['cedula'] ?? null,
                ':edad' => $data['edad'] ?? null,
                ':fechaNacimiento' => $data['fechaNacimiento'] ?? null,
                ':nombres' => $data['nombres'] ?? null,
                ':sexo' => $data['sexo'] ?? null,
                ':usuario' => $data['usuario'] ?? null,
                ':TipoCarga' => $data['TipoCarga'] ?? null,
            ];

            error_log("AprobarCargaFamiliar - SQL: " . $sql);
            error_log("AprobarCargaFamiliar - Params: " . json_encode($params));

            $stmt = $this->query($sql, $params);

            error_log("AprobarCargaFamiliar - Resultado exitoso");
            error_log("AprobarCargaFamiliar - Respuesta: " . json_encode($stmt));

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Carga familiar aprobada exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];

            $this->logError("Error en AprobarCargaFamiliar: " . json_encode($errorDetails));
            error_log("=== ERROR DETALLADO en AprobarCargaFamiliar ===");
            error_log("Mensaje: " . $e->getMessage());
            error_log("Código: " . $e->getCode());
            error_log("Archivo: " . $e->getFile() . " (Línea: " . $e->getLine() . ")");
            error_log("Datos enviados: " . json_encode($data));
            error_log("Stack trace: " . $e->getTraceAsString());
            error_log("=== FIN ERROR DETALLADO ===");

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }




    function RechazarCargaFamiliar($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("RechazarCargaFamiliar - Datos recibidos: " . json_encode($data));

            $sql = "DELETE FROM EMP_EMPLEADOS_CARGAS_PREVIA WHERE EmpleadoID = :empleadoId     
            and Cedula = :cedula and Nombres = :nombres";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':cedula' => $data['cedula'] ?? null,
                ':nombres' => $data['nombres'] ?? null,
            ];

            $stmt = $this->query($sql, $params);

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Carga familiar rechazada exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }




    function GetSolicitudesEnfermedades($data = [])
    {
        try {


            $sql = "SELECT e.Nombre as Empleado , e.Cédula as Cedula , ef.alergias
            , ef.contactoEmergenciaNombre , ef.contactoEmergenciaRelacion,
            ef.contactoEmergenciaTelefono , ef.enfermedades , ef.Editado 
            , ef.tieneAlergia , ef.tieneEnfermedad , ef.tieneDiscapacidad , ef.porcentajeDiscapacidad ,
            ef.tipoDiscapacidad , ef.archivoDiscapacidadNombre , Tipo_solicitud   , ef.EmpleadoID 
            from SGO_EMP_DATOS_MEDICOS_EMPLEADOS Ef 
            inner join EMP_EMPLEADOS e on ef.EmpleadoID = e.ID
            where Ef.Estado = 0";

            $params = [


            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetSolicitudesEnfermedades: " . $e->getMessage());
            error_log("Exception in GetSolicitudesEnfermedades: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function AprobarDatosMedicos($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("AprobarDatosMedicos - Datos recibidos: " . json_encode($data));

            $sql = "UPDATE SGO_EMP_DATOS_MEDICOS_EMPLEADOS SET Estado = 1
            where EmpleadoID = :empleadoId ";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
            ];

            $stmt = $this->query($sql, $params);


            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Carga familiar aprobada exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];

            $this->logError("Error en AprobarCargaFamiliar: " . json_encode($errorDetails));

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }



    function RechazarDatosMedicos($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("RechazarDatosMedicos - Datos recibidos: " . json_encode($data));

            $sql = "DELETE FROM SGO_EMP_DATOS_MEDICOS_EMPLEADOS WHERE EmpleadoID = :empleadoId";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
            ];

            $stmt = $this->query($sql, $params);

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Datos medicos rechazados exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }




    function GetSolicitudesDatosPersonales($data = [])
    {
        try {


            $sql = "SGO_EMP_DATOS_PERSONALES_APROBAR @empresa = :empresa";

            $params = [

                ':empresa' => $data['empresa'] ?? null,

            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetSolicitudesDatosPersonales: " . $e->getMessage());
            error_log("Exception in GetSolicitudesDatosPersonales: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function AprobarDatosPersonales($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("AprobarDatosPersonales - Datos recibidos: " . json_encode($data));

            $sql = "EXEC SGO_EMP_APROBAR_DATOS
                        
            @empleadoId = :empleadoId,
            @direccion = :direccion,
            @email = :email,
            @estadoCivil = :estadoCivil,
            @telefono = :telefono,
            @usuario = :usuario,
            @empresa = :empresa";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':direccion' => $data['direccion'] ?? null,
                ':email' => $data['email'] ?? null,
                ':estadoCivil' => $data['estadoCivil'] ?? null,
                ':telefono' => $data['telefono'] ?? null,
                ':usuario' => $data['usuario'] ?? null,
                ':empresa' => $data['empresa'] ?? null,
            ];

            error_log("AprobarDatosPersonales - SQL: " . $sql);
            error_log("AprobarDatosPersonales - Params: " . json_encode($params));

            $stmt = $this->query($sql, $params);

            error_log("AprobarDatosPersonales - Resultado exitoso");
            error_log("AprobarDatosPersonales - Respuesta: " . json_encode($stmt));

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Datos personales aprobados exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];

            $this->logError("Error en AprobarCargaFamiliar: " . json_encode($errorDetails));

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }




    function RechazarDatosPersonales($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("RechazarDatosPersonales - Datos recibidos: " . json_encode($data));

            $sql = "DELETE FROM SGO_EMPLEADOS_VALIDACIONES 
            WHERE EmpleadoID = :empleadoId";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
            ];

            $stmt = $this->query($sql, $params);

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Datos medicos rechazados exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            // Log detallado del error
            $errorDetails = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errorDetails' => $errorDetails,
                'dataSent' => $data
            ];
        }
    }



    function GetSolicitudesEstudios($data = [])
    {
        try {


            $sql = "SELECT ep.Nombre , es.EmpleadoID , es.institucion , es.titulo
            , es.anio , es.titulo_pdf as Documento , es.Tipo_solicitud ,
            ep.Código as Cedula
            from SGO_EMP_EMPLEADOS_ESTUDIOS es 
            inner join EMP_EMPLEADOS ep on es.EmpleadoID = ep.ID
            where es.Aprobado = 0";

            $params = [


            ];

            $stmt = $this->query($sql, $params);


            return $stmt;

        } catch (Exception $e) {
            $this->logError("Error en GetSolicitudesEstudios: " . $e->getMessage());
            error_log("Exception in GetSolicitudesEstudios: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }




    function AprobarEstudio($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("AprobarEstudio - Datos recibidos: " . json_encode($data));

            $sql = "UPDATE SGO_EMP_EMPLEADOS_ESTUDIOS set Aprobado = 1 , Aprobado_por = :usuario 
            , Aprobado_date = GETDATE() 
            where EmpleadoID = :empleadoId and titulo = :titulo and institucion = :institucion";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':usuario' => $data['usuario'] ?? null,
                ':titulo' => $data['titulo'] ?? null,
                ':institucion' => $data['institucion'] ?? null

            ];

            error_log("AprobarEstudio - SQL: " . $sql);
            error_log("AprobarEstudio - Params: " . json_encode($params));

            $stmt = $this->query($sql, $params);

            error_log("AprobarEstudio - Resultado exitoso");

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Estudio aprobado exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            $this->logError("Error en AprobarEstudio: " . $e->getMessage());
            error_log("Exception in AprobarEstudio: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    function RechazarEstudio($data = [])
    {
        try {

            // Log de los datos recibidos para debugging
            error_log("RechazarEstudio - Datos recibidos: " . json_encode($data));

            $sql = "DELETE FROM SGO_EMP_EMPLEADOS_ESTUDIOS 
            where EmpleadoID = :empleadoId and titulo = :titulo and institucion = :institucion";

            $params = [

                ':empleadoId' => $data['empleadoId'] ?? null,
                ':institucion' => $data['institucion'] ?? null,
                ':titulo' => $data['titulo'] ?? null

            ];

            error_log("RechazarEstudio - SQL: " . $sql);
            error_log("RechazarEstudio - Params: " . json_encode($params));

            $stmt = $this->query($sql, $params);

            error_log("RechazarEstudio - Resultado exitoso");

            // Retornar formato esperado por el controlador
            return [
                'success' => true,
                'message' => 'Estudio rechazado exitosamente',
                'data' => $stmt
            ];

        } catch (Exception $e) {
            $this->logError("Error en RechazarEstudio: " . $e->getMessage());
            error_log("Exception in RechazarEstudio: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }


}
