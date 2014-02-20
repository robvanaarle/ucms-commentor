<?php

namespace ucms\commentor\forms\comment;

class ModifyForm extends \ultimo\form\Form {
  
  protected function init() {
    $this->appendValidator('comment', 'StringLength', array(4));
  }
}