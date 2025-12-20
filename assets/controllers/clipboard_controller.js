import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['source', 'button'];
    static values = {
        suffix: String
    };

    copy() {
        const value = this.sourceTarget.value + this.suffixValue;

        navigator.clipboard.writeText(value).then(() => {
            this.showSuccess();
        });
    }

    showSuccess() {
        const button = this.buttonTarget;
        const originalHTML = button.innerHTML;

        button.innerHTML = `<svg class="size-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
        </svg>`;

        setTimeout(() => {
            button.innerHTML = originalHTML;
        }, 1500);
    }
}
