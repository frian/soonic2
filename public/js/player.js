$(function() {
    'use strict';

    const debug = 1;

    const screenWidth = $(window).width();
    let playerStatus = "paused";
    let statusClass = '';

    /**
     * Play / Pause currently loaded song
     */
    $(document).on("click", "#playPauseButton", function(e) {
        playPause();
    });


    /**
     * load and play a song from the songs list or the playlist
     */
    $(document).on("click", "#songs tbody tr, #playlist tbody tr", function(e) {

        $("tbody .playing").removeClass('playing');
        loadSong($(this));
        playerStatus = "playing";
        $(this).addClass('playing');

        $('#playPauseButton').attr('class', 'icon-pause');;

        if (screenWidth < 500) {
            $(".songInfo").css('display', 'none');
        }
    });


    /**
     * Context menu
     */
    $(document).on("contextmenu", "#songs tbody tr, #playlist tbody tr", function(e) {

        e.preventDefault();

        const $currentItem = $(this);

        // -- if we right-clic two times, remove class and listener
        $("#songs tbody tr.selected, #playlist tbody tr.selected").removeClass("selected");
        $(document).off( "click", "body");

        $currentItem.addClass("selected");

        let contextMenu = '.songsContextMenu';
        const tableId = $(e.target).parent().parent().parent().attr('id');

        if (tableId === 'playlist') {
            contextMenu = '.playlistContextMenu';
        }

        $(contextMenu).css('display', 'block');
        $(contextMenu).css('top', e.pageY);
        $(contextMenu).css('left', e.pageX);

        setTimeout(function() {
            $(document).on( "click", "body", function(e) {
                e.preventDefault();

                if (e.target.id === 'addToPlaylist') {
                    const $copy = $currentItem.clone();
                    $copy.removeClass("selected");
                    const $icon = $copy.find(".icon-plus");
                    $icon.attr('class', 'icon-minus');
                    updatePlaylistInfo($copy);
                    $("#playlist tbody").append($copy);
                    $currentItem.removeClass("selected");
                }
                else if (e.target.id === 'removeFromPlaylist') {
                    updatePlaylistInfo($currentItem, 'remove');
                    $currentItem.remove();
                }

                $(contextMenu).css('display', 'none');
                $("#songs tbody tr.selected, #playlist tbody tr.selected").removeClass("selected");
                $(document).off( "click", "body");
            });
        }, 100);
    });


    /**
     * play next song in songslist
     */
    $(document).on("click", ".icon-to-end", function(e) {
        playNext();
        // playerStatus = "playing";
    });


    /**
     * play previous song in songslist
     */
    $(document).on("click", ".icon-to-start", function(e) {
        playNext('backward');
        // playerStatus = "playing";
    });


    /**
     * show time elapsed
     */
    $("#player").on("timeupdate", function() {
        const player = document.getElementById("player");
        $("#currentTime").text(toDuration(player.currentTime) + ' /');
    });


    /**
     * on song end, play next song
     */
    $('#player').on('ended', function() {

        if (debug === 1) {
            console.log('song ended');
            console.log("running playNext");
        }

        playNext();
    });


    /**
     * Add song to playlist
     */
    $(document).on("click", "#songs .add", function(e) {
        e.stopPropagation();
        // -- add song
        const $copy = $(this).parent().clone();
        const $icon = $copy.find(".icon-plus");
        if ($copy.hasClass('playing')) {
            $copy.removeClass('playing');
        }
        $icon.attr('class', 'icon-minus');
        $("#playlist tbody").append($copy);

        if ($("#playlist").height() + 20 > $("#playlistSection").height()) {
            $('.playlist-container').scrollTop($('.playlist-container').prop("scrollHeight"));
        }

        updatePlaylistInfo($copy);
    });


    /**
     * Remove song from playlist
     */
    $(document).on("click", "#playlist .add", function(e) {
        e.stopPropagation();
        updatePlaylistInfo($(this).parent(), 'remove');
        $(this).parent().remove();
    });



    /**
     * play next song (forward or backward)
     */
    function playNext(direction) {

        if (debug === 1) {
            console.log('- in playNext');
        }

        if ($("tbody .playing").length) {

            const current = $("tbody .playing");

            if (debug === 1) {
                console.log('current : ' + current);
            }

            let next = null;

            if (!direction) {
                next = current.next('tr');

                if (debug === 1) {
                    console.log('get next song');
                }
            }
            else {
                next = current.prev('tr');

                if (debug === 1) {
                    console.log('get prev song');
                }
            }

            if (next.length) {
                current.removeClass('playing');
                next.addClass('playing');
                loadSong(next);
            }
            else {
                if (debug === 1) {
                    console.log('NO next song');
                    console.log('playerStatus : ' + playerStatus);
                }

                $("#playPauseButton").attr('class', 'icon-play');
                playerStatus = "paused";
            }
        }
        else {
            if (debug === 1) {
                console.log('NO next song');
            }

            $("#playPauseButton").attr('class', 'icon-play');;
            playerStatus = "paused";

            if (debug === 1) {
                console.log('playerStatus : ' + playerStatus);
            }
        }
    }

    /**
     * Play / Pause currently loaded song
     */
    function playPause() {

        const player = document.getElementById("player");
        const src = document.getElementById("audioSource");
        const $this = $("#playPauseButton");

        if (!$(src).attr('src')) {
            console.log("No source");
            return;
        }

        if (playerStatus === "paused") {
            player.play();
            playerStatus = "playing";
            $this.attr('class', 'icon-pause');;
        } else {
            player.pause();
            playerStatus = "paused";
            $this.attr('class', 'icon-play');;
        }

        if (debug === 1) {
            console.log('clicked on Play / Pause');
            console.log("- playerStatus = " + playerStatus);
        }
    }

    /**
     * load song
     */
    function loadSong(song) {

        const path = song.data("path");
        const format = song.data("format");
        const values = song.find('td').map(function() {
            return $(this).text();
        }).get();
        const artist = values[2];
        const title = values[3];
        const duration = values[5];
        const audioSource = document.getElementById("audioSource");
        const player = document.getElementById("player");

        $(audioSource).attr('src', path);
        player.load();

        $("#songTitle").text(title);
        $("#songArtist").text(' by ' + artist);
        $("#duration").text(duration);

        player.play();

        if ($("#playPauseButton").attr("class") === 'icon-play') {
            $("#playPauseButton").attr("class", "icon-pause");
        }
    }

    /**
     * 00:00:00, 00:00 to seconds
     */
    function toSeconds(str)  {

        const arr = str.split(':').map(Number);

        if (arr.length === 1) {
            return (arr[0]);
        }
        else if (arr.length === 2) {
            return (arr[0] * 60) + arr[1];
        }

        return (arr[0] * 3600) + (arr[1] * 60) + arr[2];
    }

    /**
     * seconds to 00:00:00, 00:00
     */
    function toDuration(secs) {

        secs = Math.round(secs);

        const hours = parseInt(secs / 3600, 10),
            minutes = parseInt((secs / 60) % 60, 10),
            seconds = parseInt(secs % 3600 % 60, 10);

        const durationParts = [];

        if (hours != 0) {
            durationParts.push(hours);
            durationParts.push(minutes);
            durationParts.push(seconds);
        }
        else{
            durationParts.push(minutes);
            durationParts.push(seconds);
        }

        return durationParts.map(function (i) { return i.toString().length === 2 ? i : '0' + i; }).join(':');
    }

    /**
     * Update playlist num files and duration
     */
    function updatePlaylistInfo(item, action) {

        action = action || 'add';

        let numFiles = document.getElementById("playlistNumFiles").textContent;
        let songDuration = $(item).data("duration");
        let playlistDuration = document.getElementById("playlistDuration").textContent;

        playlistDuration = toSeconds(playlistDuration);
        songDuration = toSeconds(songDuration);

        if (action === 'add') {
            ++numFiles;
            playlistDuration = toDuration(playlistDuration + songDuration);
        }
        else {
            --numFiles;
            playlistDuration = toDuration(playlistDuration - songDuration);
        }

        let fileInfoText = 'file';
        if (numFiles > 1) {
            fileInfoText = 'files';
        }

        document.getElementById("playlistFile").textContent = fileInfoText;
        document.getElementById("playlistNumFiles").textContent = numFiles;
        document.getElementById("playlistDuration").textContent = playlistDuration;

        let display = 'none';
        if (numFiles > 0) {
            display = 'initial';
        }
        document.getElementById("playlistInfos").style.display = display;
    }
});
