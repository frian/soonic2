$(function() {
    console.log('start');
    const observer = lozad();
    observer.observe();
    console.log('started lozard');

    albums = $('.album-container-content');

    console.log('start images load');


    function dummy() {
        return;
    };
    function show() {
        $(this).children(".lozad").fadeIn('fast');
    };


    var config = {
         over: dummy, // function = onMouseOver callback (REQUIRED)
         timeout: 200, // number = milliseconds delay before onMouseOut
         interval: 100, // number = milliseconds delay before trying to call over
         out: show // function = onMouseOut callback (REQUIRED)
    };

    $(".album-container-content").hoverIntent( config )

    $status = 'hidden';
    $(document).on("click", ".album-container-content", function(e) {

        status = status == 'hidden' ? 'visible' : 'hidden';
        if (status == 'hidden') {
            $(this).children(".lozad").fadeOut('fast');
        }
        else {
            $(this).children(".lozad").fadeIn('fast');
        }
    });

    // s$.each( albums, function( key, value ) {
    //     imgPath = $(value).attr('data-album-path') + '/cover.jpg'
    //     // console.log("<img src='" + imgPath + "' />");
    //     $(this).append("<img class=\"lozard\" data-src='" + imgPath + "' />");
    // });
});
