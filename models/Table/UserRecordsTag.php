<?php

class Table_UserRecordsTag extends Omeka_Db_Table
{

    public function findTagsBy($params = array())
    {
        if(isset($params['user'])) {
            $user = $params['user'];
        }

        $db = $this->getDb();
        $tagTable = $db->getTable('Tag');

        if($user) {
            //roles that get private tags don't leave a row in records_tags, so separate the queries
            $privateRoles = unserialize(get_option('user-tags-private-roles'));
            if(is_null($privateRoles)) {
                $privateRoles = array();
            }
            if(in_array($user->role, $privateRoles )) {
                $select = new Omeka_Db_Select;
                $db = $this->getDb();
                $select->from(array('tags'=>$db->Tag), array('tags.*', 'tagCount'=>'COUNT(tags.id)'))
                ->joinInner( array('user_records_tags'=>$db->UserRecordsTags), 'user_records_tags.tag_id = tags.id', array())
                ->group('tags.id')
                ->where('user_records_tags.owner_id = ?', $user->id);
            } else {
                $select = $tagTable->getSelectForFindBy($params);
                $select->joinInner( array('user_records_tags'=>$db->UserRecordsTags), 'user_records_tags.tag_id = tags.id', array());
                $select->where('user_records_tags.owner_id = ?', $user->id);
            }
            return $tagTable->fetchObjects($select);
        }

        //get just the tags that are associated with any user
        $select = new Omeka_Db_Select;
        $db = $this->getDb();
        $select->from(array('tags'=>$db->Tag), array('tags.*', 'tagCount'=>'COUNT(tags.id)'))
        ->joinInner( array('user_records_tags'=>$db->UserRecordsTags), 'user_records_tags.tag_id = tags.id', array())
        ->group('tags.id');
        return $tagTable->fetchObjects($select);
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