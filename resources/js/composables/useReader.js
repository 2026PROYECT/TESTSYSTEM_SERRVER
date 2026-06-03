import { ref } from 'vue';

export function useReader() {
    const isReading = ref(false);

    const read = (text, lang = 'en-US') => {
        window.speechSynthesis.cancel(); // Detiene lecturas previas

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = lang; // 'en-US', 'fr-FR', 'pt-PT'
        utterance.rate = 0.9;

        utterance.onstart = () => { isReading.value = true; };
        utterance.onend   = () => { isReading.value = false; };
        utterance.onerror = () => { isReading.value = false; };

        window.speechSynthesis.speak(utterance);
    };

    return { read, isReading };
}