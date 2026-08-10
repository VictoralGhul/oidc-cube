(function () {
  'use strict';

  function decode(value) {
    const padded = value.replace(/-/g, '+').replace(/_/g, '/') + '==='.slice((value.length + 3) % 4);
    const binary = atob(padded);
    return Uint8Array.from(binary, function (character) { return character.charCodeAt(0); });
  }

  function encode(value) {
    const bytes = new Uint8Array(value);
    let binary = '';
    bytes.forEach(function (byte) { binary += String.fromCharCode(byte); });
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
  }

  function creationOptions(json) {
    if (window.PublicKeyCredential && typeof PublicKeyCredential.parseCreationOptionsFromJSON === 'function') {
      return PublicKeyCredential.parseCreationOptionsFromJSON(json);
    }
    json.challenge = decode(json.challenge);
    json.user.id = decode(json.user.id);
    (json.excludeCredentials || []).forEach(function (credential) { credential.id = decode(credential.id); });
    return json;
  }

  function requestOptions(json) {
    if (window.PublicKeyCredential && typeof PublicKeyCredential.parseRequestOptionsFromJSON === 'function') {
      return PublicKeyCredential.parseRequestOptionsFromJSON(json);
    }
    json.challenge = decode(json.challenge);
    (json.allowCredentials || []).forEach(function (credential) { credential.id = decode(credential.id); });
    return json;
  }

  function credentialJson(credential) {
    if (typeof credential.toJSON === 'function') {
      return credential.toJSON();
    }
    const response = {
      clientDataJSON: encode(credential.response.clientDataJSON),
    };
    if (credential.response.attestationObject) {
      response.attestationObject = encode(credential.response.attestationObject);
      if (typeof credential.response.getTransports === 'function') {
        response.transports = credential.response.getTransports();
      }
    } else {
      response.authenticatorData = encode(credential.response.authenticatorData);
      response.signature = encode(credential.response.signature);
      response.userHandle = credential.response.userHandle ? encode(credential.response.userHandle) : null;
    }
    return {
      id: credential.id,
      rawId: encode(credential.rawId),
      type: credential.type,
      response: response,
      clientExtensionResults: credential.getClientExtensionResults(),
      authenticatorAttachment: credential.authenticatorAttachment || null,
    };
  }

  function status(message) {
    const element = document.getElementById('oidc-passkey-status');
    if (element) element.textContent = message;
  }

  async function authenticate() {
    try {
      if (!window.PublicKeyCredential || !navigator.credentials) throw new Error('unsupported');
      status('Waiting for your passkey…');
      const options = requestOptions(JSON.parse(rcmail.env.oidc_passkey_options));
      const credential = await navigator.credentials.get({ publicKey: options });
      const form = document.createElement('form');
      form.method = 'post';
      form.action = rcmail.env.oidc_passkey_action;
      const fields = {
        _token: rcmail.env.oidc_passkey_token,
        _response: JSON.stringify(credentialJson(credential)),
      };
      Object.keys(fields).forEach(function (name) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = fields[name];
        form.appendChild(input);
      });
      document.body.appendChild(form);
      form.submit();
    } catch (error) {
      status('Passkey authentication was cancelled or could not be completed.');
    }
  }

  async function register(optionsJson) {
    try {
      if (!window.PublicKeyCredential || !navigator.credentials) throw new Error('unsupported');
      status('Waiting for your device…');
      const credential = await navigator.credentials.create({
        publicKey: creationOptions(JSON.parse(optionsJson)),
      });
      const label = window.prompt('Name this passkey', 'Passkey') || 'Passkey';
      rcmail.http_post('plugin.oidc-passkey-register', {
        _response: JSON.stringify(credentialJson(credential)),
        _label: label,
      });
      status('Verifying passkey…');
    } catch (error) {
      status('Passkey registration was cancelled or could not be completed.');
    }
  }

  if (window.rcmail) {
    rcmail.addEventListener('init', function () {
      if (rcmail.env.oidc_passkey_mode === 'authentication') {
        const button = document.getElementById('oidc-passkey-continue');
        if (button) button.addEventListener('click', authenticate);
        window.setTimeout(authenticate, 100);
      }

      if (rcmail.env.oidc_passkey_mode === 'settings') {
        const add = document.getElementById('oidc-passkey-add');
        if (add) add.addEventListener('click', function () {
          rcmail.http_post('plugin.oidc-passkey-register-options');
        });
        document.querySelectorAll('.oidc-passkey-delete').forEach(function (button) {
          button.addEventListener('click', function () {
            if (window.confirm('Delete this passkey?')) {
              rcmail.http_post('plugin.oidc-passkey-delete', { _id: button.dataset.id });
            }
          });
        });
        rcmail.addEventListener('plugin.oidc_passkey_options', function (data) {
          if (data && data.mode === 'registration') register(data.options);
        });
      }
    });
  }
}());
