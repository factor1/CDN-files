jQuery(document).ready(function($) {
    $(document).on('submit', '#f1_cdnfiles_add_form', function() {
        if((!$('#f1_cdnfiles_add_upload').val()) && (!$('#f1_cdnfiles_add_url').val())) {
            alert('Must upload a file or provide a URL');
            return false;
        }
        return true;
    });
    $(document).on('click', '#f1CdnFiles-btn-adminadd', function() {
       $('#f1cdnfiles_admin_details_holder').slideDown();
        return false;
    });
});