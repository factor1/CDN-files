<div class="wrap">
    <div id="icon-users" class="icon32"><br></div>
    <h2>CDN Files <a href="javascript:void(0);" class="add-new-h2" id="f1CdnFiles-btn-adminadd">Add New</a></h2>

    <div id="f1cdnfiles_admin_details_holder">
        <form action="" id="f1_cdnfiles_add_form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="f1_action_add" value="1" />
            <p><strong>Add file to CDN</strong><br />
            Upload: <input type="file" name="file" id="f1_cdnfiles_add_upload" /><br />
            <strong>- or -</strong><br />
            URL: <input type="text" name="file_url" id="f1_cdnfiles_add_url" /></p>
            <button name="submit">Upload</button>
        </form>
    </div>
    <?php
    if($file_added) { ?>
    <div class="updated"><p>New File has been added</p></div>
    <?php } ?>
    <?php
    if($file_delete) { ?>
        <div class="updated"><p>File has been removed</p></div>
    <?php } ?>
    <form action="" method="post" style="float:right; width:300px; text-align:right;">
        <label>Search: <input type="text" name="f1_search_term" value="<?=(isset($_POST['f1_search_term'])? $_POST['f1_search_term']:'')?>" /></label> <button name="submit">Search Files</button>
        <p style="font-size:10px;">Searches from beginning of file name</p>
        <p><a href="<?=admin_url()?>admin.php?page=f1-cdn-files">Reset Search</a></p>
    </form>
    <table class="f1_grid" width="100%" cellspacing="5" cellpadding="5">
        <thead>
            <tr>
                <th>File</th>
                <th>Size</th>
                <th>Date Modified</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($data_list as $file_arr) { ?>
            <?php foreach($file_arr as $file) { ?>
            <tr>
                <td><a href="<?=$file->PublicURL(); ?>" target="_blank"><?=$file->PublicURL(); ?></a></td>
                <td align="right" style="font-size:10px;"><?=number_format($file->bytes/1000, 0, ".", ",")?> KB</td>
                <td align="right" style="font-size:10px;"><?=date("Y-m-d H:i:s", strtotime($file->last_modified))?></td>
                <td align="center"><form action="" method="post">
                        <input type="hidden" name="f1_action_delete" value="<?=$file->name?>" />
                        <button name="submit">Delete</button>
                </form></td>
            </tr>
            <?php } ?>
        <?php } ?>
        </tbody>

    </table>
</div>