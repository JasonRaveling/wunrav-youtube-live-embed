// create a cookie to determine whether or not to automatically slideout or not.
var $ = jQuery;

if ( !$.cookie('SubdueLivefeedSlideout') )  {
    $(document).ready(function(){
        setTimeout(function(){ $('#slideout-button').prop('checked', true); }, 6000);

        $('#slideout-trigger').click(function(){
            date = new Date();
            date.setTime( date.getTime() + ( 24 * 60 * 60 * 1000 ) );
            expires = "; expires=" + date.toGMTString();
            value= 1;
            document.cookie = "SubdueLivefeedSlideout=" + value + expires + "; path=/";
        });
    });
}
