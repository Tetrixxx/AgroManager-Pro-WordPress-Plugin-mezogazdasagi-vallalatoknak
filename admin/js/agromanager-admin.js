/**
 * AgroManager Pro – Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // Soil quality slider display
        var $slider = $('#soil_quality');
        var $display = $('#soil_quality_display');
        if ($slider.length && $display.length) {
            $slider.on('input', function() {
                $display.text(this.value);
            });
        }

        // Confirm delete actions
        $('.agro-btn-delete').on('click', function(e) {
            if (!confirm('Biztosan törölni szeretnéd ezt az elemet?')) {
                e.preventDefault();
            }
        });

        // Smooth card animations on load
        $('.agro-dash-card').each(function(i) {
            var $card = $(this);
            $card.css({ opacity: 0, transform: 'translateY(20px)' });
            setTimeout(function() {
                $card.css({
                    opacity: 1,
                    transform: 'translateY(0)',
                    transition: 'all 0.4s ease'
                });
            }, i * 80);
        });

        // Table row hover animation
        $('.agromanager-table tbody tr').each(function(i) {
            var $row = $(this);
            $row.css({ opacity: 0 });
            setTimeout(function() {
                $row.css({ opacity: 1, transition: 'opacity 0.3s ease' });
            }, i * 30);
        });

        // Auto-dismiss notices
        $('.agromanager-wrap .notice').each(function() {
            var $notice = $(this);
            setTimeout(function() {
                $notice.fadeOut(400, function() { $(this).remove(); });
            }, 4000);
        });

        // Weather forecast card click highlight
        $('.agro-forecast-day').on('click', function() {
            $('.agro-forecast-day').removeClass('agro-forecast-selected');
            $(this).addClass('agro-forecast-selected');
        });
    });

})(jQuery);
