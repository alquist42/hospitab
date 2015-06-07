<?php
require_once(dirname(__FILE__)."/../actions/everyPage.php");
$date = '--/--/--';

if (!isset($itineraries)) $itineraries = false;

function BuildSelect($name, $room, $selected = 0, $index = null){
        $kidsIndex = !is_null($index) ? '[' . $index . ']' : '';
        $select = '<select name="' . $name . '[' . $room . ']' . $kidsIndex . '">';
        switch ($name){
            case 'adults':
                for($i = 1; $i < 7; $i++){
                    if($i == $selected){
                        $select .= '<option value="' . $i . '" selected="selected">' . $i . '</option>';
                    }
                    else{
                        $select .= '<option value="' . $i . '">' . $i . '</option>';
                    }
                }
                break;
            case 'kids':
                for($i = 0; $i < 4; $i++){
                    if($i == $selected){
                        $select .= '<option value="' . $i . '" selected="selected">' . $i . '</option>';
                    }
                    else{
                        $select .= '<option value="' . $i . '">' . $i . '</option>';
                    }
                }
                break;
            case 'childrenAges':
                for($i = 0; $i < 18; $i++){
                    if($i == $selected){
                        $select .= '<option value="' . $i . '" selected="selected">' . $i . '</option>';
                    }
                    else{
                        $select .= '<option value="' . $i . '">' . $i . '</option>';
                    }
                }
                break;
        }
        $select .= '</select>';
        return $select;
}


if(1) { //$user -> auth

    $journey = new Trip($user);
    try{
        $journey -> LoadTrip();
        if(!empty($journey -> settings -> currentDate)) {
            $realDate = $journey->settings->currentDate;
        }
        $journey -> LoadPackage();
    }
    catch(\Exception $e){
        Logger::Log($e);
    }

    if(isset($realDate)){
        $date = date('M d, Y', $realDate);
    }

    $customer = \Innstant\Customer::Export();

    // Get default user country
    $country = '';
    $currency = '';
    if (empty($customer -> country -> name)) {
        $db = \Innstant\DB::GetConnection();
        $query = '
            SELECT
                `name`,
                `currency_code`
            FROM
                `countries`
            WHERE
                `code` = ' . \Innstant\DB::STRING_PLACEHOLDER;

        $country = $db->getRow($query, $_SESSION['userCountry']);

        if(!empty($country)) {
            $currency = $country['currency_code'];
            $country = $country['name'];
        }
    }

    $selectablePax = '';
    $detailedPax = '';
    if(!empty($customer -> pax)){
        foreach($customer -> pax -> rooms as $i => $room){
                $childrenAges = '';
                if($room -> children -> count > 0){
                    $childrenAges .= '
                    <dl class="ages">
                        <dt>Children ages:</dt>
                        <dd>';
                    foreach($room -> children -> ages as $j => $age){
                        $childrenAges .= BuildSelect('childrenAges', ($i + 1), $age, $j);
                    }
                    $childrenAges .= '
                        </dd>
                    </dl>';
                }
                $detailedPax .= '
                <div class="room">
                    <dl>
                        <dt class="room-number">Room ' . ($i + 1) . '</dt>
                        <dd class="room-pax" data-number="' . ($i + 1) . '">
                            <dl>
                                <dt>Adults:</dt>
                                <dd>
                                    ' . BuildSelect('adults', ($i + 1), $room -> adults) . '
                                </dd>
                            </dl>
                            <dl>
                                <dt>Kids:</dt>
                                <dd>
                                    ' . BuildSelect('kids', ($i + 1), $room -> children -> count) . '
                                </dd>
                            </dl>
                            ' . $childrenAges . '
                        </dd>
                    </dl>
                </div>';
            }
    }
    else{
        $selectablePax = '1|2';
    }

    
    ?>
    <div id="change-package-settings" class="popup <?php if ($itineraries) echo ' rooms-only'?>" style="z-index: 10000">
        <p class="title" style="text-align: center">Enter your details to price your trip</p>
        <form id="customer-details" action="/actions/create-package.php" method="post">
            <div id="main-details">
                <div class="wrapper">
                    <dl id="customer-country">
                        <dt>Your country (passport you travel with):</dt>
                        <dd>
                            <input type="text" id="customerCountry" name="customerCountry" tabindex="-1" value="<?php echo !empty($customer -> country -> name) ? $customer -> country -> name : $country;; ?>">
                            <!-- <br>
                            <span class="explanation">Issuing country name</span> -->
                        </dd>
                    </dl>
                    <dl>
                        <dt>Currency:</dt>
                        <dd>
                            <select name="currency" id="currency">
                                <?php 
                                    foreach (\Innstant\API::$AVAILABLE_CURRENCIES as $code => $sign) {
                                        $selected = (!empty($customer -> currency) && ($customer -> currency == $code)) ? ' selected' : ($currency == $code) ? ' selected' : '';
                                        echo '<option value="' . $code .'"' . $selected . '>' . $code . '</option>';
                                    } 
                                ?>
                            </select>
                        </dd>
                    </dl>
                </div>
                <div class="wrapper">
                    <dl id="customer-budget">
                        <dt>Budget:</dt>
                        <dd>
                            <?php 
                                $budget = $journey -> settings -> budget; 
                            ?>
                            <select name="budget" id="budget">
                                <option value="economy" <?php echo ($budget == 'economy') ? ' selected' : ''; ?>>Economy</option>
                                <option value="moderate" <?php echo ($budget == 'moderate') ? ' selected' : ''; ?>>Moderate</option>
                                <option value="luxury" <?php echo ($budget == 'luxury') ? ' selected' : ''; ?>>Luxury</option>
                            </select>
                        </dd>
                    </dl>
                    <dl>
                        <dt>Arrival date:</dt>
                        <dd>
                            <input type="text" id="arrivalDate" name="arrivalDate" value="<?php echo $date; ?>">
                            <!-- <br>
                            <span class="explanation">When do you arrive?</span> -->
                        </dd>
                    </dl>
                </div>
                <dl class="guests">
                    <dt>Total guests in this trip</dt>
                    <dd>
                        <select name="pax" id="pax">
                            <option value="1|1"<?php echo $selectablePax == '1|1' ? ' selected="selected"' : ''; ?>>1 room for 1 adult</option>
                            <option value="1|2"<?php echo $selectablePax == '1|2' ? ' selected="selected"' : ''; ?>>1 room for 2 adults</option>
                            <option value="2|4"<?php echo $selectablePax == '2|4' ? ' selected="selected"' : ''; ?>>2 rooms for 4 adults</option>
                            <?php
                            if(!empty($selectablePax) && $selectablePax != '1|1' && $selectablePax != '1|2' && $selectablePax != '2|4'){
                            ?>
                            <option value="<?php echo $selectablePax; ?>" selected="selected"><?php echo $customer -> pax -> total -> rooms; ?> rooms for <?php echo $customer -> pax -> total -> adults; ?> adults</option>
                            <?php
                            }
                            ?>
                            <option value=""<?php echo $selectablePax == '' ? ' selected="selected"' : ''; ?>>Other options</option>
                        </select>
                    </dd>
                </dl>
            </div>
            <div id="pax-details">
                <div id="rooms">
                    <?php echo $detailedPax; ?>
                </div>
                <div class="add-room-button">
                    <button id="add-room" type="button" class="button blue corners small">✚ Add Room</button>
                    <button id="remove-room" type="button" class="button gray corners small" style="float: right; margin-right: 43px; padding: 3px 8px;">✖ Remove Room</button>
                </div>
            </div>
            <div class="submit-button">
                <button class="button medium green corners">Price my trip</button>
            </div>
        </form>
        <span class="close"></span>
    </div>
    <div id="change-package-settings-calendar" class="popup" style="z-index: 10000">
        <div class="body"></div>
        <span class="close"></span>
    </div>
    <script id="price-pax">

        var itineraries = <?= json_encode($itineraries) ?>;

        $(document).ready(function(){

            if($('script[src$="selectBox.min.js"]').length == 0){
                $('body').append('<script src="/plugins/selectBox/selectBox.min.js"><\/script>');
            }
            if($('script[src$="datepicker.min.js"]').length == 0){
                $('body').append('<script src="/plugins/datepicker/datepicker.min.js"><\/script>');
            }
            if($('script[src$="validate.min.js"]').length == 0){
                $('body').append('<script src="/plugins/validation/jquery.validate.min.js"><\/script>');
            }       
            

            $('select', $('#change-package-settings')).selectBox();

            $('#customerCountry', $('#customer-details')).autocomplete({
                minLength: 2,
                source: '/actions/countries.php',
                autoFocus: true,
                change: function( event, ui ) {
                  
                  // if selection not from autocomplete - not allowed
                  if ( !ui.item ) {

                        $(this).val("");
                        return false;

                  }          
                  
                },
                select: function ( event, ui ) {
                    // change currency to default by country  
                    var currency = ui.item.currency_code;
                    $('#currency', $('#customer-details')).selectBox('value', currency);
                }
              // Prevent <Enter> key form submit  
            }).keydown(function(event) {

                 if ( event.which == 13 ) {

                      event.preventDefault();                

                 }
                
            }).focus(function(){
                var $this = $(this);
                $this.data('prev', $this.val());
                $this.val('');
            }).blur(function(){
                var $this = $(this);
                if ($this.val() == '') {
                    $this.val($this.data('prev'));
                }
            });

            $('#change-package-settings').jqm({
                closeClass:'close',
                overlayClass:'overlay',
                overlay:60,
                onShow: function(params){
                    if(window.Trip !== undefined && window.Trip.settings !== undefined && window.Trip.settings.dates !== undefined && window.Trip.settings.dates != '--/--/--'){
                        params.w.find('#arrivalDate').val($.datepick.formatDate('M d, yyyy', new Date(Date.parse(Trip.settings.dates))));
                    }
                    params.o.prependTo('body');
                    params.o.show();
                    params.w.show();
                    $('#pax', $('#customer-details')).change();
                    //params.w.find('select').selectBox();
                }
            });

            $('#arrivalDate').keydown(function(event) {
                event.preventDefault();                
            });

            $('#change-package-settings-calendar').jqm({
                trigger: '#customer-details #arrivalDate',
                closeClass:'close',
                overlay:0,
                onShow: function(params){
                    if(params.t.value != '--/--/--') {
                        params.w.children('.body').datepick('option', 'defaultDate', $.datepick.parseDate('M d, yyyy', params.t.value));
                    }
                    params.w.show();
                }
            });

            // Calendar initialization
            $('.body', $('#change-package-settings-calendar')).datepick({
                prevText: 'Prev',
                todayText: 'MM',
                nextText: 'Next',
                commandsAsDateFormat: true,
                onSelect: function(dates) {
                    console.log(dates);
                    var date = $.datepick.formatDate('M d, yyyy', dates[0]);
                    $('#arrivalDate', $('#customer-details')).val(date).valid();
                    $('#change-package-settings-calendar').jqmHide();
                },
                changeMonth: false,
                minDate : '+3d',
                maxDate : '+1y',
                pickerClass: 'noPrevNext'
            });

            $('#pax', $('#change-package-settings')).on('change', function(){
                var $paxDetails = $(this).parents('#main-details').siblings('#pax-details'),
                    $rooms = $paxDetails.children('#rooms');
                switch (this.value){
                    case '':
                        if($paxDetails.is(':hidden')) {
                            if($rooms.children().length == 0) {
                                $rooms.append(BuildRoom(1)).find('select').selectBox();
                            }
                            else{
                                $rooms.find('select').selectBox();
                            }
                            $paxDetails.show();
                        }
                        break;
                    default:
                        if($paxDetails.is(':visible')) {
                            $rooms.find('select').selectBox('destroy');
                            $rooms.empty();
                            $paxDetails.hide();
                        }
                        break;
                }
            });

            $('#rooms', $('#pax-details')).on('change', 'select[name^="kids"]', function(event){
                var $roomPax = $(this).parents('.room-pax');
                if(this.value == 0 && $roomPax.is(':has(.ages)')){
                    $roomPax.children('.ages').remove();
                }
                else if($roomPax.is(':has(.ages)')) {
                    var $kidsAges = $roomPax.find('.ages > dd'),
                        agesCount = $kidsAges.children('select').length,
                        agesToAdd = this.value - agesCount;
                    console.log(agesCount);
                    console.log(agesToAdd);
                    if (agesToAdd > 0) {
                        for (var i = agesCount; i < (agesToAdd + agesCount); i++) {
                            $kidsAges.append(BuildSelect('childrenAges', $roomPax.data('number'), i));
                        }
                        $kidsAges.find('select:not(.selectBox)').selectBox();
                    }
                    if(agesToAdd < 0) {
                        for (var i = 0; i < Math.abs(agesToAdd); i++) {
                            $kidsAges.children('select:last').selectBox('destroy').remove();
                        }
                    }
                }
                else{
                    var ages = '<dl class="ages"><dt>Children ages:</dt><dd>';
                    for (var i = 0; i < this.value; i++) {
                        ages += BuildSelect('childrenAges', $roomPax.data('number'), i);
                    }
                    ages += '</dd></dl>';
                    $roomPax.append(ages).find('select').selectBox();
                }
            });

            $('#add-room', $('#pax-details')).on('click', function(event){
                var $rooms = $(this).parent().siblings('#rooms'),
                    roomsCount = $rooms.children().length;
                if(roomsCount < 6){
                    $rooms.append(BuildRoom(roomsCount + 1));
                    $rooms.children(':last').find('select').selectBox();
                }
                else{
                    alert('Maximum possible count of rooms is 6!');
                }
            });

            $('#remove-room', $('#pax-details')).on('click', function(event){
                var $rooms = $(this).parent().siblings('#rooms'),
                    roomsCount = $rooms.children().length;
                if(roomsCount > 1){
                    $rooms.children(':last').find('select').selectBox('destroy');
                    $rooms.children(':last').remove();
                }
                else{
                    alert('Minimum possible count of rooms is 1!');
                }
            });

            jQuery.validator.addMethod("date", function(value, element) {
                $validDate = true;
                try{
                    $.datepick.parseDate('M d, yyyy', value);
                }
                catch ($e){
                    $validDate = false;
                }
                return this.optional(element) || $validDate;
            }, "Please enter valid date");


            $('#customer-details').validate({
                //debug: true,
                errorElement: 'span',
                focusInvalid: true,
                errorPlacement: function(error, element){
                    element.parent('dd').append(error);
                },
                rules: {
                    customerCountry: {
                        required: true,
                        minlength: 2
                    },
                    arrivalDate: {
                        required: true,
                        date: true
                    },
                    budget: {
                        required: true
                    },
                    currency: {
                        required: true
                    }
                }
            });

            $('#customer-details').submit(function(){
                //$('#customerCountry', $('#customer-details')).change();
                if($(this).valid()){
                    _gaq.push(['_trackEvent', 'Package', 'Price', window.location.pathname]);
                    $('#change-package-settings').jqmHide();
                    var messages = [
                        'Searching over 100 suppliers for the best rates may take up to a minute',
                        'Choose from over 200K hotels and b&b\'s based on your budget and preferences',
                        'Book your trip as a package and save up to 20%<br>Best price guaranteed!'
                    ];
                    loader.show('Pricing your trip, please wait...', messages);
                }
            });

            
        });


            function BuildRoom(roomNumber){
                var $room = $(document.createElement('div')).addClass('room');
                $room.append('<dl><dt class="room-number">Room ' + roomNumber + '</dt><dd class="room-pax" data-number="' + roomNumber + '"></dd></dl>');
                var $roomPax = $room.find('.room-pax');
                $roomPax.append('<dl><dt>Adults:</dt><dd>' + BuildSelect('adults', roomNumber) + '</dd></dl>');
                $roomPax.append('<dl><dt>Kids:</dt><dd>' + BuildSelect('kids', roomNumber) + '</dd></dl>');
                return $room;
            }

            function BuildSelect(name, room, index){
                var kidsIndex = index !== undefined ? '[' + index + ']' : '';
                var select = '<select name="' + name + '[' + room + ']' + kidsIndex + '">';
                switch (name){
                    case 'adults':
                        for(var i = 1; i < 7; i++){
                            select += '<option value="' + i + '">' + i + '</option>';
                        }
                        break;
                    case 'kids':
                        for(var i = 0; i < 4; i++){
                            select += '<option value="' + i + '">' + i + '</option>';
                        }
                        break;
                    case 'childrenAges':
                        for(var i = 0; i < 18; i++){
                            select += '<option value="' + i + '">' + i + '</option>';
                        }
                        break;
                }
                select += '</select>';
                return select;
            }

            function getPaxOption () {

                var rooms  = $('.room').length;
                var adults = 0;
                var kids   = 0;

                if (!rooms) return false;

                for (var i = 1; i <= rooms; i++) {
                    adults += parseInt($('[name="adults[' + i + ']"]').val());
                    kids   += parseInt($('[name="kids[' + i + ']"]').val());
                }
                var option = rooms + " rooms for " + adults + " adults";

                if (kids) {
                    option += " and " + kids + " kids";
                }

                return option;
            }
            
    </script>

<?php 

}

    else {

        echo json_encode(array('status' => 'ERROR', 'text' => array('message' => "User is not logged in", 'code' => 'SH101')));

    }

?>
