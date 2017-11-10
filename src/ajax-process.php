<?php

    include_once 'util/ssl.php';
    include_once 'util/jwt.php';

    $jwt_result = WebToken::verifyToken($_COOKIE["jwt"]);

    // Treatment relations
    if (isset($_POST['therapistId']) && isset($_POST['consentSettings'])) {
        $csrf = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/csrf/".$_POST['csrf']));
        if (isset($csrf->result) || $csrf->expiry < time() || $csrf->description != "createTreatmentReq" || $csrf->uid != $jwt_result->uid) {
            Log::recordTX($jwt_result->uid, "Warning", "Invalid csrf when creating treatment request");
            header('HTTP/1.0 400 Bad Request.');
            die();
        }
        echo createTreatmentReq($jwt_result->uid, $_POST['therapistId'], $_POST['consentSettings']);
    } else if (isset($_POST['acceptTreatmentId'])) {
        $csrf = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/csrf/".$_POST['csrf']));
        if (isset($csrf->result) || $csrf->expiry < time() || $csrf->description != "acceptTreatmentReq" || $csrf->uid != $jwt_result->uid) {
            Log::recordTX($jwt_result->uid, "Warning", "Invalid csrf when accepting treatment request");
            header('HTTP/1.0 400 Bad Request.');
            die();
        }
        echo acceptTreatmentReq($_POST['acceptTreatmentId']);
    } else if (isset($_POST['rejectTreatmentId'])) {
        $csrf = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/csrf/".$_POST['csrf']));
        if (isset($csrf->result) || $csrf->expiry < time() || $csrf->description != "rejectTreatmentReq" || $csrf->uid != $jwt_result->uid) {
            Log::recordTX($jwt_result->uid, "Warning", "Invalid csrf when rejecting treatment request");
            header('HTTP/1.0 400 Bad Request.');
            die();
        }
        echo rejectTreatmentReq($_POST['rejectTreatmentId']);
    } else if (isset($_POST['removeTreatmentId'])) {
        $csrf = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/csrf/".$_POST['csrf']));
        if (isset($csrf->result) || $csrf->expiry < time() || $csrf->description != "removeTherapist" || $csrf->uid != $jwt_result->uid) {
            Log::recordTX($jwt_result->uid, "Warning", "Invalid csrf when removing therapist");
            header('HTTP/1.0 400 Bad Request.');
            die();
        }
        echo removeTreatmentReq($_POST['removeTreatmentId']);
    }

    // Consent relations
    if (isset($_POST['consentChanges'])) {
        $csrf = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/csrf/".$_POST['csrf']));
        if (isset($csrf->result) || $csrf->expiry < time() || $csrf->description != "updateConsentSettings" || $csrf->uid != $jwt_result->uid) {
            Log::recordTX($jwt_result->uid, "Warning", "Invalid csrf when updating consent settings");
            header('HTTP/1.0 400 Bad Request.');
            die();
        }
        updateConsentStatus($_POST['consentChanges']);
    }
    if (isset($_POST['treatmentId']) && isset($_POST['currentConsentSetting']) && isset($_POST['futureConsentSetting'])) {
        $csrf = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/csrf/".$_POST['csrf']));
        if (isset($csrf->result) || $csrf->expiry < time() || $csrf->description != "updateConsentSettings" || $csrf->uid != $jwt_result->uid) {
            Log::recordTX($jwt_result->uid, "Warning", "Invalid csrf when updating consent settings");
            header('HTTP/1.0 400 Bad Request.');
            die();
        }
        echo updateDefaultConsentSettings($_POST['treatmentId'], $_POST['currentConsentSetting'], $_POST['futureConsentSetting']);
    }

    // Misc
    if (isset($_POST['attachRecords'])) {
        $csrf = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/csrf/".$_POST['csrf']));
        if (isset($csrf->result) || $csrf->expiry < time() || $csrf->description != "attachRecords" || $csrf->uid != $jwt_result->uid) {
            Log::recordTX($jwt_result->uid, "Warning", "Invalid csrf when attaching records to document");
            header('HTTP/1.0 400 Bad Request.');
            die();
        }
        echo displayAttachedRecords($_POST['attachRecords']);
    }

    // Document Sharing
    if (isset($_POST['therapistArray'])) {
        $csrf = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/csrf/".$_POST['csrf']));
        if (isset($csrf->result) || $csrf->expiry < time() || $csrf->description != "shareDocument" || $csrf->uid != $jwt_result->uid) {
            Log::recordTX($jwt_result->uid, "Warning", "Invalid csrf when sharing document");
            header('HTTP/1.0 400 Bad Request.');
            die();
        }
        echo shareDocumentsWithTherapists($_POST['therapistArray']);
    }


    // ===============================================================================
    //                          TREATMENT RELATIONS
    // ===============================================================================
    
    function createTreatmentReq($patientId, $therapistId, $consentSettings) {
        $response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/treatment/create/' 
                                . $patientId . '/' . $therapistId . '/' . $consentSettings[0] . '/' . $consentSettings[1]));
        return $response->result;
    }

    //TODO: add CSRF validation
    function acceptTreatmentReq($treatmentId) {
        createConsentPermissions($treatmentId);
        $response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/treatment/update/' . $treatmentId));
        return $response->result;
    }

    function rejectTreatmentReq($treatmentId) {
        $response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/treatment/delete/' . $treatmentId));
        return $response->result;
    }

    function removeTreatmentReq($treatmentId) {
        removeAdditionalElements($treatmentId);
        $response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/treatment/delete/' . $treatmentId));
        return $response->result;
    }

    // ===============================================================================
    //                             CONSENT RELATIONS
    // ===============================================================================
    
    // Create consent permissions between the newly assigned therapist and the records of the patient; all defaulted to false
    function createConsentPermissions($treatmentId) {
        $response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/treatment/' . $treatmentId));
        $patientId = $response->patientId;
        $therapistId = $response->therapistId;
        $currentConsent = $response->currentConsent; 

        $patient_records_json = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/record/all/' . $patientId));
        if (isset($patient_records_json->records)) {
            $patient_records = $patient_records_json->records;
            for ($i = 0; $i < count($patient_records); $i++) {
                $record = $patient_records[$i];
                // caters for the case where the patient is also a therapist - excludes the documents owned by the patient as a therapist
                if ($record->type === "File") {
                    if ($record->subtype != "document") {
                        $create_consent_response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/consent/create/' . $therapistId . '/' . $record->rid));
                    }
                } else {
                    $create_consent_response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/consent/create/' . $therapistId . '/' . $record->rid));
                }
            }
        }
        
        if ($currentConsent) {
            setAllConsentsToTrue($treatmentId);
        }
    }

    function removeAdditionalElements($treatmentId) {
        $response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/treatment/' . $treatmentId));
        $patientId = $response->patientId;
        $therapistId = $response->therapistId;

        removeDocumentsAndAssociatedConsents($patientId, $therapistId);
        removeOtherConsents($patientId, $therapistId);
    }

    // Removes all documents associated to the patient that had been written by the therapist
    function removeDocumentsAndAssociatedConsents($patientId, $therapistId) {
        $patient_consents_json = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/consent/user/' . $patientId));
        if (isset($patient_consents_json->consents)) {
            $patient_consents = $patient_consents_json->consents;
            for ($i = 0; $i < count($patient_consents); $i++) {
                $record = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/record/get/' . $patient_consents[$i]->rid));
                if ($record->therapistId === $therapistId) {
                    $delete_document_response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/record/delete/' . $record->rid . '/' . $therapistId));
                    $delete_consent_response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/consent/delete/' . $patient_consents[$i]->consentId));
                }
            }
        }
    }

    // Removes all associated consents between a therapist and a patient's records when the patient removes an assigned therapist
    function removeOtherConsents($patientId, $therapistId) {
        // Get list of record IDs of the patient records
        $patient_records_json = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/record/all/' . $patientId));
        $patient_records_ids = array();
        if (isset($patient_records_json->records)) {
            $patient_records = $patient_records_json->records;
            for ($i = 0; $i < count($patient_records); $i++) {
                array_push($patient_records_ids, $patient_records[$i]->rid);
            }
        }

        // Iterate through the list of consents associated with the therapist and delete the consents that are for records of the deleted patient
        $therapist_consents_json = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/consent/user/' . $therapistId));
        if (isset($therapist_consents_json->consents)) {
            $therapist_consents = $therapist_consents_json->consents;
            for ($i = 0; $i < count($therapist_consents); $i++) {
                if (in_array($therapist_consents[$i]->rid, $patient_records_ids)) {
                    $delete_consent_response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/consent/delete/' . $therapist_consents[$i]->consentId));
                }
            }
        }

    }

    // Iterates through the boolean array
    // If an element is set to true, then toggle the status value of the consent with the ID corresponding to the array index value
    function updateConsentStatus($consentChanges) {
        for ($i = 0; $i < count($consentChanges); $i++) {
            if ($consentChanges[$i]) { // if the value has been toggled
                $response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/consent/update/' . $i));
            }
        }
    }

    // Updates the current and future consent status flags
    function updateDefaultConsentSettings($treatmentId, $currentConsent, $futureConsent) {
        if ($currentConsent === "true") {
            setAllConsentsToTrue($treatmentId);
        }

        $consent_settings = array('id' => $treatmentId, 'currentConsent' => $currentConsent, 'futureConsent' => $futureConsent);
        $consent_settings_json = json_encode($consent_settings);
        return ssl::post_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/treatment/update/consentsetting', $consent_settings_json, array('Content-Type: application/json'));
    }

    // Sets all the consents between the patient and the therapist to true
    function setAllConsentsToTrue($treatmentId) {
        $treatment = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/treatment/' . $treatmentId));
        $consents_json = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/consent/owner/' . $treatment->patientId . '/' . $treatment->therapistId));
        $consents = $consents_json->consents;
        for ($i = 0; $i < count($consents); $i++) {
            $consent = $consents[$i];
            if (!$consent->status) {
                $response = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/consent/update/' . $consent->consentId));
            }
        }
    }

    // ===============================================================================
    //                                  MISC
    // ===============================================================================
    
    function displayAttachedRecords($attached_records_list) {
        
        /*
        $var = "";
        for ($i = 0; $i < count($attached_records_list); $i++) {
            $var .= gettype($attached_records_list[$i]) . ", "; 
        }
        return $var;
        */
        
        $count = 1;
        $line = "<br>";
        for ($i = 0; $i < count($attached_records_list); $i++) {
            if ($attached_records_list[$i] === "true") {
                $line .= "<span>";
                $line .= $count . ". <a href='#'><u>"; // TODO - include link to view the file
                $line .= json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4'].'api/team1/record/' . $i))->title;
                $line .= "</u></a></span>";
                $line .= "<br>";
                $count++;
            }
        }

        return $line;
    }

    function shareDocumentsWithTherapists($therapist_array) {
        $t_array = array();
        foreach($therapist_array AS $therapist) {
            $therapist = json_decode($therapist);
            $consent_string = getConsentJson($therapist->therapist, $therapist->rid, $therapist->owner);
            if ($therapist->isChecked) {
                if (strcmp($consent_string, "-1") == 0) { // Consent doesn't exist
                    $result = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/consent/create/".$therapist->therapist."/".$therapist->rid));
                    $consent_json = json_decode(getConsentJson($therapist->therapist, $therapist->rid, $therapist->owner));
                    $result = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/consent/update/".$consent_json->consentId));
                    array_push($t_array, $therapist->therapist." consent created");
                } else {
                    $consent_json = json_decode($consent_string);
                    if (!$consent_json->status) {
                        $result = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/consent/update/".$consent_json->consentId));
                        array_push($t_array, $therapist->therapist." consent toggled to true");
                    }
                }
            } else {
                if (strcmp($consent_string, "-1") !== 0) { // If consent exist
                    $consent_json = json_decode($consent_string);
                    if ($consent_json->status) {
                        $result = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/consent/update/".$consent_json->consentId));
                        array_push($t_array, $therapist->therapist." consent toggled to false");
                    }
                }
            }
        }
        return json_encode($t_array);
    }

    function getConsentJson($uid, $rid, $tid) {
        $consentId = "-1"; // Consent doesn't exist
        $consent_array = json_decode(ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/consent/owner/".$tid."/".$uid));
        if (!isset($consent_array->result)) {
            $consent_array = $consent_array->consents;
            foreach ($consent_array AS $consent_elem) {
                if (strcmp($consent_elem->rid, $rid) == 0) {
                    $consentId = ssl::get_content(parse_ini_file($_SERVER['DOCUMENT_ROOT']."/../misc.ini")['server4']."api/team1/consent/".$consent_elem->consentId);
                }
            }
        }
        return $consentId;
    }

    // ===============================================================================
    //                              HELPER FUNCTIONS
    // ===============================================================================
      
    // XORs the 2 strings after left-padding with zeroes to an equal length
    function doXOR($a, $b) {
        // pad both to equal length
        if (strlen($a) > strlen($b)) {
            while (strlen($a) > strlen($b)) {
                $b = '0' . $b;
            }
        } else if (strlen($b) > strlen($a)) {
            while (strlen($b) > strlen($a)) {
                $a = '0' . $a;
            }
        }

        $result = "";
        for ($i = 0; $i < strlen($a); $i++) {
            if (strcmp($a[$i], $b[$i]) === 0) { // equal
                $result = $result . "0";
            } else {
                $result = $result . "1";
            }
        }
        return $result;
    }

    // Converts a string of hex characters to its binary representation
    function hexToBinary($input) {
        $input_binary = "";
        for ($i = 0; $i < strlen($input); $i++) {
            $input_binary = $input_binary . getBinValue(substr($input, $i, 1));
        }
        return $input_binary;
    }

    function getBinValue($num) {
        switch ($num) {
            case "0":
                return "0000";
            case "1":
                return "0001";
            case "2":
                return "0010";
            case "3":
                return "0011";
            case "4":
                return "0100";
            case "5":
                return "0101";
            case "6":
                return "0110";
            case "7":
                return "0111";
            case "8":
                return "1000";
            case "9":
                return "1001";
            case "a": case "A":
                return "1010";
            case "b": case "B":
                return "1011";
            case "c": case "C":
                return "1100";
            case "d": case "D":
                return "1101";
            case "e": case "E":
                return "1110";
            case "f": case "F":
                return "1111";
        }
    }

    // For each block of 8-bits, convert it to its decimal value and then obtain the corresponding ASCII character
    function binaryToChar($input) {
        $input_hex = "";
        for ($i = 0; $i < strlen($input); $i+=8) {
            $value_dec = base_convert(substr($input, $i, 8), 2, 10);
            $input_hex = $input_hex . chr($value_dec);
        }
        return $input_hex;
    }

?>
