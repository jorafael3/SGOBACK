<?php

// require_once __DIR__ . '/../logsmodel.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

// require_once('tcpdf/tcpdf.php');
// require('fpdf/fpdf.php');
// require_once('tcpdf/tcpdf.php');

class SolicitarVacacionesModel extends Model
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

    function Cargar_Vaciones_Pedidas($param)
    {
        try {
            // $EmpleadoID = $param['EmpleadoID'] ?? null;
            $EmpleadoID = $param['userdata']['EmpleadoID'] ?? null;
            $tipo = 1;
            $sql = "EXECUTE SGO_REC_VACACIONES_SOLICITADAS @empleado = :empleado, @tipo = :tipo";
            $params = [
                ":empleado" => $EmpleadoID,
                ':tipo' => $tipo
            ];
            $query = $this->db->query($sql, $params);
            return $query;
        } catch (Exception $e) {
            return ['error' => $e];
        }
    }

    function Guardar_Vacaciones_empleados($param)
    {
        try {
            // return $param;
            $DATOS = $param['DATOS'] ?? [];
            $DATOS_EMPLEADO = $param['DATOS_EMPLEADO'] ?? [];
            $SOLICITUD_ID = date("YmdHis");

            $EmpleadoID = $param['userdata']['EmpleadoID'] ?? null;

            $OK = 0;
            $ERRORES = [];

            foreach ($DATOS as $row) {
                $ID = $SOLICITUD_ID;
                
                $fecha_salida = $param['desde'];
                $fecha_fin = $param['fin'];
                $fecha_regreso_trabajo = $param['hasta'];
                
                $Dias = $row['solicitados'];
                $Total_pendiente = $row['Disponibles'];
                $Periodo = $row['Periodo'];
                $year = $row['year'];

                $sql = "EXECUTE SGO_EMP_VACIONES_INSERT_PERIODO_EMPLEADO 
                    @ID = :ID, 
                    @EmpleadoID = :EmpleadoID, 
                    @Dias = :Dias, 
                    @fecha_salida = :fecha_salida, 
                    @fecha_regreso = :fecha_regreso, 
                    @fecha_regreso_trabajo = :fecha_regreso_trabajo, 
                    @Total_pendiente = :Total_pendiente, 
                    @periodo = :periodo, 
                    @year = :year
                ";
                $params = [
                    ":ID" => $ID,
                    ":EmpleadoID" => $EmpleadoID,
                    ":Dias" => $Dias,
                    ":fecha_salida" => $fecha_salida,
                    ":fecha_regreso" => $fecha_fin,
                    ":fecha_regreso_trabajo" => $fecha_regreso_trabajo,
                    ":Total_pendiente" => $Total_pendiente,
                    ":periodo" => $Periodo,
                    ":year" => $year
                ];
                $query = $this->db->execute($sql, $params);
                if ($query['success']) {
                    $OK++;
                } else {
                    $ERRORES[] = $query;
                }
            }
            if (count($ERRORES) == 0) {
                $C = $this->enviar_correo_solicitud($SOLICITUD_ID, $DATOS_EMPLEADO);

                return $res = array(
                    "success" => true,
                    "data" => [],
                    "message" => "Datos Guardados",
                    "sql" => $query,
                    "param" => $param,
                    "CORREO" => $C
                );
            } else {
                return $res = array(
                    "success" => false,
                    "data" => $ERRORES,
                    "message" => "Error al guardar",
                    "sql" => $query,
                    "param" => $param
                );
            }
        } catch (Exception $e) {
            return ['error' => $e];
        }
    }

    function enviar_correo_solicitud($ID, $DATOS_EMPLEADO)
    {
        $error = 0;
        $mensaje = "";

        try {

            $message = "";
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'sgoinfocorreo@gmail.com';
            $mail->Password = 'csxj xbqb uncn yuuc';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            // correos para enviar 
            // $mail->addAddress('nadelaese@gmail.com');

            $mail->setFrom('sgoinfocorreo@gmail.com', 'Cartimex');
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';

            $sql = "SELECT
                SUM(CONVERT(int,v.Dias_solicitados)) as Dias_solicitados,
                v.Dia_inicio,
                v.Dia_fin,
                v.Dia_regreso,
                e.Nombre AS Empleado,
                E.Cédula,
                s.Nombre AS Departamento,
                e.email_personal,
                ep.PadreID,
                ep2.Nombre AS NombrePadre,
                ep2.email_personal AS EmailPadre,
                ep2.email AS EmailPadreempresa,
                -- V.Periodo,
                e.FechaIngreso 
                -- v.Dias_pendientes 
                FROM 
                SGO_VACACIONES_SOLICITADAS_EMPLEADOS V
                INNER JOIN 
                EMP_EMPLEADOS E ON v.EmpleadoID = e.ID
                INNER JOIN 
                EMP_EMPLEADOS EP ON E.PadreID = EP.PadreID AND V.EmpleadoID = Ep.ID
                INNER JOIN 
                EMP_EMPLEADOS ep2 ON E.PadreID = ep2.ID  
                INNER JOIN 
                SIS_DEPARTAMENTOS s ON E.DepartamentoID = s.ID
                WHERE 
                v.ID = :ID
                GROUP BY
                v.Dia_inicio,
                v.Dia_fin,
                v.Dia_regreso, 
                e.Nombre,
                E.Cédula,
                s.Nombre,
                e.email_personal,
                ep.PadreID,
                ep2.Nombre,
                ep2.email_personal,
                ep2.email,
                --V.Periodo,
                e.FechaIngreso 
                --v.Dias_pendientes 
            ";
            $params = [
                ":ID" => $ID
            ];
            $query = $this->db->query($sql, $params);
            if ($query['success']) {
                $result = $query['data'];

                $Empleado = $result[0]['Empleado'];
                // $EmailEmpleado = $result[0]['email_personal'];
                $PadreID = $result[0]['PadreID'];
                $NombrePadre = $result[0]['NombrePadre'];
                $EmailPadre = $result[0]['EmailPadre'];
                $EmailPadreEmpresa = $DATOS_EMPLEADO['email'];

                // Correo para el empleado
                $mail->Subject = 'Solicitud - Vacaciones -> ' . $Empleado;

                $mail->addAddress($EmailPadreEmpresa);
                // $mail->addAddress($EmailPadre);
                $mail->addAddress("jalvarado@cartimex.com");
                $mail->addAddress("sistema@cartimex.com");

                // Construir el mensaje del correo
                $mensaje = "
                <html>
                <head>
                    <style>
                        body {
                            font-family: 'Arial', sans-serif;
                            background-color: #f4f4f9;
                            margin: 0;
                            padding: 0;
                            color: #333;
                        }
                        .container {
                            width: 100%; /* Ajusta el ancho al 50% */
                            margin: 0 auto;
                            background-color: #fff;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                            border-radius: 8px;
                            overflow: hidden;
                        }
                        .header {
                            background-color: #4CAF50;
                            padding: 20px;
                            text-align: center;
                            color: white;
                        }
                        .header img {
                            width: 150px;
                            height: auto;
                        }
                        .content {
                            padding: 20px;
                        }
                        .content h1 {
                            font-size: 24px;
                            margin-bottom: 20px;
                            color: #4CAF50;
                        }
                        .content p {
                            font-size: 16px;
                            line-height: 1.6;
                            margin-bottom: 20px;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-bottom: 20px;
                        }
                        th, td {
                            padding: 12px;
                            text-align: left;
                            border: 1px solid #ddd;
                        }
                        th {
                            background-color: #f2f2f2;
                        }
                        .btn {
                            display: inline-block;
                            padding: 10px 20px;
                            background-color: #4CAF50;
                            color: white !important; /* Forzar el color blanco en el texto */
                            text-align: center;
                            text-decoration: none;
                            font-size: 16px;
                            margin: 10px 5px;
                            border-radius: 5px;
                            cursor: pointer;
                        }
                        .btn-danger {
                            background-color: #f44336;
                            color: white !important; /* Forzar el color blanco en el texto */
                        }
                        .footer {
                            padding: 20px;
                            text-align: center;
                            font-size: 14px;
                            color: #777;
                            background-color: #f4f4f9;
                            border-top: 1px solid #ddd;
                        }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <img src='https://www.cartimex.com/assets/img/logo200.png' alt='Cartimex Logo'>
                        </div>
                        <div class='content'>
                            <h1>Hola {$result[0]['NombrePadre']}</h1>
                            <p>Tu subordinado <strong>$Empleado</strong> ha solicitado vacaciones. Por favor, revisa la solicitud.</p>
                            <table>
                                <tr>
                                    <th>Total días solicitados</th>
                                    <th>Desde</th>
                                    <th>Hasta</th>
                                    <th>Fecha de Regreso al trabajo</th>
                                </tr>
                                <tr>
                                    <td>{$result[0]['Dias_solicitados']}</td> 
                                    <td>" . date('Y-m-d', strtotime($result[0]['Dia_inicio'])) . "</td>
                                    <td>" . date('Y-m-d', strtotime($result[0]['Dia_fin'])) . "</td>
                                    <td>" . date('Y-m-d', strtotime($result[0]['Dia_regreso'])) . "</td>
                                </tr>
                            </table>
                            <p>
                                <a href='http://nuevo.cartimex.com:82/SGO/Cartimex/vacacionesrecursos/solicitud/Enviar_aprobado?ID=" . $ID . "' class='btn'>Aprobar</a>
                                <a href='http://nuevo.cartimex.com:82/SGO/Cartimex/vacacionesrecursos/solicitud/rechazar_solicitud?ID=" . $ID . "' class='btn btn-danger'>Rechazar</a>
                            </p>
                        </div>
                        <div class='footer'>
                            &copy; " . date('Y') . " Cartimex. Todos los derechos reservados.
                        </div>
                    </div>
                </body>
                </html>";

                // Asignar el mensaje al cuerpo del correo
                $mail->Body = $mensaje;
                $mail->AltBody = 'Mensaje en texto plano'; // Opcional: mensaje alternativo en texto plano
                $mail->isHTML(true);
                // Enviar el correo al empleado
                if ($mail->Send()) {
                    $respuesta = "Correo enviado a tu jefe";
                    return array("ENVIAR_CORREO" => 1, "MENSAJE" => $respuesta);
                } else {
                    // Error al enviar el correo
                    $error = 'Error al enviar el correo: ' . $mail->ErrorInfo;
                    return array("ENVIAR_CORREO" => 0, "MENSAJE" => $error);
                }
            }
        } catch (Exception $u) {
            // Error general
            return ($mail->ErrorInfo);
        }
    }
}
