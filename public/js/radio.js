$(function() {

    var playerStatus = "paused";

    /**
     * Play / Pause currently loaded radio
     */
    $(document).on("click", ".radioPlay", function(e) {

        // -- find currently active player and pause it
        var activePlayerButton = $("i.activePlayer")[0];

        var activePlayer = null;

        if (activePlayerButton) {
            activePlayer = $(activePlayerButton).next()[0];
            activePlayer.pause();
            $(activePlayerButton).removeClass("icon-pause");
            $(activePlayerButton).addClass("icon-play");
            $(activePlayerButton).removeClass("activePlayer");
            playerStatus = "paused";
            $(activePlayerButton).parent().parent().removeClass("activeRadio");
        }

        var radioPlayer = $(this).next()[0];

        if (radioPlayer == activePlayer) {
            playerStatus = "playing";
        }


        if (playerStatus === "paused") {
            radioPlayer.play();
            playerStatus = "playing";
            $(this).removeClass("icon-play");
            $(this).addClass("icon-pause");
            $(this).addClass("activePlayer");
            $(this).parent().parent().addClass("activeRadio");
        } else {
            radioPlayer.pause();
            playerStatus = "paused";
            $(this).removeClass("icon-pause");
            $(this).addClass("icon-play");
            $(this).removeClass("activePlayer");
            $(this).parent().parent().removeClass("activeRadio");
        }
    });
});
