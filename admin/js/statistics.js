jQuery(document).ready(function($) {
  var url = ajaxurl;
  var ykeys = ['Amount','Accepted'];
  var labels = ['Amount','Accepted'];
  var lineColors = ['#3598dc', '#e7505a']; //  ,  '#8E44AD', '#E87E04'

  function build_hash(type,date) {
    // console.log(date);
    var hash = location.hash;

    if (!hash) location.hash = type+'='+date; // If the hash is empty
    else if (hash.indexOf(type)>0) {
      // If the type is already in the hash, then replace the value

      if (type=='d') var amount = (10+hash.indexOf(type)+2);
      else amount = (7+hash.indexOf(type)+2);

      var replaceing = hash.substring((hash.indexOf(type)+2),amount);

      var replaced = hash.replace(replaceing, date);

      location.hash = replaced;
  /*
      console.log('position of type = '+hash.indexOf(type));
      console.log('amount to replace = '+amount);
      console.log('what to replace = '+replaceing);
      console.log('replaced = '+replaced);
  */
    }
    else {
      // Append the new type to the hash
      location.hash = hash+'&'+type+'='+date;
    }
  }

  function showchart(element,url,date,ykeys,labels,type,variable) {
    $.ajax({
      url: url,
      dataType: 'json',
      cache: false,
      data: {
          'action': 'jm_cookies_tracking_ajax',
          date: date,
          type: type,
          cookie_id: $('#cookie_id').val()
      },
      beforeSend: function (){
        // Show loader
        $('#'+element).addClass('loading');
      }
    })
    .done(function( data ) {
      console.log(data);
      $('#'+element).removeClass('loading');
      variable.setData(data);
    });

  }

  // Return today's date and time
  var currentTime = new Date();
  // returns the day of the month (from 1 to 31)
  var day = currentTime.getDate()
  // returns the month (from 0 to 11)
  var month = ('0' +(currentTime.getMonth()+1)).slice(-2);
  // returns the year (four digits)
  var year = currentTime.getFullYear();
  // console.log(year+'-'+month+'-'+day);

  var chart_month = Morris.Area({
    element: 'month_stats',
    data: [{y: '2006',a: 100,b: 90}],
    xkey: 'date',
    ykeys: ykeys,
    labels: labels,
    parseTime: false,
    lineWidth: 2,
    behaveLikeLine: true,
    fillOpacity: 1,
    lineColors: lineColors,
    resize: true
  });

  $('.monthdatepicker').monthpicker({pattern: 'yyyy-mm'});

  $('.monthdatepicker').change( function(e) {
    build_hash('m',$(this).val());
  });

  var chart_day = Morris.Area({
    element: 'day_stats',
    data: [{y: '2006',a: 100,b: 90}],
    xkey: 'date',
    ykeys: ykeys,
    labels: labels,
    parseTime: false,
    lineWidth: 2,
    behaveLikeLine: true,
    fillOpacity: 1,
    lineColors: lineColors,
    resize: true
  });

  $('.daydatepicker').datepicker({
      showButtonPanel: true,
      dateFormat: 'yy-mm-dd',
      firstDay: 1
  });

  $('.daydatepicker').change( function(e) {
    build_hash('d',$(this).val());
  });

  var last_day = year+'-'+month+'-'+day;
  var last_month = year+'-'+month;

  // Load control

    var hash = location.hash;

    var hash_split = hash.replace('#','');
    var hash_split = hash_split.split('&');

    var hash_arr = new Array();
    $.each(hash_split, function(index, str) {
      // console.log(index+' = '+ str);
      hash_arr[str.split('=')[0]] = str.split('=')[1];
    });

    if (hash_arr['d'])
    {
      // console.log(hash.split("|")[1]);
      $('#day_stats_date').val(hash_arr['d']);
      showchart('day_stats',url,hash_arr['d'],ykeys,labels,'day',chart_day);
      last_day = hash_arr['d'];
    }
    else {
      showchart('day_stats',url,year+'-'+month+'-'+day,ykeys,labels,'day',chart_day);
      last_day = year+'-'+month+'-'+day;
    }

    if (hash_arr['m'])
    {
      // console.log(hash.split("|")[1]);
      $('#month_stats_date').val(hash_arr['m']);
      showchart('month_stats',url,hash_arr['m'],ykeys,labels,'month',chart_month);
      last_month = hash_arr['m'];
    }
    else {
      showchart('month_stats',url,year+'-'+month,ykeys,labels,'month',chart_month);
      last_month = year+'-'+month;
    }


  // Hash control
  $(window).on('hashchange', function() {
    var hash = location.hash;

    var hash_split = hash.replace('#','');
    var hash_split = hash_split.split('&');

    var hash_arr = new Array();
    $.each(hash_split, function(index, str) {
      // console.log(index+' = '+ str);
      hash_arr[str.split('=')[0]] = str.split('=')[1];
    });
    // console.log(hash_arr['d']);

    if (hash_arr['d'])
    {
      if (hash_arr['d']!=last_day)
      {
        // console.log(hash.split("|")[1]);
        $('#day_stats_date').val(hash_arr['d']);
        showchart('day_stats',url,hash_arr['d'],ykeys,labels,'day',chart_day);
        last_day = hash_arr['d'];
      }
    }
    else if ($('#day_stats_date').val() != year+'-'+month+'-'+day)
    {
      showchart('day_stats',url,year+'-'+month+'-'+day,ykeys,labels,'day',chart_day);
      $('#day_stats_date').val(year+'-'+month+'-'+day);
      last_day = year+'-'+month+'-'+day;
    }

    if (hash_arr['m'])
    {
      if (hash_arr['m']!=last_month)
      {
        // console.log(hash.split("|")[1]);
        $('#month_stats_date').val(hash_arr['m']);
        showchart('month_stats',url,hash_arr['m'],ykeys,labels,'month',chart_month);
        last_month = hash_arr['m'];
      }
    }
    else if ($('#month_stats_date').val() != year+'-'+month)
    {
      showchart('month_stats',url,year+'-'+month,ykeys,labels,'month',chart_month);
      $('#month_stats_date').val(year+'-'+month);
      last_month = year+'-'+month;
    }
  });
});
