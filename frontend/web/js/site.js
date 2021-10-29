$(function () {
    const headToolsOffset = $('.head-tools').offset().top;
    fixHeader($(window).scrollTop(), headToolsOffset, 992);

    // fixed menu on scroll only for desktop
    $(window).scroll(function () {
        fixHeader($(this).scrollTop(), headToolsOffset, 992);
    });
});

/**
 * Fix header on top of screen
 * 
 * @param int currentOffset Current scroll offset
 * @param int thresholdOffset Thrshold offset value to fix header
 * @param int widthLimit Minimal browser window width to able to fix header
 * 
 * @return mixed
 */
function fixHeader(currentOffset, thresholdOffset, widthLimit) {
    if ($(window).width() <= widthLimit) {
        return false;
    }

    if (currentOffset > thresholdOffset) {
        $('.head-tools').addClass('fixed-top');
        // add padding top to show content behind navbar
        // $('body').css('padding-top', $('.navbar').outerHeight() + 'px');
    } else {
        $('.head-tools').removeClass('fixed-top');
        // remove padding top from body
        // $('body').css('padding-top', '0');
    }
}