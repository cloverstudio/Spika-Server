<?php
include('../lib/startup.php');

session_start();
if (!isset($_SESSION['logged_id'])) {
    header("Location: index.php");
    die();
}


$DB = connectToDB();

$result = executeQuery($DB, "select count(*) as count from queue where state = " . STATE_SUCCESS);
$sentCount = $result[0]['count'] + getDeletedCount($DB);

$result = executeQuery($DB, "select count(*) as count from queue where state = " . STATE_WAIT);
$waitingCount = $result[0]['count'];

$message = "";
$token = "";
$serviceProvider = 1;
$payload = "";

if (isset($_GET['id'])) {

    $result = executeQuery($DB, "select * from queue where id = " . $_GET['id']);
    $serviceProvider = $result[0]['service_provider'];
    $token = $result[0]['token'];
    $payload = $result[0]['payload'];

}

$_POST = stripBackSlash($_POST);

if (isset($_POST['token']) && isset($_POST['payload']) && isset($_POST['service_provider'])) {

    $token = $_POST['token'];
    $payload = urldecode($_POST['payload']);
    $serviceProvider = $_POST['service_provider'];

    if ($serviceProvider == SERVICE_PROVIDOR_APN_PROD) {
        $result = sendAPNProd($token, $payload);
    } else {
        if ($serviceProvider == SERVICE_PROVIDOR_APN_DEV) {
            $result = sendAPNDev($token, $payload);
        } else {
            if ($serviceProvider == SERVICE_PROVIDOR_GCM) {
                $result = sendGCM($token, $payload);
            }
        }
    }

    $succeed = false;

    if ($serviceProvider == SERVICE_PROVIDOR_APN_PROD) {
        $succeed = getAPNResult($result);
    } else {
        if ($serviceProvider == SERVICE_PROVIDOR_APN_DEV) {
            $succeed = getAPNResult($result);
        } else {
            if ($serviceProvider == SERVICE_PROVIDOR_GCM) {
                $succeed = getGCMResult($result);
            }
        }
    }


}


if (isset($_POST['token'])) {
    $token = $_POST['token'];
}

if (isset($_POST['payload'])) {
    $payload = $_POST['payload'];
}

if (isset($_POST['message'])) {
    $message = $_POST['message'];
}

if (isset($_POST['service_provider'])) {
    $serviceProvider = $_POST['service_provider'];
}


include('header.php');

?>

<script lang="text/javascript">

    function generatePayload() {

        var serviceProvidor = 1;

        if ($('#service_provider3').is(":checked")) {

            serviceProvidor = 3;

        }

        var message = $("#message").val();
        var token = $("#token").val();
        var payload = '';

        if (serviceProvidor == 1) {

            payload = '{"aps":{"alert":"' + message + '","badge":"","sound":"default","value":""}}';

        } else {

            payload = '{"registration_ids":["' + token + '"],"data":{"message":"' + message + '"}}';

        }

        $('#payload').val(payload);
    }

    $(document).ready(function () {

        $("#message").blur(function () {
            generatePayload();
        });

        $("#token").blur(function () {
            generatePayload();
        });

        $("#service_provider1").click(function () {
            generatePayload();
        });

        $("#service_provider2").click(function () {
            generatePayload();
        });

        $("#service_provider3").click(function () {
            generatePayload();
        });

    });

</script>

<div class="container-fluid">
    <div class="page-header">
        <div class="pull-left">
            <h1> Send push notification</h1>
        </div>
        <div class="pull-right">
            <ul class="stats">
                <li class='satgreen'>
                    <i class="icon-signin"></i>

                    <div class="details">
                        <span class="big"><?php echo $sentCount ?> sent</span>
                    </div>
                </li>
                <li class='lightred'>
                    <i class="icon-spinner"></i>

                    <div class="details">
                        <span class="big"><?php echo $waitingCount ?> in queue</span>
                    </div>
                </li>

            </ul>
        </div>
    </div>


    <div class="row-fluid">
        <div class="span12">

            <?php

            if (isset($succeed)) {

                $cssClassName = "alert-success";
                if (!$succeed) {
                    $cssClassName = "alert-error";
                }

                if ($serviceProvider == SERVICE_PROVIDOR_APN_PROD) {
                    $responseLabel = "Response from APN : ";
                } else {
                    if ($serviceProvider == SERVICE_PROVIDOR_APN_DEV) {
                        $responseLabel = "Response from APN Dev : ";
                    } else {
                        if ($serviceProvider == SERVICE_PROVIDOR_GCM) {
                            $responseLabel = "Response from GCM : ";
                        }
                    }
                }

                ?>

                <div class="alert <?php echo $cssClassName ?>">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong><?php echo $responseLabel ?></strong><?php echo $result ?>
                </div>

            <?php } ?>

            <div class="box box-bordered box-color">
                <div class="box-title">
                    <h3><i class="icon-th-list"></i> Send push notification</h3>
                </div>
                <div class="box-content nopadding">

                    <form action="" method="POST" id="form1" class='form-horizontal form-bordered'>
                        <div class="control-group">
                            <label for="textfield" class="control-label">Message</label>

                            <div class="controls">
                                <input type="text" name="message" id="message" placeholder="" class="input-xxlarge"
                                       value="<?php echo $message ?>">
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="textfield" class="control-label">token</label>

                            <div class="controls">
                                <input type="text" name="token" id="token" placeholder="" class="input-xxlarge"
                                       value="<?php echo $token ?>">
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label">Service Provider
                                <small>Please select one of them</small>
                            </label>

                            <div class="controls">
                                <label class='radio'>
                                    <input <?php if ($serviceProvider == 1) {
                                        echo 'checked=checked';
                                    } ?> type="radio"
                                                                                                       name="service_provider"
                                                                                                       id="service_provider1"
                                                                                                       value="<?php echo SERVICE_PROVIDOR_APN_PROD ?>">
                                    APN production
                                </label>
                                <label class='radio'>
                                    <input <?php if ($serviceProvider == 2) {
                                        echo 'checked=checked';
                                    } ?> type="radio"
                                                                                                       name="service_provider"
                                                                                                       id="service_provider2"
                                                                                                       value="<?php echo SERVICE_PROVIDOR_APN_DEV ?>">
                                    APN development
                                </label>
                                <label class='radio'>
                                    <input <?php if ($serviceProvider == 3) {
                                        echo 'checked=checked';
                                    } ?> type="radio"
                                                                                                       name="service_provider"
                                                                                                       id="service_provider3"
                                                                                                       value="<?php echo SERVICE_PROVIDOR_GCM ?>">
                                    GCM
                                </label>
                            </div>
                        </div>
                        <div class="control-group">
                            <label for="textarea" class="control-label">Payload
                                <small>Please make sure JSON is formatted properly.</small>
                            </label>

                            <div class="controls">
                                <textarea name="payload" id="payload" rows="5"
                                          class="input-block-level"><?php echo $payload ?></textarea>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Send</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php') ?>
