<?php
/**
 * Autoresponder plugin for phplist
 * 
 * This plugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * @category  phplist
 * @package   Autoresponder
 * @author    Cameron Lerch (Sponsored by Brightflock -- http://brightflock.com)
 * @copyright 2013 Cameron Lerch
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 * @link      http://brightflock.com
 */
class Autoresponder_Controller {
    private $model;
    private $root;
    private $base_url;
    
    public function __construct() {
        $this->model = new Autoresponder_Model();
        $this->root = AutoResponder_Util::pluginRoot('Autoresponder');
    }
    
    public function addRequest() {
        $mid = isset($_GET['mid']) ? intval($_GET['mid']) : null;
        $mins = isset($_GET['mins']) ? intval($_GET['mins']) : null;
        $new = isset($_GET['new']) ? 1 : 0;
        
        if (!$mid || !$mins) {
            return false;
        }
        
        return $this->model->addAutoresponder($mid, $mins, $new);
    }  
    
    public function deleteRequest() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        
        if (!$id) {
            return false;
        }
        
        return $this->model->deleteAutoresponder($id);
    }
    
    public function toggleEnabledRequest() {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        
        if (!$id) {
            return false;
        }
        
        return $this->model->toggleEnabled($id);
    }
    
    public function process() {
        $this->model->setLastProcess();
        
        return $this->model->setPending(); 
    }
    
    public function adminView($params) {
        $vars = array(
            'params' => $params,
            'current' => $this->model->getAutoresponders(),
            'possible' => $this->model->getPossibleMessages(),
            'last_process' => $this->model->getLastProcess(),
            'process' => AutoResponder_Util::pluginURL('process', array('pi' => 'Autoresponder'))
        );
        
        return $this->view('admin', $vars);
    }
    
    private function view($name, $vars = array()) {
        if (!is_file($this->root . $name . '.tpl.php')) {
            return null;
        }
        
        ob_start();
        extract($vars);
        require($this->root . $name . '.tpl.php');
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
}

?>