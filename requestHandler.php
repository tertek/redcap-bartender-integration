<?php
/** @var \STPH\Bartender\Bartender $module */

if ($_REQUEST['type'] == 'testAsync') {
    $module->testAsync();
}

elseif ($_REQUEST['type'] == 'getPrintJobData') {
    $module->getPrintJobData(
        $_POST["project_id"],
        $_POST["record_id"],
        $_POST["job_id"]
    );
}
