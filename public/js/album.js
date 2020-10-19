$(function() {
    console.log('start');
    const observer = lozad();
    observer.observe();
    console.log('started lozard');

    albums = $('.album-container-content');

    console.log('start images load');


    function makeTall() {
        $(this).children().next().fadeIn('fast');
    };
    function makeShort() {
        $(this).children().next().fadeOut('fast');
    };


    var config = {
         over: makeTall, // function = onMouseOver callback (REQUIRED)
         timeout: 200, // number = milliseconds delay before onMouseOut
         interval: 100, // number = milliseconds delay before trying to call over
         out: makeShort // function = onMouseOut callback (REQUIRED)
    };

    $(".album-container-content").hoverIntent( config )

    // s$.each( albums, function( key, value ) {
    //     imgPath = $(value).attr('data-album-path') + '/cover.jpg'
    //     // console.log("<img src='" + imgPath + "' />");
    //     $(this).append("<img class=\"lozard\" data-src='" + imgPath + "' />");
    // });
});
