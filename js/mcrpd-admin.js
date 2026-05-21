jQuery( function( $ ) {

    // Delete Domain button logic
    $( 'a.slm-remove-domain-btn' ).on( 'click', function( e ) {
        e.preventDefault();

        var $link = $( this );

        if ( ! confirm( slm_admin_data.confirm_remove_domain ) ) {
            $link.blur();
            return false;
        }

        var $spinner = $( '<span />' ).addClass( 'spinner' ).css( 'visibility', 'visible' ).css( 'margin', '0 0 0 2px' );
        $link.before( $spinner ).hide();

        var id = $link.attr( 'id' ),
            $msg = $( '#reg_del_msg' );

        $msg.html( slm_admin_data.msg_loading ).show();

        var req = jQuery.ajax({
            url: ajaxurl,
            type: 'post',
            data: { 
                action: 'slm_delete_domain', 
                domain_id: $(this).data('domain-id'), 
                'lic_id': $(this).data('lic-id'), 
                '_ajax_nonce': $(this).data('nonce') 
            }
        });

        req.done(function (data) {
            if (data.status !== 'success') {
                slmDeleteDomainError(data);
                return false;
            }

            $msg.addClass( 'success' ).html( slm_admin_data.msg_deleted );
                            
            var $tr = $link.parents( 'tr:first' );
            $tr.fadeOut( 'fast', function() {
                $tr.remove();

                // Check if any more rows exist.
                if ( ! $( '.domain-license-table tbody tr' ).length ) {
                    var $none = $( '<p />' ).html( slm_admin_data.msg_no_domains ).hide();
                    $( '.domain-licenses' ).after( $none ).hide();
                    $none.fadeIn( 'fast' );
                } else {
                    // Restripe table.
                    $( '.domain-license-table tbody tr.alternate' ).removeClass( 'alternate' );
                    $( '.domain-license-table tbody tr:even' ).addClass( 'alternate' );
                }
            });
        });

        req.fail(function (data) {
            slmDeleteDomainError(data);
        });

        function slmDeleteDomainError(data) {
            $msg.addClass( 'error' ).html( slm_admin_data.msg_failed );
            jQuery($spinner).remove();
            jQuery($link).show();
        }

    });

    // Bulk operation confirmation
    $('input#doaction').on('click', function(e) {
        return confirm( slm_admin_data.confirm_bulk_op );
    });

});
