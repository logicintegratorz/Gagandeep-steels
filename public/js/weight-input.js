(() => {
    const convert = (value, from) => {
        const text = value.trim();
        if (!/^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/.test(text)) return '';
        const result = from === 'metre' ? Number(text) * 0.3048 : Number(text) / 0.3048;
        return Number.isFinite(result) ? String(Number(result.toFixed(6))) : '';
    };
    if (typeof module !== 'undefined') module.exports = {convert};
    if (typeof document === 'undefined') return;
    document.querySelectorAll('[data-weight-pair]').forEach(pair => {
        const metre = pair.querySelector('[data-weight-metre]');
        const feet = pair.querySelector('[data-weight-feet]');
        const saved = pair.querySelector('[data-weight-value]');
        const update = from => {
            const source = from === 'metre' ? metre : feet;
            const target = from === 'metre' ? feet : metre;
            target.value = convert(source.value, from);
            saved.value = source.value.trim() ? (target.value !== '' ? `${metre.value.trim()} kg/mtr - ${feet.value.trim()} kg/feet` : `${source.value.trim()} kg/${from === 'metre' ? 'mtr' : 'feet'}`) : '';
            const legacy = pair.querySelector('[data-weight-legacy]');
            if (legacy) legacy.hidden = true;
        };
        metre.addEventListener('input', () => update('metre'));
        feet.addEventListener('input', () => update('feet'));
    });
})();
