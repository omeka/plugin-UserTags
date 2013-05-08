<?php

define('USER_TAGS_PLUGIN_DIR', PLUGIN_DIR . '/UserTags');

class UserTagsPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
                'install',
                'config_form',
                'config',
                'public_items_show',
                'public_head',
                'add_item_tag',
                'remove_item_tag',
                'items_browse_sql'
            );
    
    public function hookInstall()
    {
        $db = $this->_db;
        $sql = "
            CREATE TABLE IF NOT EXISTS `$db->UserRecordsTag` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `record_id` int(10) unsigned NOT NULL,
              `record_type` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
              `tag_id` int(10) unsigned NOT NULL,
              `owner_id` int(10) unsigned NOT NULL,
              `added` datetime NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `tag` (`record_type`,`record_id`,`tag_id`, `owner_id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;        
        ";
        $db->query($sql);
        
        set_option('user-tags-private-roles', serialize(array()));
    }
    
    public function hookConfigForm($args)
    {
        include(USER_TAGS_PLUGIN_DIR . '/config_form.php');
    }
    
    public function hookConfig($args)
    {
        $post = $args['post'];
        set_option('user-tags-private-roles', serialize($post['user_tags_private_roles']));
    }
    
    public function hookPublicHead($args)
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if($request->getControllerName() == 'items' && $request->getActionName() == 'show') {
            //add javascript and css for dealing with the user tags only to item show pages
            $view = $args['view'];
            //don't know why $view->item isn't giving me the object, but it isn't, so go via request
            $itemId = $request->getParam('id');
            queue_css_file('user-tags');
            queue_css_file('jquery-ui');
            queue_js_file('user-tags');
            $js = "UserTags.webRoot = '" . WEB_ROOT . "'; ";
            $js .= "UserTags.itemId = " . $itemId . "; ";
            queue_js_string($js);            
        }

    }
    
    public function hookPublicItemsShow($args)
    {
        if($user = current_user()) {
            $view = $args['view'];
            $item = $args['item'];        
            $myTags = $this->_db->getTable('UserRecordsTag')->findTagsBy(array('record'=>$item, 'user'=>$user));
            
            //need to remove itemTags that are mine from the list to display
            $myTagsIds = array();
            foreach($myTags as $tag) {
                $myTagsIds[] = $tag->id;
            }
            $itemTags = $item->Tags;
            
            foreach($itemTags as $index=>$itemTag) {
                if(in_array($itemTag->id, $myTagsIds)) {
                    unset($itemTags[$index]);
                }
            }
            
            $html = "<div class='user-tags'>";
            $html .= '<h2>My tags</h2>';
            $html .= "<p class='my-user-tags'>" . __("My tags") . "</p>";
            $html .= "<div class='user-tags-my'><ul>";
            $html .= $this->_userItemTagString($myTags, true);
            $html .= "</ul></div>";
            
            if(!empty($itemTags)) {
                $html .= "<p class='explanation'>" . __('Click existing tags to add them to your own tags.') . "</p>";
                $html .= "<div class='user-tags-general'><ul>";
                $html .= $this->_userItemTagString($itemTags, false);
                $html .= "</ul></div>";
            }
            
            $html .= "<div class='user-tags-new'>";
            
            $html .= "<div style='float:left'>";
            $html .= "<label for='user_tags_new'>" . __("Add Tags") . "</label>";
            $html .= "<p class='explanation'>" .  __('Separate tags with %s', option('tag_delimiter')) . "</p>";
            
            $html .= "</div>";
            
            $html .= '<input type="text" name="tags" id="tags" class="textinput" value="" />';
            $html .= "<button id='user-tags-submit'>" . __("Submit") . "</button>";
            $html .= "</div>";
            $html .= '</div>';
            echo $html;
        }
    }
    
    /**
     * Add the UserRecordTag when a tag is added. If the user role's tags are private, also delete the records_tags row
     * @param array $args
     */
    
    public function hookAddItemTag($args)
    {
        $user = current_user();
        if($user && in_array($user->role, unserialize(get_option('user-tags-private-roles')))) {
            $recordsTagsTable = $this->_db->getTable('RecordsTags');
            $item = $args['record'];
            $added = $args['added'];
            foreach($added as $tag) {
                $userRecordsTag = new UserRecordsTag();
                $userRecordsTag->tag_id = $tag->id;
                $userRecordsTag->record_type = 'Item';
                $userRecordsTag->record_id = $item->id;
                $userRecordsTag->save();
                //kill the record from records_tags. Oh records_tags row, we hardly knew ye
                $recordsTag = $recordsTagsTable->findForRecordAndTag($item, $tag);
                if($recordsTag) {
                    $recordsTag->delete();
                }
            }                
        }
    }
    
    /**
     * Deletes the UserRecordTag
     * @param unknown_type $args
     */
    
    public function hookRemoveItemTag($args)
    {
        if($user && in_array($user->role, unserialize(get_option('user-tags-private-roles')))) {
            $item = $args['record'];
            $removed = $args['removed'];
            $userRecordsTagTable = $this->_db->getTable('UserRecordsTag');
            foreach($removed as $tag) {
                $userRecordsTags = $userRecordsTagTable->findUsersRecordsTags($tag, $item);
                foreach($userRecordsTags as $userRecordsTag) {
                    $userRecordsTag->delete();
                }
            }
        }
    }
    
    public function hookItemsBrowseSql($args)
    {
        $select = $args['select'];
        $params = $args['params'];
        $user = current_user();
        if(!$user) {
            return;
        }
        if(isset($params['my-tag'])) {
            $tagName = $params['my-tag'];
            $db = $this->_db;
            $subSelect = new Omeka_Db_Select;
            $subSelect->from(array('user_records_tags'=>$db->UserRecordsTags), array('items.id'=>'user_records_tags.record_id'))
            ->joinInner(array('tags'=>$db->Tag), 'tags.id = user_records_tags.tag_id', array())
            ->where('tags.name = ? AND user_records_tags.`record_type` = "Item"', trim($tagName));
            $subSelect->where('user_records_tags.owner_id = ?', $user->id);            
            $select->where('items.id IN (' . (string) $subSelect . ')');            
        }
    }
    
    private function _userItemTagString($tags, $link=true)
    {
        $delimiter = get_option('tag_delimiter') . ' ';

        if (empty($tags)) {
            return '';
        }
        
        $html = '';
        foreach ($tags as $tag) {
            $name = $tag['name'];
            if($link) {
                $html .= '<li><a href="' . html_escape(url('items/browse')) . '?my-tag=' . $name . '" rel="tag">' . html_escape($name) . '</a>' . "<span id='user-tags-tag-id-{$tag->id}' class='user-tags-tag remove'>" . __('(Remove?)') . "</span></li>";
            } else {
                $html .= "<li><span id='user-tags-tag-id-{$tag->id}' class='user-tags-tag'>" . html_escape($name) . "</span></li>";                
            }
            
        }
        return $html;        
    }
}