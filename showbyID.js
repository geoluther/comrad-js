
// todos: add playlist for show from its ID tsonic 3.24: "136048"
// get show instance plist -  tsonic 3.24: "136048"
// $.get('https://' + ajax_host + '/playlist/ajax/getfullplaylistforshowinstance.php', {
//          showid: showId
//      }, function(results) {


function buildShowElement() {
    const template = `<div class='show'>
    <h4 class='show-title'>Terrasonic</h4>
    <h5>Show Host</h5>
    <p>Show Description</p>
    <div><p>player element</p></div>
    <div><p>playlist element</p></div>
    </div>`
    return template;
}

// show range: (what is start / end date format??)
// $.get('//' + ajax_host + '/playlist/ajax/geteventsbetween.php', {
//      start: start,
//      end: end,

// use jsonp
function getRecentShows() {
    const showID = 136048;
    const url="https://kgnu.org/playlist/ajax/v2/getfullplaylistforshowinstance_v2.php?callback=?";
    const jqxhr = $.getJSON(url, { showid: showID } )
    .done(function(data) {
        $('#loader').remove();
        $('#show-info').append('<p><em>Found show: ' + showID + '</em></p>');
        console.log(data);
        // buildShows(data);
    })
    .fail(function() {
        console.log( "error" );
    })
}


function formatDate(showDate, startTime) {
    const re = /am|pm/gi;
    const time = startTime.replace(re, ' $&').toUpperCase();
    const adate = `${showDate} ${time}`;
    return new Date(adate);
}


function sortShows(shows) {
    // sort shows by date.
    shows.sort(function(a, b) {
        const showA = formatDate(a.date, a.startTime);
        const showB = formatDate(b.date, b.startTime);

        if (showA > showB) {
            return -1;
        }

        if (showA < showB) {
            return 1;
        }
        // date is equal
        return 0;
    });
}


function buildShows(data) {
    $('#loader').remove();
    sortShows(data);
    console.log(data);
    // builder player elements, show info text
    // add "show more recent shows", et
    for (let item of data) {
        let show = `<li> ${item.showName} - ${item.host} </li>`;
        // console.log(item.showName, "with", item.host)
        $('#show-title').append(show);
    }
    var template = buildShowElement();
    $('#template').append(template);
}


$(document).ready( function() {
    console.log('ready freddy');
    getRecentShows();
});