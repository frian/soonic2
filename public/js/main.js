$(function() {
    'use strict';

    const debug = 1;
    let screenWidth = $(window).width();
    let mobileMenuState = 'closed';
    let openView = null;

    _init();

    // -- on resize
    let resizeTimer;
    $(window).resize(function() {
        if(resizeTimer) {
            window.clearTimeout(resizeTimer);
        }
        resizeTimer = window.setTimeout(function() {
            screenWidth = $(window).width();
            _init();
        }, 30);
    });


    /**
     * -- Ajax navigation -----------------------------------------------------
     */

    /**
     * Load library page
     */
    $(document).on("click", "#libraryButton", function(e) {

        e.preventDefault();

        $(openView).css('display', 'none');
        openView = null;

        $('.library-view').css('display', 'block');

        $('#navigationRandom, #navigationAlbums, #navigationRadios, #navigationSettings, #navigationSearchForm' ).css('display', 'list-item');
        $('#navigationLibrary, #navigationRadioNew').css('display', 'none');
        setSongInfoSize();

        if (debug === 1) {
            console.log('clicked on library');
            console.log("- openView = " + openView);
        }
    });

    /**
     * Load albums page
     */
    const observer = lozad();
    observer.observe();

    $(document).on("click", ".album-container-content", function(e) {
        $(this).children(".lozad").fadeOut('fast').delay(3000).fadeIn('fast');
    });

    $(document).on("click", "#albumsButton", function(e) {

        e.preventDefault();

        $(openView).css('display', 'none');
        $('.library-view').css('display', 'none');

        if ($('.albums-view').length) {
            $('.albums-view').css('display', 'block');
        } else {
            const url = "/album/";
            $.ajax({
                url: url,
                cache: true,
                success: function(data) {
                    $('.library-view').css('display', 'none');
                    $(document.body).append(data);
                    observer.observe();
                },
                error: function(data) {
                    console.log("error");
                }
            });
        }
        $('#navigationLibrary, #navigationRadios, #navigationSettings').css('display', 'list-item');
        $('#navigationAlbums, #navigationRadioNew, #navigationSearchForm, #navigationRandom').css('display', 'none');
        openView = '.albums-view';

        if (debug === 1) {
            console.log('clicked on albums');
            console.log("- openView = " + openView);
        }
    });


    /**
     * Load radios page
     */
    $(document).on("click", "#radioButton", function(e) {

        e.preventDefault();

        $(openView).css('display', 'none');
        $('.library-view').css('display', 'none');

        if ($('.radios-view').length) {
            $('.radios-view').css('display', 'block');
        } else {
            const url = "/radio/";

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
        $('#navigationRadios, #navigationRandom, #navigationSearchForm').css('display', 'none');
        setSongInfoSize();
        openView = '.radios-view';

        if (debug === 1) {
            console.log('clicked on radio');
            console.log("- openView = " + openView);
        }
    });


    /**
     * Load new radio page
     */
    $(document).on("click", "#radioNewButton", function(e) {

        e.preventDefault();

        $(openView).css('display', 'none');
        $('.library-view').css('display', 'none');

        if ($('.radio-new-view').length) {
            $('.radio-new-view').css('display', 'block');
        } else {
            const url = "/radio/new";

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
        setSongInfoSize();
        openView = '.radio-new-view';

        if (debug === 1) {
            console.log('clicked on new radio');
            console.log("- openView = " + openView);
        }
    });


    /**
     * Load settings page
     */
    $(document).on("click", "#settingsButton", function(e) {

        e.preventDefault();

        $(openView).css('display', 'none');
        $('.library-view').css('display', 'none');

        if ($('.settings-view').length) {
            $('.settings-view').css('display', 'block');
        } else {
            const url = "/settings/";

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
        $('#navigationSettings, #navigationRandom, #navigationSearchForm, #navigationRadioNew').css('display', 'none');
        $('#navigationLibrary, #navigationAlbums, #navigationRadios').css('display', 'list-item');
        setSongInfoSize();
        openView = '.settings-view';

        if (debug === 1) {
            console.log('clicked on settings');
            console.log("- openView = " + openView);
        }
    });


    /**
     * Load random songs
     * Updates the songs panel
     */
    $(document).on("click", "#randomButton", function(e) {

        e.preventDefault();

        const url = "/songs/random";

        $.ajax({
            url: url,
            cache: true,
            success: loadSongPanel,
            error: function(data) {
                console.log("error");
            }
        });

        if (debug === 1) {
            console.log('clicked on random songs');
            console.log("- openView = " + openView);
        }
    });


    /**
     * Returns a album list for an artist or remove album list (close)
     * Updates the navigation panel
     */
    $(document).on("click", ".artists-navigation a.artist", function(e) {

        e.preventDefault();

        const url = $(this).attr("href");

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

        if (debug === 1) {
            console.log('clicked on an artist in artist nav');
        }
    });


    /**
     * Filters the artists list
     * Updates the navigation panel
     */
    let lastval = "";
    let timeout = null;

    $("input[name=filter]").keyup(function() {

        const url = '/artist/filter/';

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

        const filter = this.value;

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

        if (debug === 1) {
            console.log('filetered artists');
        }
    });


    /**
     * Returns the songs from an album
     * Updates the songs panel
     */
    $(document).on("click", ".artists-navigation a.song", function(e) {

        e.preventDefault();

        const url = $(this).attr("href");

        $.get({
            url: url,
            cache: true,
            success: function(data) {
                $("#songs tbody").remove();
                $("#songs").append(data);
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

        if (debug === 1) {
            console.log('clicked on an album in artist nav');
        }
    });


    /**
     * Returns search results
     * Updates the songs panel
     * TODO: search on smallscreen
     */
    $(document).on("click", "#searchButton", function(e) {

        e.preventDefault();

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

        if (debug === 1) {
            console.log('clicked on search');
        }
    });


    /**
     * Helper for the two methods above
     */
    function loadSongPanel(data) {

        $("#songs tbody").remove();
        $("#songs").append(data);
        // if ($("#topBarNav").hasClass('is-active')) {
        //     $("#topBarNav").toggleClass('is-active');
        //     $(".topNav").toggleClass('is-active');
        //     $(".songs").css('display', 'initial');
        //     $(".playlist").css('display', 'none');
        //     $(".artists-navigation").css('display', 'none');
        //     $(".mobileSongsToArtistsButton").css('display', 'initial');
        //     $(".mobileSongsToPlaylistButton").css('display', 'initial');
        //     hamburger.toggleClass("is-active");
        //
        //     if (debug === 1) {
        //         console.log('after hamburger');
        //     }
        //
        //     mobileMenuState = mobileMenuState == 'closed' ? 'open' : 'closed';
        // }

        if (debug === 1) {
            console.log('in loadSongPanel');
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

        $loop = setInterval(scanTimer, 1000);

        if (debug === 1) {
            console.log('clicked on scan');
        }
    });


    /**
     * Submit setting form
     */
    $(document).on("click", "#settingsFormButton", function(e) {

        e.preventDefault();

        const form = $('#settingsForm');

        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: form.serialize(),
            dataType: 'json',
            success: function(data) {

                let href = "";
                if ($('#screenThemeCss')) {
                    href = "/css/themes/" + data.config.theme + "/screen.css";
                    $('#screenThemeCss').attr('href', href );
                }
                if ($('#layoutThemeCss')) {
                    href = "/css/themes/" + data.config.theme + "/layout.css";
                    $('#layoutThemeCss').attr('href', href );
                }

                const textToTranslate = $('[data-text]');

                $.each(textToTranslate, function( index, value ) {
                    if ($(this).prop("tagName") === 'INPUT') {
                        if ($(this).attr('type') === 'submit') {
                            $(this).attr('value', data.config.translations[$(this).attr('data-text')])
                        }
                        else if ($(this).attr('type') === 'text') {
                            $(this).attr('placeholder', data.config.translations[$(this).attr('data-text')])
                        }
                    }
                    else {
                        $(this).html(data.config.translations[$(this).attr('data-text')]);
                    }
                });


                const titlesToTranslate = $('[data-title]');

                $.each(titlesToTranslate, function( index, value ) {
                    $(this).attr('title', data.config.translations[$(this).attr('data-title')])
                });

                setSongInfoSize();
            },
            error:function(data) {
                console.log(data);
            }
        });

        if (debug === 1) {
            console.log('submitted settings form');
        }
    });


    /**
     * reload artist artist on clear filter form
     */
    $(document).on("click", ".filterForm .input-reset", function(e) {
        const url = '/artist/filter/';
        $.get({
            url: url,
            cache: true,
            success: function(data) {
                $("#artists-nav").remove();
                $("nav.artists-navigation").append(data);
            }
        });
        $('.filterInput').focus();

        if (debug === 1) {
            console.log('cleared artist filter');
        }
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

        if (debug === 1) {
            console.log('clicked on empty palylist');
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

        if (debug === 1) {
            console.log('show songs list (forward)');
        }
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

        if (debug === 1) {
            console.log('show artists list');
        }
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

        if (debug === 1) {
            console.log('show songs list (backward)');
        }
    });


    /**
     * handle mobile menu
     */
    $(".hamburger").on("click", function(e) {

        $(".topbarNav, .topNav, .hamburger").toggleClass("is-active");
        mobileMenuState = mobileMenuState == 'closed' ? 'open' : 'closed';

        if (mobileMenuState === 'open') {
            setTimeout(function() {
                $(document).on( "click", "body", function(e) {
                    e.preventDefault();
                    if (e.target.id === 'form_keyword') {
                        return;
                    }
                    else if (e.target.className.indexOf('hamburger') !== -1 ) {
                        $(".topbarNav, .topNav, .hamburger").toggleClass("is-active");
                        mobileMenuState = mobileMenuState == 'closed' ? 'open' : 'closed';
                    }
                    if (!e.target.id.indexOf('Button') !== -1) {
                        $(".topbarNav, .topNav, .hamburger").toggleClass("is-active");
                        mobileMenuState = mobileMenuState == 'closed' ? 'open' : 'closed';
                    }
                    $(document).off( "click", "body");
                });
            }, 100);
        }

        if (debug === 1) {
            console.log('clicked on mobile menu');
        }
    });


    /**
     * check if we are scanning
     */
    if ($(location).attr('pathname') === '/settings/') {
        let loop;
        $.get({
            url: '/scan/progress',
            cache: true,
            success: function(data) {
                if (data.status == 'running') {
                    $("#scanButton").toggleClass('running');
                    loop = setInterval(scanTimer, 1000);
                }
            }
        });

        if (debug === 1) {
            console.log('check if we are scanning');
        }
    }


    /**
     * update library infos while we are scanning
     */
    function scanTimer() {
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

    function _init() {
        setSongInfoSize();
        setFilterInputSize();
    }

    function setSongInfoSize() {
        let width;
        if (screenWidth < 1024) {
            width = screenWidth - ($('.logo').outerWidth() + $('#playerContainer').outerWidth() + $('.hamburger').outerWidth() + 50);
        }
        else {
            width = screenWidth - ($('.logo').outerWidth() + $('#playerContainer').outerWidth() + $('.topbarNav').outerWidth() + 50);
        }
        $('.songInfo').width(width);
    }

    function setFilterInputSize() {
        let width;
        if (screenWidth < 1024) {
            var buttonWidth = $( "#searchButton" ).actual( 'outerWidth' );
            width = (screenWidth - buttonWidth );
        }
        $('.formElementContainer').width(width);
    }
});
