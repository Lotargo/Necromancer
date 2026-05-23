/**
 * Necromancer - KaTeX Player Module
 * Изолированный рендеринг LaTeX-формул с использованием KaTeX.
 */

export class KaTeXPlayer {
    static init() {
        console.log("KaTeX Player initialized.");
    }

    /**
     * Рендерит математические формулы внутри переданного элемента
     * @param {HTMLElement} element 
     */
    static render(element) {
        if (typeof renderMathInElement === 'function') {
            try {
                renderMathInElement(element, {
                    delimiters: [
                        {left: '$$', right: '$$', display: true},
                        {left: '\\[', right: '\\]', display: true},
                        {left: '$', right: '$', display: false},
                        {left: '\\(', right: '\\)', display: false}
                    ],
                    throwOnError: false
                });
            } catch (err) {
                console.error("KaTeX rendering error:", err);
            }
        } else {
            console.warn("KaTeX renderMathInElement is not available globally.");
        }
    }
}

// Экспортируем по умолчанию и как свойство window для обратной совместимости
window.KaTeXPlayer = KaTeXPlayer;
