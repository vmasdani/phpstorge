<html x-data="{
    lumen: {{ $data }},
    signedIn: false
}" x-init="() => { 
    this.alpineData = $data 
    console.log($data)
}">

<head>
    <script src="//unpkg.com/alpinejs" defer></script>
    <script src="https://apis.google.com/js/platform.js" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/idb-keyval@6.0.3/dist/umd.js" integrity="sha256-3pK9NGoDNZL/nVNZZu4slx8QcA88Yd0yKNo2DMlJNXo=" crossorigin="anonymous"></script>
    <meta id="g-meta" name="google-signin-client_id" content="{{ json_decode($data)->googleOauthClientKey }}">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>
</head>

<body class="container">
    <div class="d-flex flex-column align-items-center">
        <h3>Storge</h3>
        <div>MySQL key-value adapter </div>
    </div>
    <input value="{{$data}}" id="lumen" style="display:none" />
    <div class="d-flex justify-content-around border border-dark p-3 rounded rounded-lg my-2">
        <div>
            <div class="g-signin2" data-onsuccess="onSignIn"></div>
            <template x-if="signedIn">
                <a href="#" onclick="signOut();">Sign out</a>
            </template>
        </div>

        <template x-if="signedIn">
            <div>
                <div>Generate API key:</div>
                <div class="d-flex justify-content-center"><button class="btn btn-sm btn-primary">API key</button></div>
            </div>
        </template>



    </div>
    <!-- <div x-text="JSON.stringify(lumen)"></div> -->
    <!-- <template x-if="signedIn"> -->
    <!-- </template> -->





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
            <div>
                <pre style="overflow: auto;padding:1em;color:white;background-color:gray" x-text="localStorage.getItem('apiKey')"></pre>
            </div>

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
                console.log(document.getElementById('g-meta'))
                await idbKeyval?.set('test', 'hello')
                console.log('[Test idbkeyval]', await idbKeyval?.get('test'))
            })()

            Alpine.store('data', {
                user: null,
                userData: null
            })


            console.log('[alpinedata]', this.alpineData)
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
                        'auth-type': localStorage.getItem('authType'),
                    },
                    body: JSON.stringify({
                        key: 'abcde'
                    })
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
                        'auth-type': localStorage.getItem('authType'),
                    }
                })

                if (resp.status !== 200) throw await resp.text()
                alert(JSON.stringify(await resp.json()))
            } catch (e) {
                console.error(e)
            }

        }



        async function onSignIn(googleUser, ) {
            if (this?.alpineData) {
                this.alpineData.signedIn = true
            }

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
                        'auth-type': 'google'
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

                if (this?.alpineData) {
                    this.alpineData.signedIn = false
                }
            });
        }
    </script>
</body>

</html>