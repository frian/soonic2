$(function() {

    var screenWidth = $(window).width();
    var state = 'closed';

    /**
     * -- Ajax navigation -----------------------------------------------------
     */
    var openView;

    /**
     * Load library page
     */
    $(document).on("click", "#libraryButton", function(e) {
        e.preventDefault();

        $(openView).css('display', 'none');
        openView = null;

        $('.screen-view').css('display', 'block');

        $('#navigationRandom, #navigationAlbums, #navigationRadios, #navigationSettings' ).css('display', 'list-item');
        $('#navigationLibrary, #navigationRadioNew').css('display', 'none');
    });

    /**
     * Load albums page
     */
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

    $(document).on("click", "#albumsButton", function(e) {

        e.preventDefault();

        $(openView).css('display', 'none');
        $('.screen-view').css('display', 'none');

        if ($('.albums-view').length) {
            $('.albums-view').css('display', 'block');
        } else {
            var url = "/album/";
            $.ajax({
                url: url,
                cache: true,
                success: function(data) {
                    $('.screen-view').css('display', 'none');
                    $(document.body).append(data);
                    // const observer = lozad();
                    observer.observe();
                    $(".album-container-content").hoverIntent( config );
                },
                error: function(data) {
                    console.log("error");
                }
            });
        }
        $('#navigationLibrary, #navigationRadios, #navigationSettings').css('display', 'list-item');
        $('#navigationAlbums, #navigationRadioNew').css('display', 'none');
        openView = '.albums-view';
    });

    /**
     * Load radios page
     */
    $(document).on("click", "#radioButton", function(e) {

        e.preventDefault();

        $(openView).css('display', 'none');
        $('.screen-view').css('display', 'none');

        if ($('.radios-view').length) {
            $('.radios-view').css('display', 'block');
        } else {
            var url = "/radio/";

            $.ajax({
                url: url,
                cache: true,
                success: function(data) {
                    $(document.body).append(data);
                },
                error: function(data) {
                    console.log("error");
                }
            });
        }

        $('#navigationLibrary, #navigationAlbums, #navigationRadioNew, #navigationSettings').css('display', 'list-item');
        $('#navigationRadios, #navigationRandom').css('display', 'none');

        openView = '.radios-view';
    });


    /**
     * Load new radio page
     */
    $(document).on("click", "#radioNewButton", function(e) {

        e.preventDefault();

        $(openView).css('display', 'none');
        $('.screen-view').css('display', 'none');

        if ($('.radio-new-view').length) {
            $('.radio-new-view').css('display', 'block');
        } else {
            var url = "/radio/new";

            $.ajax({
                url: url,
                cache: true,
                success: function(data) {
                    $(document.body).append(data);
                },
                error: function(data) {
                    console.log("error");
                }
            });
        }

        $('#navigationLibrary, #navigationAlbums, #navigationRadios, #navigationSettings').css('display', 'list-item');
        $('#navigationRandom, #navigationRadioNew').css('display', 'none');

        openView = '.radio-new-view';
    });


    /**
     * Load settings page
     */
    $(document).on("click", "#settingsButton", function(e) {

        e.preventDefault();

        $(openView).css('display', 'none');
        $('.screen-view').css('display', 'none');

        if ($('.settings-view').length) {
            $('.settings-view').css('display', 'block');
        } else {
            var url = "/settings/";

            $.ajax({
                url: url,
                cache: true,
                success: function(data) {
                    $(document.body).append(data);
                },
                error: function(data) {
                    console.log("error");
                }
            });
        }
        $('#navigationSettings').css('display', 'none');
        $('#navigationLibrary, #navigationAlbums, #navigationRadios').css('display', 'list-item');

        openView = '.settings-view';
    });


    /**
     * Load random songs
     * Updates the songs panel
     */
    $(document).on("click", "#randomButton", function(e) {

        e.preventDefault();

        var url = "/songs/random";

        $.ajax({
            url: url,
            cache: true,
            success: loadSongPanel,
            error: function(data) {
                console.log("error");
            }
        });
    });




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
            $(".mobileSongsToArtistsButton").css('display', 'initial');
            $(".mobileSongsToPlaylistButton").css('display', 'block');
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
            success: loadSongPanel,
            error: function(data) {
                console.log("error");
            }
        });
    });


    /**
     * Helper for the two methods above
     */
    function loadSongPanel(data) {
        $("#songs table tbody").remove();
        $("#songs table").append(data);
        if ($("#topBarNav").hasClass('is-active')) {
            $("#topBarNav").toggleClass('is-active');
            $(".topNav").toggleClass('is-active');
            $(".songs").css('display', 'initial');
            $(".playlist").css('display', 'none');
            $(".artists-navigation").css('display', 'none');
            $(".mobileSongsToArtistsButton").css('display', 'initial');
            $(".mobileSongsToPlaylistButton").css('display', 'initial');
            hamburger.toggleClass("is-active");

            state = state == 'closed' ? 'open' : 'closed';
        }
    }


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

        $loop = setInterval(myTimer, 1000);
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
        $('.filterInput').focus();
    });


    /**
     * set focus on search input on clear
     */
    $(document).on("click", "#searchForm .input-reset", function(e) {
        $('#form_keyword').focus();
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
     * Forward to songs list
     */
    $(document).on("click", ".mobileArtistsToSongsButton", function(e) {

        $(".songs").css('display', 'block');
        $(".playlist").css('display', 'none');
        $(".artists-navigation").css('display', 'none');
        $(".mobileArtistsToSongsButton").css('display', 'none');
        $(".mobileSongsToArtistsButton").css('display', 'initial');
        $(".mobileSongsToPlaylistButton").css('display', 'initial');

        console.log('clicked 1');
    });


    /**
     * Back to artists list
     */
    $(document).on("click", ".mobileSongsToArtistsButton", function(e) {

        $(".songs, .playlist").css('display', 'none');
        $(".artists-navigation").css('display', 'block');
        $(".mobileSongsToArtistsButton").css('display', 'none');
        $(".mobileSongsToPlaylistButton").css('display', 'none');
        $(".mobileArtistsToSongsButton").css('display', 'initial');
        console.log('clicked 2');
    });


    /**
     * Forward to playlist
     */
    $(document).on("click", ".mobileSongsToPlaylistButton", function(e) {
        $(".songs").css('display', 'none');
        $(".playlist").css('display', 'initial');
        $(".mobileArtistsToSongsButton").css('display', 'none');
        $(".mobileSongsToPlaylistButton").css('display', 'none');
        $(".mobilePlaylistToSongsButton").css('display', 'initial');
        console.log('clicked 3');
    });


    /**
     * Back to songs list
     */
    $(document).on("click", ".mobilePlaylistToSongsButton", function(e) {
        $(".songs").css('display', 'initial');
        $(".playlist").css('display', 'none');
        $(".mobileSongsToPlaylistButton").css('display', 'initial');
        console.log('clicked 4');
    });


    /**
     * handle mobile menu
     */
    var hamburger = $(".hamburger");
    hamburger.on("click", function(e) {

        $(".topbarNav").toggleClass("is-active");
        $(".topNav").toggleClass("is-active");
        hamburger.toggleClass("is-active");

        state = state == 'closed' ? 'open' : 'closed';
        console.log(state);
    });


    /**
     * check if we are scanning
     */
    if ($(location).attr('pathname') === '/settings/') {

        var $loop;

        $.get({
            url: '/scan/progress',
            cache: true,
            success: function(data) {
                if (data.status == 'running') {
                    $("#scanButton").toggleClass('running');
                    $loop = setInterval(myTimer, 1000);
                }
            }
        });
    }


    /**
     * update library infos while we are scanning
     */
    function myTimer() {
        $.get({
            url: '/scan/progress',
            cache: true,
            success: function(data) {
                if (data.status == 'stopped') {
                    clearInterval($loop);
                    $("#scanButton").toggleClass('running');
                }
                $("#numFiles").text(data.data.song);
                $("#numArtists").text(data.data.artist);
                $("#numAlbums").text(data.data.album);
            }
        });
    }

});
