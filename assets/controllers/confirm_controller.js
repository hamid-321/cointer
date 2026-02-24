import { Controller } from '@hotwired/stimulus';

export default class extends Controller
{
    static targets = ['backdrop', 'message'];

    connect()
    {
        this.formToSubmit = null;
    }

    /**
     * Opens the modal and populates
     */
    open(event)
    {
        event.preventDefault();
        event.stopPropagation();
        const button = event.currentTarget;
        const form = button.closest('form');
        if (!form) return;
        const message = button.getAttribute('data-confirm-message') || 'Are you sure?';
        this.formToSubmit = form;
        this.messageTarget.textContent = message;
        this.backdropTarget.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    close()
    {
        this.backdropTarget.classList.add('hidden');
        document.body.style.overflow = '';
        this.formToSubmit = null;
    }

    stopPropagation(event)
    {
        event.stopPropagation();
    }

    confirm()
    {
        if (this.formToSubmit)
        {
            this.formToSubmit.submit();
        }
        this.close();
    }
}
