$(document).on("click", function (event) {
    var $target = $(event.target);
    // Check if the clicked element is not the dropdown button or its descendants
    if (!$target.closest(".dropdownBtn").length) {
        $('.dropdown-menu').each(function () {
            if ($(this).hasClass('show')) {
                $(this).closest('td').find('.dropdown-menu').removeClass('show')
                $(this).closest('td').find('.dropdownBtn').removeClass('right-one')
            }
        })
    }
});
$('.dropdownBtn').click(function (event) {
    event.stopPropagation();
    var drop = $(this);
    drop.addClass('right-one');
    $('.dropdownBtn').each(function () {
        if ($(this).hasClass('right-one')) {
            if ($(this).closest('td').find('.dropdown-menu').hasClass('show')) {
                $(this).closest('td').find('.dropdown-menu').removeClass('show')
                $(this).removeClass('right-one')
            } else {
                $(this).closest('td').find('.dropdown-menu').addClass('show')
            }
        } else {
            $(this).closest('td').find('.dropdown-menu').removeClass('show')
        }
    })
    dropDownFixPosition($(this), $(this).closest('td').find('.dropdown-menu'));
})
function dropdown_open(event) {
    var drop = $(event);
    drop.addClass('right-one');
    $('.dropdownBtn').each(function () {
        if ($(this).hasClass('right-one')) {
            if ($(this).closest('td').find('.dropdown-menu').hasClass('show')) {
                $(this).closest('td').find('.dropdown-menu').removeClass('show')
                $(this).removeClass('right-one')
            } else {
                $(this).closest('td').find('.dropdown-menu').addClass('show')
            }
        } else {
            $(this).closest('td').find('.dropdown-menu').removeClass('show')
        }
    })
    dropDownFixPosition($(event), $(event).closest('td').find('.dropdown-menu'));
}
function dropDownFixPosition(button, dropdown) {
    var button_drop = button[0].getBoundingClientRect();
    var top = parseInt(button_drop.top) + 23;
    var left = parseInt(button_drop.left) - 15
    if (dropdown.closest('tr').is(':last-child')) {
        if (dropdown.height() > 200) {
            top = 295;
        }
    }
    dropdown.css('top', top + "px");
    dropdown.css('left', left + "px");
    dropdown.css('position', 'fixed');
}

// Update the dropdown position when scrolling the table container
$('#dataTable').scroll(function () {
    $('.dropdown-menu').each(function () {
        if ($(this).hasClass('show') == true) {
            dropDownFixPosition($(this).closest('.dropdown').find('.dropdownBtn'), $(this));
        }
    });
});

// Update the dropdown position when scrolling the window
$(window).scroll(function () {
    $('.dropdown-menu').each(function () {
        if ($(this).hasClass('show') == true) {
            dropDownFixPosition($(this).closest('.dropdown').find('.dropdownBtn'), $(this));
        }
    });
});
$('.modal, .modal-body').scroll(function () {
    $('.dropdown-menu').each(function () {
        if ($(this).hasClass('show') == true) {
            dropDownFixPosition($(this).closest('.dropdown').find('.dropdownBtn'), $(this));
        }
    });
});