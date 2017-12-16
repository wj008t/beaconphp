if (window.top !== window) {
    if (window.top.layer) {
        window.layer = window.top.layer;
    }
}