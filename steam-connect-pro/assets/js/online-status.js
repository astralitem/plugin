jQuery(document).ready(function ($) {
    function updateSteamStatuses() {
        let steamIds = [];

        $('.scp-user-card').each(function () {
            const id = $(this).data('steamid');
            if (id) steamIds.push(id);
        });

        if (steamIds.length === 0) return;

        $.post(scpAjax.ajax_url, {
            action: 'scp_check_online_status',
            steamids: steamIds.join(','),
            nonce: scpAjax.nonce
        }, function (response) {
            if (response && response.success && response.data) {
                $.each(response.data, function (steamid, status) {
                    const el = $('.scp-user-card[data-steamid="' + steamid + '"] .scp-online-status');
                    if (el.length) {
                        el.text(status);
                        el.removeClass('online offline').addClass(status === 'Online' ? 'online' : 'offline');
                    }
                });
            } else {
                // optional: console log for debugging in dev
                // console.warn('scp: failed to update statuses', response);
            }
        }).fail(function (xhr) {
            // console.error('scp-ajax failed', xhr);
        });
    }

    updateSteamStatuses();
    setInterval(updateSteamStatuses, 30000);
});





   // پیام خطا اگه استیم آیدی تکراری بود 
  (function(){
    const el = document.getElementById('scp-sticky-error');
    if(el){
      setTimeout(() => {
        el.style.transition = 'opacity 0.5s ease';
        el.style.opacity = '0';
        setTimeout(()=> el.remove(), 500);
      }, 10000); // 10 ثانیه روی صفحه بمونه و بعد محو بشه
    }
  })();