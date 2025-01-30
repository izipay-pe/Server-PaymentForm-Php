<?php
require_once 'checkoutController.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$endpointController = new checkoutController();

$base_path = '/Server-Api-Rest-PHP';
$request_uri = str_replace($base_path, '', $_SERVER['REQUEST_URI']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $request_uri === '/formtoken') {
    $endpointController->formToken();
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $request_uri === '/validate') {
    $endpointController->validate();
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && $request_uri === '/ipn') {
    $endpointController->ipn();
} else {
    echo json_encode(array('message' => 'Ruta no encontrada'));
}
