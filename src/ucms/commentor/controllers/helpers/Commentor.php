<?php

namespace ucms\commentor\controllers\helpers;

class Commentor extends \ultimo\mvc\plugins\ControllerHelper {
  
  public function getManager($commentModelName) {
    $manager = $this->module->getPlugin('uorm')->getManager('Commentor');
    $manager->associateModel(
      $this->module->getPlugin('uorm')->getTableIdentifier($commentModelName),
      $commentModelName,
      $this->module->getPlugin('uorm')->getModelClass($commentModelName)
    );
    return $manager;
  }
  
  protected function parseGetCommentOptions($commentModelName, array $config, array $params) {
    $options = array();
    
    if ($config['comments_enabled']) {
      if (isset($params['comment_count']) && !$config['comments_per_page_locked']) {
        $options['count'] = $params['comment_count'];
      } else {
        $options['count'] = $config['comments_per_page'];
      }
      
      if (isset($params['comment_page'])) {
        $options['offset'] = ($params['comment_page']-1) * $options['count'];
      }
      
      $options['order'] = $config['comments_sort_order'];
    }
    
    return $options;
  }
  
  public function getCommentConfig($commentModelName) {
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
    return $this->module->getPlugin('config')->mergeConfigs($moduleConfig, $viewConfig);
  }
  
  public function getComments($commentModelName, $commente_id, $locale) {
    $params = $this->request->getParams();
    $config = $this->getCommentConfig($commentModelName);
    
    if (!$config['comments_enabled']) {
      return array();
    }
    
    $commentorMgr = $this->getManager($commentModelName);

    $options = array();
    
    if ($config['comments_enabled']) {
      if (isset($params['comment_count']) && !$config['comments_per_page_locked']) {
        $options['count'] = $params['comment_count'];
      } else {
        $options['count'] = $config['comments_per_page'];
      }
      
      if (isset($params['comment_page'])) {
        if ($params['comment_page'] == 'last') {
          $commentCount = $commentorMgr->getCommentCount($commentModelName, $commente_id, $locale);
          $options['offset'] = floor(($commentCount-1)/$options['count']) * $options['count'];
        } else {
          $options['offset'] = max(0, ($params['comment_page']-1) * $options['count']);
        }
        
      }
      
      $options['order'] = $config['comments_sort_order'];
    }
    
    $comments = $commentorMgr->getComments($commentModelName, $commente_id, $locale, $options);
    return $comments;
  }
}