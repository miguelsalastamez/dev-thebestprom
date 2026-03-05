       jQuery(document).ready(function($) {
           console.log('Initializing calendar!');

           $('.ect-custom-calendar').each(function() {
               var $this = $(this);
               var evt_cal_id = $(this).attr('data-calendar-id');
               const thisCalendar = 'ect_calendar-' + evt_cal_id;
               const wrapperClass = '#ect-calendar-wrapper[data-calendar-id="' + evt_cal_id + '"]';
               const El_category = 'ect-calendar-cat-filter-wrapper';
               const popupContainer = 'ect-calendar-popup-' + evt_cal_id;
               const language = $(wrapperClass).attr('data-current-lang')
               const date_Format = $(wrapperClass).attr('data-date-format');
               const event_limit = $(wrapperClass).attr('data-events-limit');
               var featured_text_color = $(wrapperClass).attr('data-featured-textcolor');
               var featured_bg_color = $(wrapperClass).attr('data-featured-bgcolor');
               var nonfeatured_text_color = $(wrapperClass).attr('data-alt-skin-color');
               var nonfeatured_bg_color = $(wrapperClass).attr('data-skin-color');
               const readMoreText = $(wrapperClass).attr('data-readmore-text');
               var allCategories = [];
               var currentRequest = false;
               var ajaxRequest;
               var calendarEl = document.getElementById(thisCalendar);
               var all_events = [];
               const defaultSettings = {
                   initialView: 'dayGridMonth',
                   locale: language,
                   events: all_events,
                   headerToolbar: {
                       start: 'prev,today,next',
                       center: 'title',
                       end: 'dayGridMonth,timeGridWeek,timeGridDay'
                   },
               };
               // initializing FullCalendar with some default settings
               var calendar = new FullCalendar.Calendar(calendarEl, defaultSettings);

               /**
                * Fetch events for calendar
                */
               function render_new_month() {
                var current_page_lang = document.documentElement.lang;
                moment.locale(current_page_lang);
                   allCategories = [];
                   $(wrapperClass + ' .' + El_category).html('');
                   var dateStart = moment(calendar.view.activeStart).subtract(30, 'days').format("YYYY-MM-DD");
                   let evt_bgColor, evt_textColor;
                   let current_events = calendar.getEvents();
                   if (current_events.length > 0) {
                       current_events.forEach(function(deEvent, i) {
                           deEvent.remove();
                       })
                   }

                   calendar.render();
                   $('.fc-button-group button').each(function() {
                       let $this = jQuery(this);
                       let isEnglish = (language.indexOf('en-') != -1);

                       if (isEnglish && $this.text() != "") {
                           $this.css('text-transform', 'capitalize');
                       }
                   });
                   all_events = [];
                   let api_url = wpApiSettings.root + 'tribe/events/v1/events?start_date=' + dateStart + '&per_page=' + event_limit + '&status=publish';
                   if (currentRequest) {
                       ajaxRequest.abort();
                   }
                   currentRequest = true;
                   ajaxRequest = $.ajax({
                       'url': api_url,
                       'method': 'GET',
                       beforeSend: function() {
                           // initialize or setup anything before sending event request
                           $(wrapperClass + ' .ect_calendar_events_spinner').show();
                       },
                       success: function(res) {
                           let events = res['events']
                           let max = events.length;
                           for (var i = 0; i < max; i++) {
                               let available_cats = [];
                               if ((events[i].categories).length >= 0) {
                                   (events[i].categories).forEach(function(val, index) {

                                       if (allCategories.indexOf(val['slug']) == -1) {
                                           allCategories.push(val['slug']);
                                       }
                                       available_cats.push(val['slug']);
                                   });
                               }
                               if (available_cats.length == -1) {
                                   //available_cats.push('Uncategorized');
                               }

                               // override event text/background color if its featured event
                               if (events[i].featured == true) {
                                   evt_bgColor = featured_bg_color
                                   evt_textColor = featured_text_color;
                               } else {
                                if((events[i].event_bgcolor === "") || (events[i].event_text_color === "")){
                                    evt_bgColor = nonfeatured_bg_color;
                                    evt_textColor = nonfeatured_text_color;
                                }else{
                                    evt_bgColor = events[i].event_bgcolor;
                                    evt_textColor = events[i].event_text_color;
                                }
                                   
                               }
                               all_events.push({
                                   id: events[i].id,
                                   title: events[i].title,
                                   start: events[i].start_date,
                                   end: events[i].end_date,
                                   allDay: events[i].all_day,
                                   backgroundColor: evt_bgColor,
                                   textColor: evt_textColor,
                                   url: events[i].url,
                                   classNames: available_cats.toString(),
                                   extendedProps: {
                                       desc: events[i].description,
                                       venue: events[i].venue,
                                       image: events[i].image['url'],
                                       categories: available_cats,
                                       bgColor: evt_bgColor
                                   }
                               })
                           }

                           $(wrapperClass + ' .ect_calendar_events_spinner').hide();
                           all_events.forEach(function(TheEvent, i) {
                                   if (calendar.getEventById(TheEvent.id) == null) {
                                       calendar.addEvent(TheEvent);
                                   }
                               })
                               // create dynamic category filter for events
                           $(wrapperClass + ' .' + El_category).append('<select multiple="multiple" name="ect-calendar-cat-filter[]" class="ect-calendar-cat-filter" id="ect-calendar-cat-filter">');
                           //$(wrapperClass + ' .' + El_category + ' #ect-calendar-cat-filter').append('<option data-event-cat="select_all" class="select_allCat" value="all" >All Events</option>');
                           allCategories.forEach(function(val, index) {
                               $(wrapperClass + ' .' + El_category + ' .ect-calendar-cat-filter').append('<option data-event-cat="' + val + '" value="' + val + '">' + val.replace('-', ' ') + '</option>');
                           });
                           $(wrapperClass + ' .' + El_category).append('</select>');
                           // initialize multiple select2
                           $(wrapperClass + ' .ect-calendar-cat-filter').select2({
                               placeholder: 'Filter By Category',
                               allowClear: true,
                               width: '100%',
                           });
                           currentRequest = false;
                       }
                   }); // end of AJAX

               }

               render_new_month();

               /**
                * On calendar Previous / Next button click
                */
               $('#' + thisCalendar).on('click', 'button.fc-prev-button,button.fc-next-button', function(e) {
                   render_new_month();
                   $('#' + thisCalendar + ' button.fc-today-button').on('click', function(e) {
                       render_new_month();
                   })
               });


               // individual category option to hide and show
               $("#ect-calendar-wrapper[data-calendar-id='" + evt_cal_id + "']").on('change', "#ect-calendar-cat-filter", function() {
                   var cat_name = $(wrapperClass + ' .' + El_category + ' #ect-calendar-cat-filter').val();

                   let checkboxes = $("#ect-calendar-wrapper[data-calendar-id='" + evt_cal_id + "'] .ect-calendar-cat-filter input:not('.select_allCat'):checked");

                   let current_events = calendar.getEvents();

                   // remove all Events
                   current_events = calendar.getEvents();
                   current_events.forEach(function(deEvent, i) {
                       deEvent.remove();
                   })

                   all_events.forEach(function(deEvent, i) {
                       if (cat_name.length === 0) {
                           calendar.addEvent(deEvent);
                       } else {
                           let event_cats = deEvent.extendedProps.categories;
                           cat_name.forEach(function(category, i) {
                               // add event only if category exist and event already not added in calendar object
                               if (event_cats.indexOf(category) >= 0 && calendar.getEventById(deEvent.id) == null) {
                                   calendar.addEvent(deEvent);
                               }
                           })

                       }
                   })
               });

               /**
                * Create popup box display on event click
                */
               calendar.on('eventClick', function(info) {

                   info.jsEvent.preventDefault();
                   let startDate = moment(info.event.start).format(date_Format);
                   let endDate = info.event.end != null ? moment(info.event.end).format(date_Format) : null;
                   let barColor = info.event.extendedProps.bgColor;
                   barColor = barColor == '' ? '#3788d8' : barColor;
                   let eventDate = startDate;
                   let eventDates = startDate;
                //    if (!info.event.allDay && endDate != null) {
                //        eventDate = startDate != endDate ? startDate + ' - ' + endDate : startDate;
                //    }

                   const Title = "<div class='ect-calendar-header' style='border-left:4px solid " + barColor + ";'> <span class='tui-full-calendar-schedule-title'><a class='ect-title-link' href='" + info.event.url + "' target='_new'><h2>" + info.event.title + "</h2></a></span>";
                   var datetime = "<span class='tui-full-calendar-popup-detail-date tui-full-calendar-content'>" + eventDates + "</span></div>";
                   var closeButton = "<span class='ect-close-button'>X</span>";
                   const Header = "<div class='tui-full-calendar-popup tui-full-calendar-popup-detail'>" + closeButton + "<div class = 'tui-full-calendar-popup-container'>" + Title + datetime;
                   //const Title = "<div class='tui-full-calendar-popup-section tui-full-calendar-section-header'><span class='tui-full-calendar-schedule-private tui-full-calendar-icon tui-full-calendar-ic-private'></span><span class='tui-full-calendar-schedule-title'><a class='ect-title-link' href='" + info.event.url + "' target='_new'><h2>" + info.event.title + "</h2></a></span><span class='tui-full-calendar-popup-detail-date tui-full-calendar-content'><i></>" + eventDate + "</span></div>";

                   const venue = [info.event.extendedProps.venue.address, info.event.extendedProps.venue.city, info.event.extendedProps.venue.country, info.event.extendedProps.venue.zip];
                   let address = venue.toString();
                   address = address.replace(',,', ',');
                   let icon = "../images/location-icon.png";
                   address = address.length > 2 ? "<span class='ect-location-icon'></span>Venue: " + address : '';
                   let feature_image = info.event.extendedProps.image == undefined ? '' : info.event.extendedProps.image;
                   feature_image = feature_image != "" ? "<img onerror='this.src=''' class='feature_image' src='" + feature_image + "'>" : "";
                   let description = info.event.extendedProps.desc == undefined ? '' : info.event.extendedProps.desc;
                   description = description.length > 300 ? description.substring(0, 220) : description;
                   const Details = "<div class='tui-full-calendar-section-detail'><div class='tui-full-calendar-popup-detail-item'><span class='tui-full-calendar-icon tui-full-calendar-ic-location-b'></span><span class='tui-full-calendar-content'><div class='tui-location-div'>" + address + "</div>" + feature_image + "</span></div><div class='tui-full-calendar-popup-detail-item tui-full-calendar-popup-detail-item-separate'><span class='tui-full-calendar-content'>" + description + "<br/><a class='ect_readMore' target='_new' href='" + info.event.url + "'>" + readMoreText + "</a></span></div></div></div></div>";
                   const Footer = "<div class='tui-full-calendar-popup-top-line' style='background-color: #dbf5ff'></div><div id='tui-full-calendar-popup-arrow' class='tui-full-calendar-popup-arrow tui-full-calendar-arrow-right'><div class='tui-full-calendar-popup-arrow-border'><div class='tui-full-calendar-popup-arrow-fill'></div></div></div></div>";

                   $('body').prepend("<div id=" + popupContainer + ">" + Header + Details + Footer + "</div</div>");
                   // creat poup HTML and appen to <body>
                   //                   $('body').prepend("<div id=" + popupContainer + ">" + Header + Title + Details + Footer + "</div>")
                   if ($(window).width() > 720) {
                       //                       $('#' + popupContainer).css({ 'left': info.jsEvent.pageX / 2, 'top': info.jsEvent.pageY / 2 });
                   }
               })

               // Hide popup when clicked outside of the popup container
               $(document).mouseup(function(e) {

                   if ($(e.target).closest('.tui-full-calendar-popup').length === 0) {
                       // remove popup cointainer HTML from <body>
                       $('#' + popupContainer).remove();
                   }
               });
               $(document).on('click', "[id^='ect-calendar-popup-'] .ect-close-button", function() {
                   $('#' + popupContainer).remove();
               })

           }); // end of $.foreach

           console.log('FullCalender is initialized');

       });