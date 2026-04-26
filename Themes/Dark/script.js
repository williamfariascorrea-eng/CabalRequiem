// ========== CABAL REQUIEM - PROTECTION ==========

document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
}, false);

document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey && (e.key === 'u' || e.key === 'U' || e.key === 's' || e.key === 'S')) || 
        (e.ctrlKey && e.shiftKey && e.key === 'I') || 
        (e.ctrlKey && e.shiftKey && e.key === 'J') ||
        (e.ctrlKey && e.shiftKey && e.key === 'C')) {
        e.preventDefault();
    }
}, false);

document.addEventListener('selectstart', function(e) {
    e.preventDefault();
}, false);

document.addEventListener('dragstart', function(e) {
    e.preventDefault();
}, false);

document.addEventListener('copy', function(e) {
    e.preventDefault();
    alert('Copying prohibited. © 2026 Cabal Réquiem');
}, false);

if (window.top !== window.self) {
    window.top.location.href = window.self.location.href;
}

console.log('%c🚫 PROTEÇÃO ATIVA', 'color: red; font-size: 20px; font-weight: bold;');
console.log('%c© 2026 Cabal Réquiem - Todos os direitos reservados', 'color: #E6B325; font-size: 14px;');
console.log('%cEste site é protegido contra cópia.', 'color: #666; font-size: 12px;');

(function() {
    var originalCTO = Object.create;
    Object.create = function(proto) {
        return originalCTO.call(this, proto);
    };
})();