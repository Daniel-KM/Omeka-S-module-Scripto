(function($) {
    $(document).ready(function() {
        $(document).on('o:expanded', '#page-action-menu a.collapse', function() {
            var button = $(this);
            $(document).on('mouseup.page-actions', function(e) {
                var pageActionMenu = $('#page-action-menu ul');
                if (pageActionMenu.is(e.target)) {
                    return;
                }
                if (!button.is(e.target)) {
                    button.click();
                }
                $(document).off('mouseup.page-actions');
            });
        });
    
        $('.layout button').click(function(e) {
            $('.layout button').toggleClass('active');
            $('.wikitext-featherlight').toggleClass('horizontal').toggleClass('vertical');
            $('.layout button:disabled').removeAttr('disabled');
            $('.layout button.active').attr('disabled', true);
        });
    
        $('.full-screen').featherlight('.wikitext-featherlight', {
            beforeOpen: function() {
                $('#wikitext .media-render').panzoom('destroy');
            },
            afterOpen: function() {
                $('.featherlight-content .media-render').panzoom();
            },
            beforeClose: function() {
                $('.featherlight-content .media-render').panzoom('destroy');
            },
            afterClose: function() {
                $('#wikitext .media-render').panzoom();
            }
        });
    });
})(jQuery)