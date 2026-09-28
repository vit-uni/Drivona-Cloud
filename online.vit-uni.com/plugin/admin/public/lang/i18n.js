/**
 * 全局多语言工具（后端内联注入字典，同步可用）
 * 页面 head 需内联注入：window.__LANG_CODE__（zh/en/de）与 window.__LANG_DICT__（中文原文 => 译文）
 * key 采用中文原文，翻译时查表，未命中返回原文
 */
(function () {
    var SUPPORT = ['zh', 'en', 'de'];

    function currentLang() {
        var lang = window.__LANG_CODE__;
        return (lang && SUPPORT.indexOf(lang) !== -1) ? lang : 'zh';
    }

    function t(key) {
        if (typeof key !== 'string' || key === '') return key;
        if (currentLang() === 'zh') return key;
        var dict = window.__LANG_DICT__ || {};
        return dict[key] || key;
    }

    function applyI18n(root) {
        var scope = root || document;
        scope.querySelectorAll('[data-i18n]').forEach(function (el) {
            el.textContent = t(el.getAttribute('data-i18n'));
        });
        scope.querySelectorAll('[data-i18n-html]').forEach(function (el) {
            el.innerHTML = t(el.getAttribute('data-i18n-html'));
        });
        scope.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
            el.setAttribute('placeholder', t(el.getAttribute('data-i18n-placeholder')));
        });
        scope.querySelectorAll('[data-i18n-title]').forEach(function (el) {
            el.setAttribute('title', t(el.getAttribute('data-i18n-title')));
        });
    }

    window.t = t;
    window.__i18n = { currentLang: currentLang, t: t, applyI18n: applyI18n };

    // 文档就绪后自动翻译静态 data-i18n 元素
    function autoApply() {
        applyI18n();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoApply);
    } else {
        autoApply();
    }
})();