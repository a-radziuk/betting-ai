const config = window.cookieConsentConfig;

if (config) {
    const banner = document.getElementById('cookie-consent-banner');
    const modal = document.getElementById('cookie-consent-modal');
    const categoriesPanel = document.querySelector('[data-cookie-categories-panel]');
    const acceptAllButton = document.querySelector('[data-cookie-consent-accept-all]');
    const saveButton = document.querySelector('[data-cookie-consent-save]');
    const modeInputs = () => Array.from(document.querySelectorAll('[data-cookie-consent-mode]'));
    const categoryInputs = () => Array.from(document.querySelectorAll('[data-cookie-category]'));

    const optionalCategoryKeys = () => config.categories
        .filter((category) => !category.required)
        .map((category) => category.key);

    const allCategoryKeys = () => config.categories.map((category) => category.key);

    const readCookie = (name) => {
        const match = document.cookie.match(new RegExp(`(?:^|; )${name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&')}=([^;]*)`));

        return match ? decodeURIComponent(match[1]) : null;
    };

    const writeCookie = (name, value, days) => {
        const expires = new Date();
        expires.setDate(expires.getDate() + days);
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';

        document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires.toUTCString()}; path=/; SameSite=Lax${secure}`;
    };

    const parseConsent = () => {
        const raw = readCookie(config.cookieName);

        if (!raw) {
            return null;
        }

        try {
            const parsed = JSON.parse(raw);

            if (parsed.version !== config.version || typeof parsed.categories !== 'object') {
                return null;
            }

            return parsed;
        } catch {
            return null;
        }
    };

    const defaultCategories = (acceptOptional) => {
        const categories = {};

        config.categories.forEach((category) => {
            categories[category.key] = category.required ? true : acceptOptional;
        });

        return categories;
    };

    const categoriesFromInputs = () => {
        const categories = defaultCategories(false);

        categoryInputs().forEach((input) => {
            const key = input.dataset.cookieCategory;

            if (!key || input.disabled) {
                return;
            }

            categories[key] = input.checked;
        });

        return categories;
    };

    const syncInputs = (categories) => {
        categoryInputs().forEach((input) => {
            const key = input.dataset.cookieCategory;

            if (!key || input.disabled) {
                return;
            }

            input.checked = Boolean(categories[key]);
        });
    };

    const isAcceptAllMode = () => modeInputs().some((input) => input.checked && input.value === 'accept-all');

    const setConsentMode = (mode) => {
        modeInputs().forEach((input) => {
            input.checked = input.value === mode;
        });

        updateModeUi();
    };

    const updateModeUi = () => {
        const acceptAll = isAcceptAllMode();

        if (acceptAll) {
            syncInputs(defaultCategories(true));
        }

        categoryInputs().forEach((input) => {
            const key = input.dataset.cookieCategory;
            const category = config.categories.find((item) => item.key === key);

            if (category?.required) {
                return;
            }

            input.disabled = acceptAll;
        });

        if (categoriesPanel) {
            categoriesPanel.classList.toggle('cookie-consent-category-list--locked', acceptAll);
        }

        if (acceptAllButton) {
            acceptAllButton.hidden = !acceptAll;
        }

        if (saveButton) {
            saveButton.hidden = acceptAll;
        }
    };

    const hideBanner = () => {
        if (banner) {
            banner.hidden = true;
        }
    };

    const showBanner = () => {
        if (banner) {
            banner.hidden = false;
        }
    };

    const hideModal = () => {
        if (modal) {
            modal.hidden = true;
        }
    };

    const showModal = () => {
        if (modal) {
            modal.hidden = false;
        }
    };

    const loadedScripts = new Set();

    const loadScript = (definition) => {
        const signature = JSON.stringify(definition);

        if (loadedScripts.has(signature)) {
            return;
        }

        loadedScripts.add(signature);

        if (definition.type === 'external') {
            const script = document.createElement('script');
            script.src = definition.src;

            if (definition.async) {
                script.async = true;
            }

            document.head.appendChild(script);

            return;
        }

        if (definition.type === 'inline') {
            const script = document.createElement('script');
            script.text = definition.content;
            document.head.appendChild(script);
        }
    };

    const loadAllowedScripts = (categories) => {
        optionalCategoryKeys().forEach((key) => {
            if (!categories[key]) {
                return;
            }

            (config.scripts[key] || []).forEach(loadScript);
        });
    };

    const clearOptionalCookies = () => {
        document.cookie.split(';').forEach((part) => {
            const [name] = part.split('=');

            if (!name) {
                return;
            }

            const trimmed = name.trim();

            if (trimmed === config.cookieName) {
                return;
            }

            document.cookie = `${trimmed}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
        });
    };

    const persistConsent = async (consent, action) => {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        const response = await fetch(config.storeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
            },
            body: JSON.stringify({
                consent_uuid: consent.id,
                action,
                categories: consent.categories,
            }),
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error('Could not record cookie consent.');
        }

        const payload = await response.json();

        return {
            ...consent,
            id: payload.consent_uuid,
            version: payload.version,
            categories: payload.categories,
        };
    };

    const applyConsent = async (categories, action) => {
        const existing = parseConsent();
        const consent = {
            id: existing?.id || crypto.randomUUID(),
            version: config.version,
            categories,
            updatedAt: Date.now(),
        };

        try {
            const recorded = await persistConsent(consent, action);
            consent.id = recorded.id;
            consent.categories = recorded.categories;
        } catch {
            // Keep local consent even if recording fails so non-essential scripts stay blocked.
        }

        writeCookie(config.cookieName, JSON.stringify(consent), config.cookieLifetimeDays);

        if (optionalCategoryKeys().some((key) => categories[key])) {
            loadAllowedScripts(categories);
        } else {
            clearOptionalCookies();
        }

        hideBanner();
        hideModal();
    };

    const hasOptionalConsent = (categories) => optionalCategoryKeys().some((key) => categories[key]);

    const handleAccept = () => applyConsent(defaultCategories(true), 'accepted_all');

    const handleReject = () => {
        const existing = parseConsent();
        const action = existing && hasOptionalConsent(existing.categories) ? 'withdrawn' : 'rejected_all';

        return applyConsent(defaultCategories(false), action);
    };

    const handleSave = () => applyConsent(categoriesFromInputs(), 'customized');

    const openPreferences = () => {
        const existing = parseConsent();

        if (existing && !hasOptionalConsent(existing.categories)) {
            setConsentMode('customize');
            syncInputs(existing.categories);
        } else if (existing && hasOptionalConsent(existing.categories)) {
            const allOptionalAccepted = optionalCategoryKeys().every((key) => existing.categories[key]);

            if (allOptionalAccepted) {
                setConsentMode('accept-all');
            } else {
                setConsentMode('customize');
                syncInputs(existing.categories);
            }
        } else {
            setConsentMode('accept-all');
        }

        showModal();
    };

    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-cookie-consent-mode]')) {
            updateModeUi();
        }
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-cookie-consent-action]');

        if (!trigger) {
            return;
        }

        const action = trigger.dataset.cookieConsentAction;

        if (action === 'accept') {
            handleAccept();
        }

        if (action === 'reject') {
            handleReject();
        }

        if (action === 'customize') {
            openPreferences();
        }

        if (action === 'save' && !isAcceptAllMode()) {
            handleSave();
        }

        if (action === 'close-modal') {
            hideModal();
        }
    });

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-cookie-settings-open]');

        if (!trigger) {
            return;
        }

        event.preventDefault();
        openPreferences();
    });

    window.CookieConsent = {
        openPreferences,
        getConsent: parseConsent,
        acceptAll: handleAccept,
        rejectAll: handleReject,
    };

    const existingConsent = parseConsent();

    if (existingConsent) {
        hideBanner();

        if (hasOptionalConsent(existingConsent.categories)) {
            loadAllowedScripts(existingConsent.categories);
        }
    } else {
        showBanner();
    }

    updateModeUi();
}
