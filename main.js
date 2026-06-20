(function ($) {
    "use strict";

    // Spinner
    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);


    // Sticky Navbar
    $(window).scroll(function () {
        if ($(this).scrollTop() > 45) {
            $('.navbar').addClass('sticky-top shadow-sm');
        } else {
            $('.navbar').removeClass('sticky-top shadow-sm');
        }
    });

    
   // Back to top button
   $(window).scroll(function () {
    if ($(this).scrollTop() > 300) {
        $('.back-to-top').fadeIn('slow');
    } else {
        $('.back-to-top').fadeOut('slow');
    }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({scrollTop: 0}, 1500, 'easeInOutExpo');
        return false;
    }); 

})(jQuery);

// Add this to your js/main.js or a <script> tag
document.querySelector('.search-bar button').addEventListener('click', function() {
    const category = document.querySelector('.search-bar select').value;
    const query = document.querySelector('.search-bar input').value.toLowerCase();
    
    // If user selected a category, redirect to that category page
    if (category !== 'All Categories') {
        const categoryMap = {
            'Solar Panels': 'solar-panels.html',
            'Solar Batteries': 'solar-batteries.html',
            'Solar Inverters': 'solar-inverters.html',
            'Charge Controllers': 'charge-controllers.html',
            'Solar Freezers': 'solar-freezers.html',
            'Solar Generators': 'solar-generators.html',
            'Other Products': 'other-products.html'
        };
        window.location.href = categoryMap[category];
        return;
    }
    
    // If just typed a search term, you could redirect to a search results page
    // or filter visible products on the current page
    if (query) {
        // For now, redirect to products page with search param
        window.location.href = 'products.html?search=' + encodeURIComponent(query);
    }
});

