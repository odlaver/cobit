import { Controller } from '@hotwired/stimulus';
import { isEnabled, play, setEnabled } from '../lib/sfx';

// Tombol nyala atau bisu suara game
export default class extends Controller {
    connect() {
        this.render();
    }

    toggle() {
        setEnabled(!isEnabled());
        this.render();
        play('blip');
    }

    render() {
        this.element.setAttribute('aria-pressed', String(isEnabled()));
    }
}
