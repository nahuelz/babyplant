const todayDate = moment().startOf('day');
const YM = todayDate.format('YYYY-MM');
const YESTERDAY = todayDate.clone().subtract(1, 'day').format('YYYY-MM-DD');
const TODAY = todayDate.format('YYYY-MM-DD');
const TOMORROW = todayDate.clone().add(1, 'day').format('YYYY-MM-DD');
var calendar = null;

var KTCalendarListView = function() {
    return {
        //main function to initiate the module
        init: function(data) {
            var calendarEl = document.getElementById('kt_calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'es',
                plugins: [ 'interaction', 'dayGrid', 'timeGrid', 'list' ],
                isRTL: KTUtil.isRTL(),
                header: {
                    left: 'prev,next todavy',
                    center: 'title',
                    allDayDefault: true,
                    right: 'dayGridMonth,dayGridWeek,dayGridDay,listWeek'
                },
                height: 800,
                contentHeight: 450,
                aspectRatio: 3,  // see: https://fullcalendar.io/docs/aspectRatio
                views: {
                    dayGridMonth: { buttonText: 'mes' },
                    dayGridWeek: { buttonText: 'semana' },
                    dayGridDay: { buttonText: 'dia' },
                    listWeek: { buttonText: 'lista' }
                },
                defaultView: 'timeGridWeek',
                defaultDate: TODAY,
                editable: true,
                eventLimit: true, // allow "more" link when too many events
                navLinks: true,
                //events: data.data,
                //events: __HOMEPAGE_PATH__ + "planificacion/index_table/",
                eventSources: [
                    {
                        url: __HOMEPAGE_PATH__ + "planificacion/index_table/",
                        method: 'POST',
                        extraParams: {
                            custom_param1: 'something',
                            custom_param2: 'somethingelse'
                        },
                        failure: function() {
                            alert('there was an error while fetching events!');
                        },
                    }
                ],
                eventRender: function(info) {
                    var element = $(info.el);
                    if (info.event.extendedProps && info.event.extendedProps.description) {
                        if (element.hasClass('fc-day-grid-event')) {
                            element.data('content', info.event.extendedProps.description);
                            element.data('placement', 'top');
                            KTApp.initPopover(element);
                        } else if (element.hasClass('fc-time-grid-event')) {
                            element.find('.fc-title').append('<div class="fc-description">' + info.event.extendedProps.description + '</div>');
                        } else if (element.find('.fc-list-item-title').lenght !== 0) {
                            element.find('.fc-list-item-title').append('<div class="fc-description">' + info.event.extendedProps.description + '</div>');
                        }
                    }
                },
                eventDrop: function(info) {
                    if (!confirm(info.event.title + " was dropped on " + info.event.start.toISOString())) {
                        info.revert();
                    }
                }
            });

            calendar.render();
        }
    };
}();

var elem = document.getElementById('fScreen');
var isFull = false;

function mkFull() {
    //fScreen DIV
    var elem = document.getElementById("fScreen");

    if (elem.requestFullscreen) {
        elem.requestFullscreen();
    } else if (elem.msRequestFullscreen) {
        elem.msRequestFullscreen();
    } else if (elem.mozRequestFullScreen) {
        elem.mozRequestFullScreen();
    } else if (elem.webkitRequestFullscreen) {
        elem.webkitRequestFullscreen();
    }
}

document.addEventListener('webkitfullscreenchange', function(e) {
    isFull = !isFull;

    if (isFull) {
        elem.style = 'width: 100%; height: 85%; background: green;';
    } else {
        elem.style = 'width: 100px; height: 500px; background: red;';
    }
});

jQuery(document).ready(function() {
    KTCalendarListView.init();
    $('.fc-time-grid-container').hide();
    $('.fc-divider').hide();
    document.getElementById('btn-fscreen').addEventListener('click', mkFull);
    $('.fc-dayGridWeek-button').click();
});

/*
function initCalendar() {
    $.ajax({
        type: "POST",
        dataType: "json",
        url: __HOMEPAGE_PATH__ + "planificacion/index_table/",
        success: function(data) {
            if (!jQuery.isEmptyObject(data)) {
                KTCalendarListView.init(data);
                $('.fc-time-grid-container').hide();
                $('.fc-divider').hide();
                document.getElementById('btn-fscreen').addEventListener('click', mkFull);
                $('.fc-dayGridWeek-button').click();
                return true;
            }
        },
        error: function() {
            alert('Error occured')
        }
    });
    return false;

}
 */