jQuery(document).ready(function($) {
    
    // When quick edit link is clicked
    $('body').on('click', '.editinline', function() {
        var $row = $(this).closest('tr');
        var termId = $row.attr('id').replace('tag-', '');
        var orderValue = $row.find('.column-yoco_order').text();
        
        // Wait a bit for WordPress to create the quick edit form
        setTimeout(function() {
            var $editRow = $('#edit-' + termId);
            if ($editRow.length) {
                $editRow.find('input[name="yoco_category_order"]').val(orderValue);
            }
        }, 100);
    });
    
    // Handle the form submission
    $('body').on('click', '.button.save', function() {
        var $form = $(this).closest('form');
        var termId = $form.find('input[name="tag_ID"]').val();
        var orderValue = $form.find('input[name="yoco_category_order"]').val();
        
        if (orderValue) {
            // Update the displayed value in the table after save
            setTimeout(function() {
                $('#tag-' + termId).find('.column-yoco_order').text(orderValue);
            }, 1000);
        }
    });
});