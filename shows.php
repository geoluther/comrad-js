<?php
$show_name = '';
if (isset($_GET['name'])) {
    $_GET['name'] = strtolower($_GET['name']);

// Restrict show name to lowercase letters and numbers
    if (preg_match('/^[a-z0-9]+$/', $_GET['name'])) $show_name = $_GET['name'];
}

$javascriptPlayers['audiojs'] = 'audiojs/audio.min.js';
$javascriptPlayers['audioplayer'] = 'AudioPlayer/js/audioplayer.js';



//determine the user agent
if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), 'ANDROID') !== FALSE) {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'CHROME') !== FALSE) {
        $deviceType = 'ANDROID|CHROME';
    } else if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), 'FIREFOX') !== FALSE) {
        $deviceType = 'ANDROID|FIREFOX';
    } else {
        $deviceType = 'ANDROID|AOSP';
    }
    $audioPlayer = $javascriptPlayers['audiojs'];
} else {
    $deviceType = 'NONANDROID';
    $audioPlayer = $javascriptPlayers['audioplayer'];
    if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), 'CHROME') !== FALSE) {
        $deviceType = 'NONANDROID|CHROME';
        $audioPlayer = $javascriptPlayers['audiojs'];
    } else if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), 'FIREFOX') !== FALSE) {
        $deviceType = 'NONANDROID|FIREFOX';
        $audioPlayer = $javascriptPlayers['audiojs'];
    } else if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), 'MSIE') !== FALSE) {
        $deviceType = 'NONANDROID|MSIE';
    } else if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), 'SAFARI') !== FALSE) {
        $deviceType = 'NONANDROID|SAFARI';
    }
}

?>

<?php include("header.php"); ?>

<script type="text/javascript" src="<?php echo '/js/' . $audioPlayer; ?>"></script>

<?php if ($audioPlayer == $javascriptPlayers['audioplayer']) { ?>
<link rel="stylesheet" href="/js/AudioPlayer/css/audioplayer.css" />
<?php } ?>

<script type="text/javascript">
    /* <![CDATA[ */

    <?php
//sw 6/6/11 - if the "name" or "id" GET parameters are set,
//we'll show the name of the show
//otherwise, we'll show "Recent Shows" as the title
//the JavaScript variable isSpecificShow will be used to keep track of this
    if((isset($show_name) && !empty($show_name)) || isset($_GET["id"])) {
        echo "var isSpecificShow = true;\n";
        if((isset($show_name) && !empty($show_name))) {
            if(strcmp($show_name, 'invalidshow') == 0) {
                echo "\tvar isShowValid = false;\n";
            } else {
                echo "\tvar isShowValid = true;\n";
            }
        } elseif(isset($_GET["id"])) {
            echo "\tvar isShowValid = true;\n";
            echo "\tvar showId = " . $_GET['id'] . ";\n";
        }
    } else {
        echo "var isSpecificShow = false;\n";
        echo "\tvar isShowValid = true;\n";
    }

    if (isset($_GET['day']) && isset($_GET['month']) && isset($_GET['year'])) {
        echo "\tvar isSpecificDate = true;\n";
        echo "\tvar day = " . $_GET['day'] . ";\n";
        echo "\tvar month = " . $_GET['month'] . ";\n";
        echo "\tvar year = " . $_GET['year'] . ";\n";
    } else {
        echo "\tvar isSpecificDate = false;\n";
    }

#echo "\tvar ajax_host = \"localhost\"";
    echo "\tvar ajax_host = \"kgnu.org\"";
    ?>

//preload the "Hide" images so that they can display immediately when JavaScript switches
//the images to display them
var img1 = new Image();
img1.src = "btns/2/HidePlaylist.gif";
var img2 = new Image();
img2.src = "btns/2/HideDetail.gif";

var dayNames = new Array("Sunday", "Monday", "Tuesday",
    "Wednesday", "Thursday", "Friday", "Saturday");


var trackListing;
var alternatingRow;
function populatePlaylist(showId, divId, aId, type)
{

    $.get('https://' + ajax_host + '/playlist/ajax/getfullplaylistforshowinstance.php', {
        showid: showId
    }, function(results) {

        if (!results) {
            return;
        }

        results.sort(function(a, b) {
            return a.Attributes.Executed - b.Attributes.Executed;
        });

        $('#'+ divId).empty();

        var playlistTable = $('<table cellpadding="2" cellspacing="0" style="border-style:collapse" class="playlist"></table>');
        playlistTable.append('<tr class="head"><td>Artist</td><td>CD</td><td>Track</td></tr>');

// trackListing = "<table cellpadding='2' cellspacing='0' style='border-style:collapse' class='playlist'>";
// if (type == "Playlist") {
// 	trackListing += "<tr class='head'><td>Artist</td><td>CD</td><td>Track</td></tr>";
// }
// alternatingRow = false;

var trackCounter = 0; // To alternate track row shading

$.each(results, function(index, value) {
    if (value.Type) {
        var playlistType = value.Type;

        switch (playlistType) {
            case 'TrackPlay':
            if (value.Attributes.Track && value.Attributes.Track.Type && value.Attributes.Track.Type == "Track" && value.Attributes.Track.Attributes) {
                var track = value.Attributes.Track.Attributes.Title;

                var artist;
                if (value.Attributes.Track.Attributes.Album.Attributes.Artist) {
                    artist = value.Attributes.Track.Attributes.Album.Attributes.Artist;
                } else if (value.Attributes.Track.Attributes.Artist) {
                    artist = value.Attributes.Track.Attributes.Artist;
                }
                var cd;
                if (value.Attributes.Track.Attributes.Album.Attributes.Title) {
                    cd = value.Attributes.Track.Attributes.Album.Attributes.Title;
                }

                playlistTable.append('<tr' + ((trackCounter + 1) % 2 == 0 ? ' class="alternatingRow"' : '') + '><td>' + (!artist?"": artist) + '</td><td>' + (!cd?"": cd) + '</td><td>' + track + '</td></tr>');
                trackCounter++;
            }
            break;

            case 'VoiceBreak':
            playlistTable.append('<tr><td colspan="3" class="voicebreak">Voice Break</td></tr>');
            break;

            case 'DJComment':
//playlistTable.append('<tr><td colspan="3" class="comment">Comment:</td></tr>');
playlistTable.append('<tr><td colspan="3" class="comment-body">' + value.Attributes.Body + '</td></tr>')
break;

case 'ScheduledFeatureInstance':
playlistTable.append('<tr><td colspan="3" class="feature">' + value.Attributes.ScheduledEvent.Attributes.Event.Attributes.Title + '</td></tr>');
break;

case 'ScheduledUnderwritingInstance':
playlistTable.append('<tr><td colspan="3" class="underwriting">Underwriting: ' + value.Attributes.ScheduledEvent.Attributes.Event.Attributes.Title + '</td></tr>');
break;

case 'FloatingShowEvent':
switch (value.Attributes.Event.Type) {
    case 'PSAEvent':
    playlistTable.append('<tr><td colspan="3" class="psa">PSA: ' + value.Attributes.Event.Attributes.Title + '</td></tr>');
    break;
}
break;
}
}
});

// trackListing += "</table>";
$('#' + divId + " .loading").remove();
$('#'+ divId).append(playlistTable);

//setup the playlist to automatically refresh every three minutes
setTimeout('populatePlaylist("' + showId + '","' + divId + '","' + aId + '","' + type + '")',1000 * 60 * 3);
}, 'jsonp');

//setNewClickAction(divId, aId);

// $('#'+ divId).html(results);
// $('#'+ divId).show();
}

function setLoading(isLoading) {
    if(isLoading) {
        $(".showInstanceList").after('<div class="loadingImage"><img src="/graphics/ajax-loader.gif" alt="Loading" /></div>');
    } else {
//remove the loading image
$(".loadingImage").remove();
}
}

function showError(str) {
    setLoading(false);
    $(".priorShows").remove();
    list = $("#shows .showInstanceList");
    list.append("<center><h2>" + str + "</h2></center>");
}


function populateShows(start, end)
{

    $.get('//' + ajax_host + '/playlist/ajax/geteventsbetween.php', {
        start: start,
        end: end,

        eventparameters: $.toJSON({
            'Source': 'KGNU'
            <?php if (isset($show_name) && !empty($show_name)): ?>
            , 'URL': '<?php echo $show_name; ?>'
            <?php endif; ?>
        }),
        types: $.toJSON([ 'Show' ])
    }, function(results) {

        setLoading(false);

        if (!results) {
            return;
        }

        if(isSpecificDate) {
            $(".priorShows").remove();
        }

        results.sort(function(a, b) {
            return b.Attributes.StartDateTime - a.Attributes.StartDateTime;
        });

        var lastDate;
        var dateContainer;
        var list;

        $.each(results, function(index, value) {

//skip the first show if we're lazy loading more shows, since that show will already
//be displayed
//if (loadingMoreShows && index == 0) {
//	return;
//}

var longTime = value.Attributes.StartDateTime;
var startTime = new Date(value.Attributes.StartDateTime * 1000);
var duration = value.Attributes.Duration;
var endTime = new Date(startTime.getTime() + duration * 60 * 1000);
var startDay = startTime.getDay();
var startDate = startTime.getDate();
var startMonth = startTime.getMonth();
startMonth++;
var startYear = startTime.getFullYear();
var startHour = startTime.getHours();
if (startHour == 0) {
    var amPm = "AM";
    startHour = "12";
} else if (startHour == 12) {
    var amPm = "PM";
} else if (startHour > 12) {
    var amPm = "PM";
    startHour = startHour - 12;
} else {
    var amPm = "AM";
}
var startMinutes = startTime.getMinutes();
if (startMinutes < 10) {
    startMinutes = "0" + startMinutes.toString();
}

/**
* Get the Show Title
*/
var title;
if (value.Attributes.ScheduledEvent.Attributes.Event.Attributes.Title) {
    <?php if ($show_name == 'newsspecials'): ?>
    title = "News Specials";
    <?php else: ?>
    title = value.Attributes.ScheduledEvent.Attributes.Event.Attributes.Title;
    <?php endif; ?>
//set the show title in the header sw 5/30/11
if (isSpecificShow) {
    if (isSpecificDate) {
        $("#pageTitle").html(title + " on " + startMonth + '/' + startDate + '/' + startYear);
    } else {
        $("#pageTitle").html(title);
    }
}
}

list = $("#shows .showInstanceList");

/**
* Get the Show Short Description
*/
var shortDescription = '';
if (value.Attributes.ShortDescription) {
    shortDescription = value.Attributes.ShortDescription;
}
if (!shortDescription && value.Attributes.ScheduledEvent.Attributes.Event.Attributes.ShortDescription) {
    shortDescription = value.Attributes.ScheduledEvent.Attributes.Event.Attributes.ShortDescription;
}


/**
* Get the Show Long Description
*/
var longDescription = value.Attributes.LongDescription ? value.Attributes.LongDescription : '';

// Don't show long description unless it is different from the series' long description
if (longDescription == value.Attributes.ScheduledEvent.Attributes.Event.Attributes.LongDescription) longDescription = '';


/**
* Get the Show ID
*/
var playlist = "";
var playlistSpanId = "";
var playlistDivId = "";
var playlistAId;
var showId;
if (value.Attributes.Id) {
    showId = value.Attributes.Id;
    playlistSpanId = longTime + "_PlaylistSpanId";
    playlistDivId = longTime + "_PlaylistDivId";
    playlistAId = longTime + "_PlaylistAId";
//determine the button type to show
var buttonType;
switch (value.Attributes.ScheduledEvent.Attributes.Event.Attributes.Category) {
    case "Music":
    buttonType = "Playlist";
    break;
    case "NewsPA":
    buttonType="Detail";
    break;
    default:
    buttonType="Playlist";
    break;
}
playlist = "<span id=\"" + playlistSpanId + "\" title=\"View Playlist\">" +
"<a id=\"" + playlistAId + "\" showId=\"" + showId + "\" playListDivId=\"" + playlistDivId + "\" playlistAId=\"" + playlistAId + "\" type=\"" + buttonType + "\" href=\"#\">" +
"<img class=\"block-img\" src=\"btns/2/Show" + buttonType + ".gif\" />" +
"</a>" +
"</span>";
//playlist = $('<span id=\"" + divId + "\" title=\"View Playlist\"><a href=\"javascript:;\">Playlist</a></span>').click(function() {
//	populatePlaylist('" + showId + "', '" + playlistDiv + "');
//});
}

/**
* Get the Host ID
*/
var hostId;
if (value.Attributes.HostId) {
    hostId = value.Attributes.HostId;
}
if (!hostId && value.Attributes.Host && value.Attributes.Host.Attributes.UID) {
    hostId = value.Attributes.Host.Attributes.UID;
}
if (!hostId && value.Attributes.ScheduledEvent.Attributes.Event.Attributes.HostId) {
    hostId = value.Attributes.ScheduledEvent.Attributes.Event.Attributes.HostId;
}

/**
* Get the Host Name
*/
var hostName;
if (value.Attributes.Host && value.Attributes.Host.Attributes.Name) {
    hostName = value.Attributes.Host.Attributes.Name;
}
if (!hostName && value.Attributes.ScheduledEvent.Attributes.Event.Attributes.Host) {
    hostName = value.Attributes.ScheduledEvent.Attributes.Event.Attributes.Host.Attributes.Name;
}

/**
*
*/
var hostURL;
if (hostId && hostName) {
hostURL = //"<a href=\"hosts.php?id=" + hostId + //sw temporarily removed host link 6/10/11
//"\" title=\"Click for all " + hostName + "'s shows\">" +
hostName
//+ "</a>"
;
}

if (hostURL) {
    hostName = hostURL;
}

/**
* Get the Show Recorded Audio
*/
var eventHasRecordedAudio;
if (value.Attributes.ScheduledEvent.Attributes.Event.Attributes.RecordAudio) {
    eventHasRecordedAudio = value.Attributes.ScheduledEvent.Attributes.Event.Attributes.RecordAudio;
}

/**
* Determine whether to show the audio - wait two hours after show end before showing the link
*/
if (endTime.getTime() < (<?php echo time() * 1000; ?> - (2 * 60 * 60 * 1000))) {
    var showAudio = true;
} else {
    var showAudio = false;
}

var player = "";
var eventRecordedAudioURL;
if (value.Attributes.RecordedFileName) {
    eventRecordedAudioURL = value.Attributes.RecordedFileName ;
    player = $('<div class="showDetail" />');
}


// if(!shortDescription && !longDescription && !player) {
// 	return;
// }
divTitle = (!title || (isSpecificShow && !isSpecificDate) ? "": title + ", " ) +
dayNames[startDay] + ', ' + startMonth + '/' + startDate + '/' + startYear +
(isSpecificShow ? "" : " " + startHour + ":" + startMinutes + " " + amPm)

permaURI = value.Attributes.ScheduledEvent.Attributes.Event.Attributes.URL;
// replace URL with permlink if show shortcode is present (i.e.,
// anything not starting with http://)  -- CPH

if(!permaURI) {
    permaURI = '/special/' + value.Attributes.Id;
    permaTitle = '<a class="permalink" href="' + permaURI + '">' +
    divTitle + '</a>';
} else if(permaURI.match(/^http:\/\//)) {
    permaTitle = '<a class="permalink" href="' + permaURI + '">' +
    divTitle + '</a>';
    permaURI = null;
} else {
    permaURI = '/' +
    value.Attributes.ScheduledEvent.Attributes.Event.Attributes.URL +
    '/' + startMonth + '/' + startDate + '/' + startYear;
    permaTitle = '<a class="permalink" href="' + permaURI + '">' +
    divTitle + '</a>';
}

if(permaURI) {
    permalink = '<a class="permalink" href="' + permaURI +
    '"><img src="btns/2/permalink_icon.gif">&nbsp;Permalink</a>';
}

console.log('start time: ' + value.Attributes.StartDateTime);
console.log('current time:' + new Date().getTime());

//10/7/16 - always load from aa.kgnu.net so we can cut out PEER1
//if (value.Attributes.StartDateTime * 1000 + (1000 * 60 * 60 * 24 * 7) >= new Date().getTime()) { //is archive less than a week old?
//var archiveLocation = "http://www.kgnu.org/audioarchives/";
//downloadAudio = '<div class="topButton" style="float: left;">' +
//'<a href="http://www.kgnu.org/forcedownload.php?mp3file=' + value.Attributes.RecordedFileName + '">' +
//'<img src="btns/2/save.gif"> Download MP3</a></div>';
//} else {
    var archiveLocation = "http://aa.kgnu.net/audioarchives/";
    downloadAudio = '<div class="topButton" style="float: left;">' +
    '<a href="http://aa.kgnu.net/audioarchives/' + value.Attributes.RecordedFileName + '?response-content-disposition=attachment&x-kgnu=kgnu-org-shows-download">' +
    '<img src="btns/2/save.gif"> Download MP3</a></div>';
//}

streamAudio = '<div class="topButton" style="float: left;">' +
"<a href=\"/recordedstream.m3u?mp3file=" +
archiveLocation + value.Attributes.RecordedFileName + "%3Fx-kgnu%3Dkgnu-org-shows-stream\" target=\"_blank\">" + '<img src="btns/2/sound.gif"> Stream MP3' + "</a></div>";


// <div style="float: right; width:200px;">.</div>

list.append(
    $('<li class="showInstance"></li>').append(
        '<div class="showTitleContainer">' +
        '<div class="showTitle">' + permaTitle + '</div>' + (permaURI ?
            '<div class="permaTitle">' + permalink + '</div>' : '') +
        '</div>' +
        '<div class="spacer">&nbsp;</div>' +
        (!hostName ? "": '<div class="showDetail" style="clear: both;">Host: ' + hostName + '</div>') +
        (!shortDescription ? '' : '<div class="showDetail shortDescription">' + shortDescription + '</div>') +
        (!longDescription ? '' : '<div class="showDetail longDescription">' + longDescription + '</div>') +
        (value.Attributes.RecordedFileName && showAudio ?
            '<div class="showButtonTopMenu">' + streamAudio +
            '<div class="topButtonSpacer"></div>' + downloadAudio + '</div>'
            : '')
).append(player).append(/*
(permaURI ?
'<div class="showButton showButtonFloat">' + permalink + '</div>' : ''
) +
(value.Attributes.RecordedFileName ?
'<div class="showButton showButtonFloat">' + streamAudio + '</div>' +
'<div class="showButton showButtonFloat">' + downloadAudio +
'</div>' : '') + */
'<div class="showButton playlistContainer">' + playlist + '</div>' +
'<div id="' + playlistDivId + '" class="showDetail">' + '</div>'
) //sw 6/5/11 changed
);

// Initialize the player
if(eventRecordedAudioURL !== undefined && showAudio) {
    <?php if ($audioPlayer == $javascriptPlayers['audioplayer']) { ?>
        player.append('<p><div id="audioPlayer"><audio preload="none"" controls><source src="' + archiveLocation + eventRecordedAudioURL + '" ></audio></div></p>');
        <?php } else if ($audioPlayer == $javascriptPlayers['audiojs']) { ?>
            player.append('<p><audio src="' + archiveLocation + eventRecordedAudioURL + '?x-kgnu=kgnu-org-shows-player" type="audio/mpeg" preload="none"" /></p>');
            <?php } ?>


        }


// Add a click handler to the anchor tag inside of the content div
if (playlistAId) {
    $('#' + playlistAId).click(function(eventObject) {
eventObject.preventDefault(); //prevent the default click action

//if the playlist has not been populated yet, do so
if (!$(this).is("[playlistPopulated]")) {
//add a loading notification
if ($(this).attr("type") == "Playlist") {
    $("#" + $(this).attr("playListDivId")).append('<div class="loading">Loading Playlist...</div>');
} else {
    $("#" + $(this).attr("playListDivId")).append('<div class="loading">Loading Details...</div>');
}
$('#'+ $(this).attr("playListDivId")).show();
populatePlaylist($(this).attr("showId"), $(this).attr("playListDivId"), $(this).attr("playlistAId"), $(this).attr("type"));
$(this).attr("playlistPopulated","1");
} else {

//show/hide the div
$("#" + $(this).attr("playListDivId")).toggle();

}

//change the image from "show" to "hide"
//sw 6/5/11 - changed these from using "==" to find the image name to using indexOf, since
//IE sets the SRC property to a full URL
if ($(this).find("img").attr("src").indexOf("btns/2/ShowPlaylist.gif") != -1) {
    $(this).find("img").attr("src","btns/2/HidePlaylist.gif");
} else if ($(this).find("img").attr("src").indexOf("btns/2/HidePlaylist.gif") != -1) {
    $(this).find("img").attr("src","btns/2/ShowPlaylist.gif");
} else if ($(this).find("img").attr("src").indexOf("btns/2/ShowDetail.gif") != -1) {
    $(this).find("img").attr("src","btns/2/HideDetail.gif");
} else if ($(this).find("img").attr("src").indexOf("btns/2/HideDetail.gif") != -1) {
    $(this).find("img").attr("src","btns/2/ShowDetail.gif");
}
});
}
});
loadingMoreShows = false;

<?php if ($audioPlayer == $javascriptPlayers['audioplayer']) { ?>
    $( 'audio' ).audioPlayer();
    <?php } else if ($audioPlayer == $javascriptPlayers['audiojs']) { ?>
        audiojs.createAll();
        <?php } ?>

    }, 'jsonp');
}

function start() {

    if(!isShowValid) {
        showError("Show does not exist.");
        return;
    }

//sw 5/30/11 - if the shows is for all shows, show "Recent Shows" in the header
if (!isSpecificShow) {
    $("#pageTitle").html("Recent Shows");
}

// Make an AJAX call to get recent shows from the server

var now = new Date().getTime();

/*
<?php if (array_key_exists('d', $_GET) && preg_match('/^[0-9]+$/', $_GET['d'])): ?>
var end = new Date(<?php echo $_GET['d'] ?>);
<?php else: ?>
var end = new Date(now);
<?php endif; ?>
*/

// var end = new Date(<?php echo (array_key_exists('d', $_GET) && preg_match('/[0-9]+/', $_GET['d']) ? $_GET['d'] * 1000 : '') ?>);
var end = new Date(now);

var cutoff = new Date(2011, 4, 31).getTime() // Don't show shows before May 31st, 2011


// Don't allow dates before the cutoff
if (end.getTime() < cutoff) {
// $('#shows .nav.later').hide();
end.setTime(cutoff);
} else {
// var href = '<?php echo $_SERVER['REQUEST_URI'] ?>?d=' + start.getTime();
// $('#shows .nav.later a').attr('href', href);
// $('#shows .nav.later').show();
}

// Don't allow dates in the future
if (end.getTime() >= now) {
// $('#shows .nav.earlier').hide();
end.setTime(now);
} else {
// var href = '<?php echo $_SERVER['REQUEST_URI'] ?>';
// $('#shows .nav.earlier a').attr('href', href).show();
// $('#shows .nav.earlier').show();
}


<?php if ($show_name != 'newsspecials'): ?>
// Start seven days before the end date or at the cutoff
var numDays = (isSpecificShow ? 30 : 3);
<?php else: ?>
// One year for news specials
var numDays = 365;
<?php endif; ?>


var start = new Date(Math.max(end.getTime() - numDays * 24 * 60 * 60 * 1000, cutoff));
showsStartDate = start;

if(isSpecificDate) {
// According to docs (and results), the JavaScript Date object month
// is 0-indexed, while day and year are as would be expected.
// Therefor we have to substract one from the month.
start = new Date(year, month-1, day);
end = new Date(year, month-1, day, 23, 59, 59);
}

populateShows(start.format("yyyy-mm-dd HH:MM:ss"), end.format("yyyy-mm-dd HH:MM:ss"));

//sw 6/20/11 -
if(!isSpecificDate) {
    $(window).scroll(function() {
        if ($(window).scrollTop() + $(window).height() >= $('.showInstanceList .showInstance:last-child').offset().top) {
            if (!loadingMoreShows) {
                loadingMoreShows = true;
                setLoading(true);
                //$(".showInstanceList").after('<div class="loadingImage"><img src="/graphics/ajax-loader.gif" alt="Loading" /></div>');
                var end = showsStartDate;
                var start;
                if (isSpecificShow) {
                //If it's a specific show, get one week's worth of shows.
                    start = new Date(Math.max(end.getTime() - 1080 * 60 * 60 * 1000, cutoff));
                }
                else {
                    //If it's not a specific show (Recent Shows view), get 12 hours more.
                    start = new Date(Math.max(end.getTime() - 12 * 60 * 60 * 1000, cutoff));
                }
                showsStartDate = start;
                populateShows(start.format("yyyy-mm-dd HH:MM:ss"), end.format("yyyy-mm-dd HH:MM:ss"));
            }
        }
    });
}

};


var loadingMoreShows = false;
var showsStartDate;

start();
/* ]]> */
</script>

<div id="shows">
    <div class="nav earlier" style="display: none">
        <a>View earlier shows</a>
    </div>

    <?php if (isset($_GET['name']) && $_GET['name'] == 'bluegrass'): ?>
        <div class="headerText">
            For Buck's Bluegrass Hotline, KGNU's calendar of bluegrass concerts in Colorado, go <a href="/bluegrasscalendar">here</a>.<br><br>
            Archives of RockyGrass 2012 can be found <a href="/rockygrass">here</a>.<br><br>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['name']) && $_GET['name'] == 'pasalavoz'): ?>
        <div class="headerText">
            For show descriptions and information about Pasa La Voz, visit their <a href="http://pasalavoz.us/" target="_blank">website</a>.<br>
            <br>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['name']) && $_GET['name'] == 'chinese'): ?>
        <div class="headerText">
            Please visit Colorado Chinese Radio's website <a href="http://cacenetwork.wordpress.com/chinese-radio/" target="_blank">here</a>.<br><br>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['name']) && $_GET['name'] == 'outsources'): ?>
        <div class="headerText">
            Visit Outsources' <a href="http://news.kgnu.org/category/outsources" target="_blank">blog</a>
            on <a href="http://news.kgnu.org" target="_blank">News.KGNU.org</a> for expanded coverage, podcasts (<a href="http://news.kgnu.org/category/outsources/feed" target="_blank">rss link</a>), and more.<br><br>
        </div>
    <?php endif; ?>


    <div class="loadingImage"><img src="/graphics/ajax-loader.gif" alt="Loading" /></div>
    <ul class="showInstanceList">
    </ul>
    <div class="nav later" style="display: none">
        <a>View later shows</a>
    </div>
</div>

<?php include("footer.php"); ?>
