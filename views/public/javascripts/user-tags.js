
var UserTags = {
        
        //onclick action to add a tag to my tags
        addMyTag : function() {
            var tag = jQuery(this).html();
            var json = {"tag" : tag, "itemId" : UserTags.itemId};
            jQuery.post(UserTags.webRoot + "/user-tags/index/add-my", json, UserTags.addMyTagResponseHandler);
            UserTags.refreshTagListeners();
        },
        
        addMyTagResponseHandler : function(response, status, jqx) {
            var myTagsEl = jQuery('.user-tags ul');
            var html = myTagsEl.html();
            jQuery('#user-tags-tag-id-' + response.added).parent('li').remove();
            myTagsEl.html(html + response.html);
            jQuery('.user-tags-list span.remove').click(UserTags.removeMyTag);
        },
        
        removeMyTag : function() {
            var elId = jQuery(this).attr('id');
            var tagId = elId.replace('user-tags-tag-id-', '');
            var json = {"tagId" : tagId, "itemId" : UserTags.itemId};
            jQuery.post(UserTags.webRoot + "/user-tags/index/remove-my", json, UserTags.removeMyTagResponseHandler);
        },
        
        removeMyTagResponseHandler : function(response, status, jqx) {
            var elId = '#user-tags-tag-id-' + response.removed;
            var removeSpan = jQuery(elId);
            removeSpan.parent().remove();
        },
        
        addNewTags : function() {
            var tags = jQuery('input#tags').val();
            json = {"tags" : tags, "itemId" : UserTags.itemId};
            jQuery.post(UserTags.webRoot + "/user-tags/index/add", json, UserTags.addNewTagsResponseHandler);
            jQuery('input#tags').val('');
        },
        
        addNewTagsResponseHandler : function(response, status, jqx) {
            var myTagsEl = jQuery('.user-tags-list');
            var html = myTagsEl.html();
            var newTag = jQuery();
            var newTagId = '';
            var duplicates = '';
            for(var i=0; i<response.length; i++) {
                newTag = jQuery(response[i].html);
                newTagId = "#" + newTag.find('span').attr('id');
                if (jQuery(newTagId).length < 1) {
                    html += response[i].html;
                } else {
                    duplicates += '<li>"' + newTag.find('a').text() + '"</li>';
                }
            }
            myTagsEl.html(html);

            jQuery('#tag-errors ul').html('');

            if (duplicates.length > 0) {
                jQuery('#tag-errors ul').append(duplicates);
                jQuery('#tag-errors').show();
            } else {
                jQuery('#tag-errors').hide();
            }
            UserTags.refreshTagListeners();
        },
        
        refreshTagListeners : function() {
            jQuery('div.user-tags-general span.user-tags-tag').unbind("click");
            jQuery('.user-tags-list span.remove').unbind("click");
            jQuery('div.user-tags-general span.user-tags-tag').bind("click", UserTags.addMyTag);
            jQuery('.user-tags-list span.remove').bind("click", UserTags.removeMyTag);
        }
};

/**
 * Set up autocomplete for tags field.
 *
 * @param {string} inputSelector Selector for input to autocomplete on.
 * @param {string} tagChoicesUrl Autocomplete JSON URL.
 */


UserTags.tagChoices = function () {
    var inputSelector = "#tags";
    var tagChoicesUrl = "http://localhost/Omeka/admin/tags/autocomplete";
    UserTags.tagDelimiter = ",";
    function split(val) {
        var escapedTagDelimiter = UserTags.tagDelimiter.replace(/([.?*+\^$\[\]\\(){}\-])/g, "\\$1");
        var re = new RegExp(escapedTagDelimiter + '\\s*');
        return val.split(re);
    }
    function extractLast(term) {
        return split(term).pop();
    }

    // Tokenized input based on
    // http://jqueryui.com/demos/autocomplete/multiple.html
    jQuery(inputSelector).autocomplete({
        source: function (request, response) {
            jQuery.getJSON(tagChoicesUrl, {
                term: extractLast(request.term)
            }, function (data) {
                response(data);
            });
        },
        focus: function () {
            return false;
        },
        select: function (event, ui) {
            var terms = split(this.value);
            // remove the current input
            terms.pop();
            // add the selected item
            terms.push(ui.item.value);
            // add placeholder to get the comma-and-space at the end
            terms.push('');
            this.value = terms.join(UserTags.tagDelimiter + ' ');
            return false;
        }
    });
};

jQuery(document).ready(function() {
   jQuery('button#user-tags-submit').click(UserTags.addNewTags);
   jQuery('div.user-tags-general span.user-tags-tag').bind("click", UserTags.addMyTag);
   jQuery('.user-tags-list span.remove').bind("click", UserTags.removeMyTag);
   UserTags.tagChoices();
});
