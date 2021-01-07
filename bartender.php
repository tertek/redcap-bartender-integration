<?php
// Set the namespace defined in your config file
namespace STPH\Bartender;

// The next 2 lines should always be included and be the same in every module
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;

/**
 * Class Bartender
 * @package STPH\Bartender
 */
class Bartender extends AbstractExternalModule {

    public function __construct()
    {
        parent::__construct();
        define("MODULE_DOCROOT_BARTENDER", $this->getModulePath());        
    }

    public function includeJsAndCss()
    {
    ?>
        <script src="<?= $this->getUrl("/js/bartender.js") ?>"></script>
        <link href="<?= $this->getUrl("/style.css") ?>" rel="stylesheet">
    <?php
    }

    public function getFieldValue($project_id, $record_id, $field_name){  
        
        //  Check if record_id has been renamed and use correct field_name
        if($field_name == $this->getRecordIdField($project_id)) {
            $field_name = 'record_id';
        }

        $result = $this->query(
            '
              select value
              from redcap_data
              where
                project_id = ?
                and record = ?                
                and field_name = ?
            ',
            [
              $project_id,
              $record_id,
              $field_name
            ]
          );

         return db_fetch_assoc($result)["value"];         
    }

    public function redcap_every_page_top($project_id) {

        $this->includeJsAndCss();

        if (PAGE === "DataEntry/record_home.php") {
            
            if( isset($_GET["id"]) && isset($_GET["pid"]) ) {

                //  
                $record_id = $_GET["id"];
                $project_id = $_GET["pid"];

                //  Get Project settings
                $print_jobs = $this->getSubSettings("print_jobs");
                $printers = $this->getSubSettings("printers");

                // Init arrays for POST request
                $variables = [];
                $task = [];
                $data = [];

                ?>
                    <div id="formSaveTip" style="position: fixed; left: 923px; display: block;">
						<div class="btn-group nowrap" style="display: block;">
                            <button type="button" class="btn btn btn-primaryrc btn-lg" data-toggle="modal" data-target="#printModal">
                                <span class="fas fa-print" aria-hidden="true"></span> Print Job
                            </button>
                        </div>
                    </div>
                    
                    <div id="printModal" class="modal  fade" tabindex="-1" data-backdrop="static">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content" style="min-height:500px;">
                                <div class="modal-header">
                                    <h5 class="modal-title"><span class="fas fa-print" aria-hidden="true"></span> Print Job</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="container-fluid">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="modal-step mt-3" id="modal-step-1">
                                                    <p class="h6">1. Select a print job:</p>
                                                    <select class="custom-select">
                                                        <option selected>Click here to select a print job..</option>
                                                    <?php foreach ($print_jobs as $key=>$print_job):?>
                                                        <option value="'.$key.'"><?= $print_job["pj_name"] ?></option>
                                                    <?php endforeach; ?>                        
                                                    </select>                        
                                                </div>

                                                <div class="modal-step  mt-3" id="modal-step-2">
                                                    <p class="h6">2. Select a printer:</p>
                                                    <select class="custom-select">
                                                        <option selected>Click here to select a printer..</option>
                                                        <?php 
                                                        foreach ($printers as $key=>$printer) {
                                                            echo '<option value="'.$key.'">'. $printer["printer_name"] .'</option>';
                                                            } 
                                                        ?>                            
                                                    </select>
                                                </div>

                                                <div class="modal-step mt-3" id="modal-step-3">
                                                    <p class="h6">3. Define copies:</p>
                                                    <input class="form-control" type="number" name="copies" value="1">
                                                </div>

                                            </div>
                                            <div class="col-md-6">
                                                <?php foreach( $print_jobs as $j_id=>$print_job ): ?>
                                                <?php $variables[$j_id] = [] ?>
                                                <div id="print-job-<?= $j_id ?>" class="mt-3 mb-3">
                                                    <div class="print-job-preview text-secondary">
                                                        <p><b>Job description:</b><br><?= $print_job["pj_descr"] ?></p>
                                                        <p><b>Bartender file:</b><br><img class="file-icon" src="<?= $this->getUrl('img\bartender_icon.png') ?>"><span class="font-italic"><?= $print_job["pj_file"] ?></span></p>
                                                        <p><b>Variables: </b></p>
                                                        <table class="table table-sm table-borderless">
                                                            <thead>
                                                                <tr>
                                                                <th scope="col">Task #</th>
                                                                <th scope="col">Variable</th>
                                                                <th scope="col">Value</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>                                            
                                                                <?php foreach ( $print_job["print_job_tasks"] as $t_id => $print_task): ?>
                                                                    <?php $task = []; ?>
                                                                    <td colspan="3" class="table-info" scope="row"><?= $t_id + 1 ?></td>
                                                                    <?php foreach ( $print_task["print_job_variables"] as $v_id => $p_var ):  ?>
                                                                        <?php 
                                                                            $field_value="";
                                                                            if(!empty($p_var["pj_var_field"])) {
                                                                                $field_value=$this->getFieldValue($project_id, $record_id, $p_var["pj_var_field"]);
                                                                            }
                                                                            $var_name = $p_var["pj_var_name"];
                                                                            $var_value = $p_var["pj_var_prefix"].$field_value.$p_var["pj_var_suffix"];
                                                                            $task[$var_name]  = $var_value;
                                                                        ?>
                                                                        <tr>
                                                                            <td scope="row"></td>
                                                                            <td><?= $var_name  ?></td>
                                                                            <td><?= $var_value ?></td>
                                                                        </tr>                                                        
                                                                    <?php endforeach; ?>
                                                                    <?php array_push( $variables[$j_id], $task); ?>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>                                       
                                                    </div>
                                                </div>
                                                <?php endforeach;                                
                                                    $data["variables"] = $variables;
                                                ?>
                                                <!-- <pre><?php // json_encode($data) ?></pre> -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary" disabled>Print</button>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php
            }
        }    
    }

}