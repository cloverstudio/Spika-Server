<?php

define("ROOT_DIR", dirname(__FILE__));
include(ROOT_DIR . "/lib/startup.php");

$DB = connectToDB();

$startTime = time();

while (true) {

    $now = time();

    $passed = $now - $startTime;

    if ($passed > 60) {
        break;
    }

    // get worker thread num
    $workerThreadCount = processCount(QUEUE_WORKER_NAME);

    $newProcessNum = MAX_REQUESTS_PER_INTERNAL - $workerThreadCount;

    $query = "
			select 
				* 
			from 
				queue 
			where 
				state = " . STATE_WAIT . " 
			order by 
				id 
			limit {$newProcessNum}
		";

    $lastPushAry = executeQuery($DB, $query);

    if ($newProcessNum == 0) {
        _log("OVER LOAD!!! Please increase max thread count!!!");
    }

    if ($lastPushAry === false) {

        _log("Reconnect to DB");

        //DB problem with DB connection try to reconnect..
        mysql_close($DB);
        $DB = connectToDB();
    }

    if (count($lastPushAry) == 0) {
        continue;
    }

    // save queue state
    $resultWaiting = executeQuery($DB, "select * from queue where state = " . STATE_WAIT);
    $query = generateQuery(
        INSERT,
        "queue_state_log",
        array(
            'capture_time' => date("Y-m-d H:i:s", time()),
            'notifications_queued' => count($resultWaiting)
        )
    );

    executeQuery($DB, $query);

    foreach ($lastPushAry as $row) {

        $queueId = $row['id'];

        executeQuery($DB, "update queue set state = " . STATE_PROCESSING . ",sent='{$now}' where id = {$queueId}");

        // activate worker thread
        $command = PHP_COMMAND . " " . ROOT_DIR . "/queue_worker.php {$queueId} > /dev/null &";
        exec($command);

    }

    _log(count($lastPushAry) . " worker threads activated by manager.");


    //sleep(RELEASE_INTERVAL);

}

mysql_close($DB);

?>