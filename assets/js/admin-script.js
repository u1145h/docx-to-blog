jQuery(document).ready(function($) {
    // Add loading state to export buttons
    $('.button-primary').on('click', function() {
        $(this).addClass('updating-message').prop('disabled', true);
    });

    // Handle file input changes
    $('#docx_file').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        if (fileName) {
            $(this).next('.file-name').text(fileName);
        }
    });
});
