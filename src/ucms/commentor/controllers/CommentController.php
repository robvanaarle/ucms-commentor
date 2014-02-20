<?php

namespace ucms\commentor\controllers;

/**
 * Todo:
 * [x] What if commente to comment on does not exist?
 * [x] What if comment to update does not exist?
 */

abstract class CommentController extends \ultimo\mvc\Controller {
  
  /**
   * @var ucms\commentor\managers\CommentorManager
   */
  protected $commentorMgr;
  protected $config;
  
  
  protected $commenteControllerName;
  protected $commenteModelName;
  protected $commentModelName;
  protected $commenteForeignKey;
  
  protected function init() {
    $this->commenteModelName = $this->detectCommenteModelName();
    $this->commenteControllerName = strtolower($this->commenteModelName);
    $this->commentModelName = $this->commenteModelName . 'Comment';
    
    $this->commenteForeignKey = $this->detectCommenteForeignKey();
  }
  
  protected function beforeAction($actionName) {
    $this->commentorMgr = $this->getPlugin('helper')
                               ->getHelper('Commentor')
                               ->getManager($this->commentModelName);
                               
    $this->config = $this->getPlugin('helper')
                         ->getHelper('Commentor')
                         ->getCommentConfig($this->commentModelName);
    
    $this->application->getPlugin('viewRenderer')->setController('comment');
    
    if (!$this->config['comments_enabled']) {
      throw new \ultimo\mvc\exceptions\DispatchException("Comments are not enabled.", 404);
    }
  }
  
  public function actionIndex() {
    $commente_id = $this->request->getParam($this->commenteForeignKey);
    $locale = $this->request->getParam('locale');
    
    $this->view->comments = $this->module->getPlugin('helper')
                                         ->getHelper('Commentor')
                                         ->getComments($this->commentModelName, $commente_id, $locale, $this->request->getParams());
    $this->view->commentModelName = strtolower($this->commentModelName);
  }
  
  public function actionCreate() {
    $commente_id = $this->request->getParam('commente_id');
    $locale = $this->request->getParam('locale');
    
    if ($this->getCommente($commente_id, $locale) === null) {
      throw new \ultimo\mvc\exceptions\DispatchException("Commente with id '{$commente_id}' and locale '{$locale}' does not exist.", 404);
    }
    
    $returnUri = $this->request->getParam('returnUri');
    
    $this->view->commente_id = $commente_id;
    $this->view->locale = $locale;
    $this->view->commentModelName = strtolower($this->commentModelName);
    $this->view->returnUri = $returnUri;

    
    $commentForm = $this->module->getPlugin('formBroker')->createForm(
      'comment\CreateForm', $this->request->getParam('comment', array())
    );
    
    if ($this->request->isPost()) {
      if ($commentForm->validate()) {
        $comment = $this->commentorMgr->create($this->commentModelName);
        $comment->commente_id = $commente_id;
        $comment->locale = $locale;
        $comment->comment = $commentForm['comment'];
        $comment->datetime = date("Y-m-d H:i:s");
        $comment->ip = $_SERVER['REMOTE_ADDR'];
        $comment->commentor_id = $this->module->getPlugin('authorizer')->getUser()->getId();
        $comment->save();
        
        if ($returnUri === null) {
          return $this->getPlugin('redirector')->redirect($this->getRedirectParams($commente_id, $locale));
        } else {
          return $this->getPlugin('redirector')->setRedirectUrl($returnUri);
        }
      }
    }
    
    $this->view->commentForm = $commentForm;
  }
  
  public function actionUpdate() {
    $returnUri = $this->request->getParam('returnUri');
    $id = $this->request->getParam('id');
    $key = array('id' => $id);
    
    // check if the user is allowed to update messages of other users
    if (!$this->module->getPlugin('authorizer')->isAllowed("{$this->commenteControllerName}comment.update", array('user_id' => 1, 'commentor_id' => 2))) {
      $isAllowedUpdateUnowned = false;
      
      // add the user id to the key, this way it's used in a query
      $key['commentor_id'] = $this->module->getPlugin('authorizer')->getUser()->getId();
    } else {
      $isAllowedUpdateUnowned = true;
    }
    
    $commentForm = $this->module->getPlugin('formBroker')->createForm(
      'comment\UpdateForm', $this->request->getParam('comment', array())
    );

    if ($this->request->isPost()) {
      if ($commentForm->validate()) {
        $comment = $this->commentorMgr->get($this->commentModelName, $key, true);
        $comment->comment = $commentForm['comment'];
        $comment->save();
      
        if ($returnUri === null) {
          $comment = $this->commentorMgr->get($this->commentModelName, $key);
          if ($comment === null) {
            $comment = array('commente_id' => null, $locale => null);
          }
          return $this->getPlugin('redirector')->redirect($this->getRedirectParams($comment['commente_id'], $comment['locale']));
        } else {
          return $this->getPlugin('redirector')->setRedirectUrl($returnUri);
        }
      }
    } else {
      $comment = $this->commentorMgr->get($this->commentModelName, $id);

      if (!$isAllowedUpdateUnowned && $comment->commentor_id != $this->module->getPlugin('authorizer')->getUser()->getId()) {
        return $this->module->getPlugin('authorizer')->handleAccessDenied();
      }
      
      if ($comment === null) {
        throw new \ultimo\mvc\exceptions\DispatchException("Comment with id '{$id}' does not exist.", 404);
      }
      
      $commentForm->fromArray($comment->toArray());
    }
    
    $this->view->returnUri = $returnUri;
    $this->view->id = $id;
    $this->view->commentModelName = strtolower($this->commentModelName);
    $this->view->commentForm = $commentForm;
  }
  
  
  public function actionDelete() {
    $id = $this->request->getParam('id', 0);
    $key = array('id' => $id);
    
    if (!$this->module->getPlugin('authorizer')->isAllowed("{$this->commenteControllerName}comment.update", array('user_id' => 1, 'commentor_id' => 2))) {
      $isAllowedUpdateUnowned = false;
      $key['commentor_id'] = $this->module->getPlugin('authorizer')->getUser()->getId();
    } else {
      $isAllowedUpdateUnowned = true;
    }
    
    $returnUri = $this->request->getParam('returnUri');
    if ($returnUri === null) {
      // do this first, before delete it
      $comment = $this->commentorMgr->get($this->commentModelName, $key);
      if ($comment === null) {
        $comment = array('commente_id' => null, 'locale' => null);
      }
      
      $returnParams = $this->getRedirectParams($comment['commente_id'], $comment['locale']);
    }
    
    $comment = $this->commentorMgr->get($this->commentModelName, $key, true);
    $comment->delete();
    if ($returnUri === null) {
      $this->getPlugin('redirector')->redirect($returnParams);
    } else {
      $this->getPlugin('redirector')->setRedirectUrl($returnUri);
    }
  }
  
  protected function detectCommenteForeignKey() {
    // detect relation between commente and comment model
    $commenteClass = $this->module->getFQName('models\\' . $this->commenteModelName);
    $relations = call_user_func($commenteClass . '::getRelations');
    foreach ($relations as $relation) {
      if ($relation[0] == $this->commentModelName) {
        foreach ($relation[1] as $foreignKey => $key) {
          if ($key == 'commente_id') {
            return $foreignKey;
          }
        }
      }
    }
    return null;
  }
  
  protected function detectCommenteModelName() {
    $controllerName = strtolower($this->getName());
    
    // strip off 'comment' postfix
    return ucfirst(substr($controllerName, 0, strlen($controllerName)-7));
  }
  
  abstract protected function getCommente($commente_id, $locale);
  
  private function getRedirectParams($commenteId, $locale=null) {
    $returnParams = array('action' => 'index', 'controller' => $this->commenteControllerName);
    if ($commenteId === null) {
      return $returnParams;
    }
    $returnParams['locale'] = $locale;
    
    if ($this->commenteForeignKey !== null) {
      $returnParams['action'] = 'read';
      $returnParams[$foreignKey] = $commenteId;
    }
    
    return $returnParams;
  }
}