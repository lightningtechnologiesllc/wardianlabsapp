import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['field', 'container'];

    connect() {
        this.toggle();
    }

    toggle() {
        const checkbox = this.element.querySelector('input[type="checkbox"]');
        const isChecked = checkbox?.checked || false;

        this.containerTargets.forEach(container => {
            container.style.display = isChecked ? '' : 'none';
        });
    }
}
