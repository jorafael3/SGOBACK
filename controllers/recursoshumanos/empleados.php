<?php

require_once __DIR__ . '/../../libs/JwtHelper.php';
require_once __DIR__ . '../../../../libs/EmailService.php';
class Empleados extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->folder = 'recursoshumanos/';
        $this->loadModel('empleados');
    }

    /**
     * Obtener datos personales del empleado
     */
    function GetDatosPersonales()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->getDatosPersonales($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener datos personales',
                'details' => $result
            ], 200);
        }
    }

    /**
     * Obtener cargas familiares del empleado
     */
    function GetCargasFamiliares()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetCargasFamiliares($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener cargas familiares',
                'details' => $result
            ], 200);
        }
    }

    /**
     * Obtener vacaciones del empleado
     */
    function GetVacaciones()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetVacaciones($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener vacaciones',
                'details' => $result
            ], 200);
        }
    }


    function SolicitudActualizacionDatos()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->SolicitudActualizacionDatos($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener vacaciones',
                'details' => $result
            ], 200);
        }
    }



    function ActualizarDatosPersonales()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }

        // Detectar si es FormData (con archivos) o JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isFormData = stripos($contentType, 'multipart/form-data') !== false;

        // Si es FormData, usar $_POST; si es JSON, usar getJsonInput()
        if ($isFormData) {
            $data = $_POST;
            $empleadoId = $_POST['empleadoId'] ?? null;
        } else {
            $data = $this->getJsonInput();
            $empleadoId = $data['empleadoId'] ?? null;
        }

        if (!$empleadoId) {
            $this->jsonResponse(['success' => false, 'error' => 'Falta el ID del empleado'], 400);
            return;
        }

        $fileName = null;

        // Verificar si se subió un archivo de documento_estado_civil (solo en FormData)
        if ($isFormData && isset($_FILES['documento_estado_civil']) && $_FILES['documento_estado_civil']['error'] === UPLOAD_ERR_OK) {

            // Ruta base
            $SO = PHP_OS;
            if (stripos($SO, 'Linux') !== false) {
                $baseUpload = '/var/www/html/sgo_docs/Cartimex/recursoshumanos/documento_estado_civil';
            } else {
                $baseUpload = 'C:\xampp\htdocs\sgo_docs\Cartimex\recursoshumanos\documento_estado_civil';
            }

            // Crear carpeta si no existe
            if (!file_exists($baseUpload)) {
                mkdir($baseUpload, 0777, true);
            }

            $file = $_FILES['documento_estado_civil'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Nombre final del archivo: EmpleadoID_TIMESTAMP.ext
            $fileName = $empleadoId . "_" . time() . "." . $extension;
            $targetPath = $baseUpload . DIRECTORY_SEPARATOR . $fileName;

            // Mover el archivo
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $this->jsonResponse(['success' => false, 'error' => 'Error al mover el archivo subido'], 500);
                return;
            }
        } else if (isset($data['documento_estado_civil_nombre']) && !empty($data['documento_estado_civil_nombre'])) {
            // Si no se sube archivo pero se quiere mantener uno existente
            $fileName = $data['documento_estado_civil_nombre'];
        }

        // Agregar el nombre del archivo a los datos
        $data['documento_estado_civil'] = $fileName;

        $result = $this->model->ActualizarDatosPersonales($data);

        if ($result && $result['success']) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Datos personales actualizados correctamente',
                'fileName' => $fileName,
                'data' => $result
            ], 200);
        } else {
            // Si falla la BD y se subió archivo, eliminamos el archivo huérfano
            if ($fileName && isset($targetPath) && file_exists($targetPath)) {
                unlink($targetPath);
            }
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al actualizar datos personales',
                'details' => $result
            ], 500);
        }
    }




    function ActualizarCargasEmpleado()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        // Para multipart/form-data, los datos vienen en $_POST
        $empleadoId = $_POST['empleadoId'] ?? null;
        $nombres = $_POST['nombres'] ?? null;
        $cedula = $_POST['cedula'] ?? null;
        $tipoCarga = $_POST['tipoCarga'] ?? null;
        $sexo = $_POST['sexo'] ?? null;
        $fechaNacimiento = $_POST['fechaNacimiento'] ?? null;

        // Convertir fecha de YYYY-MM-DD a YYYYMMDD
        if ($fechaNacimiento) {
            $fechaNacimiento = str_replace('-', '', $fechaNacimiento);
        }

        $edad = $_POST['edad'] ?? null;
        $creadoPor = $_POST['Creado_Por'] ?? $jwtData['username'] ?? 'SISTEMA';

        if (!$empleadoId) {
            $this->jsonResponse(['success' => false, 'error' => 'Falta el ID del empleado'], 400);
            return;
        }

        $fileName = null;

        // Verificar si se subió un archivo PDF
        if (isset($_FILES['documento_carga']) && $_FILES['documento_carga']['error'] === UPLOAD_ERR_OK) {

            // Ruta base
            $SO = PHP_OS;
            if (stripos($SO, 'Linux') !== false) {
                $baseUpload = '/var/www/html/sgo_docs/Cartimex/recursoshumanos/cargas_empleado';
            } else {
                $baseUpload = 'C:\xampp\htdocs\sgo_docs\Cartimex\recursoshumanos\cargas_empleado';
            }

            // Crear carpeta si no existe
            if (!file_exists($baseUpload)) {
                mkdir($baseUpload, 0777, true);
            }

            $file = $_FILES['documento_carga'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Nombre final del archivo: EmpleadoID_TIMESTAMP.ext
            // Usamos un nombre seguro para evitar caracteres extraños de la cédula si viniera
            $fileName = $empleadoId . "_" . time() . "." . $extension;
            $targetPath = $baseUpload . DIRECTORY_SEPARATOR . $fileName;

            // Mover el archivo
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $this->jsonResponse(['success' => false, 'error' => 'Error al mover el archivo subido'], 500);
                return;
            }
        } else if (isset($_POST['nombreArchivo']) && !empty($_POST['nombreArchivo'])) {
            // Si no se sube archivo pero se quiere mantener uno existente (lógica opcional)
            $fileName = $_POST['nombreArchivo'];
        }

        // Preparar datos para el modelo
        $cargaData = [
            'empleadoId' => $empleadoId,
            'Nombres' => $nombres,
            'cedula' => $cedula,
            'TipoCarga' => $tipoCarga,
            'Sexo' => $sexo,
            'FechaNacimiento' => $fechaNacimiento,
            'Edad' => $edad,
            'Creado_Por' => $creadoPor,
            'documento_carga' => $fileName
        ];

        // Llamar al modelo
        $result = $this->model->ActualizarCargasEmpleado($cargaData);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Carga familiar y documento guardados correctamente',
                'fileName' => $fileName,
                'data' => $result
            ], 200);
        } else {
            // Si falla la BD y se subió archivo, eliminamos el archivo huérfano
            if ($fileName && isset($targetPath) && file_exists($targetPath)) {
                unlink($targetPath);
            }
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar la carga familiar: ' . ($result['error'] ?? 'Desconocido'),
                'details' => $result
            ], 500);
        }
    }





    function ActualizarEstudios()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        // Para multipart/form-data, los datos vienen en $_POST y los archivos en $_FILES
        $empleadoId = $_POST['empleadoId'] ?? null;
        $titulo = $_POST['titulo'] ?? null;
        $institucion = $_POST['institucion'] ?? null;
        $anio = $_POST['anio'] ?? null;
        $creadoPor = $jwtData['username'] ?? 'SISTEMA';

        if (!$empleadoId) {
            $this->jsonResponse(['success' => false, 'error' => 'Falta el ID del empleado'], 400);
            return;
        }

        $fileName = null;

        // Verificar si se subió un archivo PDF
        if (isset($_FILES['titulo_pdf']) && $_FILES['titulo_pdf']['error'] === UPLOAD_ERR_OK) {

            // Ruta base
            $SO = PHP_OS;
            if (stripos($SO, 'Linux') !== false) {
                $baseUpload = '/var/www/html/sgo_docs/Cartimex/recursoshumanos/titulo_empleado';
            } else {
                $baseUpload = 'C:\xampp\htdocs\sgo_docs\Cartimex\recursoshumanos\titulo_empleado';
            }

            // Crear carpeta si no existe
            if (!file_exists($baseUpload)) {
                mkdir($baseUpload, 0777, true);
            }

            $file = $_FILES['titulo_pdf'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Nombre final del archivo: EmpleadoID_TIMESTAMP.ext
            $fileName = $empleadoId . "_" . time() . "." . $extension;
            $targetPath = $baseUpload . DIRECTORY_SEPARATOR . $fileName;

            // Mover el archivo
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $this->jsonResponse(['success' => false, 'error' => 'Error al mover el archivo subido'], 500);
                return;
            }
        }

        // Preparar datos para el modelo
        $estudioData = [
            'empleadoId' => $empleadoId,
            'titulo' => $titulo,
            'institucion' => $institucion,
            'anio' => $anio,
            'titulo_pdf' => $fileName,
            'Creado_Por' => $creadoPor
        ];

        // Llamar al modelo
        $result = $this->model->ActualizarEstudios($estudioData);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Estudio guardado correctamente',
                'fileName' => $fileName
            ], 200);
        } else {
            // Si falla la BD y se subió archivo, podríamos eliminarlo
            if ($fileName && file_exists($targetPath)) {
                unlink($targetPath);
            }
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al guardar el estudio: ' . ($result['error'] ?? 'Desconocido')
            ], 500);
        }
    }



    function GetCargasEstudios()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->GetCargasEstudios($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener cargas estudios',
                'details' => $result
            ], 200);
        }
    }




    function ActualizarEnfermedades()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        // Para multipart/form-data, leer de $_POST
        // Intentar leer ambas variantes por si acaso
        $empleadoId = $_POST['empleadoId'] ?? $_POST['EmpleadoID'] ?? null;
        $creadoPor = $jwtData['username'] ?? 'SISTEMA';

        if (!$empleadoId) {
            // DEBUG: Devolver qué está llegando para diagnosticar
            $debugInfo = [
                'POST_KEYS' => array_keys($_POST),
                'FILES_KEYS' => array_keys($_FILES),
                'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? 'N/A'
            ];
            $this->jsonResponse(['success' => false, 'error' => 'EmpleadoID es obligatorio', 'debug' => $debugInfo], 400);
            return;
        }

        // Manejo del archivo de discapacidad
        $fileName = null;
        if (isset($_FILES['archivoDiscapacidad']) && $_FILES['archivoDiscapacidad']['error'] === UPLOAD_ERR_OK) {

            $SO = PHP_OS;
            if (stripos($SO, 'Linux') !== false) {
                $baseUpload = '/var/www/html/sgo_docs/Cartimex/recursoshumanos/discapacidad';
            } else {
                $baseUpload = 'C:\xampp\htdocs\sgo_docs\Cartimex\recursoshumanos\discapacidad';
            }

            if (!file_exists($baseUpload)) {
                mkdir($baseUpload, 0777, true);
            }

            $file = $_FILES['archivoDiscapacidad'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            // Nombre seguro: EmpleadoID_TIMESTAMP.ext
            $fileName = $empleadoId . "_" . time() . "." . $extension;
            $targetPath = $baseUpload . DIRECTORY_SEPARATOR . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $this->jsonResponse(['success' => false, 'error' => 'Error al mover el archivo de discapacidad'], 500);
                return;
            }
        } elseif (isset($_POST['archivoDiscapacidadNombre']) && !empty($_POST['archivoDiscapacidadNombre'])) {
            // Mantener archivo existente si viene el nombre
            $fileName = $_POST['archivoDiscapacidadNombre'];
        }

        // Construir datos para el modelo
        $datos = [
            'empleadoId' => $empleadoId,
            'alergias' => $_POST['alergias'] ?? '',
            'contactoEmergenciaNombre' => $_POST['contactoEmergenciaNombre'] ?? '',
            'contactoEmergenciaRelacion' => $_POST['contactoEmergenciaRelacion'] ?? '',
            'contactoEmergenciaTelefono' => $_POST['contactoEmergenciaTelefono'] ?? '',
            'enfermedades' => $_POST['enfermedades'] ?? '',
            'tieneAlergia' => $_POST['tieneAlergia'] ?? 'NO',
            'tieneEnfermedad' => $_POST['tieneEnfermedad'] ?? 'NO',
            'tieneDiscapacidad' => $_POST['tieneDiscapacidad'] ?? 'NO',
            'porcentajeDiscapacidad' => $_POST['porcentajeDiscapacidad'] ?? 0,
            'tipoDiscapacidad' => $_POST['tipoDiscapacidad'] ?? '',
            'archivoDiscapacidadNombre' => $fileName,
            'Editado' => $_POST['Editado'] ?? 0,
            'Creado_Por' => $creadoPor
        ];

        // Llamar modelo
        $result = $this->model->ActualizarEnfermedades($datos);

        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Datos médicos actualizados correctamente',
                'fileName' => $fileName,
                'result' => [$result]
            ]);
        } else {
            // Eliminar archivo si falla BD
            if ($fileName && isset($targetPath) && file_exists($targetPath)) {
                unlink($targetPath);
            }
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al guardar datos médicos',
                'errors' => [['error' => $result['error'] ?? 'Error desconocido']]
            ]);
        }
    }



    function Getenfermedades()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->Getenfermedades($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener cargas estudios',
                'details' => $result
            ], 200);
        }
    }


    function ConsultarRolesPago()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->ConsultarRolesPago($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al obtener roles de pago',
                'details' => $result
            ], 200);
        }
    }




    function DescargarRolPago()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $data = $this->getJsonInput();
        $rolId = $data['rolId'] ?? null;

        if (!$rolId) {
            return $this->jsonResponse(['error' => 'rolId requerido'], 400);
        }

        // Generar PDF
        $result = $this->model->generarPDF($rolId);

        if ($result['success']) {
            // Devolver PDF directamente al navegador
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="ROL_' . $result['rolId'] . '.pdf"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            echo $result['pdfData'];
            exit; // Importante: detener la ejecución para no agregar contenido adicional
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error'] ?? 'Error desconocido al generar PDF'
            ], 500);
        }
    }




    function ActualizarPassword()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData) {
            return;
        }
        $data = $this->getJsonInput();

        $result = $this->model->ActualizarPassword($data);

        if ($result && $result['success']) {
            $this->jsonResponse($result, 200);
        } else {
            $this->jsonResponse([
                'success' => false,
                'error' => $result['error'] ?? 'Error al actualizar la contraseña',
                'details' => $result
            ], 200);
        }
    }




    function SubirFotoPerfil()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;

        $empleadoId = $_POST['empleadoId'] ?? null;

        if (!$empleadoId) {
            $this->jsonResponse(['success' => false, 'error' => 'Falta el ID del empleado'], 400);
            return;
        }

        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonResponse(['success' => false, 'error' => 'No se ha subido ningún archivo o hubo un error'], 400);
            return;
        }

        // Ruta base
        $SO = PHP_OS;
        if (stripos($SO, 'Linux') !== false) {
            $baseUpload = '/var/www/html/sgo_docs/Cartimex/recursoshumanos/foto_empleados_perfil';
        } else {
            $baseUpload = 'C:\xampp\htdocs\sgo_docs\Cartimex\recursoshumanos\foto_empleados_perfil';
        }

        // Crear carpeta si no existe
        if (!file_exists($baseUpload)) {
            mkdir($baseUpload, 0777, true);
        }

        $file = $_FILES['foto'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Nombre final del archivo: EmpleadoID.ext
        $fileName = $empleadoId . "." . $extension;
        $targetPath = $baseUpload . DIRECTORY_SEPARATOR . $fileName;

        // 🔥 1. Buscar archivos anteriores del mismo empleado
        $pattern = $baseUpload . DIRECTORY_SEPARATOR . $empleadoId . '.*';
        $archivosExistentes = glob($pattern);

        // 🔥 2. Eliminar archivos previos si existen
        if (!empty($archivosExistentes)) {
            foreach ($archivosExistentes as $archivo) {
                unlink($archivo);
            }
        }

        // 🔥 3. Guardar el nuevo archivo
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {

            // Actualizar la BD con el nuevo nombre
            $data = [
                'empleadoId' => $empleadoId,
                'name' => $fileName
            ];

            $result = $this->model->SubirFotoPerfil($data);

            if ($result['success']) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Foto actualizada correctamente',
                    'fileName' => $fileName
                ], 200);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'error' => 'Archivo subido pero error al actualizar BD'
                ], 500);
            }
        } else {
            $this->jsonResponse(['success' => false, 'error' => 'Error al mover el archivo subido'], 500);
        }
    }




    function ActualizarEstudios2()
    {
        $jwtData = $this->authenticateAndConfigureModel(2);
        if (!$jwtData)
            return;
        // Para multipart/form-data, los datos vienen en $_POST y los archivos en $_FILES
        $empleadoId = $_POST['empleadoId'] ?? null;
        $estudioId = $_POST['estudioId'] ?? null; // ID del estudio a actualizar
        $titulo = $_POST['titulo'] ?? null;
        $institucion = $_POST['institucion'] ?? null;
        $anio = $_POST['anio'] ?? null;
        $modificadoPor = $_POST['Modificado_Por'] ?? $jwtData['username'] ?? 'SISTEMA';
        $esActualizacion = $_POST['esActualizacion'] ?? 'false';
        if (!$empleadoId) {
            $this->jsonResponse(['success' => false, 'error' => 'Falta el ID del empleado'], 400);
            return;
        }
        $fileName = null;
        // CASO 1: Se subió un archivo nuevo
        if (isset($_FILES['titulo_pdf']) && $_FILES['titulo_pdf']['error'] === UPLOAD_ERR_OK) {
            // Ruta base
            $SO = PHP_OS;
            if (stripos($SO, 'Linux') !== false) {
                $baseUpload = '/var/www/html/sgo_docs/Cartimex/recursoshumanos/titulo_empleado';
            } else {
                $baseUpload = 'C:\xampp\htdocs\sgo_docs\Cartimex\recursoshumanos\titulo_empleado';
            }
            // Crear carpeta si no existe
            if (!file_exists($baseUpload)) {
                mkdir($baseUpload, 0777, true);
            }
            $file = $_FILES['titulo_pdf'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            // Nombre final del archivo: EmpleadoID_TIMESTAMP.ext
            $fileName = $empleadoId . "_" . time() . "." . $extension;
            $targetPath = $baseUpload . DIRECTORY_SEPARATOR . $fileName;
            // Mover el archivo
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $this->jsonResponse(['success' => false, 'error' => 'Error al mover el archivo subido'], 500);
                return;
            }
        }
        // CASO 2: No se subió archivo nuevo, pero existe un PDF actual (mantener el existente)
        else if (isset($_POST['titulo_pdf_nombre']) && !empty($_POST['titulo_pdf_nombre'])) {
            $fileName = $_POST['titulo_pdf_nombre'];
            // No se sube archivo, solo se mantiene el nombre del PDF existente
        }
        // Preparar datos para el modelo
        $estudioData = [
            'empleadoId' => $empleadoId,
            'estudioId' => $estudioId,
            'titulo' => $titulo,
            'institucion' => $institucion,
            'anio' => $anio,
            'titulo_pdf' => $fileName,
            'Modificado_Por' => $modificadoPor,
            'esActualizacion' => $esActualizacion
        ];
        // Llamar al modelo
        $result = $this->model->ActualizarEstudios2($estudioData);
        if ($result['success']) {
            $this->jsonResponse([
                'success' => true,
                'message' => 'Estudio actualizado correctamente',
                'fileName' => $fileName
            ], 200);
        } else {
            // Si falla la BD y se subió archivo nuevo, eliminarlo
            if ($fileName && isset($targetPath) && file_exists($targetPath)) {
                unlink($targetPath);
            }
            $this->jsonResponse([
                'success' => false,
                'error' => 'Error al actualizar el estudio: ' . ($result['error'] ?? 'Desconocido')
            ], 500);
        }
    }

}
