<?php

    include_once 'util/ssl.php';
    include_once 'util/jwt.php';
    $result = WebToken::verifyToken($_COOKIE["jwt"], "dummykey");

    $user_json = json_decode(ssl::get_content('http://172.25.76.76/api/team1/user/uid/' . $result->uid));
    $user_type = "patient";

    $settings_save = false;
    $hasError = false;
    
    // Input
    $currentpass = $pass = $cfmpass = $hashedpass = $salt =  "";

    // Error messages
    $currentpassErr = $passErr = $cfmpassErr = "";

    function sanitise($input) {
      $input = trim($input);
      $input = stripcslashes($input);
      $input = htmlspecialchars($input);
      return $input;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $currentpass = sanitise($_POST['input-current-pass']);
        $pass = sanitise($_POST["input-pass"]);
        $cfmpass = sanitise($_POST["input-cfmpass"]);
        
        if (empty($currentpass)) {
            $hasError = true;
            $currentpassErr = "Please input your current password for verification";
        } else {
            $currentpass = hash('SHA256', password_hash($currentpass, PASSWORD_BCRYPT, salt_cost($user_json->salt)));
            if (strcmp($currentpass, $user_json->password) !== 0) {
                $hasError = true;
                $currentpassErr = "Current password is incorrect";
            } else {
                if (empty($pass)) {
                    $hasError = true;
                    $passErr = "Please input your new password";
                } else if (strlen($pass) < 8) {
                    $hasError = true;
                    $passErr = "Password is too short";
                } else if ($pass != $cfmpass) {
                    $hasError = true;
                    $cfmpassErr = "Passwords don't match";
                }
            }
        }
        
        if (!$hasError) {
            $settings_save = true;
            $hashedpass = password_hash($pass, PASSWORD_BCRYPT);
            $salted = substr($hashedpass, 0, 29);
            $hashedpass = hash('SHA256', $hashedpass);
            //ssl::get_content('http://172.25.76.76/api/team1/user/update/'.$user_json->username.'/'.$hashedpass.'/'.$salt.'/');
            
            $pass_json = json_array($user_json->username, $hashedpass, $salted);
            $url = 'http://172.25.76.76/api/team1/user/update/password';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $pass_json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_exec($ch);
            $user_json = json_decode(ssl::get_content('http://172.25.76.76/api/team1/user/uid/' . $result->uid));
            
        }
    }

    function salt_cost($long_salt) {
        $cost = substr($long_salt, 4, 6);
        $salt = substr($long_salt, 7, 29);
        return [
            "cost" => $cost,
            "salt" => $salt
        ];
    }

    function hasError() {
        $hasError = true;
    }

    function json_array($username, $password, $salt) {
        $arr = array(
            'username' => $username,
            'password' => $password,
            'salt' => $salt
        );
        return json_encode($arr);
    }

?>

<html>
<meta charset="utf-8">

    <head>
        <title>Change your password</title>
        <link href="css/main.css" rel="stylesheet">
    </head>

    <body>
        <?php include 'sidebar.php' ?>
        <div class="shifted">
            <h1>Change your password</h1>
            <hr style="margin-top:-15px">
            <?php //echo $currentpass."<br>".$user_json->password."<br>".$user_json->salt."<br>".$hashedpass ."<br>".$salt ?>
            <form class="profile-form" name="profile-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <?php if ($settings_save) { ?>
                    <script>alert("Your password has been updated!")</script> 
                <?php } ?>
                <div class="profile-update">Current Password: <span class="error-message"><?php echo empty($currentpassErr) ? "" : "*" . $currentpassErr ?></span><br>
                    <input name="input-current-pass" type="password" placeholder=""
                        value=""><br>
                </div>
                <div class="profile-update">New Password: <span class="error-message"><?php echo empty($passErr) ? "" : "*" . $passErr ?></span><br>
                    <input name="input-pass" type="password" placeholder=""
                        value=""><br>
                </div>
                <div class="profile-update">Confirm New Password: <span class="error-message"><?php echo empty($cfmpassErr) ? "" : "*" . $cfmpassErr ?></span><br>
                    <input name="input-cfmpass" type="password" placeholder="" 
                        value=""><br>
                </div>
                <div class="profile-update"><input class="profile-submit" type="submit" id="btn-login" name="login" class="btn-login" value="Save"></div>
            </form>
        </div>
    </body>


</html>
