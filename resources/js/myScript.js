document.addEventListener("DOMContentLoaded", function () {
    let open = false;
    const menuIcon = document.getElementById('menuIcon');
    const closeIcon = document.getElementById('closeIcon');
    const mobileNav = document.getElementById('mobileNav');
    const toggleButton = document.getElementById('toggleButton');

    window.addEventListener('scroll', function() {
        var header = document.querySelector('header');
        var aside = document.querySelector('aside');
        
        if (window.scrollY > header.offsetHeight) {
            aside.classList.add('top-0');
            aside.classList.remove('top-20');
        } else {
            aside.classList.add('top-20');
            aside.classList.remove('top-0');
        }
    });

    window.toggleNav = function () {
        open = !open;
        if (open) {
            menuIcon.classList.remove('inline-flex');
            menuIcon.classList.add('hidden');

            closeIcon.classList.remove('hidden');
            closeIcon.classList.add('inline-flex');

            mobileNav.classList.remove('hidden');
            mobileNav.classList.add('block');
        } else {
            menuIcon.classList.remove('hidden');
            menuIcon.classList.add('inline-flex');

            closeIcon.classList.remove('inline-flex');
            closeIcon.classList.add('hidden');
            
            mobileNav.classList.remove('block');
            mobileNav.classList.add('hidden');
        }
    };

    document.addEventListener('click', function (event) {
        if (!toggleButton.contains(event.target) && !menuIcon.contains(event.target) && !closeIcon.contains(event.target)) {
            mobileNav.classList.remove('block');
            mobileNav.classList.add('hidden');

            closeIcon.classList.remove('inline-flex');
            closeIcon.classList.add('hidden');

            menuIcon.classList.remove('hidden');
            menuIcon.classList.add('inline-flex');
        }
    });
});