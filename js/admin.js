(function($){
	$( document ).ready( function() {
		var url = window.location.href,
			tab_link = url.split( '#tab_' )[1],
			premade_grid_cache = '';

		//Set the current tab to home by default
		if ( typeof tab_link === 'undefined' ) {
			window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_header_support', 'header' );
			$( '#rad_dashboard_wrapper' ).addClass( 'rad_dashboard_hidden_nav' );
            $('.non_marketing_page').hide();
		} else {
			var $toplevelPageRadRapidologyOptions = $('#toplevel_page_rad_rapidology_options');
            var link_to_highlight = $toplevelPageRadRapidologyOptions.find('a[href$="#tab_' + tab_link + '"]');
			window.rad_dashboard_set_current_tab( tab_link, 'header' );


			$toplevelPageRadRapidologyOptions.find('ul li' ).removeClass( 'current' );

			if ( link_to_highlight.length ) {
				link_to_highlight.parent().addClass( 'current' );
			} else {
				$toplevelPageRadRapidologyOptions.find('.wp-first-item' ).addClass( 'current' );
			}

			if ( 'rad_dashboard_tab_content_header_stats' === tab_link ) {
				refresh_stats_tab( false );
			}
		}

		/** Handle clicks in the WP navigation menu:
		 * 1) Open appropriate tab in dashboard
		 * 2) Highlihgt an appropriate link in the WP menu
		 */
		var $body = $( 'body' );
		$body.on( 'click', '#toplevel_page_rad_rapidology_options li a', function() {
			var this_link = $( this ),
				open_link = this_link.attr( 'href' ).split( '#tab_' )[1];
			if ( typeof open_link !== 'undefined' ) {
                $('.non_marketing_page').show();
				window.rad_dashboard_set_current_tab( open_link, 'header' );
				if ( 'rad_dashboard_tab_content_header_stats' === open_link ) {
					refresh_stats_tab( false );
				}
                if( open_link != 'rad_dashboard_tab_content_header_home' ){
                    $('#rad_dashboard_options').hide();
                }
                if( open_link == 'rad_dashboard_tab_content_header_home' ){
                    $( '#rad_dashboard_wrapper' ).addClass( 'rad_dashboard_hidden_nav' );
                    $('#rad_dashboard_options').hide();
                    init_optin_modal();
                }
			} else {
				window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_header_support', 'header' );
                $('.non_marketing_page').hide();
				$( '#rad_dashboard_wrapper' ).addClass( 'rad_dashboard_hidden_nav' );
			}

			$( '#toplevel_page_rad_rapidology_options').find('ul li' ).removeClass( 'current' );
			this_link.parent().addClass( 'current' );

			return false;
		});

		if ( 'rad_dashboard_tab_content_header_home' === tab_link || 'rad_dashboard_tab_content_header_importexport' === tab_link || 'rad_dashboard_tab_content_header_accounts' === tab_link || 'rad_dashboard_tab_content_header_stats' === tab_link ) {
            $( '#rad_dashboard_wrapper' ).addClass( 'rad_dashboard_hidden_nav' );
		}

		$body.on( 'click', '#rad_dashboard_header ul li a', function() {
			var $page_rapidology_options = $('#toplevel_page_rad_rapidology_options');
            var tab_link = $( this) .attr( 'href' ).split( '#tab_' )[1],
				link_to_highlight = $page_rapidology_options.find('a[href$="#tab_' + tab_link + '"]');
			$( '#rad_dashboard_wrapper' ).addClass( 'rad_dashboard_hidden_nav' );

			//Highlight appropriate menu link in WP menu
			$page_rapidology_options.find('ul li' ).removeClass( 'current' );
			if ( link_to_highlight.length ) {
				link_to_highlight.parent().addClass( 'current' );
			} else {
				$page_rapidology_options.find('.wp-first-item' ).addClass( 'current' );
			}
		});

		$body.on( 'click', '#rad_dashboard_tab_content_header_home', function() {
			reset_home_tab();
		});

		$body.on( 'click', '.rad_dashboard_save_changes button', function() {
			var $provider = $( '.rad_dashboard_select_provider select' ).val(),
				$list = $( '.rad_dashboard_select_list select' ).val();

            //is this a redirect bar? if so overide all account and list settings with empty
            if($provider == 'redirect'){
                $list = 'empty';
            }

			if ( 'empty' == $provider || ( 'custom_html' !== $provider && $provider !== 'redirect' && 'empty' == $list ) ) {
				window.rad_dashboard_generate_warning( rapidology_settings.no_account_text, '#tab_rad_dashboard_tab_content_optin_setup', rapidology_settings.add_account_button, rapidology_settings.save_inactive_button, '#', 'rad_rapidology_save_inactive' );
			} else {
				rapidology_dashboard_save( $( this ) );
			}

			return false;
		});

		$body.on( 'click', '.rad_rapidology_save_inactive', function() {
			$( '#rad_dashboard_optin_status' ).val( 'inactive' );
			rapidology_dashboard_save( $( '.rad_dashboard_save_changes button' ) );
			$( '.rad_dashboard_warning' ).remove();

			return false;
		});

		$body.on( 'click', '.rad_dashboard_next_design button', function() {
			window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_optin_design', 'side' );
			$( 'html, body' ).animate( { scrollTop :  0 }, 400 );

			return false;
		});

		$body.on( 'click', '.rad_rapidology_open_premade', function() {
			window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_optin_premade', 'side' );
			$( '.rad_dashboard_tab_content_side_design #rad_dashboard_tab_content_optin_design' ).addClass( 'current' );
            var isRapidBar = '';
            var isRedirect = '';
            isRapidBar = $('#rad_dashboard_navigation').hasClass('current_optin_type_rapidbar');
            isRedirect = $('.rad_dashboard_enable_redirect_form input').is(':checked'); //need to check if its a redirect form to load the proper layouts
            premade_grid_cache = '';
			if ( '' == premade_grid_cache ) {
				$.ajax({
					type: 'POST',
					url: rapidology_settings.ajaxurl,
					data: {
						action : 'rapidology_generate_template_filter',
						rapidology_premade_nonce : rapidology_settings.rapidology_premade_nonce,
                        isRapidBar  : isRapidBar,
                        isRedirect  : isRedirect,
                        formLocation: '',
                        imgLocation: '',
					},
					beforeSend: function( data ) {
						//$( '.rad_rapidology_premade_spinner' ).addClass( 'rad_dashboard_spinner_visible' );

					},
					success: function( data ) {
                        $('.layout_filter_wrapper').remove();
                        $('.templates_loading').remove();
						premade_grid_cache = data;
						$( '.rad_rapidology_premade_grid' ).replaceWith( premade_grid_cache );
					}
				});
			} else {
                $('.layout_filter_wrapper').remove();
                $('.templates_loading').remove();
				$( '.rad_rapidology_premade_grid' ).replaceWith( premade_grid_cache );
			}
		});

        $body.on('click', '.layout_filter img', function(){

            window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_optin_premade', 'side' );
            $( '.rad_dashboard_tab_content_side_design #rad_dashboard_tab_content_optin_design' ).addClass( 'current' );
            var isRapidBar = '';
            var isRedirect = '';
            var formLocation = $(this).data('form');
            var imgLocation = $(this).data('img');
            $('.rad_rapidology_premade_grid').hide();
            isRapidBar = $('#rad_dashboard_navigation').hasClass('current_optin_type_rapidbar');
            isRedirect = $('.rad_dashboard_enable_redirect_form input').is(':checked'); //need to check if its a redirect form to load the proper layouts
            premade_grid_cache = '';
            if ( '' == premade_grid_cache ) {
                $.ajax({
                    type: 'POST',
                    url: rapidology_settings.ajaxurl,
                    data: {
                        action : 'rapidology_generate_premade_grid',
                        rapidology_premade_nonce : rapidology_settings.rapidology_premade_nonce,
                        isRapidBar  : isRapidBar,
                        isRedirect  : isRedirect,
                        formLocation: formLocation,
                        imgLocation: imgLocation,
                    },
                    beforeSend: function( data ) {
                        $( '.templates_loading' ).show();
                    },
                    success: function( data ) {
                        $( '.templates_loading' ).hide();
                        $('.rad_rapidology_premade_grid').show();
                        premade_grid_cache = data;
                        $( '.rad_rapidology_premade_grid' ).replaceWith( premade_grid_cache );
                    }
                });
            } else {
                $( '.rad_rapidology_premade_grid' ).replaceWith( premade_grid_cache );
            }
        });


		$body.on( 'click', '.rad_dashboard_next_customize button', function() {

			$( '.rad_dashboard_next_design button' ).removeClass( 'rad_rapidology_open_premade' );
			$( '.rad_dashboard_tab_content_side_design a' ).removeClass( 'rad_rapidology_open_premade' );

			var selected_layout = JSON.stringify({ 'id' : $( this ).data( 'selected_layout' ) });
            var isRapidBar = $('#rad_dashboard_navigation').hasClass('current_optin_type_rapidbar');
            var isRedirect = $('.rad_dashboard_enable_redirect_form input').is(':checked'); //need to check if its a redirect form to load the proper layouts
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				dataType: 'json',
				data: {
					action : 'rapidology_get_premade_values',
                    isRapidBar  : isRapidBar,
                    isRedirect  : isRedirect,
					rapidology_premade_nonce : rapidology_settings.rapidology_premade_nonce,
					premade_data_array : selected_layout
				},
				success: function( data ) {
					if ( $.isPlainObject( data ) ) {
						$( data ).each( function( i, val ) {
							$.each( val, function( optin_name, optin_value ) {
								var $optin_name = $('.' + optin_name);
                                switch( optin_name ) {
									case 'rad_dashboard_optin_title' :
									case 'rad_dashboard_optin_message' :
									case 'rad_dashboard_footer_text' :
									case 'rad_dashboard_success_text' :
										$optin_name.text( optin_value );
										break;

									case 'rad_dashboard_border_orientation' :
									case 'rad_dashboard_image_orientation' :
									case 'rad_dashboard_image_orientation_widget' :
									case 'rad_dashboard_header_font' :
									case 'rad_dashboard_body_font' :
									case 'rad_dashboard_text_color' :
									case 'rad_dashboard_corner_style' :
									case 'rad_dashboard_form_orientation' :
									case 'rad_dashboard_name_fields' :
									case 'rad_dashboard_field_orientation' :
									case 'rad_dashboard_field_corners' :
									case 'rad_dashboard_form_text_color' :
									case 'rad_dashboard_field_button_text_color' :
										$( '.' + optin_name + ' select' ).val( optin_value );

										if ( 'no_image' != optin_value && 'rad_dashboard_image_orientation' == optin_name ) {
											$( '.rad_dashboard_upload_image' ).parent().parent().removeClass( 'rad_dashboard_hidden_option' );
										}

										if ( 'no_border' != optin_value && 'rad_dashboard_border_orientation' == optin_name ) {
											$( '.rad_dashboard_border_color' ).removeClass( 'rad_dashboard_hidden_option' );
											$( '.rad_dashboard_border_style' ).removeClass( 'rad_dashboard_hidden_option' );
										}

										if ( 'no_name' != optin_value && 'rad_dashboard_redirect_checkbox' == optin_name ) {
											$( '.rad_dashboard_name_checkbox input' ).prop( 'checked', true );

											if ( $( '.rad_dashboard_name_checkbox' ).hasClass( 'rad_dashboard_visible_option' ) || ( 'single_name' == optin_value ) ) {
												$( '.rad_dashboard_name_text_single' ).parent().removeClass( 'rad_dashboard_hidden_option' );
											}

											if ( 'first_last_name' == optin_value ) {
												$( '.rad_dashboard_last_name_text' ).parent().removeClass( 'rad_dashboard_hidden_option' );
												$( '.rad_dashboard_name_text' ).parent().removeClass( 'rad_dashboard_hidden_option' );
											}
										}

										break;
                                    case 'rad_dashboard_redirect_checkbox' :
                                        if( optin_value == 'true'){
                                            $( '.rad_dashboard_redirect_checkbox input' ).prop( 'checked', true );
                                        }
                                        break;
									case 'rad_dashboard_name_text' :
									case 'rad_dashboard_last_name_text' :
									case 'rad_dashboard_email_text' :
									case 'rad_dashboard_button_text' :
										$optin_name.val( optin_value );
										break;

									case 'rad_dashboard_upload_image' :
										$optin_name.find( '.rad-dashboard-upload-field' ).val( optin_value );
										rad_dashboard_generate_preview_image( $optin_name.find( '.rad-dashboard-upload-field' ).siblings( '.rad-dashboard-upload-button' ) );
										break;

									case 'rad_dashboard_optin_bg' :
									case 'rad_dashboard_form_bg_color' :
									case 'rad_dashboard_form_button_color' :
									case 'rad_dashboard_border_color' :
										$optin_name.find( '.rad-dashboard-color-picker' ).wpColorPicker( 'color', optin_value );
										break;

									case 'rad_dashboard_border_style' :
									case 'rad_dashboard_optin_edge' :
										var tabs = $optin_name.find( 'div.rad_dashboard_single_selectable' ),
											inputs = $optin_name.find( 'input' );

										tabs.removeClass( 'rad_dashboard_selected' );
										inputs.prop( 'checked', false );
										var selected = tabs.find( 'input[value="' + optin_value + '"]' );
										selected.parent().toggleClass( 'rad_dashboard_selected' );
										selected.prop( 'checked', true );
										break;
								}
							});
						});
					}

					window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_optin_design', 'side' );
					$( 'html, body' ).animate( { scrollTop :  0 }, 400 );
				}
			});

			return false;
		});

		$body.on( 'click', '.rad_dashboard_next_display button', function() {
			window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_optin_display', 'side' );
			$( 'html, body' ).animate( { scrollTop :  0 }, 400 );

			return false;
		});

        $body.on( 'click', '.rad_dashboard_optin_add', function() {
            $('.rad_dashboard_optin_select').dialog("close");
        });

        $body.on( 'click', '.rad_dashboard_icon_edit', function() {
            //$('.rad_dashboard_optin_select').dialog("close");
        });


        $body.on( 'click', '.rad_dashboard_close_button:not(.duplicate_close)', function() {

            $('.rad_dashboard_optin_select').dialog("close")
        });

		$body.on( 'click', '.rad_dashboard_new_optin button', function() {
            init_optin_modal();
            $('.rad_dashboard_optin_select').dialog("open");
            $('li.rad_dashboard_optin_type').css('opacity', 1);
        });

        function init_optin_modal() {

            $('.rad_dashboard_optin_select').dialog({
                autoOpen: false,
                modal: true,
                position: {
                    my: "center center",
                    at: "center center",
                    of: window,
                },
                width: "auto",
                resizable: false,
                draggable: false,

                close: function(){ $( this ).dialog( "close" ); $('.ui-dialog').remove(); },
                create: function () {

                    $(window).resize(function () {
                        $(".rad_dashboard_optin_select").position({
                            my: "center",
                            at: "center",
                            of: window
                        });
                    });

                },
            });
        }

        function init_optin_modal_duplicate() {

            $('.rad_duplicate_form').dialog({
                autoOpen: false,
                modal: true,
                position: {
                    my: "center center",
                    at: "center center",
                    of: window,
                },
                width: "auto",
                resizable: false,
                draggable: false,

                close: function(){ $( this ).dialog( "close" ); $('.ui-dialog').remove(); $('.rad_duplicate_form').remove(); },
                create: function () {

                    $(window).resize(function () {
                        $(".rad_dashboard_optin_select").position({
                            my: "center",
                            at: "center",
                            of: window
                        });
                    });

                },
            });
        }


        $(function(){
            var windowWidth = $(window).width();
            if(windowWidth < 820 && windowWidth > 560){
                $('.rad_dashboard_optin_select ul').addClass('responsive_optin_select_70');
                $('li.rad_dashboard_optin_type').addClass('responsive_optin_select_li');
            }else if(windowWidth < 560){
                $('.rad_dashboard_optin_select ul').removeClass('responsive_optin_select_70');
                $('.rad_dashboard_optin_select ul').addClass('responsive_optin_select');
                $('li.rad_dashboard_optin_type').addClass('responsive_optin_select_li');
            } else{
                $('.rad_dashboard_optin_select ul').removeClass('responsive_optin_select');
                $('li.rad_dashboard_optin_type').removeClass('responsive_optin_select_li');
            }
        });
        $(window).resize(function(){

            var windowWidth = $(window).width();
            if(windowWidth < 820 && windowWidth > 560){
                $('.rad_dashboard_optin_select ul').addClass('responsive_optin_select');
                $('li.rad_dashboard_optin_type').addClass('responsive_optin_select_li');
            }else if(windowWidth < 560){
                $('li.rad_dashboard_optin_type').addClass('responsive_optin_select_li');
            }else{
                $('.rad_dashboard_optin_select ul').removeClass('responsive_optin_select');
                $('li.rad_dashboard_optin_type').removeClass('responsive_optin_select_li');
            }
        });
		$body.on( 'click', '.rad_dashboard_optin_add', function() {
			$( '.rad_dashboard_new_optin button' ).addClass( 'rad_rapidology_loading' );
			reset_options( $( this ), '', true, false, '' );
		});

		$body.on( 'click', '.rad_dashboard_new_account_row button', function() {
			window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_header_edit_account', 'header' );
			display_edit_account_tab( false, '', '' );
		});

		$body.on( 'click', '.rad_dashboard_icon_edit_account', function() {
			var this_el = $( this );

			window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_header_edit_account', 'header' );
			display_edit_account_tab( true, this_el.data( 'service' ), this_el.data( 'account_name' ) );
		});

		$body.on( 'click', '.rad_dashboard_icon_edit', function() {
			var $this_el = $( this ),
				optin_id = $this_el.parent().parent().data( 'optin_id' ),
				parent_id = typeof $this_el.data( 'parent_id' ) !== 'undefined' ? $this_el.data( 'parent_id' ) : '',
				is_child = '' != parent_id;

			$this_el.find( '.spinner' ).addClass( 'rad_dashboard_spinner_visible' );

			reset_options( $this_el, optin_id, false, is_child, parent_id );
		});

		$body.on( 'click', '.rad_dashboard_icon_delete:not(.clicked_button)', function() {
			var this_el = $( this );

			$( '.rad_dashboard_icon_delete' ).removeClass( 'clicked_button' );

			this_el.addClass( 'clicked_button' );
			$( '.rad_dashboard_confirmation' ).hide();

			this_el.find( '.rad_dashboard_confirmation' ).fadeToggle();
		});

		$body.on( 'click', '.rad_rapidology_clear_stats', function() {
			var this_el = $( this );

			this_el.parent().find( '.rad_dashboard_confirmation' ).fadeToggle();
		});

		$body.on( 'click', '.rad_dashboard_confirm_stats', function() {
			$( this ).parent().hide();

			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_clear_stats',
					rapidology_stats_nonce : rapidology_settings.rapidology_stats
				},
				beforeSend: function( data ){
					$( '.rad_rapidology_clear_stats' ).addClass( 'rad_rapidology_loading' );
				},
				success: function( data ){
					$( '.rad_rapidology_clear_stats' ).removeClass( 'rad_rapidology_loading' );
					refresh_stats_tab( true );
				}
			});
		});

        $body.on('click', '.clear_individual_stat', function(){
            var optin_id = $(this).data('optin_id');
            $.ajax({
                type: 'POST',
                url: rapidology_settings.ajaxurl,
                data: {
                    action : 'rapidology_clear_stats_single_optin',
                    rapidology_stats_nonce : rapidology_settings.rapidology_stats,
                    optin_id: optin_id
                },
                beforeSend: function( data ){
                    $( '.rad_rapidology_stats_spinner' ).addClass( 'rad_dashboard_spinner_visible' );
                    $( 'html, body' ).animate( { scrollTop :  0 }, 400 );
                },
                success: function( data ){
                    location.reload();
                    refresh_stats_tab( true );
                    $( '.rad_rapidology_stats_spinner' ).removeClass( 'rad_dashboard_spinner_visible' );

                }
            });
        });

		$body.on( 'click', '.rad_rapidology_refresh_stats', function() {
			var $this = $(this),
				button_width = $this.width();

			$this.width( button_width ).addClass( 'rad_rapidology_loading' );
			refresh_stats_tab( true );
		});

		$body.on( 'click', '.rad_dashboard_confirm_delete', function() {

			var this_el = $( this ),
				optin_id = this_el.data( 'optin_id' ),
				need_refresh = false,
				table_row = this_el.parent().parent().parent().parent(),
				parent_id = typeof this_el.data( 'parent_id' ) !== 'undefined' ? this_el.data( 'parent_id' ) : '',
				is_account = true == this_el.data('remove_account'),
				service = table_row.data( 'service' );

			//if we're about to remove the last item in table, then we need to refresh page after removal.
			if ( 1 === table_row.parent().find( '.rad_dashboard_optins_item.rad_dashboard_parent_item' ).length || true === is_account || '' != parent_id ) {
				need_refresh = true;
			} else {
				table_row.remove();
			}

			remove_optin( optin_id, need_refresh, is_account, service, parent_id );
		});

		$body.on( 'click', '.rad_dashboard_cancel_delete', function() {
			$( this ).parent().hide();
			$( this ).parent().parent().removeClass( 'clicked_button' );
		});

		/*$body.on( 'click', '.rad_dashboard_optin_select .rad_dashboard_close_button', function() {
			var this_select = $( this ).parent();

			this_select.parent().find( '.clicked_button').removeClass( 'clicked_button' );
		});*/

		$body.on( 'click', '.clicked_button', function() {
			return false;
		});


		$body.on( 'click', '.rad_dashboard_icon_duplicate:not(.clicked_button)', function() {
			var this_el = $( this ),
				parent = this_el.parent().parent();
			$( '.rad_dashboard_icon_duplicate' ).removeClass( 'clicked_button' );
			this_el.addClass( 'clicked_button' );

			var select_type_box = '<div class="rad_dashboard_row rad_dashboard_optin_select rad_duplicate_form"><h3>' + rapidology_settings.optin_type_title + '</h3><span class="rad_dashboard_icon rad_dashboard_close_button duplicate_close"></span><ul data-optin_id="' + parent.data( 'optin_id' ) + '"><li class="rad_dashboard_optin_type rad_dashboard_optin_duplicate rad_dashboard_optin_type_popup" data-type="pop_up"><h6>pop up</h6><div class="optin_select_grey"><div class="optin_select_light_grey"></div></div></li><li class="rad_dashboard_optin_type rad_dashboard_optin_duplicate rad_dashboard_optin_type_flyin" data-type="flyin"><h6>fly in</h6><div class="optin_select_grey"></div><div class="optin_select_light_grey"></div></li><li class="rad_dashboard_optin_type rad_dashboard_optin_duplicate rad_dashboard_optin_type_below" data-type="below_post"><h6>below post</h6><div class="optin_select_grey"></div><div class="optin_select_light_grey"></div></li><li class="rad_dashboard_optin_type rad_dashboard_optin_duplicate rad_dashboard_optin_type_inline" data-type="inline"><h6>inline</h6><div class="optin_select_grey"></div><div class="optin_select_light_grey"></div><div class="optin_select_grey"></div></li></ul><ul data-optin_id="' + parent.data( 'optin_id' ) + '"><li class="rad_dashboard_optin_type rad_dashboard_optin_duplicate rad_dashboard_optin_type_locked" data-type="locked"><h6>locked content</h6><div class="optin_select_grey"></div><div class="optin_select_light_grey"></div><div class="optin_select_grey"></div></li><li class="rad_dashboard_optin_type rad_dashboard_optin_duplicate rad_dashboard_optin_type_widget" data-type="widget"><h6>widget</h6><div class="optin_select_grey"></div><div class="optin_select_light_grey"></div><div class="optin_select_grey_small"></div><div class="optin_select_grey_small last"></div></li><li class="rad_dashboard_optin_type rad_dashboard_optin_duplicate rad_dashboard_optin_type_rapidbar" data-type="rapidbar"><h6>bar</h6><div class="optin_select_light_grey"></div><div class="optin_select_grey"></div></li></ul></div>';

			parent.append( select_type_box );


            init_optin_modal_duplicate();
            $('.rad_duplicate_form').dialog("open");
            $('li.rad_dashboard_optin_type').css('opacity', 1);
				//$( '.rad_dashboard_optins_item .rad_dashboard_optin_select').addClass( 'rad_dashboard_visible' );

		});

		$body.on( 'click', '.rad_duplicate_form .rad_dashboard_close_button', function() {
			$( '.rad_dashboard_icon_duplicate' ).removeClass( 'clicked_button' );
            $('.rad_duplicate_form').dialog("close");
		});

		$body.on( 'click', '.rad_dashboard_optin_duplicate', function() {
			var this_el = $( this ),
				form_id = this_el.parent().data( 'optin_id' ),
				form_type = this_el.data( 'type' );

            $( '.rad_dashboard_icon_duplicate' ).removeClass( 'clicked_button' );
            $('.rad_duplicate_form').dialog("close");
			duplicate_optin( form_id, form_type );
		});

		$body.on( 'click', '.rad_dashboard_toggle_status', function() {
			var this_el = $( this ),
				optin_id = this_el.parent().parent().data( 'optin_id' ),
				new_status = this_el.data( 'toggle_to' );
			if ( this_el.hasClass( 'rad_rapidology_no_account' ) && 'active' == new_status ) {
				window.rad_dashboard_generate_warning( rapidology_settings.cannot_activate_text, '#', '', '', '', '' );
			} else {
				$.ajax({
					type: 'POST',
					url: rapidology_settings.ajaxurl,
					data: {
						action : 'rapidology_toggle_optin_status',
						toggle_status_nonce : rapidology_settings.toggle_status,
						status_optin_id : optin_id,
						status_new : new_status
					},
					beforeSend: function() {
						this_el.find( '.spinner' ).addClass( 'rad_dashboard_spinner_visible' );
					},
					success: function( data ){
						reset_home_tab();
					}
				});
			}
		});

		$body.on( 'click', '.rad_dashboard_icon_shortcode, .rad_dashboard_next_shortcode button', function() {
			var this_el = $( this ),
				optin_id = typeof this_el.data( 'optin_id' ) !== 'undefined' ? this_el.data( 'optin_id' ) : this_el.parent().parent().data( 'optin_id' ),
				shortcode_text = '',
				shortcode_type = this_el.data( 'type' );
                click_trigger = this_el.data('click_trigger');

			if ( 'locked' == shortcode_type ) {
				shortcode_text = '[rad_rapidology_locked optin_id="' + optin_id + '"] content [/rad_rapidology_locked]';
			} else if (click_trigger == true) {
				shortcode_text = '[rapidology_on_click_intent optin_id='+ optin_id + '] [/rapidology_on_click_intent]]';
			}else{
                shortcode_text = '[rad_rapidology_inline optin_id="' + optin_id + '"]';
            }

			var message_text = rapidology_settings.shortcode_text + shortcode_text;

			window.rad_dashboard_generate_warning( message_text, '#', '', '', '', '' );

			return false;
		});

		//disable links on side nav to avoid confusion during page refresh
		$body.on( 'click', '.rad_dashboard_optin_nav', function(){
			return false;
		} );

		$body.on( 'change', '.rad_dashboard_select_account select', function() {
			var this_el = $( this );
			var service = this_el.data( 'service' ),
				account_name = this_el.val();
			if ( 'add_new' == account_name ) {
				display_actual_accounts( service, true, '' );
			} else {
				if ( 'empty' != account_name ) {
					display_actual_lists( account_name, service );
				}
			}
		});


		$body.on( 'change', '.rad_dashboard_select_provider select', function() {

			var selected_provider = $( '.rad_dashboard_select_provider select' ).val(),
				selected_account = 'empty';

			if ( 'empty' == selected_provider || 'custom_html' == selected_provider ) {
				$( '.rad_dashboard_select_account' ).css( { 'display' : 'none' } );
			} else {
				display_actual_accounts( selected_provider, false, '' );
				selected_account = $( '.rad_dashboard_select_account select' ).val();
			}

		});

		$body.on( 'click', '.rad_dashboard_new_account .authorize_service', function(){
			$( '.account_settings_fields' ).addClass( 'rad_visible_settings' );
			$( this ).text( 'Authorize' );
			$( this ).addClass('clicked_button');
			return false;
		});

		$body.on( 'click', '.authorize_service.clicked_button, .authorize_service.new_account_tab, .rad_dashboard_icon_update_lists', function(){
			var this_el = $( this ),
				on_form = this_el.hasClass( 'new_account_tab' ) ? false : true,
				account_name = typeof this_el.data( 'account_name' ) !== 'undefined' ? this_el.data( 'account_name' ) : '',
				account_exists = this_el.hasClass( 'rad_dashboard_icon_update_lists' ) ? true : false;

			authorize_network( this_el.data( 'service' ), this_el.parent(), on_form, account_name, account_exists );
		});

		$body.on( 'change', '.rad_dashboard_select_provider_new select', function() {
			var selected = $( this ).val();
				display_new_account_form( selected );
		});

		$body.on( 'click', '.save_account_tab', function(){
			var fields_container = $( '.rad_dashboard_new_account_fields' ),
				service = $( '.rad_dashboard_tab_content_header_edit_account .rad_dashboard_select_provider_new select' ).val();

			if ( fields_container.hasClass( 'rad_dashboard_edit_account_fields' ) || 'empty' == service ) {
				save_account_tab( '', '', true );
			} else {
				var account_name = fields_container.find( '#name_' + service ).val();

				if ( '' == account_name ) {
					window.rad_dashboard_generate_warning( rapidology_settings.no_account_name_text, '#', '', '', '', '' );
				} else {
					save_account_tab( service, account_name, false );
				}
			}

			return false;
		});

		$body.on( 'click', '.rad_dashboard_icon_abtest:not(.active_child_optins)', function(){
			var table_row = $( this ).parent().parent();
			$( 'ul.rad_dashboard_temp_row' ).remove();
			$( '.rad_dashboard_icon_abtest' ).removeClass( 'clicked_button' );
			$( this ).addClass( 'clicked_button' );

			table_row.append('<ul class="rad_dashboard_temp_row"><li class="rad_dashboard_add_variant rad_dashboard_optins_item"><a href="#" class="rad_dashboard_add_var_button">Add variant</a></li></ul>');
		});

		$body.on( 'click', '.rad_dashboard_add_var_button', function(){
			var optin_id = $( this ).parent().parent().parent().data( 'optin_id' );

			add_variant( optin_id );
			return false;
		});

		$body.on( 'click', '.child_buttons_right a', function(){
			var button = $( this ),
				parent_id = button.data( 'parent_id' ),
				action = '';

			if ( button.hasClass( 'rad_dashboard_pause_test' ) ) {
				button.removeClass( 'rad_dashboard_pause_test' );
				action = 'pause';
			} else if ( button.hasClass( 'rad_dashboard_start_test' ) ) {
				button.addClass( 'rad_dashboard_pause_test' );
				action = 'start';
			} else {
				action = 'end';
			}

			ab_test_controls( parent_id, action, button );
			return false;
		});

		//stats graph
		$( 'ul.rad_rapidology_graph' ).each( function() {
			resize ( $( this ) );
		});

		$body.on( 'mouseenter', '.rad_rapidology_graph .rad_rapidology_graph_bar', function(){
			var $this_el = $( this ),
				value = $this_el.attr( 'value' );

			$( '<div class="rad_rapidology_tooltip"><strong>' + value + '</strong></div>' ).appendTo( $this_el );

		}).on( 'mouseleave', '.rad_rapidology_graph .rad_rapidology_graph_bar', function(){
			$( this ).find( 'div.rad_rapidology_tooltip' ).remove();
		});

		$body.on( 'click', '.rad_rapidology_graph_button', function(){
			var this_el = $( this ),
				period = this_el.data( 'period' ),
				list_id = $( '.rad_rapidology_graph_select_list' ).val();

			if ( ! this_el.hasClass( 'rad_rapidology_active_button' ) ) {
				$( '.rad_rapidology_graph_button' ).removeClass( 'rad_rapidology_active_button' );
				this_el.addClass( 'rad_rapidology_active_button' );

				switch_graph( period, list_id, true );
			}

			return false;
		});

		$body.on( 'change', '.rad_rapidology_graph_select_list', function(){
			var this_el = $( this ),
				period = $( 'a.rad_rapidology_graph_button.rad_rapidology_active_button' ).data( 'period' ),
				list_id = this_el.val();

				switch_graph( period, list_id, false );
		});

        var lastExection = 0;
        if (window.addEventListener) {
            window.addEventListener('resize', function(event) {
                period = jQuery( 'a.rad_rapidology_graph_button.rad_rapidology_active_button' ).data( 'period' );
                list_id = jQuery('.rad_rapidology_graph_select_list').val();
                var now = Date.now();
                if (now - lastExection < 2000) {
                    setTimeout(function(){
                        //switch_graph( period, list_id, false );
                    },500);
                    return
                }
                lastExection = Date.now();
                switch_graph( period, list_id, false );
            });
        }

		$body.on( 'click', '.rad_dashboard_sort_button:not(.active_sorting)', function(){
			var this_el = $( this ),
				orderby = this_el.data( 'order_by' ),
				table = this_el.parent().data( 'table' );

			if ( 'lists' == table ) {
				$table_class = '.rad_dashboard_lists_stats .rad_dashboard_table_contents';
			}

			if ( 'optins' == table ) {
				$table_class = '.rad_dashboard_optins_all_table .rad_dashboard_table_contents';
			}

			refresh_stats_table( $table_class, orderby, table );

			this_el.parent().find( '.rad_dashboard_sort_button' ).removeClass( 'active_sorting' );

			this_el.addClass( 'active_sorting' );
		});

		$body.on( 'click', 'a#rad_dashboard_tab_content_header_stats:not(.current)', function(){
			refresh_stats_tab( false );
		});

		$body.on( 'click', '.end_test_table .rad_dashboard_content_row', function(){
			var this_el = $( this ),
				winner_id = this_el.data( 'optin_id' ),
				parent_id = this_el.parent().data( 'parent_id' ),
				optins_set = this_el.parent().data( 'optins_set' );

			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_pick_winner_optin',
					remove_option_nonce : rapidology_settings.remove_option,
					winner_id : winner_id,
					optins_set : optins_set,
					parent_id : parent_id
				},
				success: function( data ){
					reset_home_tab();
					$( '.rad_dashboard_end_test' ).remove();
				}
			});
		});

		$body.on( 'click', '.display_on_section .display_on_checkboxes_everything label', function() {
			check_display_options( $( this ).parent(), false );
		});

		$body.on( 'click', '.rad_rapidology_premade_item', function() {
			var this_item = $( this );
				$( '.rad_rapidology_premade_item' ).removeClass( 'rad_rapidology_layout_selected' );
				this_item.addClass( 'rad_rapidology_layout_selected' );

			$( '.rad_dashboard_next_customize button' ).data( 'selected_layout', this_item.data( 'layout' ) );

            $('html,body').animate({
                    scrollTop: $(".rad_dashboard_next_customize").offset().top},
                '2000');
		});

		$body.on( 'click', '.rad_dashboard_preview button', function() {
			if ( ! $( this ).hasClass( 'rapidology_preview_opened' ) ) {
				tinyMCE.triggerSave();
				var options_fromform = $( '.rad_rapidology #rad_dashboard_options' ).serialize();
				$( this ).addClass( 'rapidology_preview_opened' );
				$.ajax({
					type: 'POST',
					url: dashboardSettings.ajaxurl,
					dataType: 'json',
					data: {
						action : 'rapidology_display_preview',
						preview_options : options_fromform,
                       // preview: 'true',
						rapidology_preview_nonce : rapidology_settings.preview_nonce
					},
					success: function( data ){
						var $head = $( 'head' );
						$( '#wpwrap' ).append( data.popup_code );
						var $radRapidologyPreviewPopup = $('.rad_rapidology_preview_popup');
                        define_popup_position( $radRapidologyPreviewPopup, true );
						display_image( $radRapidologyPreviewPopup );
						$head.append( data.popup_css );

						$( data.fonts ).each( function( i, font_name ) {
							var font_name_converted = font_name.replace(/ /g,'+');

							if ( $head.find( 'link#' + font_name_converted ).length ) return;

							$head.append( '<link id="' + font_name_converted + '" href="http://fonts.googleapis.com/css?family=' + font_name_converted + '" rel="stylesheet" type="text/css" />' );
						});

						$( 'body' ).addClass( 'rad_rapidology_popup_active' );

						$( '.rad_rapidology_custom_html_form input[type="radio"], .rad_rapidology_custom_html_form input[type="checkbox"]' ).uniform();
					}
				});
			}

			return false;
		} );

        //uncheck redirect buttons if the other is checked non redirect bar
        $body.on('click', '.rad_rapidology_redirect_new_window input', function(){
            if($('.rad_rapidology_redirect_new_tab input').is(':checked')){
                $('.rad_rapidology_redirect_new_tab input').removeAttr('checked');
            };
            if($('.rad_rapidology_redirect_current_window input').is(':checked')){
                $('.rad_rapidology_redirect_new_tab input').removeAttr('checked');
            };
        });
        $body.on('click', '.rad_rapidology_redirect_new_tab input', function(){
            if($('.rad_rapidology_redirect_new_window input').is(':checked')){
                $('.rad_rapidology_redirect_new_window input').removeAttr('checked');
            };
            if($('.rad_rapidology_redirect_current_window input').is(':checked')){
                $('.rad_rapidology_redirect_current_window input').removeAttr('checked');
            };
        });
        $body.on('click', '.rad_rapidology_redirect_current_window', function(){
            if($('.rad_rapidology_redirect_new_window input').is(':checked')){
                $('.rad_rapidology_redirect_new_window input').removeAttr('checked');
            };
            if($('.rad_rapidology_redirect_new_tab input').is(':checked')){
                $('.rad_rapidology_redirect_new_tab input').removeAttr('checked');
            };
        });
        
		$body.on( 'click', '.rad_rapidology_preview_popup .rad_rapidology_close_button', function() {
			$( this ).parent().parent().remove();
			$( '#rad_rapidology_preview_css' ).remove();
			$( '.rad_dashboard_preview button' ).removeClass( 'rapidology_preview_opened' );
			$body.removeClass( 'rad_rapidology_popup_active' );
		});

        $body.on( 'click', '.rad_rapidology_preview_rapidbar .rad_rapidology_close_button', function() {
            $( this ).parent().parent().remove();
            $('.rapidbar_preview_wrapper').remove();
            $( '#rad_rapidology_preview_css' ).remove();
            $( '.rad_dashboard_preview button' ).removeClass( 'rapidology_preview_opened' );
            $body.removeClass( 'rad_rapidology_rapidbar_active' );
        });

		$body.on( 'click', '.rad_rapidology_preview_popup .rad_rapidology_submit_subscription', function() {
			return false;
		});

        $body.on( 'click', '#toplevel_page_rad_rapidology_options ul li a', function(){
            tinyMCE.remove();
        });

		function display_image( $popup ) {
			setTimeout( function() {
				$popup.find( '.rad_rapidology_image' ).addClass( 'rad_rapidology_visible_image' );
			}, 500 );
		}

		function resize( $current_ul ) {
			var bar_array = $( $current_ul ).find( 'li > div' ).map( function() {
				return $( this ).attr( 'value' );
			}).get();
			var bar_height = Math.max.apply( Math, bar_array );

			$( $current_ul ).find( 'li > div' ).each( function() {
				set_bar_height( $( this ), bar_height );
			});
		}

		function set_bar_height( $element, $bar_height ) {
			var value = $( $element ).attr( 'value' );
			var li_height = value / $bar_height * 375;
			$( $element ).animate({ height: li_height }, 700);
		}

		function switch_graph( $period, $list_id, $period_changed ) {

            $.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_get_stats_graph_ajax',
					rapidology_stats_nonce : rapidology_settings.rapidology_stats,
					rapidology_list : $list_id,
					rapidology_period : $period,
				},
				success: function( data ){
					if ( true === $period_changed ) {
                        stats = rapidology_settings.chart_stats;
                        $('.rad_rapidology_overall').remove();
                        $( '.rad_dashboard_lists_stats_graph_container' ).replaceWith( function() {
                            return $( data ).hide().fadeIn();
                        } );
                        rapidology_drawChart($period, stats, $list_id);

					} else {
                        $('.rad_rapidology_overall').remove();
                        $( '.rad_dashboard_lists_stats_graph_container' ).replaceWith( function() {
                            return $( data ).hide().fadeIn();
                        } );
                        stats = rapidology_settings.chart_stats;
                        rapidology_drawChart($period, stats, $list_id);

					}

				},
			});
		}



		function refresh_stats_table( $id, $orderby, $table ) {
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_refresh_optins_stats_table',
					rapidology_stats_nonce : rapidology_settings.rapidology_stats,
					rapidology_orderby : $orderby,
					rapidology_stats_table : $table
				},

				success: function( data ){

					$( $id ).replaceWith( data );
				}
			});
		}

		function refresh_stats_tab( $force_upd ) {
			if ( ! $( '.rad_dashboard_stats_ready' ).length || true == $force_upd ) {
                $( '.rad_rapidology_stats_spinner' ).addClass( 'rad_dashboard_spinner_visible' );
				//make sure that graphs start loading from the 0 height to avoid weird jumping of bars
				$( '.rad_dashboard_lists_stats_graph_container ul li div' ).css( 'height', '0' );

				$.ajax({
					type: 'POST',
					url: rapidology_settings.ajaxurl,
					data: {
						action : 'rapidology_reset_stats',
						rapidology_stats_nonce : rapidology_settings.rapidology_stats,
						rapidology_force_upd_stats : $force_upd
					},
					beforeSend: function( data ){
                        $('.stats-collapse').each(function(){
                           $(this).remove();
                        });
                        $('.rad_dashboard_optins_stats').each(function(){
                            $(this).remove();
                        });

						if ( ! $force_upd ) {
							$( '.rad_rapidology_stats_spinner' ).addClass( 'rad_dashboard_spinner_visible' );
						}
					},
					success: function( data ){

						$( '.rad_dashboard_stats_contents' ).replaceWith( data );

						$( 'ul.rad_rapidology_graph' ).each( function() {
							resize ( $( this ) );
						});

						$( '.rad_rapidology_refresh_stats' ).removeClass( 'rad_rapidology_loading' );
                        $( '.rad_rapidology_stats_spinner' ).removeClass( 'rad_dashboard_spinner_visible' );
                        stats = rapidology_settings.chart_stats;
                        rapidology_chart_init(30, stats);
					}
				});
			} else {
				$( '.rad_dashboard_lists_stats_graph_container ul li div' ).css( 'height', '0' );
				$( 'ul.rad_rapidology_graph' ).each( function() {
					resize ( $( this ) );
				});
			}
		}

		/**
		 * Restore all jQuery events after dashboard regeneration
		 */
		function restore_events() {
			var $rad_dashboard_upload_button = $('.rad-dashboard-upload-button');
            if ( $rad_dashboard_upload_button.length ) {
				var upload_button = $rad_dashboard_upload_button;

				rad_dashboard_image_upload( upload_button );

				upload_button.siblings( '.rad-dashboard-upload-field' ).on( 'input', function() {
					rad_dashboard_generate_preview_image( $( this ).siblings( '.rad-dashboard-upload-button' ) );
					$(this).siblings( '.rad-dashboard-upload-id' ).val('');
				} );

				upload_button.siblings( '.rad-dashboard-upload-field' ).each( function() {
					rad_dashboard_generate_preview_image( $( this ).siblings( '.rad-dashboard-upload-button' ) );
				} );
			}

            //rapidbar redirect auto select if selected
            if(jQuery('.rad_dashboard_enable_redirect_form input').is(':checked')){
                $('.rad_dashboard_select_provider select').append($('<option>', {
                    value: 'redirect',
                    text: 'Redirect Button'
                }));
                $('.rad_dashboard_select_provider select').val('redirect');
                $('.rad_dashboard_select_provider select option').each(function () {
                    if ($(this).val() != 'redirect') {
                        $(this).hide();
                    }
                });
                $('.rad_dashboard_new_account').hide();
                $('.rad_rapidology_success_redirect').hide();
                $('.rad_dashboard_select_optin').show();
            }


			$( '.rad-dashboard-color-picker' ).wpColorPicker();

			var $radDashboardConditional = $('.rad_dashboard_conditional');
            if ( $radDashboardConditional.length ) {
				$radDashboardConditional.each( function() {
					window.rad_dashboard_check_conditional_options( $( this ), true );
				});
			}

			//restore email services selections
			var selected_provider = $( '.rad_dashboard_select_provider select' ).val(),
				selected_account = 'empty';

			if ( 'empty' == selected_provider || 'custom_html' == selected_provider ) {
				$( '.rad_dashboard_select_account' ).css( { 'display' : 'none' } );
			} else {
				display_actual_accounts( selected_provider, false, '' );
				selected_account = $( '.rad_dashboard_select_account select' ).val();
			}

			$( '.rad_dashboard_select_list' ).css( { 'display' : 'none' } );

			if ( 'empty' !== selected_account && 'add_new' !== selected_account && ! ( 'empty' == selected_provider || 'custom_html' == selected_provider ) ) {
				display_actual_lists( selected_account, selected_provider );
			}

			check_display_options( $( '.display_on_checkboxes_everything' ), true );

			//fix the removing of tinymce editors in FireFox
			tinymce.init({
				mode : 'specific_textareas',
				editor_selector : 'rad_dashboard_optin_title',
				menubar : false,
				plugins: "textcolor",
				forced_root_block : "h2",
				toolbar: [
					"forecolor | bold italic | alignleft aligncenter alignright"
				]
			});

			tinymce.init({
				mode : 'specific_textareas',
				editor_selector : 'rad_dashboard_optin_message',
				menubar : false,
				plugins: "textcolor",
				toolbar: [
					"forecolor | bold italic | alignleft aligncenter alignright"
				]
			});

            tinymce.init({
                mode : 'specific_textareas',
                editor_selector : 'success_message',
                menubar : false,
                toolbar: [
                    "bold italic"
                ]
            });
		}

		function reset_options( $this_el, $form_id, $new_form, $is_child, $parent_id ) {
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_reset_options_page',
					reset_options_nonce : rapidology_settings.reset_options,
					reset_optin_id : $form_id
				},
				success: function( data ){
                    $('#rad_dashboard_options').show();
					$( '#rad_dashboard_wrapper_outer' ).replaceWith(data);
					open_optin_settings( $this_el, $new_form, $is_child, $parent_id );

					if ( true == $new_form ) {
						$( '.rad_dashboard_next_design button' ).addClass( 'rad_rapidology_open_premade' );
						$( '.rad_dashboard_tab_content_side_design a' ).addClass( 'rad_rapidology_open_premade' );
					}

                    var nav =  $('#rad_dashboard_navigation');
                    $(nav).remove();
                    $(nav).insertAfter('.rad_dashboard_tab_content .rad_dashboard_selection');
                    $('.rad_dashboard_tab_content_side_premade').hide();
                    $('.rad_dashboard_tab_content_optin_design #rad_dashboard_navigation:not(:first)').remove();
				}
			});
		}

		function reset_home_tab() {
            $('#rad_dashboard_options').hide();
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_home_tab_tables',
					home_tab_nonce : rapidology_settings.home_tab
				},
				success: function( data ){
					$( '.rad_dashboard_home_tab_content' ).replaceWith( data );
					try {
						tinymce.remove();
					} catch (e) {}
                    init_optin_modal();
				}
			});
		}

		function reset_accounts_tab() {
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_reset_accounts_table',
					accounts_tab_nonce : rapidology_settings.accounts_tab
				},
				success: function( data ){
					$( '.rad_dashboard_accounts_content' ).replaceWith( data );
				}
			});
		}

		function remove_optin( $form_id, $need_refresh, $is_account, $service, $parent_id ) {
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_remove_optin',
					remove_option_nonce : rapidology_settings.remove_option,
					remove_optin_id : $form_id,
					is_account : $is_account,
					service : $service,
					parent_id : $parent_id
				},

				success: function( data ){
					if ( true === $need_refresh ) {
						if ( true === $is_account ) {
							reset_accounts_tab();
						} else {
							reset_home_tab();
						}
					}
				}
			});
		}

		function duplicate_optin( $form_id, $form_type ) {
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_duplicate_optin',
					duplicate_option_nonce : rapidology_settings.duplicate_option,
					duplicate_optin_id : $form_id,
					duplicate_optin_type : $form_type
				},
				beforeSend: function() {
					$( '.duplicate_id_' + $form_id ).find( '.spinner' ).addClass( 'rad_dashboard_spinner_visible' );
				},
				success: function( data ){
					reset_home_tab();
				}
			});
		}

		function open_optin_settings( $this_el, $new_form, $is_child, $parent_id ) {
			restore_events();
			if ( true === $new_form ) {
				$( '#rad_dashboard_optin_type' ).val( $this_el.data( 'type' ) );
				$( '#rad_dashboard_optin_status' ).val( 'active' );
				$type = $this_el.data( 'type' );

				if ( 'flyin' == $type ) {
					$( '.rad_dashboard_field_orientation select' ).val( 'stacked' );
					$( '.rad_rapidology_load_in_animation select' ).val( 'slideup' );
				}
			} else {
				$type = $( '#rad_dashboard_optin_type' ).val();
			}

			var $radDashboardWrapper = $('#rad_dashboard_wrapper');
			$radDashboardWrapper.addClass( 'rad_dashboard_visible_nav' );

			$( '#rad_dashboard_options' ).removeAttr( 'class' ).addClass( 'current_optin_type_' + $type );
            if($('#rad_dashboard_options').hasClass('current_optin_type_rapidbar')){
                $('.rad_dashboard_enable_redirect_form').show();
                $('.rad_dashboard_select_rapidbar_position').show();
                $('.rad_dashboard_display_as_link_checkbox').show();
                $('.rad_rapidology_allow_dismiss').show();
            }else{
                $('.rad_dashboard_enable_redirect_form').hide();
                $('.rad_dashboard_select_rapidbar_position').hide();
                $('.rad_dashboard_display_as_link_checkbox').hide();
                $('.rad_rapidology_allow_dismiss').hide();
            }
            $('#rad_dashboard_options').show();
			var $radDashboardNavigation = $('#rad_dashboard_navigation');
			$radDashboardNavigation.find('> ul' ).removeAttr( 'class' ).addClass( 'nav_current_optin_type_' + $type );
			$radDashboardNavigation.removeAttr( 'class' ).addClass( 'current_optin_type_' + $type );
			$radDashboardWrapper.removeClass( 'rad_dashboard_edit_child' );

			if ( 'locked' == $type || 'inline' == $type ) {
				var $radDashboardNextShortcode = $('.rad_dashboard_next_shortcode button');
				$radDashboardNextShortcode.data( 'type', $type );
				$radDashboardNextShortcode.data( 'optin_id', $( '.rad_dashboard_save_changes button' ).data( 'subtitle' ) );
			}

			if ( true === $is_child ) {
				$( '#rad_dashboard_child_of' ).val( $parent_id );
				$radDashboardWrapper.addClass( 'rad_dashboard_edit_child' );
			}

			window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_optin_setup', 'side' );
		}

		function clear_account_confirmation() {
			$( '.rad_dashboard_confirmation_add_account' ).remove();
			$( '.rad_dashboard_account_new.clicked_button' ).removeClass( 'clicked_button' );
		}

		function authorize_network( $service, $container, $on_form, $account_name, $account_exists ) {
			var key_field = $( $container ).find( '#api_key_' + $service ),
				token_field = $( $container ).find( '#token_' + $service ),
				username_field = $( $container ).find( '#username_' + $service ),
				client_field = $( $container ).find( '#client_id_' + $service ),
				password_field = $( $container ).find( '#password_' + $service ),
				account_name = $( $container ).find( '#name_' + $service ),
                public_api_key = $( $container ).find( '#api_key_' + $service ),
                private_api_key = $( $container ).find( '#client_id_' + $service ),
                account_id = $( $container ).find( '#username_' + $service ),
                //salesforce items
                url = $( $container ).find( '#url_' + $service ),
                version = $( $container ).find( '#version_' + $service ),
                client_key = $( $container ).find( '#client_key_' + $service ),
                client_secret = $( $container ).find( '#client_secret_' + $service ),
                username_sf = $( $container ).find( '#username_sf_' + $service ),
                password_sf = $( $container ).find( '#password_sf_' + $service ),
                token = $( $container ).find( '#token_' + $service ),
                //end salesforce items
				account_name_val = '' == $account_name ? $( $container ).find( '#name_' + $service ).val() : $account_name;

			$( $container ).find( 'input' ).css( { 'border' : 'none' } );

			if ( ( key_field.length && '' == key_field.val() ) || ( token_field.length && '' == token_field.val() ) || ( username_field.length && '' == username_field.val() ) || ( client_field.length && '' == client_field.val() ) || ( password_field.length && '' == password_field.val() ) || ( account_name.length && '' == account_name_val ) ) {
				if ( '' == key_field.val() ) {
					key_field.css( { 'border' : '1px solid red' } );
				}
				if ( '' == token_field.val() ) {
					token_field.css( { 'border' : '1px solid red' } );
				}
				if ( '' == username_field.val() ) {
					username_field.css( { 'border' : '1px solid red' } );
				}
				if ( '' == client_field.val() ) {
					client_field.css( { 'border' : '1px solid red' } );
				}
				if ( '' == password_field.val() ) {
					password_field.css( { 'border' : '1px solid red' } );
				}
				if ( '' == account_name_val ) {
					account_name.css( { 'border' : '1px solid red' } );
				}
			} else {
				$.ajax({
					type: 'POST',
					url: rapidology_settings.ajaxurl,
					data: {
						action : 'rapidology_authorize_account',
						get_lists_nonce : rapidology_settings.get_lists,
						rapidology_api_key : key_field.val(),
						rapidology_upd_service : $service,
						rapidology_upd_name : account_name_val,
						rapidology_constant_token : token_field.val(),
						rapidology_username : username_field.val(),
						rapidology_client_id : client_field.val(),
						rapidology_password : password_field.val(),
						rapidology_account_exists : $account_exists,
                        rapidology_public_api_key : public_api_key.val(),
                        rapidology_private_api_key : private_api_key.val(),
                        rapidology_account_id : account_id.val(),
                        //salesforce start
                        rapidology_url : url.val(),
                        rapidology_version : version.val(),
                        rapidology_client_key : client_key.val(),
                        rapidology_client_secret : client_secret.val(),
                        rapidology_username_sf : username_sf.val(),
                        rapidology_password_sf : password_sf.val(),
                        rapidology_token : token.val(),
                        //salesforce end


					},
					beforeSend: function( data ) {
						$( $container ).find( 'span.spinner' ).addClass( 'rad_dashboard_spinner_visible' );
					},
                    error: function (xhr, ajaxOptions, thrownError){
                        $(  '.spinner'	).removeClass( 'rad_dashboard_spinner_visible' );
                        var error = 'There appears to be an issue with your credientals. Please check them and try again';
                        window.rad_dashboard_generate_warning( error, '#', '', '', '', '' );
                    },
					success: function( data ){
						$( $container ).find( 'span.spinner' ).removeClass( 'rad_dashboard_spinner_visible' );

						if ( 'success' == data || '' == data ) {
							reset_accounts_tab();

							if ( true === $on_form ) {
								hide_account_form( account_name_val );
							} else {
								$( '.rad_dashboard_select_provider_new select' ).prop( 'disabled', true ).addClass( 'rad_dashboard_disabled_input' );
								account_name.prop( 'disabled', true ).addClass( 'rad_dashboard_disabled_input' );

								$( '.authorize_service.new_account_tab' ).text( rapidology_settings.reauthorize_text );
								append_lists( $service, account_name_val );
							}
						} else {
							window.rad_dashboard_generate_warning( data, '#', '', '', '', '' );
						}
					}
				});
			}

			return false;
		}

		function append_lists( $service, $name ){
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_generate_current_lists',
					accounts_tab_nonce : rapidology_settings.accounts_tab,
					rapidology_service : $service,
					rapidology_upd_name : $name
				},
				success: function( data ){
					$( '.rad_dashboard_new_account_lists' ).remove();
					$( '.rad_dashboard_new_account_fields' ).after( function() {
						return $( data ).hide().fadeIn();
					} );
				}
			});
		}

		function hide_account_form( $account_name ) {
			$account_fields = $( '.account_settings_fields.rad_visible_settings' );
			$account_fields.removeClass( 'rad_visible_settings' );
			setTimeout( function() {
				display_actual_accounts( $account_fields.data( 'service' ), false, $account_name );
			}, 100 );
		}

		function display_actual_accounts( $service, $new_account, $set_to ) {
			var optin_id = $( '.rad_dashboard_save_changes button' ).data( 'subtitle' ),
				new_account = true == $new_account ? true : '';
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_generate_accounts_list',
					retrieve_lists_nonce : rapidology_settings.retrieve_lists,
					rapidology_service : $service,
					rapidology_optin_id : optin_id,
					rapidology_add_account : new_account
				},
				success: function( data ){
					$( 'li.rad_dashboard_select_account' ).replaceWith( function() {
						return $( data ).hide().fadeIn();
					} );

					$( 'li.rad_dashboard_select_list' ).hide();

					var $dashboard_select_account_select = $('li.rad_dashboard_select_account select');
                    if ( '' !== $set_to ) {
						$dashboard_select_account_select.val( $set_to );
					}

					if ( $dashboard_select_account_select.length && 'empty' !== $dashboard_select_account_select.val() && 'add_new' !== $dashboard_select_account_select.val() ){
						display_actual_lists( $dashboard_select_account_select.val(), $service );
					}
				}
			});
		}

		function display_actual_lists( $account_name, $service ) {
			var optin_id = $( '.rad_dashboard_save_changes button' ).data( 'subtitle' );
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_generate_mailing_lists',
					retrieve_lists_nonce : rapidology_settings.retrieve_lists,
					rapidology_account_name : $account_name,
					rapidology_service : $service,
					rapidology_optin_id : optin_id
				},
				success: function( data ){
                    if($('li.rad_dashboard_select_list').length > 0) {
                        $('li.rad_dashboard_select_list').replaceWith(function () {
                            return $(data).hide().fadeIn();
                        });
                    }else{
                        $('.rad_dashboard_provider_setup_dropdown li:last').after(function(){
                            return $(data).hide().fadeIn();
                        });
                    }
				}
			});
		}

		function display_new_account_form( $service ) {
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_generate_new_account_fields',
					accounts_tab_nonce : rapidology_settings.accounts_tab,
					rapidology_service : $service
				},
				success: function( data ){
					$( 'ul.rad_dashboard_new_account_fields' ).replaceWith( function() {
							return $( data ).hide().fadeIn();
						} );
					$( '.account_settings_fields' ).addClass( 'rad_visible_settings' );
				}
			});
		}

		function display_edit_account_tab( $edit_account, $service, $name ) {
			$( '#rad_dashboard_edit_account_tab' ).css( { 'display' : 'none' } );
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_generate_edit_account_page',
					accounts_tab_nonce : rapidology_settings.accounts_tab,
					rapidology_service : $service,
					rapidology_edit_account : $edit_account,
					rapidology_account_name : $name
				},
				success: function( data ){
					$( '#rad_dashboard_edit_account_tab' ).replaceWith( function() {
							return $( data ).hide().fadeIn();
					} );
				}
			});
		}

		function save_account_tab( $service, $account_name, $force_exit ) {
			if ( true == $force_exit ) {
				window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_header_accounts', 'header' );
			} else {
				$.ajax({
					type: 'POST',
					url: rapidology_settings.ajaxurl,
					data: {
						action : 'rapidology_save_account_tab',
						accounts_tab_nonce : rapidology_settings.accounts_tab,
						rapidology_service : $service,
						rapidology_account_name : $account_name
					},
					success: function( data ){
						reset_accounts_tab();
						window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_header_accounts', 'header' );
					}
				});
			}
		}

		function add_variant( $form_id ) {
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_add_variant',
					duplicate_option_nonce : rapidology_settings.duplicate_option,
					duplicate_optin_id : $form_id
				},
				success: function( data ){
					reset_options( '', data, false, true, $form_id );
				}
			});
		}

		function ab_test_controls( $parent_id, $action, $button ) {
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rapidology_ab_test_actions',
					ab_test_nonce : rapidology_settings.ab_test,
					parent_id : $parent_id,
					test_action : $action
				},
				success: function( data ){
					if ( 'start' == $action ) {
						$button.text( rapidology_settings.ab_test_pause_text );
					}

					if ( 'pause' == $action ) {
						$button.text( rapidology_settings.ab_test_start_text );
					}

					if ( 'end' == $action ) {
						$( '#wpwrap' ).append( data );
					}
				}
			});
		}

		function check_display_options( current_li, is_load ) {
			if ( ( current_li.find( 'input' ).prop( 'checked' ) && false == is_load ) || ( true != current_li.find( 'input' ).prop( 'checked' ) && true == is_load ) ) {
				current_li.siblings().removeClass( 'rad_rapidology_hidden_option' );
				$( '.categories_include_section' ).removeClass( 'rad_rapidology_hidden_option' );
			} else {
				current_li.siblings().addClass( 'rad_rapidology_hidden_option' );
				$( '.categories_include_section' ).addClass( 'rad_rapidology_hidden_option' );
			}
		}

		function rapidology_dashboard_save( $button ) {
			tinyMCE.triggerSave();
			var options_fromform = $( '.' + dashboardSettings.plugin_class + ' #rad_dashboard_options' ).serialize();
			$spinner = $button.parent().find( '.spinner' );
			$options_subtitle = $button.data( 'subtitle' );
			$.ajax({
				type: 'POST',
				url: rapidology_settings.ajaxurl,
				data: {
					action : 'rad_rapidology_save_settings',
					options : options_fromform,
					options_sub_title : $options_subtitle,
					save_settings_nonce : rapidology_settings.save_settings
				},
				beforeSend: function ( xhr ) {
					$spinner.addClass( 'rad_dashboard_spinner_visible' );
				},
				success: function( data ) {
					$spinner.removeClass( 'rad_dashboard_spinner_visible' );
					window.rad_dashboard_display_warning( data );
                    $('#rad_dashboard_options').hide();
					window.rad_dashboard_set_current_tab( 'rad_dashboard_tab_content_header_home', 'header' );
					$( '#rad_dashboard_wrapper' ).removeClass( 'rad_dashboard_visible_nav' );
					reset_home_tab();
				}
			});
		}

		function define_popup_position( $this_popup, $just_loaded ) {
			setTimeout( function() {
				var this_popup = $this_popup.find( '.rad_rapidology_form_container' ),
				popup_max_height = this_popup.hasClass( 'rad_rapidology_popup_container' ) ? $( window ).height() - 40 : $( window ).height() - 20,
				real_popup_height = 0,
				flyin_percentage = this_popup.parent().hasClass( 'rad_rapidology_flyin' ) ? 0.03 : 0.05,
				percentage = this_popup.hasClass( 'rad_rapidology_with_border' ) ? flyin_percentage + 0.03 : flyin_percentage,
				breakout_offset = this_popup.hasClass( 'breakout_edge' ) ? 0.95 : 1,
				dashed_offset = this_popup.hasClass( 'rad_rapidology_border_dashed' ) ? 4 : 0,
				form_height = this_popup.find( 'form' ).innerHeight(),
				form_add = true == $just_loaded ? 5 : 0;

				if ( this_popup.find( '.rad_rapidology_form_header' ).hasClass('split' ) ) {
					var image_height = this_popup.find( '.rad_rapidology_form_header img' ).innerHeight(),
						text_height = this_popup.find( '.rad_rapidology_form_header .rad_rapidology_form_text' ).innerHeight(),
						header_height = image_height < text_height ? text_height + 30 : image_height + 30;
				} else {
					var header_height = this_popup.find( '.rad_rapidology_form_header img' ).innerHeight() + this_popup.find( '.rad_rapidology_form_header .rad_rapidology_form_text' ).innerHeight() + 30;
				}

				this_popup.css( { 'max-height' : popup_max_height } );

				if ( this_popup.hasClass( 'rad_rapidology_popup_container' ) ) {
					var top_position = $( window ).height() / 2 - this_popup.innerHeight() / 2;
					this_popup.css( { 'top' : top_position + 'px' } );
				}

				this_popup.find( '.rad_rapidology_form_container_wrapper' ).css( { 'max-height' : popup_max_height - 20 } );


				if ( ( 768 > $body.outerWidth() + 15 ) || this_popup.hasClass( 'rad_rapidology_form_bottom' ) ) {
					if ( this_popup.hasClass( 'rad_rapidology_form_right' ) || this_popup.hasClass( 'rad_rapidology_form_left' ) ) {
						this_popup.find( '.rad_rapidology_form_header' ).css( { 'height' : 'auto' } );
					}

					real_popup_height = this_popup.find( '.rad_rapidology_form_header' ).innerHeight() + this_popup.find( '.rad_rapidology_form_content' ).innerHeight() + 30 + form_add;

					if ( this_popup.hasClass( 'rad_rapidology_form_right' ) || this_popup.hasClass( 'rad_rapidology_form_left' ) ) {
						this_popup.find( '.rad_rapidology_form_container_wrapper' ).css( { 'height' : real_popup_height - 30 + dashed_offset } );
					}
				} else {
					if ( header_height < form_height ) {
						real_popup_height = this_popup.find( 'form' ).innerHeight() + 30;
					} else {
						real_popup_height = header_height + 30;
					}

					if ( this_popup.hasClass( 'rad_rapidology_form_right' ) || this_popup.hasClass( 'rad_rapidology_form_left' ) ) {
						this_popup.find( '.rad_rapidology_form_header' ).css( { 'height' : real_popup_height * breakout_offset - dashed_offset } );
						this_popup.find( '.rad_rapidology_form_content' ).css( { 'min-height' : real_popup_height - dashed_offset } );
						this_popup.find( '.rad_rapidology_form_container_wrapper' ).css( { 'height' : real_popup_height } );
					}
				}

				if ( real_popup_height > popup_max_height ) {
					this_popup.find( '.rad_rapidology_form_container_wrapper' ).addClass( 'rad_rapidology_vertical_scroll' );
				} else {
					this_popup.find( '.rad_rapidology_form_container_wrapper' ).removeClass( 'rad_rapidology_vertical_scroll' );
				}

				if ( $this_popup.hasClass( 'rad_rapidology_popup' ) ) {
					$( 'body' ).addClass( 'rad_rapidology_popup_active' );
				}
			}, 100 );
		}


		$( window ).scroll( function(){
			if( $( this ).scrollTop() > 200 ) {
				$( '.rad_dashboard_preview' ).addClass( 'rad_dashboard_fixed' );
			} else {
				$( '.rad_dashboard_preview' ).removeClass( 'rad_dashboard_fixed' );
			}
		});


		$( window ).resize( function(){
			var $radRapidologyPreviewPopup = $('.rad_rapidology_preview_popup');
            if ( $radRapidologyPreviewPopup.length ) {
				define_popup_position( $radRapidologyPreviewPopup, false );
			}
		});

        $body.on( 'click', '.rad_dashboard_show_hide', function(){
            var trigger = $(this).parent().next('.rad_hidden');
            if(trigger.css('display') == 'none'){
                $(this).removeClass('dashicons-arrow-down-alt2');
                $(this).addClass('dashicons-arrow-up-alt2');
            }
            if(trigger.css('display') == 'block'){
                $(this).removeClass('dashicons-arrow-up-alt2');
                $(this).addClass('dashicons-arrow-down-alt2');
            }
            trigger.toggle('slow');

        });

        $body.on( 'click', '.stats-collapse .rad_dashboard_show_hide', function(){
            var trigger = $(this);
            if(trigger.hasClass('dashicons-arrow-down-alt2')){
                $(this).removeClass('dashicons-arrow-down-alt2');
                $(this).addClass('dashicons-arrow-up-alt2');
            }else{
                $(this).removeClass('dashicons-arrow-up-alt2');
                $(this).addClass('dashicons-arrow-down-alt2');
            }
            //trigger.toggle('slow');

        });

        $body.on( 'click','.rad_rapidology_redirect_page',function(e){
            e.preventDefault();
            var redirectUrl = $(this).data('redirect_url');
            window.open(redirectUrl);
        });

        $body.on('click','.rad_dashboard_enable_redirect_form input',function(){
            var thisbox = $(this);
            if(thisbox.is(':checked')){
                ischecked = 1;
            }else{
                ischecked = 0;
            }
            if(ischecked) {
                $('.rad_dashboard_select_provider select').append($('<option>', {
                    value: 'redirect',
                    text: 'Redirect Button'
                }));
                $('.rad_dashboard_select_provider select').val('redirect');
                $('.rad_dashboard_select_provider select option').each(function () {
                    if ($(this).val() != 'redirect') {
                        $(this).hide();
                    }
                });
               $(".rad_dashboard_select_account").hide();
               $(".rad_dashboard_select_list").hide();
               $('.rad_rapidology_success_redirect').hide();
               $('.rad_dashboard_select_optin').show();

                return;
            }

            if(!ischecked){
                $('.rad_dashboard_select_provider select option').each(function(){
                    if ($(this).val() == 'redirect') {
                        $(this).remove();
                    }
                    $(this).show();
                });
                $(".rad_dashboard_select_provider select").val("empty");
                $('.rad_rapidology_success_redirect').show();
                $('.rad_dashboard_select_optin').hide();
                return;
            }
        });

        $body.on('click','.rad_rapidology_save_list',function(event){
            event.preventDefault();
            var text_box = $('#rad_dashboard_redirect_list_id');
            list_name_text = text_box.val();
            if(list_name_text.length == 0){
                text_box.css('border-left','thick solid red');
                text_box.addClass('error');
                return;
            }
            $.ajax({
                type: 'POST',
                url: rapidology_settings.ajaxurl,
                data: {
                    action : 'rad_rapidology_save_redirect_lists',
                    rapidology_premade_nonce : rapidology_settings.rapidology_premade_nonce,
                    list_name : list_name_text,
                },
                success: function( data ) {
                    if(text_box.hasClass('error')) {
                        text_box.removeClass('error');
                    }
                        text_box.css('border-left', 'thick solid green');
                        text_box.val('List Added, Click here to add another list');

                },
                error: function(){
                    text_box.addClass('error');
                    text_box.css('border-left', 'thick solid red');
                    text_box.val('Something went wrong');
                }
            });
        });

				$body.on('click', '.webhook_authorize', function(event){
					event.preventDefault();
					var url = $('.rad_dashboard_center_webhook_url').val();
					console.log(url);
					var data =
					{
						'email': '',
						'first_name': '',
						'last_name': '',
						'full_name': '',
					}

					var submit_data = {
						url: url,
						data: data
					}
					$.ajax({
						type: 'POST',
						dataType: 'json',
						url: rapidology_settings.ajaxurl,
						data: {
							data: submit_data,
							action : 'rapidology_center_webhooks',
							center_nonce: rapidology_settings.center_nonce
						},
						beforeSend: function (data) {

						},
						success: function(response) {

							console.log(response);
						}

					});

				});
	});
})(jQuery);