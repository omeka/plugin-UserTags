<?php

class Table_UserRecordsTag extends Omeka_Db_Table
{
    
    public function findTagsBy($params = array())
    {
        $user = current_user();
        if(!isset($params['user'])) {
            $user = current_user();
        }
        
        if($user) {
            $db = $this->getDb();
            $tagTable = $db->getTable('Tag');
            $select = $tagTable->getSelectForFindBy($params);
            $select->joinInner( array('user_records_tags'=>$db->UserRecordsTags), 'user_records_tags.tag_id = tags.id', array());
            $select->where('user_records_tags.owner_id = ?', $user->id);
            $select->where('user_records_tags.record_type = ?', 'Item');
            return $tagTable->fetchObjects($select);
        }
    }
    public function findForUser($tagId, $itemId, $userId = null)    
    {
        if(!$userId) {
            $user = current_user();
            $userId = $user->id;
        }
        $select = $this->getSelectForFindBy(array('tag_id'=>$tagId, 'item_id'=>$itemId, 'owner_id'=>$userId));
        return $this->fetchObject($select);
    }
    
}