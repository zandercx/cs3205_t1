<?php
require __DIR__ . '/../composer/vendor/autoload.php';
use \Firebase\JWT\JWT;

class WebToken
{

    private static $serverurl = "http://172.25.76.76/";

    /*
     * @param $admin_id
     * @param $key - secret key
     */
    static function getToken($admin_id, $key)
    {
        if (self::refreshSecret($admin_id)) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, self::$serverurl . "api/team1/admin/secret/" . $admin_id);
            curl_setopt($curl, CURLOPT_PORT, 80);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $secret = json_decode(curl_exec($curl))->secret;
            $token = array(
                "exp" => time() + 3600,
                "secret" => $secret,
                "data" => array(
                    "admin_id" => $admin_id,
                )
            );
            return JWT::encode($token, $key);
        } else {
            throw new Exception('Fail to update secret');
        }
    }

    /*
     * @param $token - token found in cookie
     * @param $key - secret key
     */
    static function verifyToken($token, $key)
    {
        if ($token) {
            try {
                $decoded = JWT::decode($token, $key, array(
                    'HS256'
                ));
                if ($decoded->exp < time()) {
                    header("Location: /login.php?error=1");
                    die();
                } else {
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, self::$serverurl . "api/team1/admin/secret/" . $decoded->data->admin_id);
                    curl_setopt($curl, CURLOPT_PORT, 80);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    $result = json_decode(curl_exec($curl));
                    if (strcmp($result->secret, $decoded->secret) != 0) {
                        /*
                         * Possile cause: logout, login somewhere else, change psw
                         */
                        header("Location: /login.php?error=2");
                        die();
                    }
                    return $decoded->data;
                }
            } catch (Exception $e) {
                /*
                 * This token cannot be decoded.
                 * The signature cannot be verified.
                 */
                echo $e;
                header("Location: /login.php?error=3");
                die();
            }
        } else {
            /*
             * Token is not present.
             */
            header("Location: /login.php?error=4");
            die();
        }
    }

    /*
     * @param $admin_id
     * @return true for success, false otherwise
     */
    static function refreshSecret($admin_id)
    {
        $secret = self::getSecret($admin_id);
        $string = bin2hex(random_bytes(20));
        while ($secret && strcmp($secret, $string) == 0)
            $string = bin2hex(random_bytes(20));
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::$serverurl . "api/team1/admin/secret/set/" . $admin_id . "/" . $string);
        curl_setopt($curl, CURLOPT_PORT, 80);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = json_decode(curl_exec($curl));
        if ($result->result == 1)
            return true;
        else
            return false;
    }

    static function getSecret($admin_id)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::$serverurl . "api/team1/admin/secret/" . $admin_id);
        curl_setopt($curl, CURLOPT_PORT, 80);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = json_decode(curl_exec($curl));
        if (isset($result->secret))
            return $result->secret;
        else
            return null;
    }
}
?>