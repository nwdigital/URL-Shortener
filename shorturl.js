jQuery(document).ready(function(){
    
    jQuery('#shorturl_newform').on('submit', function(e) {
        e.preventDefault();
        
        const form = document.getElementById('shorturl_newform')
        
        jQuery.ajax({
            type : "post",
            url : shorturlObject.ajax_url,
            data : {
                action : 'on_submit_show_user_shorturl',
                url : jQuery('input[name=shorturl_url_input').val(),
                security : shorturlObject.security
            },
            beforeSend : function ( response ) {
                console.log('Sending...');  
            },
            success : function( response ) {
                jQuery('#shorturlform_response').html(response)
            }
        })
        
    })

    
})