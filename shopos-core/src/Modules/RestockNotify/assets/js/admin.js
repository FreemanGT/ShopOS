(function($){
    'use strict';
    $(document).ready(function(){
        var $all = $('#shopos-restock-select-all, .shopos-restock-check-all');
        $all.on('change', function(){ var c=$(this).is(':checked'); $('input[name="shopos_restock_ids[]"]').prop('checked',c); $all.prop('checked',c); });
        $(document).on('change','input[name="shopos_restock_ids[]"]',function(){ var t=$('input[name="shopos_restock_ids[]"]').length, c=$('input[name="shopos_restock_ids[]"]:checked').length; $all.prop('checked',t===c); });
    });
})(jQuery);
