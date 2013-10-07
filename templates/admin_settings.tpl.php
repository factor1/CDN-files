<form action="" method="post" class="f1_cdnfiles_form" enctype="multipart/form-data">
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br></div>
    <h2>Settings</h2>
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                    <?php if($saved) { ?>
                        <p style="color:#C00;font-weight: bold;">Settings have been saved.</p>
                    <?php } ?>
                    <div class="field">
                        <label for="f1_settings_back_image">Rackspace Username</label><br />
                        <input id="f1_settings_container_name" type="text" name="f1_settings[cdn_username]" value="<?=$this->get_setting('cdn_username')?>" />

                    </div>
                    <div class="field">
                        <label for="f1_settings_back_image">Rackspace API Key</label><br />
                        <input id="f1_settings_container_name" type="password" name="f1_settings[cdn_apikey]" value="<?=$this->get_setting('cdn_apikey')?>" />

                    </div>
                    <div class="field">
                        <label for="f1_settings_back_image">CDN Container Name</label><br />
                        <input id="f1_settings_container_name" type="text" name="f1_settings[container_name]" value="<?=$container_name?>" />

                    </div>
            </div>
            <div id="postbox-container-1" class="postbox-container">

                <div id="submitdiv" class="postbox " >
                    <div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle' style="cursor: pointer;"><span id="f1_admin_edit_header">Save Settings</span></h3>
                    <div class="inside">
                        <div class="padding">


                            <div id="major-publishing-actions">

                                <div id="publishing-action">
                                    <span class="spinner"></span>
                                    <input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="Save" />
                                </div>
                                <div class="clear"></div>
                            </div>
                        </div>
                     </div>
                </div>

            </div>
        </div>
    </div>

</div>
</form>