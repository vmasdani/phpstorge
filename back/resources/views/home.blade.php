<html x-data="{lumen: {{ $data }}} ">

<head>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/idb-keyval@6.0.3/dist/umd.js" integrity="sha256-3pK9NGoDNZL/nVNZZu4slx8QcA88Yd0yKNo2DMlJNXo=" crossorigin="anonymous"></script>
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

            <div style="display:flex">
                <div>
                    <button @click="handleSync">Synchronise</button>
                </div>
                <div>
                    <button @click="handleSyncTestAdd">Synchronise Test Add</button>
                </div>
            </div>

            <hr />

            <div x-text="`Storages: ${
                $store?.data?.userData?.storages?.length
            }, Records: ${
                $store?.data?.userData?.storages?.reduce((acc, s) => acc + (s?.storage_records?.length ?? 0), 0)
            }, JSON length: ${
                JSON.stringify($store?.data?.userData)?.length
            }`"></div>
            <!-- <div x-text="JSON.stringify($store.data)"></div> -->
            <div style="height:60vh;resize:vertical;border:2px solid grey;overflow:auto">
                <pre>
                    <small x-text="JSON.stringify ($store?.data?.userData, null, 2)">
                        
                    </small>
                </pre>
            </div>

        </div>

    </template>

    <script>
        document.addEventListener('alpine:init', () => {
            (async () => {
                await idbKeyval?.set('test', 'hello')
                console.log('[Test idbkeyval]', await idbKeyval?.get('test'))
            })()
            
            Alpine.store('data', {
                user: null,
                userData: null
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

                if (resp.status !== 200) throw await resp.text()

                const respData = await resp.json()

                Alpine.store('data').userData = respData
            } catch (e) {
                console.error(e)
            }

        }

        const handleSyncTestAdd = async () => {
            try {
                const baseUrl = JSON.parse(document.getElementById('lumen')?.value)?.baseUrl
                alert(`sync`)
                console.log('handle sync')

                const resp = await fetch(`${baseUrl}/api/v1/sync-test-add`, {
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