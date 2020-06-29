<b>Example XML Structure:</b>
<table class="mw-ui-table mw-full-width" border="1" width="100%">
<tr>
    <td>Type</td>
    <td>Redirect From URL Address</td>
    <td>Redirect To URL Address</td>
</tr>
    <tr>
        <td>Redirect 301</td>
        <td>https://mysite.com/redirect-from-link</td>
        <td>/redirect-to-link</td>
    </tr>
</table>
<br />
<script type="text/javascript">

    $(document).ready(function(){

        var uploader = mw.uploader({
            filetypes:"images,videos",
            multiple:false,
            element:"#mw_uploader"
        });

        $(uploader).bind("FileUploaded", function(event, data){
            mw.$("#mw_uploader_loading").hide();
            mw.$("#mw_uploader").show();
            mw.$("#upload_info").html("");
            alert(data.src);
        });

        $(uploader).bind('progress', function(up, file) {
            mw.$("#mw_uploader").hide();
            mw.$("#mw_uploader_loading").show();
            mw.$("#upload_info").html(file.percent + "%");
        });

        $(uploader).bind('error', function(up, file) {
            mw.notification.error("The file is not uploaded.");
        });

    });
</script>
<span id="mw_uploader" class="mw-ui-btn">
<span class="ico iupload"></span>
<span><i class="fa fa-upload"></i> Select file to upload<span id="upload_info"></span>
</span>
</span>
