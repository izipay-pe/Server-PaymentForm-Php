<?php
require_once "keys.php";

class checkoutController {
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
                "phoneNumber"=>  $data["phoneNumber"],
                "identityType"=>  $data["identityType"],
                "identityCode"=>  $data["identityCode"],
                "address"=>  $data["address"],
                "country"=>  $data["country"],
                "city"=>  $data["city"],
                "state"=>  $data["state"],
                "zipCode"=>  $data["zipCode"],
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

    public function validate(){
        $inputJSON = file_get_contents('php://input');
        $data = json_decode($inputJSON, true);

        if (empty($data)) {
            throw new Exception("No post data received!");
        }
          
        $validate = $this->checkHash($data, HMAC_SHA256);

        echo json_encode($validate);
    }

    public function ipn(){             
        $inputJSON = file_get_contents('php://input');
        parse_str($inputJSON, $data);

        if (empty($data)) {
            throw new Exception("No post data received!");
        }
          
        $validate = $this->checkHash($data, PASSWORD);

        echo json_encode($validate);
    }

    public function checkHash($data, $key){
        $krAnswer = str_replace('\/', '/',  $data["kr-answer"]);
    
        $calculateHash = hash_hmac("sha256", $krAnswer, $key);
    
        return ($calculateHash == $data["kr-hash"]) ;
    }
}
