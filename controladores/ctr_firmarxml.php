<?php
set_time_limit(300);
require_once './ctr_funciones.php';
require_once './ctr_pdf.php';
require_once '../lib/nusoap.php';
//require_once 'ruth_esmeralda_sanchez_herrera.p12';
//if (isset($_POST['submit'])) {
class autorizar{
   public function autorizar_xml($fecha,$correo){

        $firma = 'MARIA_AUGUSTA_VEGA_MERA.p12';
        $clave = 'fovyaruqui18';
        if (!$almacen_cert = file_get_contents($firma)) {
            echo "Error: No se puede leer el fichero del certificado\n";
            exit;
        }
        if (openssl_pkcs12_read($almacen_cert, $info_cert, $clave)) {
            $func = new fac_ele();
            $vtipoambiente=1;
            $wsdls = $func->wsdl($vtipoambiente);
            $recepcion = $wsdls['recepcion'];        
            $autorizacionws = $wsdls['autorizacion'];
            //RUTAS PARA LOS ARCHIVOS XML
            $ruta_no_firmados = 'C:\\xampp\\htdocs\\facturacionphp\\comprobantes\\no_firmados\\prueba.xml';
            $ruta_si_firmados = 'C:\\xampp\\htdocs\\facturacionphp\\comprobantes\\si_firmados\\';
            $ruta_autorizados = 'C:\\xampp\\htdocs\\facturacionphp\\comprobantes\\autorizados\\';
            $pathPdf = 'C:\\xampp\\htdocs\\facturacionphp\\comprobantes\\pdf\\';
            $tipo='FV';
            $nuevo_xml = 'prueba.xml';
            $controlError = false;
            $m = '';
            $show = '';
            //VERIFICAMOS SI EXISTE EL XML NO FIRMADO CREADO
            if (file_exists($ruta_no_firmados)) {
                $argumentos = $ruta_no_firmados . ' ' . $ruta_si_firmados . ' ' . $nuevo_xml . ' ' . $firma . ' ' . $clave;
                //FIRMA EL XML 
                $comando = ('java -jar C:\\Comprobantes\\firmaComprobanteElectronico\\dist\\firmaComprobanteElectronico.jar ' . $argumentos);
                $resp = shell_exec($comando);
                $claveAcces = simplexml_load_file($ruta_si_firmados . $nuevo_xml);
            	$claveAcceso['claveAccesoComprobante'] = substr($claveAcces->infoTributaria[0]->claveAcceso, 0, 49);
            	var_dump($claveAcceso);
                var_dump($comando);
                var_dump($resp);
                switch (substr($resp, 0, 7)) {
                    case 'FIRMADO':
                        $xml_firmado = file_get_contents($ruta_si_firmados . $nuevo_xml);
                        $data['xml'] = base64_encode($xml_firmado);
                        try {
                            $client = new nusoap_client($recepcion, true);
                            $client->soap_defencoding = 'utf-8';
                            $client->xml_encoding = 'utf-8';
                            $client->decode_utf8 = false;
                            $response = $client->call('validarComprobante', $data);
                            //var_dump($response);
                            //echo 'COMPROBANTE FIRMADO<br>';
                        } catch (Exception $e) {
                            echo "Error!<br />";
                            echo $e->getMessage();
                            echo 'Last response: ' . $client->response . '<br />';
                        }
                        switch ($response["RespuestaRecepcionComprobante"]["estado"]) {
                            case 'RECIBIDA':
                                //echo $response["RespuestaRecepcionComprobante"]["estado"] . '<br>';
                                $client = new nusoap_client($autorizacionws, true);
                                $client->soap_defencoding = 'utf-8';
                                $client->xml_encoding = 'utf-8';
                                $client->decode_utf8 = false;
                                try{
                                  $responseAut = $client->call('autorizacionComprobante', $claveAcceso);
                                  }catch(Exception $e) {
                                  echo "Error!<br>";
                                  echo $e->getMessage();
                                  echo 'Last response: ' . $client->response . '<br />';
                                }
                                switch ($responseAut['RespuestaAutorizacionComprobante']['autorizaciones']['autorizacion']['estado']) {
                                    case 'AUTORIZADO':
                                        $autorizacion = $responseAut['RespuestaAutorizacionComprobante']['autorizaciones']['autorizacion'];
                                        $estado = $autorizacion['estado'];
                                        $numeroAutorizacion = $autorizacion['numeroAutorizacion'];
                                        $fechaAutorizacion = $autorizacion['fechaAutorizacion'];
                                        $comprobanteAutorizacion = $autorizacion['comprobante'];
                                        echo '<script>alert("COMPROBANTE AUTORIZADO Y ENVIADO AL CORREO");location.href="../vistas/index.php";</script>';
                                        //echo '<script>alert(Comprobante AUTORIZADO y enviado con exito con autoricacion N° '.$numeroAutorizacion.');</script>';
                                        $vfechaauto = substr($fechaAutorizacion, 0, 10) . ' ' . substr($fechaAutorizacion, 11, 5);
                                        //echo 'Xml ' .
                                        $func->crearXmlAutorizado($estado, $numeroAutorizacion, $fechaAutorizacion, $comprobanteAutorizacion, $ruta_autorizados, $nuevo_xml);
                                        $pdf = new pdf();
                                        $pdf->pdfFactura($correo);
                                        $func->correos($correo);                                    
                                        //unlink($ruta_si_firmados . $nuevo_xml);
                                       //require_once './funciones/factura_pdf.php';
                                        //var_dump($func);
                                    break;
                                    case 'EN PROCESO':
                                        echo "El comprobante se encuentra EN PROCESO:<br>";
                                        echo $responseAut['RespuestaAutorizacionComprobante']['autorizaciones']['autorizacion']['estado'] . '<br>';
                                        $m .= 'El documento se encuentra en proceso<br>';
                                        $controlError = true;
                                    break;
                                    default:
                                        if ($responseAut['RespuestaAutorizacionComprobante']['numeroComprobantes'] == "0") {
                                            echo 'No autorizado</br>';
                                            echo 'No se encontro informacion del comprobante en el SRI, vuelva an enviarlo.</br>';
                                        } else if ($responseAut['RespuestaAutorizacionComprobante']['numeroComprobantes'] == "1") {
                                            echo $responseAut['RespuestaAutorizacionComprobante']["autorizaciones"]["autorizacion"]["estado"].'</br>';
                                            echo $responseAut['RespuestaAutorizacionComprobante']["autorizaciones"]["autorizacion"]["mensajes"]["mensaje"]["mensaje"].'</br>';
                                            if(isset($responseAut['RespuestaAutorizacionComprobante']["autorizaciones"]["autorizacion"]["mensajes"]["mensaje"]["mensaje"]["informacionAdicional"])){
                                                echo $responseAut['RespuestaAutorizacionComprobante']["autorizaciones"]["autorizacion"]["mensajes"]["mensaje"]["mensaje"]["informacionAdicional"].'</br>';
                                                $ms = $responseAut['RespuestaAutorizacionComprobante']["autorizaciones"]["autorizacion"]["mensajes"]["mensaje"]["mensaje"].' => '.
                                                        $responseAut['RespuestaAutorizacionComprobante']["autorizaciones"]["autorizacion"]["mensajes"]["mensaje"]["mensaje"]["informacionAdicional"];
                                            }else{
                                                $ms = $responseAut['RespuestaAutorizacionComprobante']["autorizaciones"]["autorizacion"]["mensajes"]["mensaje"]["mensaje"];
                                            }
                                            //BORRAR EL VAR_DUMP 
                                            echo '<br/><br/>'.var_dump($responseAut).'<br/><br/>';
                                        } else {
                                            echo 'No autorizado<br/>';
                                            echo "Esta es la respuesta de SRI:<br/>";
                                            echo var_dump($responseAut);
                                            echo "<br/>";
                                            echo 'INFORME AL ADMINISTRADOR!</br>';
                                        }
                                    break;
                                }
                            break;
                            case 'DEVUELTA':
                                $m .= $response["RespuestaRecepcionComprobante"]["estado"] . '<br>';
                                $m .= $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["claveAcceso"] . '<br>';
                                $m .= $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["mensaje"] . '<br>';
                                if (isset($response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["informacionAdicional"])) {
                                    $m .= $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["informacionAdicional"] . '<br>';
                                    $ms = $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["mensaje"] . ' => ' . $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["informacionAdicional"];
                                } else {

                                    $ms = $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["mensaje"];
                                }

                                $m .= $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["tipo"] . '<br><br>';
                                echo $response["RespuestaRecepcionComprobante"]["estado"] . '<br>';
                                echo $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["claveAcceso"] . '<br>';
                                echo $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["mensaje"] . '<br>';
                                if (isset($response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["informacionAdicional"])) {
                                    echo $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["informacionAdicional"] . '<br>';
                                }
                                echo $response["RespuestaRecepcionComprobante"]["comprobantes"]["comprobante"]["mensajes"]["mensaje"]["tipo"] . '<br><br>';
                                $controlError = true;
                            break;
                            case  false:
                            	//echo 'nose';
                            break;
                            default:
                            echo "<br>Se ha producido un problema. Vuelve a intentarlo.<br>";
                            echo "Esta es la respuesta de SRI:<br/>";
                            //echo var_dump($response).'<br>';
                            $m .= var_dump($response).'<br>';
                            echo "<br><br>";
                            $controlError = true;
                            break;
                        }            
                    break;
                    default:
                        echo 'no se puede firmar el doc';
                    break;
                }
               // echo 'veamos';
            } else {
                echo "Error: No se puede leer el almacén de certificados o clave del cert p12 es incorrecta.\n";
                exit;
            }
        } else {
            echo 'cargar un comprobante';
        }
   }
}

?>