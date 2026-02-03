// =====================================================top btn
$(window).on('scroll', function () {
    var scrollTop = $(window).scrollTop();
    if (scrollTop > 50) {
        $('.go-top').css("display","block");
    }
    else {
      $('.go-top').css("display","none");
    }
});
// ===========================================================sidebar

//* Loop through all dropdown buttons to toggle between hiding and showing its dropdown content - This allows the user to have multiple dropdowns without any conflict */
var dropdown = document.getElementsByClassName("dropdown-btn");
// var link = document.getElementsByClassName("dropdown-container-link")
var i;

for (i = 0; i < dropdown.length; i++) {
  dropdown[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var dropdownContent = this.nextElementSibling;
    if (dropdownContent.style.opacity === "1") {
      dropdownContent.style.opacity = "0";
      dropdownContent.style.maxHeight = "0px";
      dropdownContent.style.minHeight = "0px";
      dropdownContent.style.zIndex= "-100";
      dropdownContent.style.padding= "0px  0px 0px 30px";

    } else {
      dropdownContent.style.opacity = "1";
      dropdownContent.style.maxHeight = "600px";
      dropdownContent.style.minHeight = "0px";
      dropdownContent.style.zIndex= "1000";
      dropdownContent.style.padding= "10px  50px 10px 30px";
    }
  });
}


// ===================================dataTables
let table = new DataTable('#myTable', {
  // "bPaginate": true,
  // "bLengthChange": true,
  // "bFilter": true,
  // "bInfo": false,
  // "bAutoWidth": false,
  "pageLength" : 100,
});

let table2 = new DataTable('#myTable2', {
  "pageLength" : 100,
});

// =============================================sidemenu
$('.side-menu').on("click",function(){
  $(".sidebar").css("left","0px");
  $(".backdrop").css("display","block")
})

$('.backdrop').on("click",function(){
  $(".sidebar").css("left","-300px");
  $(this).css("display","none")
})

// =======================================Chart
    google.charts.load('current', { packages: ['corechart', 'bar'] });
    google.charts.setOnLoadCallback(drawBasic);

    function drawBasic() {

      var data = new google.visualization.DataTable();
      data.addColumn('timeofday', 'Time of Day');
      data.addColumn('number', 'Motivation Level');

      data.addRows([
        [{ v: [8, 0, 0], f: '8 am' }, 1],
        [{ v: [9, 0, 0], f: '9 am' }, 2],
        [{ v: [10, 0, 0], f: '10 am' }, 3],
        [{ v: [11, 0, 0], f: '11 am' }, 4],
        [{ v: [12, 0, 0], f: '12 pm' }, 5],
        [{ v: [13, 0, 0], f: '1 pm' }, 6],
        [{ v: [14, 0, 0], f: '2 pm' }, 7],
        [{ v: [15, 0, 0], f: '3 pm' }, 8],
        [{ v: [16, 0, 0], f: '4 pm' }, 9],
        [{ v: [17, 0, 0], f: '5 pm' }, 10],
      ]);

      var options = {
        title: 'Motivation Level Throughout the Day',
        hAxis: {
          title: 'Time of Day',
          format: 'h:mm a',
          viewWindow: {
            min: [7, 30, 0],
            max: [17, 30, 0]
          }
        },
        vAxis: {
          title: 'Rating (scale of 1-10)'
        }
      };

      var chart = new google.visualization.ColumnChart(
        document.getElementById('chart_div'));

      chart.draw(data, options);
    }
    // =======================chart
    google.charts.load('current', { 'packages': ['corechart'] });
    google.charts.setOnLoadCallback(drawChart);

    function drawChart() {

      var data = google.visualization.arrayToDataTable([
        ['Task', 'Hours per Day'],
        ['Work', 11],
        ['Eat', 2],
        ['Commute', 2],
        ['TV', 2],
        ['Sleep', 7]
      ]);

      var options = {
        title: 'My Daily Activities'
      };

      var chart = new google.visualization.PieChart(document.getElementById('piechart'));

      chart.draw(data, options);
    }
// ================================================DragDrop
document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
  const dropZoneElement = inputElement.closest(".drop-zone");

  dropZoneElement.addEventListener("click", (e) => {
    inputElement.click();
  });

  inputElement.addEventListener("change", (e) => {
    if (inputElement.files.length) {
      updateThumbnail(dropZoneElement, inputElement.files);
    }
  });

  dropZoneElement.addEventListener("dragover", (e) => {
    e.preventDefault();
    dropZoneElement.classList.add("drop-zone--over");
  });

  ["dragleave", "dragend"].forEach((type) => {
    dropZoneElement.addEventListener(type, (e) => {
      dropZoneElement.classList.remove("drop-zone--over");
    });
  });

  dropZoneElement.addEventListener("drop", (e) => {
    e.preventDefault();

    if (e.dataTransfer.files.length) {
      inputElement.files = e.dataTransfer.files;
      updateThumbnail(dropZoneElement, e.dataTransfer.files);
    }

    dropZoneElement.classList.remove("drop-zone--over");
  });
});

/**
 * Updates the thumbnail on a drop zone element.
 *
 * @param {HTMLElement} dropZoneElement
 * @param {File} file
 */
function updateThumbnail(dropZoneElement, files) {
  let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

  // First time - remove the prompt
  if (dropZoneElement.querySelector(".drop-zone__prompt")) {
    dropZoneElement.querySelector(".drop-zone__prompt").remove();
  }

  // First time - there is no thumbnail element, so lets create it
  if (!thumbnailElement) {
    thumbnailElement = document.createElement("div");
    thumbnailElement.classList.add("drop-zone__thumb");
    dropZoneElement.appendChild(thumbnailElement);
  }

  // Loop through all files and display their names
  let label = "";
  let docMsg = "";
  for (let i = 0; i < files.length; i++) {
    if (i === 0) {
      label += files[i].name;
      docMsg += `${i+1}. ` + files[i].name + ` uploaded`;
    } else {
      label += ", " + files[i].name;
      docMsg += "<br>" + `${i+1}. ` + files[i].name + ` uploaded`;
    }
  }
  thumbnailElement.dataset.label = label;
  $("#doc-msg").html(docMsg)

  // Show thumbnail for image files
  if (files[0].type.startsWith("image/")) {
    const reader = new FileReader();

    reader.readAsDataURL(files[0]);
    reader.onload = () => {
      thumbnailElement.style.backgroundImage = `url('${reader.result}')`;
    };
  } else {
    thumbnailElement.style.backgroundImage = null;
  }
}

$('.nav-link').click(function(){
  $(this).find('.bi-chevron-down').toggleClass('bi-chevron-left')
})

$('.dropdown-btn').click(function () {
  $(this).next().find('a').toggleClass("dropdown-container-link")
})

// Translator
function readCookie(name) {
  var c = document.cookie.split('; '),
      cookies = {}, i, C;

  for (i = c.length - 1; i >= 0; i--) {
    C = c[i].split('=');
    cookies[C[0]] = C[1];
  }

  return cookies[name];
}

var lang_en = document.querySelector('#lang-en');
var lang_sv = document.querySelector('#lang-sv');

if(readCookie('googtrans') == undefined) {
  lang_en.style.pointerEvents = 'none';
  lang_en.classList.add('black-white')

}else{
  lang_sv.style.pointerEvents = 'none';
  lang_sv.classList.add('black-white')
}

lang_en.addEventListener('click', function () {
  lang_en.style.pointerEvents = 'none';
  lang_en.classList.add('black-white')
  lang_sv.style.pointerEvents = '';
  lang_sv.classList.remove('black-white')
})
lang_sv.addEventListener('click', function () {
  lang_sv.style.pointerEvents = 'none';
  lang_sv.classList.add('black-white')
  lang_en.style.pointerEvents = '';
  lang_en.classList.remove('black-white')
})


function googleTranslateElementInit2() {
  new google.translate.TranslateElement({
    pageLanguage: 'en',
    autoDisplay: false
  }, 'google_translate_element2');
}

/* <![CDATA[ */
eval(function (p, a, c, k, e, r) {
  e = function (c) {
    return (c < a ? '' : e(parseInt(c / a))) + ((c = c % a) > 35 ? String.fromCharCode(c + 29) : c.toString(36))
  };
  if (!''.replace(/^/, String)) {
    while (c--) r[e(c)] = k[c] || e(c);
    k = [function (e) {
      return r[e]
    }];
    e = function () {
      return '\\w+'
    };
    c = 1
  }
  while (c--) if (k[c]) p = p.replace(new RegExp('\\b' + e(c) + '\\b', 'g'), k[c]);
  return p
}('6 7(a,b){n{4(2.9){3 c=2.9("o");c.p(b,f,f);a.q(c)}g{3 c=2.r();a.s(\'t\'+b,c)}}u(e){}}6 h(a){4(a.8)a=a.8;4(a==\'\')v;3 b=a.w(\'|\')[1];3 c;3 d=2.x(\'y\');z(3 i=0;i<d.5;i++)4(d[i].A==\'B-C-D\')c=d[i];4(2.j(\'k\')==E||2.j(\'k\').l.5==0||c.5==0||c.l.5==0){F(6(){h(a)},G)}g{c.8=b;7(c,\'m\');7(c,\'m\')}}', 43, 43, '||document|var|if|length|function|GTranslateFireEvent|value|createEvent||||||true|else|doGTranslate||getElementById|google_translate_element2|innerHTML|change|try|HTMLEvents|initEvent|dispatchEvent|createEventObject|fireEvent|on|catch|return|split|getElementsByTagName|select|for|className|goog|te|combo|null|setTimeout|500'.split('|'), 0, {}))
/* ]]> */