<style>
    .scrolling-text {
        white-space: nowrap;
        display: inline-block;
        animation: scroll-left 15s linear infinite;
    }

    @keyframes scroll-left {
        0% {
            transform: translateX(100%);
        }

        100% {
            transform: translateX(-100%);
        }
    }
</style>
<!-- marquee text -->
<div class="bg-light py-3 overflow-hidden position-relative">
    <div class="scrolling-text text-primary fw-bold">
        <span class="me-5">💻 HTML</span>
        <span class="me-5">🎨 CSS</span>
        <span class="me-5">🖥️ JavaScript</span>
        <span class="me-5">🛠️ Laravel</span>
        <span class="me-5">🌐 APIs</span>
        <span class="me-5">📦 Bootstrap 5</span>
        <span class="me-5">🐘 PHP</span>
        <span class="me-5">🧠 MySQL</span>
        <span class="me-5">📱 Responsive Design</span>
    </div>
</div>