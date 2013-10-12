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

$query = "";
$error = "";


$_POST = stripBackSlash($_POST);


if (isset($_POST['query'])) {

    $query = $_POST['query'];

} else {

    $query = "select * from queue order by sent desc limit 50";

}

if (!empty($query)) {

    if (preg_match("/insert|update|delete/i", $query)) {

        $error = "Only SELECT is permitted.";

    } else {

        if (!preg_match("/limit/i", $query)) {
            $query .= " limit 200";
            $error = "Automatically added 'LIMIT 200'";
        }

        $result = executeQuery($DB, $query);

        if ($result === false) {
            $error = "Something goes wrong...";
        } else {
            if (!is_array($result)) {
                $error = $result;
            } else {
                if (count($result) == 0) {
                    $error = "Nothing found.";
                }
            }
        }
    }

}

include('header.php');

?>


    <script lang="text/javascript">

        $(document).ready(function () {

            $(".detail-btn").click(function () {

                var rowid = $(this).attr("rowid");
                var content = $("#detail_" + rowid).html();

                bootbox.animate(true);
                bootbox.alert(content);

            });

            $(".send-btn").click(function () {

                var rowid = $(this).attr("rowid");

                document.location = "send.php?id=" + rowid;

            });

        });

    </script>


    <div class="container-fluid">


        <div class="page-header">
            <div class="pull-left">
                <h1>Queue browser</h1>
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

                if (!empty($error)) {

                    $cssClassName = "alert-error";

                    ?>

                    <div class="alert <?php echo $cssClassName ?>">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong>Error : </strong><?php echo $error ?>
                    </div>

                <?php } ?>

                <div class="box box-bordered box-color">
                    <div class="box-title">
                        <h3><i class="icon-th-list"></i> Please write SQL query to run.</h3>
                    </div>
                    <div class="box-content nopadding">
                        <form action="" method="POST" class='form-horizontal form-bordered'>
                            <div class="control-group">
                                <label for="textfield" class="control-label">Example</label>

                                <div class="controls">
                                    select * from queue order by sent desc limit 50
                                </div>
                            </div>
                            <div class="control-group">
                                <label for="textarea" class="control-label">Query</label>

                                <div class="controls">
                                    <textarea name="query" id="textarea" rows="5"
                                              class="input-block-level"><?php echo $query ?></textarea>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Run Query</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <div class="row-fluid">
            <div class="span12">
                <div class="box">
                    <div class="box-title">
                        <h3>
                            <i class="icon-table"></i>
                            Query result
                        </h3>
                    </div>
                    <div class="box-content nopadding">
                        <table class="table table-hover table-nomargin">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Provider</th>
                                <th>State</th>
                                <th>Result</th>
                                <th>Token</th>
                                <th>Timestamp</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <? if (is_array($result)) {
                                foreach ($result as $row) {

                                    $rowClass = "";
                                    if ($row['state'] == STATE_ERROR) {
                                        $rowClass = "redrow";
                                    }
                                    ?>
                                    <tr class="<?php echo $rowClass ?>">
                                        <td><?php echo $row['id'] ?></td>
                                        <td><?php echo $PROVIDER_LABELS[$row['service_provider']] ?></td>
                                        <td>
                                            <?php $stateLabel = $STATE_LABELS[$row['state']];
                                            if ($row['state'] == STATE_ERROR) {
                                                echo "<strong>{$stateLabel}</strong>";
                                            } else {
                                                echo $stateLabel;
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo truncateStr($row['result_from_service_provider'], 20) ?></td>
                                        <td><?php echo truncateStr($row['token'], 20) ?></td>
                                        <td>
                                            Queued : <?php echo $row['queued'] ?><br/>
                                            Sent : <?php echo $row['sent'] ?><br/>
                                            Waited : <strong><?php echo strtotime($row['sent']) - strtotime(
                                                        $row['queued']
                                                    ) ?><strong> sec
                                        </td>
                                        <td>
                                            <button class="btn btn-satgreen detail-btn"
                                                    rowid="<?php echo $row['id'] ?>"><i class="icon-info-sign"></i> See
                                                datail
                                            </button>
                                            <br/>
                                            <button class="btn btn-grey send-btn" rowid="<?php echo $row['id'] ?>"><i
                                                    class="icon-circle-arrow-right"></i> Send again
                                            </button>
                                        </td>
                                        <div id="detail_<?php echo $row['id'] ?>" style="display:none">
												<pre><?php

                                                    $row['payload'] = json_decode($row['payload'], true);
                                                    $result = json_decode($row['result_from_service_provider'], true);
                                                    if (is_array($result)) {
                                                        $row['result_from_service_provider'] = $result;
                                                    }

                                                    $show = print_r($row, true);
                                                    $show = str_replace("Array", "", $show);

                                                    echo $show;
                                                    ?></pre>
                                        </div>
                                    </tr>
                                <?php }
                            } ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include('footer.php') ?>