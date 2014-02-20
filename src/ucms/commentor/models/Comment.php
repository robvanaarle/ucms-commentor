<?php

namespace ucms\commentor\models;

abstract class Comment extends \ultimo\orm\Model {
  public $id;
  public $datetime = '';
  public $commentor_id = '';
  public $comment = '';
  public $commente_id = '';
  public $locale = '';
  public $ip = '';
  
  static protected $fields = array('id', 'datetime', 'commentor_id', 'comment', 'commente_id', 'locale', 'ip');
  static protected $primaryKey = array('id');
  static protected $autoIncrementField = 'id';
  
  /* you can define something like below in your comment model
  static protected $relations = array(
    'commentor' => array('User', array('commentor_id' => 'id'), self::MANY_TO_ONE),
    'message' => array('Commente', array('commente_id' => 'id', 'locale' => 'locale'), self::MANY_TO_ONE)
  );
  */

}