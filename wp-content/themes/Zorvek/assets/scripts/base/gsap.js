document.addEventListener("DOMContentLoaded", function() {
    gsap.registerPlugin(ScrollTrigger);

    console.log('Scroll Trigger Registered');

    var navParent = document.querySelector(".nav-parent");
    var logo = document.querySelector(".logo");

    if (navParent && logo) {
        console.log('Elements found, setting up triggers.');

        // Add class when scrolled
        ScrollTrigger.create({
            trigger: ".site-content",
            start: "20px",
            onEnter: () => navParent.classList.add("scrolled"),
            onLeaveBack: () => navParent.classList.remove("scrolled"),
        });
        console.log('Parent ScrollTrigger created.');
    } else {
        console.error('Elements not found!');
    }
});
