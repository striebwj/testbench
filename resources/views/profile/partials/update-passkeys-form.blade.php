<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Manage Passkeys') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Passkeys are password-less authentication tokens that can be used to access your account.') }}
            {{ __('They are more secure than passwords and can be used to access your account from trusted devices.') }}
        </p>
    </header>

    <div id="generate-passkey" class="hidden">
        <button onclick="generatePasskey()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-black bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Generate Passkey</button>
    </div>

</section>


<script>
    // Availability of `window.PublicKeyCredential` means WebAuthn is usable.
    // `isUserVerifyingPlatformAuthenticatorAvailable` means the feature detection is usable.
    // `isConditionalMediationAvailable` means the feature detection is usable.
    if (window.PublicKeyCredential &&
        PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable &&
        PublicKeyCredential.isConditionalMediationAvailable) {
        // Check if user verifying platform authenticator is available.
        Promise.all([
            PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable(),
            PublicKeyCredential.isConditionalMediationAvailable(),
    ]).then(results => {
            if (results.every(r => r === true)) {
                console.log('User Verifying Platform Authenticator is available.');
                document.getElementById('generate-passkey').classList.remove('hidden');
            }
        });
    }

    // Validate webauthn support
    function validateWebAuthnSupport() {
        if (!window.PublicKeyCredential) {
            alert('This browser does not support WebAuthn.');
            return false;
        }
        return true;
    }

    // Generate a new passkey
    function generatePasskey() {
        if (!validateWebAuthnSupport()) {
            return;
        }

        // Create a new credential
        navigator.credentials.create({
            publicKey: {
                // Relying Party (a.k.a. - Service):
                rp: {
                    name: 'Laravel',
                },

                // User:
                user: {
                    id: Uint8Array.from(window.crypto.getRandomValues(new Uint8Array(32))),
                    name: '{{ auth()->user()->email }}',
                    displayName: '{{ auth()->user()->name }}',
                },

                // Challenge:
                challenge: Uint8Array.from(window.crypto.getRandomValues(new Uint8Array(32))),

                // Relying Party:
                pubKeyCredParams: [{alg: -7, type: "public-key"},{alg: -257, type: "public-key"}],

                // Authenticator Selection:
                authenticatorSelection: {
                    authenticatorAttachment: 'platform',
                    userVerification: 'required',
                },

                // Attestation:
                attestation: 'direct',
            },
        }).then((newCredentialInfo) => {
            // Send new credential info to the server for verification and registration.
            const data = {
                id: newCredentialInfo.id,
                rawId: new Uint8Array(newCredentialInfo.rawId),
                type: newCredentialInfo.type,
                response: {
                    attestationObject: new Uint8Array(newCredentialInfo.response.attestationObject),
                    clientDataJSON: new Uint8Array(newCredentialInfo.response.clientDataJSON),
                },
            };

            fetch('/passkeys', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(data),
            }).then((response) => {
                if (response.ok) {
                    return response.json();
                }

                return Promise.reject(new Error('Something went wrong.'));
            }).then((response) => {
                // Passkey was successfully generated.
                alert('Passkey was successfully generated.');
            }).catch((error) => {
                // Passkey was not generated.
                alert('Passkey was not generated.');
            });
        }).catch((error) => {
            // Passkey was not generated.
            alert('Passkey was not generated.');
        });
    }
</script>
