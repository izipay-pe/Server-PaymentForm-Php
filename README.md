<p align="center">
  <img src="https://github.com/izipay-pe/Imagenes/blob/main/logos_izipay/logo-izipay-banner-1140x100.png?raw=true" alt="Formulario" width=100%/>
</p>

# Server-PaymentForm-Php

## Índice

➡️ [1. Introducción](#-1-introducci%C3%B3n)  
🔑 [2. Requisitos previos](#-2-requisitos-previos)  
🚀 [3. Ejecutar ejemplo](#-3-ejecutar-ejemplo)  
🔗 [4. APIs](#4-APIs)  
💻 [4.1. FormToken](#41-formtoken)  
💳 [4.2. Validación de firma](#42-validaci%C3%B3n-de-firma)  
📡 [4.3. IPN](#43-ipn)  
📮 [5. Probar desde POSTMAN](#-5-probar-desde-postman)  
📚 [6. Consideraciones](#-6-consideraciones)

## ➡️ 1. Introducción

En este manual podrás encontrar una guía paso a paso para configurar un servidor API REST (Backend) en **[PHP]** para la pasarela de pagos de IZIPAY. **El actual proyecto no incluye una interfaz de usuario (Frontend)** y debe integrarse con un proyecto de Front. Te proporcionaremos instrucciones detalladas y credenciales de prueba para la instalación y configuración del proyecto, permitiéndote trabajar y experimentar de manera segura en tu propio entorno local.
Este manual está diseñado para ayudarte a comprender el flujo de la integración de la pasarela para ayudarte a aprovechar al máximo tu proyecto y facilitar tu experiencia de desarrollo.

<p align="center">
  <img src="https://i.postimg.cc/KYpyqYPn/imagen-2025-01-28-082121144.png" alt="Formulario"/>
</p>

## 🔑 2. Requisitos Previos

- Comprender el flujo de comunicación de la pasarela. [Información Aquí](https://secure.micuentaweb.pe/doc/es-PE/rest/V4.0/javascript/guide/start.html)
- Extraer credenciales del Back Office Vendedor. [Guía Aquí](https://github.com/izipay-pe/obtener-credenciales-de-conexion)
- Para este proyecto utilizamos la herramienta Visual Studio Code.
- Servidor Web
- PHP 7.0 o superior
> [!NOTE]
> Tener en cuenta que, para que el desarrollo de tu proyecto, eres libre de emplear tus herramientas preferidas.

## 🚀 3. Ejecutar ejemplo

### Instalar Xampp u otro servidor local compatible con php

Xampp, servidor web local multiplataforma que contiene los intérpretes para los lenguajes de script de php. Para instalarlo:

1. Dirigirse a la página web de [xampp](https://www.apachefriends.org/es/index.html)
2. Descargarlo e instalarlo.
3. Inicia los servicios de Apache desde el panel de control de XAMPP.


### Clonar el proyecto
```sh
git clone https://github.com/izipay-pe/Server-PaymentForm-Php.git
``` 

### Datos de conexión 

Reemplace **[CHANGE_ME]** con sus credenciales de `API REST` extraídas desde el Back Office Vendedor, revisar [Requisitos previos](#-2-requisitos-previos).

- Editar el archivo `keys.php` en la ruta raiz del proyecto:
```php
// Identificador de su tienda
define("USERNAME", "~ CHANGE_ME_USER_ID ~");

// Clave de Test o Producción
define("PASSWORD", "~ CHANGE_ME_PASSWORD ~");

// Clave Pública de Test o Producción
define("PUBLIC_KEY","~ CHANGE_ME_PUBLIC_KEY ~");

// Clave HMAC-SHA-256 de Test o Producción
define("HMAC_SHA256","~ CHANGE_ME_HMAC_SHA_256 ~");
```

### Ejecutar proyecto

1. Mover el proyecto y descomprimirlo en la carpeta htdocs en la ruta de instalación de Xampp: `C://xampp/htdocs/[proyecto_php]`

2.  Abrir el navegador web(Chrome, Mozilla, Safari, etc) con el puerto 80 que abrió xampp : `http://localhost/[nombre_de_proyecto]`

## 🔗4. APIs
- 💻 **FormToken:** Generación de formToken y envío de la llave publicKey necesarios para desplegar la pasarela.
- 💳  **Validacion de firma:** Se encarga de verificar la autenticidad de los datos.
- 📩 ️ **IPN:** Comunicación de servidor a servidor. Envío de los datos del pago al servidor.

## 💻4.1. FormToken
Para configurar la pasarela se necesita generar un formtoken. Se realizará una solicitud API REST a la api de creación de pagos:  `https://api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment` con los datos de la compra para generar el formtoken. El servidor devuelve el formToken generado junto a la llave `publicKey` necesaria para desplegar la pasarela

Podrás encontrarlo en el archivo `checkoutController.php`.

```php
public function formToken() {
        $inputJSON = file_get_contents('php://input');
        $data = json_decode($inputJSON, true);

        // URL de Web Service REST
        $url = "https://api.micuentaweb.pe/api-payment/V4/Charge/CreatePayment";

        // Encabezado Basic con concatenación de "usuario:contraseña" en base64
        $auth = USERNAME.":".PASSWORD;

        $headers = array(
            "Authorization: Basic " . base64_encode($auth),
            "Content-Type: application/json"
        );

        $body = [
            "amount" => $data["amount"] * 100,
            "currency" => $data["currency"],
            "orderId" => $data["orderId"],
            "customer" => [
            "email" => $data["email"],
            "billingDetails" => [
                "firstName"=>  $data["firstName"],
                "lastName"=>  $data["lastName"],
                ..
                ..
            ]
            ],
        ];

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $raw_response = curl_exec($curl);

        $response = json_decode($raw_response , true);

        $formToken = $response["answer"]["formToken"];

        echo json_encode(['formToken' => $formToken, 'publicKey' => PUBLIC_KEY]);
    }

```
Podrás acceder a esta API a través:
```bash
localhost/Server-Api-Rest-PHP/formtoken
```
ℹ️ Para más información: [Formtoken](https://secure.micuentaweb.pe/doc/es-PE/rest/V4.0/javascript/guide/embedded/formToken.html)

## 💳4.2. Validación de firma
Se configura la función `checkHash` que realizará la validación de los datos recibidos por el servidor luego de realizar el pago mediante el parámetro `kr-answer` utilizando una clave de encriptación definida en `key`. Podrás encontrarlo en el archivo `checkoutController.php`.

```php
public function checkHash($data, $key){
    $krAnswer = str_replace('\/', '/',  $data["kr-answer"]);

    $calculateHash = hash_hmac("sha256", $krAnswer, $key);

    return ($calculateHash == $data["kr-hash"]) ;
}
```

Se valida que la firma recibida es correcta. Para la validación de los datos recibidos a través de la pasarela de pagos (front) se utiliza la clave `HMACSHA256`.

```php
public function validate(){
    $inputJSON = file_get_contents('php://input');
    parse_str($inputJSON, $data);

    if (empty($data)) {
        throw new Exception("No post data received!");
    }
      
    $validate = $this->checkHash($data, HMAC_SHA256);

    echo json_encode($validate);
}
```
El servidor devuelve un valor booleano `true` o `false` verificando si los datos de la transacción coinciden con la firma recibida. Se confirma que los datos son enviados desde el servidor de Izipay.

Podrás acceder a esta API a través:
```bash
localhost/Server-Api-Rest-PHP/validate
```

ℹ️ Para más información: [Analizar resultado del pago](https://secure.micuentaweb.pe/doc/es-PE/rest/V4.0/kb/payment_done.html)

## 📩4.3. IPN
La IPN es una notificación de servidor a servidor (servidor de Izipay hacia el servidor del comercio) que facilita información en tiempo real y de manera automática cuando se produce un evento, por ejemplo, al registrar una transacción.

Se realiza la verificación de la firma utilizando la función `checkHash`. Para la validación de los datos recibidos a través de la IPN (back) se utiliza la clave `PASSWORD`. Se devuelve al servidor de izipay un mensaje confirmando el estado del pago.

Se recomienda verificar el parámetro `orderStatus` para determinar si su valor es `PAID` o `UNPAID`. De esta manera verificar si el pago se ha realizado con éxito.

Podrás encontrarlo en el archivo `checkoutController.php`.

```php
public function ipn(){             
    $inputJSON = file_get_contents('php://input');
    parse_str($inputJSON, $data);

    if (empty($data)) {
        throw new Exception("No post data received!");
    }
      
    $validate = $this->checkHash($data, PASSWORD);

    echo json_encode($validate);
}
```
Podrás acceder a esta API a través:
```bash
localhost/Server-Api-Rest-PHP/ipn
```

La ruta o enlace de la IPN debe ir configurada en el Backoffice Vendedor, en `Configuración -> Reglas de notificación -> URL de notificación al final del pago`

<p align="center">
  <img src="https://i.postimg.cc/XNGt9tyt/ipn.png" alt="Formulario" width=80%/>
</p>

ℹ️ Para más información: [Analizar IPN](https://secure.micuentaweb.pe/doc/es-PE/rest/V4.0/api/kb/ipn_usage.html)

## 📡4.3.Pase a producción

Reemplace **[CHANGE_ME]** con sus credenciales de PRODUCCIÓN de `API REST` extraídas desde el Back Office Vendedor, revisar [Requisitos Previos](#-2-requisitos-previos).

- Editar el archivo `keys.php` en la ruta raiz del proyecto:
```php
// Identificador de su tienda
define("USERNAME", "~ CHANGE_ME_USER_ID ~");

// Clave de Test o Producción
define("PASSWORD", "~ CHANGE_ME_PASSWORD ~");

// Clave Pública de Test o Producción
define("PUBLIC_KEY","~ CHANGE_ME_PUBLIC_KEY ~");

// Clave HMAC-SHA-256 de Test o Producción
define("HMAC_SHA256","~ CHANGE_ME_HMAC_SHA_256 ~");
```

## 📮 5. Probar desde POSTMAN
* Puedes probar la generación del formToken desde POSTMAN. Coloca la URL con el metodo POST con la ruta `/formToken`.
  
 ```bash
localhost/Server-Api-Rest-PHP/formToken
```

* Datos a enviar en formato JSON raw:
 ```node
{
    "amount": 1000,
    "currency": "PEN", //USD
    "orderId": "ORDER12345",
    "email": "cliente@example.com",
    "firstName": "John",
    "lastName": "Doe",
    "phoneNumber": "123456789",
    "identityType": "DNI",
    "identityCode": "ABC123456",
    "address": "Calle principal 123",
    "country": "PE",
    "city": "Lima",
    "state": "Lima",
    "zipCode": "10001"
}
```

## 📚 6. Consideraciones

Para obtener más información, echa un vistazo a:

- [Formulario incrustado: prueba rápida](https://secure.micuentaweb.pe/doc/es-PE/rest/V4.0/javascript/quick_start_js.html)
- [Primeros pasos: pago simple](https://secure.micuentaweb.pe/doc/es-PE/rest/V4.0/javascript/guide/start.html)
- [Servicios web - referencia de la API REST](https://secure.micuentaweb.pe/doc/es-PE/rest/V4.0/api/reference.html)
