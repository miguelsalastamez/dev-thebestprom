! function(window, $) {
 $(document).ready(function(){
    $(document).on('click', '.ect-prev', function(e) {
        var prev_week = $('#ect-query-arg').html();
        var nextweekstart = JSON.parse(prev_week);
        nextweekstart.click = "prev";
        ect_weekly_ajax(nextweekstart);
    });

    $(document).on('click', '.ect-next', function(e) {
        var next_week = $('#ect-query-arg').html();
        var nextweekstart = JSON.parse(next_week);
        nextweekstart.click = "next";
        ect_weekly_ajax(nextweekstart);
    });

    function ect_weekly_ajax(nextweekstart){
        var ajaxUrl = extradata['ajax_url'];
        var nonce = extradata['nonce'];
       
        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: {
                'action': 'ect_get_prev_nxt_data',
                '_ajax_nonce': nonce,
                'weekly_date': nextweekstart
            },
            success: function(response) {
                // console.log(response);
                jQuery('#ect-weekly-events-wrapper').html(response);
                $('.ect_calendar_events_spinner').hide();
                //do whatever with the callback
            },

            beforeSend: function() {
                // initialize or setup anything before sending event request
                $('.ect_calendar_events_spinner').show();
            },
        });
    }
    
 });
}(window, jQuery);