jQuery(document).ready(function($) {
    // Handle deactivation click
    $('.mcrpd-deactivate-domain-btn').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var domainId = $btn.data('domain-id');
        var licId = $btn.data('lic-id');
        var nonce = $btn.data('nonce');
        var $li = $btn.closest('li');
        
        if (!confirm(mcrpd_front.confirm_deactivate)) {
            return;
        }
        
        // Disable button to prevent double submits
        $btn.css('pointer-events', 'none').css('opacity', '0.5').text(mcrpd_front.deactivating);
        
        $.post(mcrpd_front.ajax_url, {
            action: 'mcrpd_deactivate_domain',
            lic_id: licId,
            domain_id: domainId,
            nonce: nonce
        }, function(response) {
            if (response.status === 'success') {
                $li.fadeOut(300, function() {
                    $(this).remove();
                    // Reload is safest to make sure status badge (e.g. active -> pending) is updated.
                    location.reload();
                });
            } else {
                alert(response.message || 'Deactivation failed.');
                $btn.css('pointer-events', 'auto').css('opacity', '1').text('Deactivate');
            }
        }, 'json').fail(function() {
            alert('An error occurred. Please try again.');
            $btn.css('pointer-events', 'auto').css('opacity', '1').text('Deactivate');
        });
    });
});
