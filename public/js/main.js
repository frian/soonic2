$(function() {

    var screenWidth = $(window).width();

    /**
     * Returns a album list for an artist or remove album list (close)
     * Updates the navigation panel
     */
    $(document).on("click", ".artists-navigation a.artist", function(e) {

        e.preventDefault();

        var url = $(this).attr("href");

        if ($(this).next('ul').length) {
            $(this).next().remove();
        } else {
            $.get({
                url: url,
                context: this,
                cache: true,
                success: function(data) {
                    $(this).after(data);
                }
            });
        }
        $(".active").removeClass("active");
        $(this).addClass('active');
    });


    /**
     * Filters the artists list
     * Updates the navigation panel
     */
    var lastval = "";
    var timeout = null;

    $("input[name=filter]").keyup(function() {

        var url = '/artist/filter/';

        // -- if input is cleared
        if (this.value.length === 0 && lastval.length > 0) {

            $.get({
                url: url,
                cache: true,
                success: function(data) {
                    $("#artists-nav").remove();
                    $("nav.artists-navigation").append(data);
                }
            });
        }

        // -- if input has not changed
        if (this.value === lastval) {
            return;
        }

        lastval = this.value;

        // -- if input has less than 3 chars
        if (this.value.length < 3) {
            return;
        }

        var filter = this.value;

        if (timeout) {
            clearTimeout(timeout);
        }

        timeout = setTimeout(function() {

            $.get({
                url: url + filter,
                cache: true,
                success: function(data) {
                    $("#artists-nav").remove();
                    $("nav.artists-navigation").append(data);
                }
            });
        }, 300);
    });


    /**
     * Returns the songs from an album
     * Updates the songs panel
     */
    $(document).on("click", ".artists-navigation a.song", function(e) {

        e.preventDefault();

        var url = $(this).attr("href");

        $.get({
            url: url,
            cache: true,
            success: function(data) {
                $("#songs table tbody").remove();
                $("#songs table").append(data);
            }
        });
        $(".active").removeClass("active");
        $(this).addClass('active');

        if (screenWidth < 1024) {
            $(".artists-navigation").css('display', 'none');
            $(".songs").css('display', 'block');
            $(".songs").css('width', '100%');
            $(".playlist").css('display', 'none');
        }
    });


    /**
     * Returns search results
     * Updates the songs panel
     */
    $(document).on("click", "#searchButton", function(e) {

        e.preventDefault();

        var keyword = $("#form_keyword").val().length;

        if ($("#form_keyword").val().length < 3) {
            $("#form_keyword").val('');
            return;
        }

        var form = $('#searchForm');

        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: form.serialize(),
            success: function(data) {
                $("#songs table tbody").remove();
                $("#songs table").append(data);
            },
            error: function(data) {
                console.log("error");
            }
        });
    });


    /**
     * Start scan
     */
    $(document).on("click", "#scanButton", function(e) {

        e.preventDefault();

        if ($("#scanButton").hasClass('running')) {
            return;
        }

        $("#scanButton").toggleClass('running');

        $.get({
            url: '/scan',
            cache: true,
            success: function(data) {
                // $("body").append(data);
            }
        });

        $("#numFiles").text("0");
        $("#numArtists").text("0");
        $("#numAlbums").text("0");
        $("#scanStatus").text('scanning');

        var $loop = setInterval(myTimer, 1000);

        function myTimer() {
            $.get({
                url: '/scan/progress',
                cache: true,
                success: function(data) {
                    if (data.status == 'stopped') {
                        $("#numFiles").text(data.data.media_file);
                        $("#numArtists").text(data.data.artist);
                        $("#numAlbums").text(data.data.album);
                        clearInterval($loop);
                        $("#scanButton").toggleClass('running');
                        return;
                    }
                    $("#numFiles").text(data.data.media);
                    $("#numArtists").text(data.data.artist);
                    $("#numAlbums").text(data.data.album);
                }
            });
        }
    });


    /**
     * reload artist artist on clear filter form
     */
    $(document).on("click", ".filterForm .input-reset", function(e) {
        var url = '/artist/filter/';
        $.get({
            url: url,
            cache: true,
            success: function(data) {
                $("#artists-nav").remove();
                $("nav.artists-navigation").append(data);
            }
        });
    });


    /**
     * empty playlist
     */
    $(document).on("click", ".icon-trash", function(e) {
        if ($("#playlist tbody tr").length) {
            $("#playlist tbody tr").remove();
            $("#playlistNumFiles").text(0);
            $("#playlistFile").text('file');
            $("#playlistDuration").text('00:00');
            $("#playlistInfos").css('display', 'none');
        }
    });


    /**
     * Back to artists list
     */
    $(document).on("click", ".mobileBackButton", function(e) {

        $(".songs, .playlist").css('display', 'none');
        $(".artists-navigation").css('display', 'block');

        console.log('clicked');
    });


    /**
     * Forward to songs list/playlist
     */
    $(document).on("click", ".mobileForwardButton", function(e) {

        $(".songs, .playlist").css('display', 'block');
        $(".artists-navigation").css('display', 'none');

        console.log('clicked');
    });


});
