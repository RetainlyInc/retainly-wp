(function() {
	tinymce.create('tinymce.plugins.rapidology', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */


		init : function(ed, url) {
			var t = this,
				optins_locked = jQuery.parseJSON( rapidology.locked_optins ),
				optins_inline = jQuery.parseJSON( rapidology.inline_optins ),
                onclick_optins = jQuery.parseJSON( rapidology.onclick_optins ),
				$rapidology_tooltip = rapidology.rapidology_tooltip,
				$inline_text = rapidology.inline_text,
				$locked_text = rapidology.locked_text,
				count = 0,
				$menu_items_locked = [],
				$menu_items_inline = [],
                $menu_items_onclick = [];
			jQuery(optins_locked).each(function(i,val){
				jQuery.each(val,function(optin_id,optin_title) {
					$menu_items_locked.push( {'text' : optin_title,
						'onclick' : function() {
							if ( 'empty' !== optin_id ) {
								var selected_text = ed.selection.getContent();
									return_text = '[rad_rapidology_locked optin_id='+ optin_id + ']' + selected_text + '[/rad_rapidology_locked]';

								ed.insertContent(return_text);
							}
						}
					} );
				});
			});

			jQuery(optins_inline).each(function(i,val){
				jQuery.each(val,function(optin_id,optin_title) {
					$menu_items_inline.push( {
						'text' : optin_title,
						'onclick' : function() {
							if ( 'empty' !== optin_id ) {
								return_text = '[rad_rapidology_inline optin_id='+ optin_id + ']';
								ed.insertContent(return_text);
							}
						}
					} );
				});
			});

            jQuery(onclick_optins).each(function(i,val){
                jQuery.each(val,function(optin_id,optin_title) {
                    $menu_items_onclick.push( {
                        'text' : optin_title,
                        'onclick' : function() {
                            if ( 'empty' !== optin_id ) {
                                return_text = '[rapidology_on_click_intent optin_id='+ optin_id + ' display=inline] [/rapidology_on_click_intent]';
                                ed.insertContent(return_text);
                            }
                        }
                    } );
                });
            });

			ed.addButton('rapidology_button', {
				text: '',
				icon: 'rad_rapidology_shortcode_icon',
				type: 'menubutton',
				tooltip : $rapidology_tooltip,
				menu:
					[
						{
							text: $locked_text,
							menu: $menu_items_locked
						},
						{
							text: $inline_text,
							menu: $menu_items_inline
						},
                        {
                            text: 'Onclick Optins',
                            menu: $menu_items_onclick
                        }
					]
			});
		},


		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl : function(n, cm) {
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : "Rapidology",
				author : 'Rapidology',
				authorurl : 'http://www.rapidology.com/',
				infourl : 'http://www.rapidology.com/',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add( 'rapidology', tinymce.plugins.rapidology );
})();