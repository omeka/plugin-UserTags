<?php $view = get_view(); ?>
<div class="field">
    <div class="two columns alpha">
        <label><?php echo __('Roles with private tags')?></label>    
    </div>
    <div class="inputs five columns omega">
        <p class="explanation"><?php echo __("Check the roles whose tags should be private. Their tags will be visible only to themselves. This only applies to tags on Items."); ?></p>
        <div class="input-block">        
            <?php 
            $privateRoles = unserialize(get_option('user-tags-private-roles'));
            $userRoles = get_user_roles();
            echo '<ul>';
            
            foreach($userRoles as $role=>$label) {
                echo '<li>';
                echo $view->formCheckbox('user_tags_private_roles[]', $role,
                        array('checked'=> in_array($role, $privateRoles) ? 'checked' : '')
                );          
                echo $label;
                echo '</li>';
            }   
            echo '</ul>';
            ?>
        </div>
    </div>
</div>