
$('.menu-lg').on('click',function(){
  // $('.sidebar-section').css("left","-270px");
  // $('.main-section ').css("width","100%");
  $('.sidebar-section').toggleClass('hide-side');
  $('.main-section').toggleClass('full-main');
})

// $('.backdrop').on('click',function(){
//   $('.sidebar-section').css("left","-270px");
//   $(this).css("display","none");
//   $('body').css("overflow","auto");
// })

// =======================================sidebar collapse on small screen 
$('.menu-btn').on('click',function(){
    $('.sidebar-section').css("left","0px");
    $('.backdrop').css("display","block");
    $('body').css("overflow","hidden");
})

$('.backdrop').on('click',function(){
    $('.sidebar-section').css("left","-270px");
    $(this).css("display","none");
    $('body').css("overflow","auto");
})


// ================================sidebar dropdown
$('.dropdown-li').on('click',function(){
    $(this).toggleClass('showMenu');
})
// ===================================dataTables
// let table = new DataTable("#myTable", {
//   // "bPaginate": false,
//   // "bLengthChange": false,
//   language: { search: "", searchPlaceholder: "Search..." },
//   // "bFilter": false,
//   // "bInfo": false,
//   //  "bAutoWidth": false
// });

// ================================================DragDrop
document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
    const dropZoneElement = inputElement.closest(".drop-zone");
  
    dropZoneElement.addEventListener("click", (e) => {
      inputElement.click();
    });
  
    inputElement.addEventListener("change", (e) => {
      if (inputElement.files.length) {
        updateThumbnail(dropZoneElement, inputElement.files[0]);
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
        updateThumbnail(dropZoneElement, e.dataTransfer.files[0]);
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
  function updateThumbnail(dropZoneElement, file) {
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
  
    thumbnailElement.dataset.label = file.name;
  
    // Show thumbnail for image files
    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
  
      reader.readAsDataURL(file);
      reader.onload = () => {
        thumbnailElement.style.backgroundImage = `url('${reader.result}')`;
      };
    } else {
      thumbnailElement.style.backgroundImage = null;
    }
  }
