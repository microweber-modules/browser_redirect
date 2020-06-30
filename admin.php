<?php
only_admin_access();
/**
 * Dev: Bozhidar Slaveykov
 * Emai: bobi@microweber.com
 * Date: 11/18/2019
 * Time: 10:26 AM
 */
?>


<?php if (isset($params['backend'])): ?>
    <module type="admin/modules/info"/>
<?php endif; ?>

<script type="text/javascript">

    
    function uploadRedirectsFromFile() {
        var data = {};
        var module_id = 'upload-redirects-browser-redirect';

        var opts = {};
        opts.width = '600';
        opts.height = '600';

        uploadXmlModal = mw.tools.open_global_module_settings_modal('browser_redirect/upload_redirects', module_id, opts, data);
    }
    
    function editBrowserRedirect(id) {
        var data = {};
        data.id = id;
        var module_id = 'edit-browser-redirect-' + id;

        var opts = {};
        opts.width = '500';
        opts.height = '600';

        editBrowserRedirectModal = mw.tools.open_global_module_settings_modal('browser_redirect/edit_form', module_id, opts, data);
    }

    function deleteAll() {
        mw.tools.confirm('<?php _e('Do you want to delete all browser redirects?'); ?>', function() {
            data = {};
            $.post(mw.settings.api_url + 'browser_redirect_delete_all', data, function(resp) {
                mw.reload_module('browser_redirect');
            });
        });
    }
    
    function deleteBrowserRedirect(id) {

        $.post("<?php echo api_url('browser_redirect_delete');?>", {id:id}, function(data){
           // Deleted
        });

        $('.js-browser-redirect-tr-' + id).remove();
    }

</script>

<div id="mw-admin-content" class="admin-side-content">
    <div class="mw_edit_page_default" id="mw_edit_page_left">

        <a href="javascript:;" onClick="editBrowserRedirect(false)" class="mw-ui-btn mw-ui-btn-medium mw-ui-btn-notification">
            <i class="fa fa-plus"></i> &nbsp; <?php echo _e('Add new browser redirect');?>
        </a>

        <a href="javascript:;" onClick="uploadRedirectsFromFile(false)" class="mw-ui-btn mw-ui-btn-medium mw-ui-btn-notification">
            <i class="fa fa-upload"></i> &nbsp; <?php echo _e('Upload Redirects From File');?>
        </a>

        <div class="mw-ui-box mw-ui-box-content" data-view="" style="margin-top: 15px;">

            <table class="mw-ui-table mw-full-width mw-ui-table-basic">
                <thead>
                <tr>
                    <th style="width:300px;"><?php echo _e('Redirect from URL');?></th>
                    <th><?php echo _e('Redirect to URL');?></th>
                    <th><?php echo _e('Redirect Browsers');?></th>
                    <th><?php echo _e('Redirect code');?></th>
                    <th><?php echo _e('Enabled');?></th>
                    <th style="width:190px;"><?php echo _e('Action');?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $browserRedirects = get_browser_redirects();
                if (!empty($browserRedirects)):
                foreach($browserRedirects as $browserRedirect):
                ?>
                <tr class="js-browser-redirect-tr-<?php echo $browserRedirect['id']; ?>">
                    <td><?php echo $browserRedirect['redirect_from_url']; ?></td>
                    <td><?php echo $browserRedirect['redirect_to_url']; ?></td>
                    <td><?php echo $browserRedirect['redirect_browsers']; ?></td>
                    <td><?php echo $browserRedirect['redirect_code']; ?></td>
                    <td>
                        <?php if ($browserRedirect['active']): ?>
                        <?php echo _e('Yes');?>
                        <?php else: ?>
                            <?php echo _e('No');?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="javascript:;" onClick="editBrowserRedirect('<?php echo $browserRedirect['id']; ?>')" class="mw-ui-btn mw-ui-btn-medium"><span class="mw-icon-pen"></span> &nbsp; <?php echo _e('Edit');?></a>
                        <a href="javascript:;" onClick="deleteBrowserRedirect('<?php echo $browserRedirect['id']; ?>')" class="mw-ui-btn mw-ui-btn-medium"><span class="fa fa-times"></span> &nbsp; <?php echo _e('Delete');?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="5">No redirects found.</td>
                </tr>
                <?php endif; ?>

                </tbody>
            </table>

            <a class="mw-ui-btn mw-ui-btn-medium" style="float:right;margin-top:15px;margin-right:22px;margin-bottom: 15px" onclick="deleteAll()"><span class="fa fa-times"></span> &nbsp;Delete all redirects</a>
        </div>

    </div>
</div>
