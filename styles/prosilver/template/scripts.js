$(document).ready(function ()  {
    // Initially hide the usernames_list
    $('#usernames_list').hide();

    // Add click event to the link
    $('#expand_usernames a').click(function (e) {
        e.preventDefault(); // Prevent the default link behavior

        // Toggle the visibility of usernames_list with fade animation
        $('#usernames_list').fadeToggle(300);

        // Change the link text between [+] and [-]
        let linkText = $(this).text();
        if (linkText === '[+]') {
            $(this).text('[-]');
        } else {
            $(this).text('[+]');
        }
    });
});

$(document).ready(function () {
    // Create a tooltip element
    $('body').append('<div id="uc_tooltip" class="uname_tooltip"></div>');

    // Add hover event for icons with the 'fa-question-circle' class
    $('.fa-question-circle').hover(function (e) {
        // Get the user-date attribute value
        let userData = $(this).attr('user-data');

        // Set the tooltip text
        $('#uc_tooltip').text(userData);

        // Position and show the tooltip
        $('#uc_tooltip').css({
            top: e.pageY + 10 + 'px',
            left: e.pageX + 10 + 'px'
        }).fadeIn(200);
    }, function () {
        $('#uc_tooltip').fadeOut(200);
    });

    // Move the tooltip with the cursor
    $('.fa-question-circle').mousemove(function (e) {
        $('#uc_tooltip').css({
            top: e.pageY + 10 + 'px',
            left: e.pageX + 10 + 'px'
        });
    });
});