<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/** Configurable Reports
 * A Moodle block for creating customizable reports
 * @package blocks
 * @author Nick Koeppen
 */ 

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

abstract class plugin_form extends moodleform {
    
    function add_action_buttons(){
        if (!isset($this->_customdata['id'])) {
            parent::add_action_buttons(true, get_string('add'));
        } else {
            parent::add_action_buttons();
        }
    }
    
    function get_used_columns(){
        return array();
    }
    
    function get_column_options(){
        $plugclass = $this->_customdata['plugclass'];
        $report = $plugclass->report;
        $reportclass = report_base::get($report);
    
        $options = array();
        if ($report->type != 'sql') {
            $columnclass = $reportclass->get_component('columns');
            if(!isset($columnclass)){
                return null;
            }
            $columns = $columnclass->get_all_instances();
            if (empty($columns)) {
                //print_error('nocolumns');
            }
             
            $columnsused = $this->get_used_columns();
    
            $i = 0;
            foreach($columns as $c){
                if(!in_array($i,$columnsused))
                    $options[$i] = $c->summary;
                $i++;
            }
        } else {
            $customsqlclass = $reportclass->get_component('customsql');
            if(!isset($customsqlclass)){
                return null;
            }
            $config = $customsqlclass->config;
            
            if(isset($config->querysql)){
                $sql = $config->querysql;
                $sql = $reportclass->prepare_sql($sql);
                if($rs = $reportclass->execute_query($sql)){
                    foreach ($rs as $row) {
                        $i = 0;
                        foreach($row as $colname=>$value){
                            $options[$i] = str_replace('_', ' ', $colname);
                            $i++;
                        }
                        break;
                    }
                    $rs->close();
                }
            }
        }
    
        return $options;
    }
    
    function get_data(){
        $data = parent::get_data();
        if(!isset($data)){
            return NULL;
        }
        
        unset($data->mform_showadvanced_last);
        unset($data->submitbutton);
        
        return $data;
    }
    
    function set_data($instance){
        $data = cr_unserialize($instance->configdata);
        
        parent::set_data($data);
    }
    
    function save_data($data, $instanceid = null){  
        global $DB;
        
        $plugclass = $this->_customdata['plugclass'];
        if (isset($instanceid)) {
            $record = $DB->get_record('block_configurable_reports_plugin', array('id' => $instanceid));
            $plugclass->update_instance($record, $data);
        } else {
            $plugclass->add_instance($data);
        }
    }
}