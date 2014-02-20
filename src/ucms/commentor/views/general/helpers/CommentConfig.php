<?php

namespace ucms\commentor\views\general\helpers;

class CommentConfig extends \ultimo\phptpl\mvc\Helper {
	protected $configs = array();
	
  public function __invoke($commentModelName, $key=null) {
  	if (!isset($this->configs[$commentModelName])) {
      
        $moduleConfig = $this->module->getPlugin('config')->getConfig('commentor');
        if ($moduleConfig === null) {
          $moduleConfig = array();
        }

        $defaultConfig = $this->module->getPlugin('config')->getViewConfig('comment');
        if ($defaultConfig === null) {
          $defaultConfig = array();
        }
        $config = $this->module->getPlugin('config')->getViewConfig(strtolower($commentModelName));
        if ($config === null) {
          $config = array();
        }

        $viewConfig = $this->module->getPlugin('config')->mergeConfigs($defaultConfig, $config);
        $this->configs[$commentModelName] = $this->module->getPlugin('config')->mergeConfigs($moduleConfig, $viewConfig);

  	}
  	
    if ($key !== null) {
      return $this->configs[$commentModelName][$key];
    }
    
    return $this->configs[$commentModelName];
  }
}