<?php

class UserTags_IndexController extends Omeka_Controller_AbstractActionController
{
    
    public function addAction()
    {
        $newTags = explode(get_option('tag_delimiter'), $_POST['tags']);
        $newTags = array_map('trim', $newTags);
        $item = $this->_helper->db->getTable('Item')->find($_POST['itemId']);
        $item->addTags($newTags);
        $response = array();
        foreach($newTags as $tagName) {
            $response[] = array('status'=>'OK', 'html'=> $this->_tagResponseHtml($tagName));
        }
        $this->_helper->json($response);
    }
    
    public function addMyAction()
    {
        $userRecordsTag = new UserRecordsTag();
        $tagName = $_POST['tag'];
        $itemId = $_POST['itemId'];
        $userRecordsTag->record_type = 'Item';
        $userRecordsTag->record_id = $itemId;
        $tag = $this->_helper->db->getTable('Tag')->findOrNew($tagName);
        $userRecordsTag->tag_id = $tag->id;
        if ($userRecordsTag->save(false)) {
            $response = array('status'=>'OK', 'added'=>$tag->id, 'html'=> $this->_tagResponseHtml($tag));
        } else {
            $response = array('status'=>'FAIL', 'added'=>$tag->id);
        }                
        $this->_helper->json($response);
    }
    
    public function removeMyAction()
    {
        $tagId = $_POST['tagId'];
        $itemId = $_POST['itemId'];
        $userRecordsTag = $this->_helper->db->getTable('UserRecordsTag')->findForUser($tagId, $itemId);
        $userRecordsTag->delete();
        $response = array('status'=>'OK', 'removed'=>$tagId);
        $this->_helper->json($response);
    }
    
    private function _tagResponseHtml($tag)
    {
        if(is_string($tag)) {
            $tag = $this->_helper->db->getTable('Tag')->findOrNew($tag);
        }
         
        $html = '<li><a href="' . html_escape(url('items/browse')) . '?my-tag=' . $tag->name . '" rel="tag">' . html_escape($tag->name) . '</a>' . "<span id='user-tags-tag-id-{$tag->id}' class='user-tags-tag remove'>" . __('(Remove?)') . "</span></li>";
        return $html;
    }
}