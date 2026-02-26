import { Controller } from '@hotwired/stimulus';

export default class extends Controller 
{
    static targets = ["window", "input", "messages"]

    toggle()
    {
        this.windowTarget.classList.toggle('hidden');
    }

    sendOnEnter(event)
    {
        if (event.key === 'Enter') {
            event.preventDefault();
            this.send();
        }
    }

    async send()
    {
        const msg = this.inputTarget.value.trim();
        if (!msg) return;

        this.appendMessage('user', msg);
        this.inputTarget.value = '';

        const history = [];
        this.messagesTarget.querySelectorAll('.w-fit').forEach(el => {
            const isUser = el.classList.contains('bg-purple-500/20');
            history.push({
                role: isUser ? 'user' : 'assistant',
                content: el.textContent.trim()
            });
        });

        const response = await fetch('/chatbot',
        {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: msg, history: history }),
            credentials: 'include'
        });

        const data = await response.json().catch(() => ({}));
        const message = data.response ?? (response.ok ? '' : 'Something went wrong. Assistant is currently unavailable.');
        if (message) 
        {
            this.appendMessage('bot', message);
        }
    }

    appendMessage(type, text)
    {
        const wrapper = document.createElement('div');
        wrapper.className = type === 'user' ? 'flex justify-end' : 'flex justify-start';

        const bubble = document.createElement('div');
        bubble.className = type === 'user'
            ? 'w-fit max-w-[85%] bg-purple-500/20 border border-purple-400/40 rounded-lg px-3 py-2 text-white text-right'
            : 'w-fit max-w-[85%] bg-card border border-white/10 rounded-lg px-3 py-2 text-gray-300';
        bubble.innerText = text;

        wrapper.appendChild(bubble);
        this.messagesTarget.appendChild(wrapper);
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
    }
}