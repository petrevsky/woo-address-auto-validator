jQuery( function ( $ ) {


	

	tippy.delegate('body', {
		target: '.waav-message',
		//content: '<div class="waav-tooltip">' + jQuery('body').find('.waav-message').text() + '</div>', 
		allowHTML: true,
		onShow(instance) {
			instance.setContent( jQuery('body').find('.waav-message').text() );
			// Code here is executed every time the tippy shows
		},
	});

	
	$('.edit_address').on( 'click', function() {

		// check if shipping address is clicked
		if( $(this).closest('.order_data_column').find('.waav-after-address').length > 0 ) {

			$('.waav-after-address .auto-correct-field').show();
			//$('.wac-after-address .auto-correct-field input').prop( 'checked', true );
			
		}
	});


	function replace_shipping_address( replace_address ) {
		
		address_string = "";

		$.each(replace_address, function( index, value ) {

			if( index == "_shipping_first_name" ) {
				separator = " ";
			} else {
				separator = "<br>";
			}

			if( value ) {
				address_string += value + separator
			}

			$('#' + index).val(value);

		});

		if( address_string !== "" ) {
			$('.waav-after-address').closest('.order_data_column').find('.address p:first-of-type').html(address_string);
		}

	}

	function reset_address_html( post_id, shipping_address_field ) {

		$.ajax({
			type: "POST",
			url: waav_var.ajax_url,
			data: { 
				action: 'waav_generate_address_html',
				post_id: post_id,
				nonce: waav_var.nonce
			},
			cache: false,
			success: function( data ){

				if( data ) {
					$('.waav-post-container').html( data );
				}

				shipping_address_field.unblock();
			}
		});

		

	}


	function get_pretty_address( address ) {

		var address_string = '';
		var separator = ", ";
		var address_count = 0;

		$.each(address, function( index, value ) {

			if( value ) {
				//console.log( value );
				address_count++;
			}

		});

		var i = 0;

		$.each(address, function( index, value ) {

			if( value ) {
				address_string += value;
				// console.log( value );
			}
			
			if( value && ( i != ( address_count - 1 ) ) ) {

				// console.log( address_count );
				// console.log( i );
				address_string += separator;
			}
			
			if( value ) {
				i++;
			}
			
		});
		

		return address_string;
	}



	$(document.body).on('click', '.shipping-label__new-label-button', function() {
		
		var post_id = $('#post_ID').val();
		

		$.ajax({
			type: "POST",
			url: waav_var.ajax_url,
			dataType: "json",
			data: { 
				action: 'waav_check_invalid_address',
				post_id: post_id,
				nonce: waav_var.nonce
			},
			cache: false,
			success: function( data ) {
				if( data == 1 ) {
					alert( "Be careful! This order is related to subscription that has invalid address. It is advised to not ship anything yet." );
				}
			},
			error: function(xhr, status, error) {
				
			}			  
		});

	});

	$(document.body).on('click', '.waav-button-revert', function() {

		var post_id = $('#post_ID').val();

		var shipping_address_field = $(this).closest('.order_data_column');
		shipping_address_field.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

		$.ajax({
			type: "POST",
			url: waav_var.ajax_url,
			dataType:"json",
			data: { 
				action: 'waav_reset_address',
				post_id: post_id,
				nonce: waav_var.nonce
			},
			cache: false,
			success: function( data ){

				if( data ) {
					
					matched_address = data['matched_address_formatted'];
					original_address = data['original_address_formatted'];

					replace_shipping_address( data['replace_address'] );
					
					reset_address_html( post_id, shipping_address_field );

					var note1 = '<b>Current address</b>: ' + get_pretty_address( matched_address );
					var note2 = '<b>Original address</b>: ' + get_pretty_address( original_address );
					var note3 = 'Address reset success.';

					var notes = [note1, note2, note3];
					add_order_notes( post_id, notes.reverse() );

					waav_notify_msg.success( "Address reverted" );

				} else {

					waav_notify_msg.error( "Address revert error" );
					var notes = ['Address revert error. No data found.'];
					add_order_notes( post_id, notes.reverse() );

				}
				
				
			}
		});


	});

	
	$(document.body).on('click', '.waav-button-validate', function() {

		var shipping_address_field = $(this).closest('.order_data_column');
		shipping_address_field.block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

		post_id = $('#post_ID').val();


		$.ajax({
			type: "POST",
			url: waav_var.ajax_url,
			dataType:"json",
			data: { 
				action: 'waav_validate_address',
				post_id: post_id,
				nonce: waav_var.nonce
			},
			cache: false,
			success: function( data ){

				if( data ) {
					// console.log(data);

					if( 'replace_address' in data ) {

						matched_address = data['matched_address_formatted'];
						original_address = data['original_address_formatted'];

						if( data['status'] !== 'error' ) {
							replace_shipping_address( data['replace_address'] );
						}		
						

						// after replace add comments with current shipping address & matched
						// add comment Address Verification: [status]
						// update address validation section dynamically

						var note1 = '<b>Current address</b>: ' + get_pretty_address( original_address );
						
						var note2 = "";
						matched_address_pretty = get_pretty_address( matched_address );
						if( matched_address_pretty ) {
							note2 = '<b>Matched address</b>: ' + get_pretty_address( matched_address );
						}

						var note3 = 'Address verification: ' + data['status']; 
						var note4 = 'Address manually validated.';

						var notes = [note1, note2, note3, note4];

						notes = notes.filter(item => item);

						add_order_notes( post_id, notes.reverse() );

						reset_address_html( post_id, shipping_address_field );

						waav_notify_msg.success( "Address validated" );
					} else {
						original_address = data['original_address_formatted'];
						var note1 = '<b>Current address</b>: ' + get_pretty_address( original_address );
						var note2 = 'Address verification: ' + data['status']; 
						var note3 = 'Address manually validated.';

						var notes = [note1, note2, note3];

						add_order_notes( post_id, notes.reverse() );
						reset_address_html( post_id, shipping_address_field );
						waav_notify_msg.success( "Address validated" );
					}


				} else {

					var notes = ['Address verification failed. Please contact developer.'];
					waav_notify_msg.error( "Validation failed. Please try again." );

					add_order_notes( post_id, notes.reverse() );

					shipping_address_field.unblock();
				}

				
			}
		});

	});

	function add_order_notes( post_id, notes ) {
		
		//console.log( notes );

		if ( notes.length > 0 ) {

			note = notes.pop();
			// console.log( note );
			// console.log( notes );

			$( '#woocommerce-order-notes' ).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});

			var data = {
				action:    'woocommerce_add_order_note',
				post_id:   post_id,
				note:      note,
				note_type: "admin",
				security:  woocommerce_admin_meta_boxes.add_order_note_nonce
			};


			$.ajax({
				type: "POST",
				url: waav_var.ajax_url,
				data: data,
				success: function( response ){

					$( 'ul.order_notes .no-items' ).remove();
					$( 'ul.order_notes' ).prepend( response );
					$( '#woocommerce-order-notes' ).unblock();

					add_order_notes( post_id, notes );
				}
			});

		}


		/*$.post( waav_var.ajax_url, data, function( response ) {

			$( 'ul.order_notes .no-items' ).remove();
			$( 'ul.order_notes' ).prepend( response );
			$( '#woocommerce-order-notes' ).unblock();

		}); */
    };





});


var waav_notify_msg=
{
	error:function(message)
	{
		var er_elm=jQuery('<div class="waav_notify_msg" style="background:#dd4c27; border:solid 1px #dd431c;">'+message+'</div>');				
		this.setNotify(er_elm);
	},
	success:function(message)
	{
		var suss_elm=jQuery('<div class="waav_notify_msg" style="background:#4bb543; border:solid 1px #2bcc1c;">'+message+'</div>');				
		this.setNotify(suss_elm);
	},
	setNotify:function(elm)
	{
		jQuery('body').append(elm);
		jQuery('.wt_notify_msg').click(function(){
			jQuery(this).remove();
		});
		elm.stop(true,true).animate({'opacity':1,'top':'50px'},1000);
		setTimeout(function(){
			elm.animate({'opacity':0,'top':'100px'},1000,function(){
				elm.remove();
			});
		},3000);
	}
}