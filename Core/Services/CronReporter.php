<?php
namespace Core\Services;
class CronReporter {
    private $output = "";
    
    public function log($message) {
        echo $message;
        $this->output .= $message;
    }
    
    public function getOutput() {
        return $this->output;
    }
    
    public function getHtmlOutput() {
        return "<pre style='background-color: #1e1e1e; color: #ffffff; padding: 20px; border-radius: 8px; overflow-x: auto; font-family: Consolas, monospace; font-size: 12px; line-height: 1.4;'>" 
               . htmlspecialchars($this->output, ENT_QUOTES, 'UTF-8') 
               . "</pre>";
    }
}


