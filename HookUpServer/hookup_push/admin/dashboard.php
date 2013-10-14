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

/*
for($i = 0;$i < 1000;$i++){


    $queudTime = time() - rand(0,60 * 60 * 24 * 30);
    $processingTime = rand(0,10);
    $state = STATE_SUCCESS;

    if(rand(0,100) < 5)
        $state = RELEASE_INTERVAL;

    $query = generateQuery(INSERT,"queue",array(
        'service_provider' => rand(1,3),
        'token' => "test",
        'payload' => "test",
        'state' => $state,
        'queued' => date("Y-m-d H:i:s",$queudTime) ,
        'sent' => date("Y-m-d H:i:s",$queudTime + $processingTime) ,
    ));

    executeQuery($DB,$query);

}


$time = time();
for($i = 0;$i < 100;$i++){

    $time -= rand(60 * 5,60 * 60);

    $queudTime = $time;

    $query = generateQuery(INSERT,"queue_state_log",array(
        'capture_time' => date("Y-m-d H:i:s",$queudTime) ,
        'notifications_queued' => rand(0,3) ,
    ));

    executeQuery($DB,$query);

}
*/


// get push sent last 30 days
$daysToCapture = 30;
$resultSentPush = executeQuery(
    $DB,
    "select * from queue where state = " . STATE_SUCCESS . " and sent > '" . date(
        'Y-m-d H:i:s',
        time() - 60 * 60 * 24 * $daysToCapture
    ) . "' order by sent"
);

$dateAry = array();
foreach ($resultSentPush as $row) {

    $date = date("Y-m-d", strtotime($row['sent']));

    if (!isset($dateAry[$date])) {
        $dateAry[$date] = 0;
    }

    $dateAry[$date]++;

}

$aryForJava = array();
$dateAryForJava = array();
foreach ($dateAry as $date => $count) {

    $aryForJava[] = array(
        strtotime($date) * 1000,
        $count
    );

    $dateAryForJava[] = $date;
}

$overallSent = count($resultSentPush);
$avgDaily = sprintf("%.2f", $overallSent / count($dateAry));
$sentToday = $dateAry[date('Y-m-d')];

$resultErrors = executeQuery(
    $DB,
    "select count(*) as count from queue where state = " . STATE_ERROR . " and queued > '" . date(
        'Y-m-d H:i:s',
        time() - 60 * 60 * 24 * $daysToCapture
    ) . "' order by sent"
);
$errorsCount = $resultErrors[0]['count'];
$errorRate = sprintf("%.2f", $errorsCount / $overallSent);


$hoursToCapture = 24;
$queueStateResult = executeQuery(
    $DB,
    "select * from queue_state_log where capture_time > '" . date(
        'Y-m-d H:i:s',
        time() - 60 * 60 * $hoursToCapture
    ) . "' order by capture_time"
);
$waitCountResult = executeQuery(
    $DB,
    "select * from queue where state = " . STATE_SUCCESS . " and queued > '" . date(
        'Y-m-d H:i:s',
        time() - 60 * 60 * $hoursToCapture
    ) . "' order by queued"
);
$queueStateCountAry = array();
$queueStateValueAry = array();

foreach ($queueStateResult as $row) {

    $date = date("Y-m-d H:00:00", strtotime($row['capture_time']));

    if (!isset($queueStateCountAry[$date])) {
        $queueStateValueAry[$date] = 0;
        $queueStateCountAry[$date] = 0;
    }

    $queueStateValueAry[$date] += $row['notifications_queued'];
    $queueStateCountAry[$date]++;
}

$queueStateAry = array();
$sumCount = 0;
foreach ($queueStateCountAry as $date => $row) {
    $queueStateAry[$date] = $queueStateValueAry[$date] / $queueStateCountAry[$date];
    $sumCount += $queueStateValueAry[$date];
}
$totalAverageQueud = sprintf("%.2f", $sumCount / count($queueStateCountAry));

$queueStateAryForJava = array();
foreach ($queueStateAry as $date => $value) {

    $queueStateAryForJava[] = array(
        strtotime($date) * 1000,
        $value
    );

}

// calc average wait time
$sumWaitTime = 0;
$sentIn5Sec = 0;
foreach ($waitCountResult as $row) {
    $wait = strtotime($row['sent']) - strtotime($row['queued']);
    $sumWaitTime += $wait;

    if ($wait < 5) {
        $sentIn5Sec++;
    }


}
$averageWaitTime = sprintf("%.2f", $sumWaitTime / count($waitCountResult));
$rateSentIn5Sec = sprintf("%.2f", $sentIn5Sec / count($waitCountResult));

include('header.php');

if (SHOW_SERVERSTAT) {
    $cpuUsage = sprintf("%02d", getCPUUsage());
    $memoryUsage = sprintf("%02d", getMemoryUsage());
    $hdUsage = sprintf("%02d", getHDUsage());
}
?>

<script lang="text/javascript">

    $(document).ready(function () {

        if ($("#flot-push").length > 0) {
            var data = <?php echo json_encode($aryForJava) ?>;

            $.plot($("#flot-push"), [
                {
                    label: "Sent",
                    data: data,
                    color: "#3a8ce5"
                }
            ], {
                xaxis: {
                    mode: "time",
                    timeformat: "%m/%d",
                },
                series: {
                    lines: {
                        show: true,
                        fill: true
                    },
                    points: {
                        show: true,
                    }
                },
                grid: { hoverable: true, clickable: true },
                legend: {
                    show: false
                }
            });

            $("#flot-push").bind("plothover", function (event, pos, item) {
                if (item) {
                    if (previousPoint != item.dataIndex) {
                        previousPoint = item.dataIndex;

                        $("#tooltip").remove();
                        var y = item.datapoint[1].toFixed();

                        showTooltip(item.pageX, item.pageY,
                            item.series.label + " = " + y);
                    }
                }
                else {
                    $("#tooltip").remove();
                    previousPoint = null;
                }
            });

        }

        if ($("#flot-queue").length > 0) {
            var data = <?php echo json_encode($queueStateAryForJava) ?>;

            $.plot($("#flot-queue"), [
                {
                    label: "Average number of pushes in queue",
                    data: data,
                    color: "#f36b6b"
                }
            ], {
                xaxis: {
                    mode: "time",
                    tickSize: [3, "hour"],
                },
                series: {
                    lines: {
                        show: true,
                        fill: true
                    },
                    points: {
                        show: true,
                    }
                },
                grid: { hoverable: true, clickable: true },
                legend: {
                    show: false
                }
            });

            $("#flot-queue").bind("plothover", function (event, pos, item) {
                if (item) {
                    if (previousPoint != item.dataIndex) {
                        previousPoint = item.dataIndex;

                        $("#tooltip").remove();
                        var y = item.datapoint[1].toFixed();

                        showTooltip(item.pageX, item.pageY,
                            item.series.label + " = " + y);
                    }
                }
                else {
                    $("#tooltip").remove();
                    previousPoint = null;
                }
            });
        }


    });

</script>
<div class="container-fluid">
    <div class="page-header">
        <div class="pull-left">
            <h1>Dashboard</h1>
        </div>
        <div class="pull-right">
            <ul class="stats">
                <li class='satgreen'>
                    <i class="icon-signin"></i>

                    <div class="details">
                        <span class="big"><?php echo number_format($sentCount) ?> sent</span>
                    </div>
                </li>
                <li class='lightred'>
                    <i class="icon-spinner"></i>

                    <div class="details">
                        <span class="big"><?php echo number_format($waitingCount) ?> in queue</span>
                    </div>
                </li>

            </ul>
        </div>
    </div>

    <?php if (SHOW_SERVERSTAT) { ?>
        <div class="row-fluid">
            <div class="span12">
                <div class="box">
                    <div class="box-title">
                        <h3>
                            <i class="icon-bar-chart"></i>
                            Server usage
                        </h3>
                    </div>
                    <div class="box-content">
                        <div class="row-fluid margin-top">
                            <div class="span4">
                                <ul class="pagestats style-3">
                                    <li>
                                        <div class="spark">
                                            <div class="chart" data-percent="<?php echo $cpuUsage ?>"
                                                 data-color="#f96d6d" data-trackcolor="#fae2e2"><?php echo $cpuUsage ?>%
                                            </div>
                                        </div>
                                        <div class="bottom">
                                            <span class="name">CPU Usage</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="span4">
                                <ul class="pagestats style-3">
                                    <li>
                                        <div class="spark">
                                            <div class="chart" data-percent="<?php echo $memoryUsage ?>"
                                                 data-color="#368ee0"
                                                 data-trackcolor="#d5e7f7"><?php echo $memoryUsage ?>%
                                            </div>
                                        </div>
                                        <div class="bottom">
                                            <span class="name">Memory Usage</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="span4">
                                <ul class="pagestats style-3">
                                    <li>
                                        <div class="spark">
                                            <div class="chart" data-percent="<?php echo $hdUsage ?>"
                                                 data-color="#56af45" data-trackcolor="#dcf8d7"><?php echo $hdUsage ?>%
                                            </div>
                                        </div>
                                        <div class="bottom">
                                            <span class="name">HD Usage</span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <div class="row-fluid">
        <div class="span6">
            <div class="box box-color box-bordered">
                <div class="box-title">
                    <h3>
                        <i class="icon-bar-chart"></i>
                        Sent Push Notifications in last <?php echo $daysToCapture ?> days
                    </h3>
                </div>
                <div class="box-content">
                    <div class="statistic-big">
                        <div class="top">
                            <div class="right">
                                <?php echo number_format($sentToday) ?><span
                                    class="small">notifications sent today<span><i
                                            class="icon-circle-arrow-up"></i></span>
                            </div>
                        </div>
                        <div class="bottom">
                            <div class="flot medium" id="flot-push"></div>
                        </div>
                        <div class="bottom">
                            <ul class="stats-overview">
                                <li>
												<span class="name">
													Overall Sent
												</span>
												<span class="value">
													<?php echo number_format($overallSent) ?>
												</span>
                                </li>
                                <li>
												<span class="name">
													Avg. Daily
												</span>
												<span class="value">
													<?php echo number_format($avgDaily) ?>
												</span>
                                </li>
                                <li>
												<span class="name">
													Total errors
												</span>
												<span class="value">
													<?php echo number_format($errorsCount) ?>
												</span>
                                </li>
                                <li>
												<span class="name">
													Erros rate
												</span>
												<span class="value">
													<?php echo $errorRate * 100 ?> % 
												</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="span6">
            <div class="box box-color lightred box-bordered">
                <div class="box-title">
                    <h3>
                        <i class="icon-bar-chart"></i>
                        Queue state in last <?php echo $hoursToCapture ?> hours
                    </h3>
                </div>
                <div class="box-content">
                    <div class="statistic-big">
                        <div class="top">
                            <div class="right">
                                <?php echo $waitingCount ?> <span
                                    class="small">notifications is waiting in queue<span><span><i
                                                class="icon-circle-arrow-right"></i></span>
                            </div>
                        </div>
                        <div class="bottom">
                            <div class="flot medium" id="flot-queue"></div>
                        </div>
                        <div class="bottom">
                            <ul class="stats-overview">
                                <li>
												<span class="name">
													Avg. Wait time
												</span>
												<span class="value">
													<?php echo $averageWaitTime ?> sec
												</span>
                                </li>
                                <li>
												<span class="name">
													Avg. Wait count 
												</span>
												<span class="value">
													<?php echo $totalAverageQueud ?>
												</span>
                                </li>
                                <li>
												<span class="name">
													Notifications sent in 5 sec
												</span>
												<span class="value">
													<?php echo $rateSentIn5Sec * 100 ?> %
												</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include('footer.php') ?>
