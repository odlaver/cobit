import * as Turbo from '@hotwired/turbo';
import { Application } from '@hotwired/stimulus';
import { startSfx } from './lib/sfx';
import { startNotifier } from './realtime/notifier';

Turbo.config.drive.progressBarDelay = 300;

// Daftarkan semua controller Stimulus
const app = Application.start();
const modules = import.meta.glob('./controllers/*_controller.js', { eager: true });

for (const [path, module] of Object.entries(modules)) {
    const name = path.split('/').pop().replace('_controller.js', '').replaceAll('_', '-');
    app.register(name, module.default);
}

startSfx();
startNotifier();
