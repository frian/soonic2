$(function() {

    var screenWidth = $(window).width();
    var playerStatus = "paused";
    var statusClass = '';

    /**
     * Play / Pause currently loaded song
     */
    $(document).on("click", ".musicPlayer", function(e) {

        var player = document.getElementById("player");
        var src = document.getElementById("mpegSource");

        // if (!document.getElementById("mpegSource")) {
        //     player.append('<source id="mpegSource" src="" type="audio/mpeg"/>');
        //     return;
        // }

        if (!$(src).attr('src')) {
            console.log("No source");
            return;
        }
        else {

            if (playerStatus === "paused") {
                player.play();
                playerStatus = "playing";
                $(this).removeClass("icon-play");
                $(this).addClass("icon-pause");
            } else {
                player.pause();
                playerStatus = "paused";
                $(this).removeClass("icon-pause");
                $(this).addClass("icon-play");
            }
        }
    });


    /**
     * load and play a song from the songs list or the playlist
     */
    $(document).on("click", "#songs tbody tr, #playlist tbody tr", function(e) {
        $("tbody .playing").removeClass('playing');
        loadSong($(this));
        playerStatus = "playing";
        $(this).addClass('playing');

        $('#startStopButton').removeClass("icon-play");
        $('#startStopButton').addClass("icon-pause");

        if (screenWidth < 500) {
            // if ($(".songInfo").css('display') === 'none') {
            //     // $(".songInfo").css('display', 'inline-block');
            //     // -- adapt height to obove
            //     $(".playlist").css('height', ($(".playlist").height() - $(".songInfo").height()) + 'px');
            // }
            $(".songInfo").css('display', 'none');
        }

    });


    /**
     * Context menu
     */
    $(document).on("contextmenu", "#songs tbody tr, #playlist tbody tr", function(e) {

        e.preventDefault();

        // -- if we right-clic two times, remove class and listener
        $("#songslist tbody tr.playing").removeClass("playing");
        $(document).off( "click", "body");

        var currentItem = $(this);
        currentItem.addClass("playing");

        var contextMenu = '.songsContextMenu';
        var tableId = $(e.target).parent().parent().parent().attr('id');

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
                    var copy = currentItem.clone();
                    copy.removeClass("playing");
                    var icon = copy.find(".icon-plus");
                    $(icon).attr('class', 'icon-minus');
                    updatePlaylistInfo(copy);
                    $("#playlist tbody").append(copy);
                    currentItem.removeClass("playing");
                }
                else if (e.target.id === 'removeFromPlaylist') {
                    updatePlaylistInfo(currentItem, 'remove');
                    currentItem.remove();
                }

                $(contextMenu).css('display', 'none');
                $(document).off( "click", "body");
            });
        }, 100);
    });


    /**
     * play next song in songslist
     */
    $(document).on("click", ".icon-to-end", function(e) {
        playNext();
        playerStatus = "playing";
    });


    /**
     * play previous song in songslist
     */
    $(document).on("click", ".icon-to-start", function(e) {
        playNext('backward');
        playerStatus = "playing";
    });


    /**
     * show time elapsed
     */
    $("#player").on("timeupdate", function() {
        var player = document.getElementById("player");
        $("#currentTime").text(toDuration(player.currentTime) + ' /');
    });


    /**
     * on song end, play next song
     */
    $('#player').on('ended', function() {
       playNext();
    });


    /**
     * Add song to playlist
     */
    $(document).on("click", "#songs .add", function(e) {
        e.stopPropagation();
        // -- add song
        var copy = $(this).parent().clone();
        var icon = copy.find(".icon-plus");
        if ($(copy).hasClass('playing')) {
            $(copy).removeClass('playing');
        }
        $(icon).attr('class', 'icon-minus');
        $("#playlist tbody").append(copy);

        if ($("#playlist").height() + 20 > $("#playlistSection").height()) {
            $('.playlist-container').scrollTop($('.playlist-container').prop("scrollHeight"));
        }

        updatePlaylistInfo(copy);
    });


    /**
     * Remove song from playlist
     */
    $(document).on("click", "#playlist .add", function(e) {
        e.stopPropagation();
        updatePlaylistInfo($(this).parent(), 'remove');
        $(this).parent().remove();
    });

});


/**
 * load song
 */
function loadSong(song) {
    var path = song.data("path");

    var format = song.data("format");

    var values = song.find('td').map(function() {
        return $(this).text();
    }).get();

    var artist = values[2];
    var title = values[3];
    var duration = values[5];

    var mpegSource = document.getElementById("mpegSource");

    $(mpegSource).attr('src', path);

    var player = document.getElementById("player");

    player.load();

    $("#songTitle").text(title);
    $("#songArtist").text(' by ' + artist);
    $("#duration").text(duration);

    player.play();

    if ($("#startStopButton").attr("class") === 'icon-play') {
        $("#startStopButton").attr("class", "icon-pause");
    }

}

/**
 * play next song (forward or backward)
 */
function playNext(direction) {

    if ($("tbody .playing").length) {

        var current = $("tbody .playing");

        var next = null;

        if (!direction) {
            next = current.next('tr');
        }
        else {
            next = current.prev('tr');
        }

        if (next.length) {
            current.removeClass('playing');
            next.addClass('playing');
            loadSong(next);
        }
        else {
            $("#startStopButton").attr("class", "icon-play");
        }
    }
}

/**
 * 00:00:00, 00:00 to seconds
 */
function toSeconds(str)  {

    var arr = str.split(':').map(Number);

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

    var hours = parseInt(secs / 3600, 10),
        minutes = parseInt((secs / 60) % 60, 10),
        seconds = parseInt(secs % 3600 % 60, 10);

    var durationParts = [];
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

    var numFiles = document.getElementById("playlistNumFiles").textContent;

    var songDuration = $(item).data("duration");
    var playlistDuration = document.getElementById("playlistDuration").textContent;

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

    var fileInfoText = 'file';
    if (numFiles > 1) {
        fileInfoText = 'files';
    }

    document.getElementById("playlistFile").textContent = fileInfoText;
    document.getElementById("playlistNumFiles").textContent = numFiles;
    document.getElementById("playlistDuration").textContent = playlistDuration;

    var display = 'none';
    if (numFiles > 0) {
        display = 'initial';
    }
    document.getElementById("playlistInfos").style.display = display;
}
