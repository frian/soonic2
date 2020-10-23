$(function() {

    const observer = lozad();
    observer.observe();

    $status = 'hidden';
    $(document).on("click", ".album-container-content", function(e) {
        status = status == 'hidden' ? 'visible' : 'hidden';
        console.log('clic');
        if (status == 'hidden') {
            $(this).children(".lozad").fadeOut('fast');
        }
        else {
            $(this).children(".lozad").fadeIn('fast');
        }
    });

});
