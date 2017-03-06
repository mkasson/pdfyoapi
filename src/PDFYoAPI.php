<?php

namespace Pdfyo\PdfyoAPI;

/**
 * @license Docsmit API PHP SDK
 * (c) 2014-2015 Docsmit.com, Inc. http://www.docsmit.com
 * License: GNU GPL 3
 */
class PDFYoAPI {

    private $username;
    private $password;
    public $testing;
    public $html;
    public $htmlURL;
    public $fields;
    public $JSON;
    public $mail;
    public $email;
    private $headers;
    private $curl_info;
    private $response_body;
    private $http_code;

    const HOST = "https://www.pdfyo.com/api";
    const HTTP_OK = 200;
    const HTTP_UNAUTHORIZED = 401;
// SendType values
    const ST_CERTERR = "Certified, Electronic Return Receipt";
    const ST_CERTRR = "Certified, Return Receipt";
    const ST_CERT = "Certified";
    const ST_FIRST = "First Class";
    const ST_PRIORMWS = "Priority Mail with Signature";
    const ST_PRIORM = "Priority Mail";
    const ST_PRIORITY_FRE = "Priority Mail, Flat Rate Env";
// Envelope Values
    const ENV_10 = "#10";
    const ENV_FLAT = "Flat";
    const ENV_PRIORITY_FLAT = "Priority Flat";
    const ENV_PRIORITY_PADDED = "Priority Padded";
// single or double sided printing
    const SIDED_SINGLE = 1;
    const SIDED_DOUBLE = 2;

    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
        return self::authping ($username, $password);
    }

    public static function authping($username, $password) {
        $process = curl_init(self::HOST . "/authping");
        curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($process, CURLOPT_HEADER, 1);
        curl_setopt($process, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
//curl_setopt($process, CURLOPT_POST, 1);
//curl_setopt($process, CURLOPT_POSTFIELDS, $payloadName);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        $return = curl_exec($process);
        $curl_info = curl_getinfo($process);
        $http_code = $curl_info["http_code"];
        curl_close($process);
        return $http_code;
    }

    public static function health() {
        $process = curl_init(self::HOST . "/health");
        curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($process, CURLOPT_HEADER, 1);
//curl_setopt($process, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
//curl_setopt($process, CURLOPT_POST, 1);
//curl_setopt($process, CURLOPT_POSTFIELDS, $payloadName);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        $return = curl_exec($process);
        $curl_info = curl_getinfo($ch);
        $http_code = $curl_info["http_code"];
        curl_close($process);
        return $http_code;
    }

    public function responseBody() {
        return $this->response_body;
    }

    public function responseObject() {
        return json_decode($this->responseBody());
    }

    function curlInfo() {
        return $this->curl_info;
    }

    public function status() {
        return $this->http_code;
    }

    function returnedError() {
        return ( $this->http_code >= 400);
    }

    function returnedSuccess() {
        return ( $this->http_code == 200 || $this->http_code == 201);
    }

    public function send($params) {

        $process = curl_init(self::HOST . "/pdfgen");
        curl_setopt($process, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($process, CURLOPT_HEADER, 1);
        curl_setopt($process, CURLOPT_USERPWD, $username . ":" . $password);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        curl_setopt($process, CURLOPT_POST, 1);
        curl_setopt($process, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($process, CURLOPT_RETURNTRANSFER, TRUE);
        $return = curl_exec($process);
        curl_close($process);
    }

    private function execAndFill($ch, $params = NULL, $contentType = "application/json", $jsonEncode = true) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:$contentType"));
        if ($params != NULL) {
            if ($jsonEncode)
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            else
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        $this->response_body = curl_exec($ch);

        $this->curl_info = curl_getinfo($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $this->headers = substr($this->response_body, 0, $header_size);
        $this->http_code = $this->curl_info["http_code"];
    }

    public function get($url, $params = NULL) {

        if ($params != NULL) {
            $qsSeparator = strrpos($url, "?") === FALSE ? '?' : '&';
            $url .= $qsSeparator . http_build_query($params);
        }

        $ch = curl_init($this->URIBase() . $url);
        $this->execAndFill($ch);
    }

    public function post($url, $params = NULL, $isFile = false) {
        $ch = curl_init($this->URIBase() . $url);
        curl_setopt($ch, CURLOPT_POST, true);

        if ($isFile && !empty($params["filePath"]) && file_exists($params["filePath"])) {
            $finfo = new finfo(FILEINFO_MIME);
            $mimeType = $finfo->file($params["filePath"]);
            $params["file"] = new CurlFile($params["filePath"], $mimeType);
            $this->execAndFill($ch, $params, "multipart/form-data", false);
        } elseif ($isFile && !empty($params["blob"])) {
            $this->execAndFill($ch, $params, "multipart/form-data", false);
        } else
            $this->execAndFill($ch, $params);

        if ($this->http_code == self::HTTP_UNAUTHORIZED) {
            $this->refreshToken();
            $ch = curl_init($this->URIBase() . $url);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($isFile && !empty($params["filePath"]) && file_exists($params["filePath"])) {
                $finfo = new finfo(FILEINFO_MIME);
                $mimeType = $finfo->file($params["filePath"]);
                $params["file"] = new CurlFile($params["filePath"], $mimeType);
                $this->execAndFill($ch, $params, "multipart/form-data", false);
            } elseif ($isFile && !empty($params["blob"])) {
                $this->execAndFill($ch, $params, "multipart/form-data", false);
            } else
                $this->execAndFill($ch, $params);
        }
    }


// If you don't have PECL with http_parse_headers
//see: http://php.net/manual/en/function.http-parse-headers.php#68698
}
