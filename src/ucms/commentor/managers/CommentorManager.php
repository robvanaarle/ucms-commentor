<?php

namespace ucms\commentor\managers;

class CommentorManager extends \ultimo\orm\Manager {
  
  public function init() {
    $this->registerModelNames(array('User'));
  }
  
  public function getComments($modelName, $commente_id, $locale, $options = array()) {
    $options = array_merge(array(
      'order' => 'ASC',
      'count' => 10,
      'offset' => 0
    ), $options);
    
    $query = $this->selectAssoc($modelName)
                  ->calcFoundRows('total_comment_count')
                  ->with('@commentor')
                  ->where('@locale = :locale')
                  ->where('@commente_id = :commente_id')
                  ->order('@id', $options['order'])
                  ->limit($options['offset'], $options['count']);
    
    $comments = $query->fetch(array(
      ':locale' => $locale, ':commente_id' => $commente_id)
    );
    
    $comments['commente_id'] = $commente_id;
    $comments['locale'] = $locale;
    
    return $comments;
  }
  
  public function getCommentCount($modelName, $commente_id, $locale) {
    return $this->selectAssoc($modelName)
                ->where('@locale = :locale')
                ->where('@commente_id = :commente_id')
                ->count(array(
                  ':locale' => $locale, ':commente_id' => $commente_id)
                );
  }
  
}