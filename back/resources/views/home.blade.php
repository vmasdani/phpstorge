<html x-data="{lumen: {{ $data }}} ">

<head>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <meta name="google-signin-client_id" :content="lumen.googleOauthClientKey">
</head>

<body>
    <input value="{{$data}}" id="lumen" style="display:none" />
    <div class="g-signin2" data-onsuccess="onSignIn"></div><a href="#" onclick="signOut();">Sign out</a>

    <template x-if="$store.data?.user">
        <div>
            <h5>User Info</h5>
            <hr />
            <div x-text="`Name: ${$store?.data?.user?.name}`"></div>
            <div x-text="`Email: ${$store?.data?.user?.email}`"></div>
            <img referrerPolicy="no-referrer" style="max-width:350" :src="$store?.data?.user?.picture">

            <div>
                <button @click="handleSync">Synchronise</button>
            </div>
        </div>

    </template>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('data', {
                user: null,
            })
        })

        const handleSync = async () => {
            try {
                const baseUrl = JSON.parse(document.getElementById('lumen')?.value)?.baseUrl
                alert(`sync`)
                console.log('handle sync')

                const resp = await fetch(`${baseUrl}/api/v1/sync`, {
                    method: 'post',
                    headers: {
                        'authorization': localStorage.getItem('apiKey'),
                        'auth_type': localStorage.getItem('authType'),
                    }
                })
            } catch (e) {
                console.error(e)
            }

        }

        async function onSignIn(googleUser) {
            var profile = googleUser.getBasicProfile();
            console.log('ID: ' + profile.getId()); // Do not send to your backend! Use an ID token instead.
            console.log('Name: ' + profile.getName());
            console.log('Image URL: ' + profile.getImageUrl());
            console.log('Email: ' + profile.getEmail()); // This is null if the 'email' scope is not present.
            console.log('Auth response: ', googleUser.getAuthResponse()); // This is null if the 'email' scope is not present.

            localStorage.setItem('apiKey', googleUser.getAuthResponse().id_token)
            localStorage.setItem('authType', 'google')

            try {
                const resp = await fetch(`${JSON.parse(document.getElementById('lumen').value)?.baseUrl}/api/v1/info`, {
                    method: 'post',
                    headers: {
                        authorization: googleUser.getAuthResponse().id_token,
                        auth_type: 'google'
                    }
                })

                if (resp.status !== 200) throw await resp.text()

                const user = await resp.json()

                Alpine.store('data').user = user

                console.log('[user]', user, Alpine.store('data').user)
            } catch (e) {
                console.error(e)
            }

        }

        function signOut() {
            var auth2 = gapi.auth2.getAuthInstance();
            auth2.signOut().then(function() {
                console.log('User signed out.');

                localStorage.removeItem('apiKey')
                localStorage.removeItem('authType')

                Alpine.store('data').user = null
            });
        }
    </script>
</body>

</html>