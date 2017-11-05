<?php
    $patientId = $therapistId = $rid = "";

    if (isset($_POST['patientId'])) {
        $patientId = $_POST['patientId'];
    }
    if (isset($_POST['therapistId'])) {
        $therapistId = $_POST['therapistId'];
    }
    if (isset($_POST['rid'])) {
        $rid = $_POST['rid'];
    }

    if ($patientId === "0") {
        $therapist_list = json_decode(file_get_contents("http://cs3205-4-i.comp.nus.edu.sg/api/team1/user/therapists"))->users;
    } else {
        $therapist_list = json_decode(file_get_contents("http://172.25.76.76/api/team1/treatment/patient/".$patientId."/true"))->treatments;
    }

    function getUser($uid) {
        return json_decode(file_get_contents("http://cs3205-4-i.comp.nus.edu.sg/api/team1/user/uid/".$uid));
    }

    function hasConsent($uid, $rid) {
        $hasConsent = false;
        $consent_array = json_decode(file_get_contents("http://172.25.76.76/api/team1/consent/user/".$uid));
        if (!isset($consent_array->result)) {
            $consent_array = $consent_array->consents;
            foreach ($consent_array AS $consent_elem) {
                if (strcmp($consent_elem->rid, $rid) == 0 && $consent_elem->status) {
                    $hasConsent = true;
                }
            }
        }
        return $hasConsent;
    }

?>

<table class="main-table">
    <tr>
        <th class = "first-col">S/N</th>
        <th>Therapist</th>
        <th class="last-col">Select</th>
    </tr>

    <?php
    $i = 0;
    foreach($therapist_list AS $therapist) {
        if ($patientId !== "0") {
            $therapist = getUser($therapist->therapistId);
        }
        if (strcmp($therapistId, $therapist->uid) !== 0) {
    ?>
            <tr>
                <td><?php echo $i + 1 ?></td>
                <td><?php echo $therapist->firstname." ".$therapist->lastname ?></td>
                <td><input type="checkbox" class="selectDocumentCheckbox" value="<?php echo $therapist->uid ?>" <?php echo hasConsent($therapist->uid, $rid, $therapistId) ?>/></td>
            </tr>
    <?php
            $i++;
        }
    }
    ?>
</table>
