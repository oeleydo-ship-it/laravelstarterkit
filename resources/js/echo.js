import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { reverbEchoOptions } from './chat/realtime';

window.Pusher = Pusher;

window.Echo = new Echo(reverbEchoOptions());
