<?php
/*
 * Usage:
 * include_once 'ssl.php';
 * $result = ssl::get_content($url);
 */
class ssl
{
    private static $certFile = "/usr/keys/team1-cert.pem";
    private static $keyFile = "/usr/keys/team1-key.pem";
    private static $cainfo = "/usr/keys/cacert.crt";
    /*
     * Use this method to set necessary SSL credentials for cURL reference passed into this method.
     *
     * @param $curl the curl variable for calling api
     * @return $curl
     */
    static function setSSL($curl)
    {
        // curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        // Set ssl key here
        curl_setopt($curl, CURLOPT_SSLKEY, self::$keyFile);
        // The --cert option
        curl_setopt($curl, CURLOPT_SSLCERT, self::$certFile);
        curl_setopt($curl, CURLOPT_CAINFO, self::$cainfo);
        // curl_setopt($curl, CURLOPT_CAPATH, '/home/sadm/keys/ssl/');
        return $curl;
    }
    
    static function get_content($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        self::setSSL($curl);
        $headers = ['Authorization: dGVhbTE6MHRlYW0xTG92ZXNDb3JnaW4wTW9SMw=='];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        return curl_exec($curl);
    }
    
    static function post_content($url, $data, $header)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        array_push($header, 'Authorization: dGVhbTE6MHRlYW0xTG92ZXNDb3JnaW4wTW9SMw==');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        self::setSSL($curl);
        return curl_exec($curl);
    }
}
?>
