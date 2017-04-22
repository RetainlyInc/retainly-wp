(function($) {
    //setup some variables to use throughout
    var isSticky = ($('.rad_rapidology_rapidbar').hasClass('stickytop')) ? true : false;
    var isStickyStuck = ($('.rad_rapidology_rapidbar').hasClass('stickytop_stick')) ? true : false;
    var rapidbar_displayed = jQuery('.rad_rapidology_rapidbar').length;
    var rapidbar_timedelay = jQuery('.rad_rapidology_rapidbar.rad_rapidology_rapidbar_trigger_auto').data('delay');
    var delay = '' !== rapidbar_timedelay ? rapidbar_timedelay * 1000 : 500;
    var submit_remove = $('.rad_rapidology_redirect_page').data('submit_remove');
    var $body = $('body');

    if(isSticky == true){
        $(window).on('scroll', function(){
            if($(window).scrollTop() > 0){
                $('.rad_rapidology_rapidbar').addClass('stickytop_stick');
                $('.rad_rapidology_rapidbar').removeClass('stickytop');
                if(rapidbar.admin_bar) {
                    $('.rad_rapidology_rapidbar').css('margin-top', '32px');
                }
            }else{
                $('.rad_rapidology_rapidbar').removeClass('stickytop_stick');
                $('.rad_rapidology_rapidbar').addClass('stickytop');
                if(rapidbar.admin_bar) {
                    $('.rad_rapidology_rapidbar').css('margin-top', '0px');
                }
            }
        });
    }

    /*---------------------------------------
    ------------Adding heights for bar-------
    -----------------------------------------*/
    $(window).on('load', function () {
        //set inital heights
        load_delay = delay + 500;
	//need to delay as if we don't and the bar has a delay we end up with a negative margin
        setTimeout(function(){
            new_height = $('.rad_rapidology_rapidbar').height();
            rapidbar_add_padding(new_height);
        }, load_delay);
        replicate_text_color(delay);
        var text_height = $('.rad_rapidology_rapidbar_form_header').height();
        rapidbar_responsive_css(text_height);
    });
    
    $(window).resize(function() {
        new_height = $('.rad_rapidology_rapidbar').height();
        rapidbar_add_padding(new_height);
        var text_height = $('.rad_rapidology_rapidbar_form_header').height();
        rapidbar_responsive_css(text_height);
    });

    function rapidbar_add_padding(height){
        $('body').attr('data-rad_height', height);
        /*---fixed header heights----*/
        var header = $('header');
        if($(header).css('position') == 'fixed' || $(header).css('position') == 'absolute'){
            $(header).css('margin-top', height);
            $(header).attr('data-rapid_height', height);
        }

        jQuery('body').children().each(function(){
            var this_el = jQuery(this);
            if (jQuery(this_el).css('position') == 'fixed' || jQuery(this_el).css('position') == 'absolute' ) {
                if(!jQuery(this_el).hasClass('rad_rapidology_rapidbar') && jQuery(this_el).attr('id') != 'wpadminbar') {
                    var current_padding_top_el = jQuery(this_el).css('padding-top');
                    var new_padding_el = parseInt(current_padding_top_el.replace('px', '')) + height;
                    jQuery(this_el).css('padding-top', height);
                    jQuery(this_el).attr('data-rapid_height', height);
                }
            }
        });
        $('.sticky_adminbar_push').css('height', '32');
    }

    /*------------------------------------------
     ------------removing heights for bar-------
     ------------------------------------------*/

    // triggers for closing rapidbar
    jQuery('.rad_rapidology_redirect_page').on('click', function () {
        var submit_remove = $('.rad_rapidology_redirect_page').data('submit_remove');//will be true of false to pass into remove padding to keep bar from being removed
        if(submit_remove == true) {
            setTimeout(
                function () {
                    console.log('here');
                    rapidbar_remove_padding(true);
                }, 400); //use set timeout as it is used the other closing functions
        }
    });

    jQuery('.rad_rapidology_rapidbar .rad_rapidology_close_button').on('click', function () {
        setTimeout(
            function(){
                rapidbar_remove_padding(true, true);
            }, 400); //use set timeout as it is used the other closing functions
    });

    //scroll trigger to remove padding
    if(isSticky == false) {
        jQuery(window).scroll(function () {
            rad_scroll_height = $('.rad_rapidology_rapidbar').height();
            var scroll = $(window).scrollTop();
            if (scroll >= rad_scroll_height) {
                rapidbar_remove_padding(false);
            } else {
                rapidbar_add_padding(rad_scroll_height);
            }
        });
    }

    function rapidbar_remove_padding(remove_bar, closebtn){
        height = $('.rad_rapidology_rapidbar').height(); //get height of bar
        if( $('.rapidbar_consent_form') && $('.consent_error').is(":visible") ){
            consent_height = $('.rapidbar_consent_form').height();
            height = height - consent_height;
        }
        if( $('.consent_error') && $('.consent_error').is(":visible") ){
            consent_error_height = $('.consent_error').height();
            console.log(consent_error_height);
            height = height - consent_error_height;
        }
        var removebar = (remove_bar == false ? false : true);
        var header = $('header');
        if($(header).data('rapid_height')){
            var current_height = $(header).data('rapid_height');
            $(header).css('margin-top', current_height - height);
        }
        $('.sticky_adminbar_push').css('height', '0');

        $("[data-rapid_height]").each(function(){
            var padding_to_remove = jQuery(this).data('rapid_height');
            var current_padding = jQuery(this).css('padding-top');
            var new_padding_el = parseInt(current_padding.replace('px', '')) - padding_to_remove;
            $(this).css('padding-top', new_padding_el);
        });

        if(removebar == true) {
            var redirectUrl = $('.rad_rapidology_submit_subscription').data('redirect_url');
            if (!redirectUrl) { //dont want to remove if they have a redirect setup with a timer as we want the form to stick around
                $('.rad_rapidology_rapidbar').remove();
            }
        }
        if(closebtn ==  true){
            $('.rad_rapidology_rapidbar').remove();
        }
    }

    function replicate_text_color(delay){
        //loop through any rapidbar on the page and set the color appropriately if text color has been changed in the admin editor
        //only happens on btns as links
        setTimeout(function(delay){
            $('.rad_rapidology_rapidbar').each(function(){
                var this_el = jQuery(this);
                var button = jQuery(this_el).find('button'); //find our button on this form
                var btnAsLink = jQuery(button).attr('class').match(/btnaslink/); //make sure button has link class
                if(btnAsLink && btnAsLink.index > 0) { //if the result index from match is > 0 then we can change it, if not we won't.
                    var barTextEl = jQuery(this_el.find('.rad_rapidology_form_text p span'));
                    var textColor = barTextEl.css('color');
                    if (textColor) {
                        var buttonText = jQuery(this_el.find('.rad_rapidology_button_text'));
                        jQuery(buttonText).attr('style', 'color: ' + textColor + ' !important; text-decoration: underline !important');
                    }else{
                        $('.rad_rapidology_button_text').attr('style', 'text-decoration: underline !important');
                    }
                }
            });
        }, delay);
    }



    /*------------------------------------------
     -----fix css for bar on window resize------
     ------------------------------------------*/

    function rapidbar_responsive_css(height){
        if(height > 50){
            $('.rad_rapidology_rapidbar .rad_rapidology_form_text p').addClass('rapidbar_form_responsive');
        }else if(height <= 50){
            $('.rad_rapidology_rapidbar .rad_rapidology_form_text p').removeClass('rapidbar_form_responsive');
        }
    }


    /*------------------------------------------
     -----consent for rapidbar------
     ------------------------------------------*/
    if('.rapidbar_consent_form') {
        $(".rad_rapidology_rapidbar_input input").keyup(function () {
            if($(this).val().length > 0) {
                if ($('.rapidbar_consent_form').hasClass('rapid_consent_closed')) {
                    $('.rapidbar_consent_form').removeClass('rapid_consent_closed');
                    $('.rapidbar_consent_form').addClass('rapid_consent');
                }
            }else{
                $('.rapidbar_consent_form').removeClass('rapid_consent');
                $('.rapidbar_consent_form').addClass('rapid_consent_closed');
            }
        });
    }

    $body.on('click', '.rapidbar_consent_form .accept_consent', function(){
        if($('.rapidbar_consent_form .accept_consent').prop('checked')){
            $('.consent_error').hide();
        }
    });
})( jQuery );





