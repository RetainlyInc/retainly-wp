(function($){


    $('body').on('click', '.newsletter_submit_button',function(e){
        e.preventDefault;
        var emailaddress = $('.newsletter_email').val();
        var site = window.location + ''; //need to make it a string
        var urlTest = 'https://2-dot-rapidology-server.appspot.com/signup.json';
        var server = 'https://rapidology.server.appspot.com/signup.json';
        var valid = true;

        //email validation
        if (!emailaddress || emailaddress.length < 1 || !/^.+@.+\..+$/.test(emailaddress)) {
            $('.error.email').show();
            valid = false;
        }

        if(valid == true){
            $.ajax({
                url: urlTest,
                type: "GET",
                "content-type": "application/json",
                dataType: 'jsonp',
                data: { email: emailaddress, site: site},
                beforeSend: function(){
                    $('.loader').show();
                    $('.error').hide();
                }
            })
            .success(function(response){
                if(response.status == 'ok'){
                    $('.loader').hide();
                    $('.signup_tagline').hide();
                    $('.rapidology_newsletter_form').hide();
                    $('.signup_thankyou').show();
                }else{
                    $('.error .badresponse').show();
                }
            });
        }
     });

})(jQuery);