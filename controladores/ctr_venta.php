<?php
include 'ctr_xml.php';
include 'ctr_firmarxml.php';
$fecha 		=$_POST['fecha'];
$correo 	=$_POST['correo'];
$secuencial =$_POST['secuencial'];
$codigo	    =$_POST['codigo'];
$cantidad   =$_POST['cantidad'];
$descripcion=$_POST['descripcion'];
$preciou    =$_POST['preciou'];
$descuento  =$_POST['descuento'];
$preciot    =$_POST['preciot'];
$subtotal   =$_POST['subtotal'];
$iva0 		=$_POST['iva0'];
$iva12 		=$_POST['iva12'];
$descuento  =$_POST['descuento'];
$total 		=$_POST['total'];

$xmlf=new xml();
$xmlf->xmlFactura($fecha,$correo,$secuencial,$codigo,$cantidad,$descripcion,$preciou,$descuento,$preciot,$subtotal,$iva12,$total);

$xmla=new autorizar();
$xmla->autorizar_xml($fecha,$correo);

?>