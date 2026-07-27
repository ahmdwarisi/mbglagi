document.querySelectorAll("a, button").forEach(el => {

    el.addEventListener("mousedown", () => {
        el.style.transform = "scale(.96)";
    });

    el.addEventListener("mouseup", () => {
        el.style.transform = "";
    });

    el.addEventListener("mouseleave", () => {
        el.style.transform = "";
    });

});