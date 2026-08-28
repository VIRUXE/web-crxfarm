/**
 * Minimal vanilla-JS WebAuthn client for laravel/passkeys, using the
 * browser's native PublicKeyCredential.parseCreationOptionsFromJSON /
 * parseRequestOptionsFromJSON + credential.toJSON() (WebAuthn Level 3,
 * widely supported) instead of the @laravel/passkeys npm package — this
 * app has no JS build step, so no npm client is pulled in.
 */
(function () {
  const base = document.body.dataset.base || '';

  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').content;
  }

  async function postJSON(url, body) {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const message = data.errors ? Object.values(data.errors).flat().join(' ') : 'Something went wrong.';
      throw new Error(message);
    }
    return data;
  }

  async function registerPasskey(name) {
    const { options } = await fetch(`${base}/user/passkeys/options`, {
      headers: { Accept: 'application/json' },
    }).then((r) => r.json());

    const publicKey = PublicKeyCredential.parseCreationOptionsFromJSON(options);
    const credential = await navigator.credentials.create({ publicKey });

    return postJSON(`${base}/user/passkeys`, {
      name: name || 'Passkey',
      credential: credential.toJSON(),
    });
  }

  async function loginWithPasskey() {
    const { options } = await fetch(`${base}/passkeys/login/options`, {
      headers: { Accept: 'application/json' },
    }).then((r) => r.json());

    const publicKey = PublicKeyCredential.parseRequestOptionsFromJSON(options);
    const credential = await navigator.credentials.get({ publicKey });

    const data = await postJSON(`${base}/passkeys/login`, {
      credential: credential.toJSON(),
      remember: true,
    });

    window.location.href = data.redirect || `${base}/admin/listings`;
  }

  window.CrxPasskeys = { registerPasskey, loginWithPasskey };
})();
