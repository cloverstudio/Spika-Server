<?php
include('../lib/startup.php');

session_start();
if (!isset($_SESSION['logged_id'])) {
    header("Location: index.php");
    die();
}

include('header.php');


$DB = connectToDB();

$result = executeQuery($DB, "select count(*) as count from queue where state = " . STATE_SUCCESS);
$sentCount = $result[0]['count'] + getDeletedCount($DB);

$result = executeQuery($DB, "select count(*) as count from queue where state = " . STATE_WAIT);
$waitingCount = $result[0]['count'];


?>

<script lang="text/javascript">

    $(document).ready(function () {


    });

</script>

<div class="container-fluid">
    <div class="page-header">
        <div class="pull-left">
            <h1> Settings</h1>
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

        <div class="row-fluid">
            <div class="span12">
                <div class="box">
                    <div class="box-content">
                        <div class="alert alert-info">
                            <h4>Warning</h4>

                            <p>Please change lib/init.php directly to change settings</p>
                        </div>
                        <table id="user" class="table table-bordered table-striped table-force-topborder"
                               style="clear: both">
                            <tbody>
                            <tr>
                                <td width="30%">Database host</td>
                                <td width="70%"><?php echo DB_HOST ?></td>
                            </tr>
                            <tr>
                                <td width="30%">Database name</td>
                                <td width="70%"><?php echo DB_NAME ?></td>
                            </tr>

                            <tr>
                                <td width="30%">GCM API Key</td>
                                <td width="70%"><?php echo GCM_API_KEY ?></td>
                            </tr>

                            <tr>
                                <td width="30%">Path for APN development certificate</td>
                                <td width="70%"><?php echo APN_DEV_CERT ?></td>
                            </tr>

                            <tr>
                                <td width="30%">Path for APN production certificate</td>
                                <td width="70%"><?php echo APN_PROD_CERT ?></td>
                            </tr>

                            <tr>
                                <td width="30%">Use queue feature</td>
                                <td width="70%"><?php echo getYesNo(USE_QUEUE) ?></td>
                            </tr>

                            <tr>
                                <td width="30%">Max push count to send in one interval</td>
                                <td width="70%"><?php echo MAX_REQUESTS_PER_INTERNAL ?></td>
                            </tr>

                            <tr>
                                <td width="30%">Seconds for one interval</td>
                                <td width="70%"><?php echo RELEASE_INTERVAL ?> sec</td>
                            </tr>

                            <tr>
                                <td width="30%">PHP command</td>
                                <td width="70%"><?php echo PHP_COMMAND ?></td>
                            </tr>

                            <tr>
                                <td width="30%">Timeout at sending push</td>
                                <td width="70%"><?php echo SP_TIMEOUT ?> sec</td>
                            </tr>

                            <tr>
                                <td width="30%">Show server stat in dashboard</td>
                                <td width="70%"><?php echo getYesNo(SHOW_SERVERSTAT) ?></td>
                            </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row-fluid">
            <div class="span12">

                <div class="box">
                    <div class="box-title">
                        <h3>
                            <i class="icon-th-large"></i>
                            Clear data
                        </h3>
                    </div>
                    <div class="box-content">
                        <ul class="tiles">
                            <li class="red long">
                                <a href="?delete=7">
												<span class='count'><i class="icon-trash"></i> 7<span class='name'>Delete older than 7 days</span>
                                </a>
                            </li>
                            <li class="red long">
                                <a href="?delete=30">
												<span class='count'><i class="icon-trash"></i> 30<span class='name'>Delete older than 30 days</span>
                                </a>
                            </li>
                            <li class="red long">
                                <a href="?delete=60">
												<span class='count'><i class="icon-trash"></i> 60<span class='name'>Delete older than 60 days</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>





<?php include('footer.php') ?>
