import Echo from "laravel-echo";

import Pusher from "pusher-js";
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "reverb",
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? "https") === "https",
    enabledTransports: ["ws", "wss"],
});

// window.Echo.private(`offer-notification.7`).listen("OfferSendEvent", (e) => {
//     console.log('message', e);
// });

// window.Echo.private(`offer-notification.11`)
//     .listen('OfferSendEvent', (e) => {
//         console.log('got event', e);
        
//     });

window.Echo.private(`offer-send-notification.5`)
    .listen('OfferSendEvent', (e) => {
        console.log('Got Offer Send Notification:', e);
       
    });

