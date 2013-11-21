<?php

class UserRecordsTag extends Omeka_Record_AbstractRecord
{
    public $owner_id;
    public $record_id;
    public $tag_id;
    public $record_type;
    public $added;
    
    
    protected function _initializeMixins()
    {
        $this->_mixins[] = new Mixin_Timestamp($this, 'added', false);
        $this->_mixins[] = new Mixin_Owner($this);
    }
}