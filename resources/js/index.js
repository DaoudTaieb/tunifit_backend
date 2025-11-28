import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'aebc2eec94386d5d25a3',
    cluster: 'mt1',
    forceTLS: true
  });
 

  window.Echo.private('user.' + 1) // Make sure userId is dynamically set
  .listen('NotificationCreated', (event) => {
      console.log('New notification:', event);
  });
