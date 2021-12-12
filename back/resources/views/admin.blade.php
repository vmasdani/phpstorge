<script src="//unpkg.com/alpinejs" defer></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous"></script>

<div x-data="{
    lumen: {{ $data }},
    apiKey: localStorage.getItem('apiKey'),
    passphrase: '',
    users: []
}">
    <template x-if="!apiKey">
        <div class="vw-100 vh-100 d-flex align-items-center justify-content-center">
            <div class="d-flex flex-column justify-content-center align-items-center p-3 shadow shadow-md border">
                <div>
                    <input class="form-control form-control-sm" placeholder="Passphrase..." @change="e => {
                    passphrase = e?.target?.value
                }" />
                </div>
                <div x-text="passphrase"></div>
                <div class="my-2">
                    <button class="btn btn-primary btn-sm" @click="async () => {
                        try {
                            const resp = await fetch(`${lumen?.data?.baseUrl}/api/v1/admin/login`, {
                                method: 'post',
                                headers: {
                                    'content-type': 'application/json'
                                },
                                body: JSON.stringify({
                                    passphrase: passphrase
                                })
                            })

                            if (resp.status !== 200) throw await resp.text();

                            const apiKeyData = await resp.text();
                            apiKey = apiKeyData
                            localStorage.setItem('apiKey', apiKeyData)

                            
                        } catch (e) {
                            console.error(e)
                            alert('Error logging in')
                        }
                    }">
                        Login
                    </button>
                </div>

            </div>
        </div>
    </template>

    <template x-if="apiKey">
        <div class="container" x-init="async () => {
                users = await fetchUsers({
                                baseUrl: lumen?.data?.baseUrl ?? '',
                                apiKey: apiKey ?? '',
                            })
            }">
            <input value="{{ $data }}" style="display:none" />
            <div class="my-2">
                <h4>Admin page</h4>
            </div>

            <hr />

            <!-- <div x-text="JSON.stringify(users)"></div> -->
            <div class="my-2">
                <table class="table table-sm table-bordered table-hover">
                    <thead>
                        <tr class="bg-dark text-light ">
                            <th>#</th>
                            <th>Email</th>
                            <th>Stores</th>
                            <th>API key</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(u, i) in users">
                            <tr>
                                <td class="border border-secondary">
                                    <div x-text="i + 1"></div>
                                </td>
                                <td class="border border-secondary">
                                    <div x-text="u?.email "></div>
                                </td>
                                <td class="border border-secondary">
                                    <ol>
                                        <template x-for="(s, j) in (u?.storages ?? []) ">
                                            <li>
                                                <div x-text="s?.key ?? '[NO KEY]'"></div>
                                            </li>
                                        </template>
                                    </ol>
                                </td>
                                <td class="border border-secondary">
                                    <template x-if="!(u?.api_key) || u?.api_key === ''">
                                        <div>
                                            <button class="btn btn-primary btn-sm" @click="async () => {
                                                try {
                                                    const resp = await fetch(`${lumen?.data?.baseUrl}/api/v1/admin/users-gen-api-key/${u?.id}`, {
                                                        method: 'post',
                                                        headers: {
                                                            authorization: apiKey ?? '',
                                                            auth_type: 'jwt',
                                                            'content-type': 'application/json'
                                                        }
                                                    })

                                                    if (resp.status !== 200) throw await resp.text()

                                                    window.location.reload();
                                                    return await resp.json()
                                                } catch (e) {
                                                    console.error(e)
                                                    return null
                                                }
                                                }">
                                                Generate
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="u?.api_key">
                                        <div x-text="u?.api_key">

                                        </div>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <hr />


            <button class="btn btn-danger" @click="() => {
            apiKey = null;
            localStorage.removeItem('apiKey');
        }">
                Logout
            </button>
        </div>

    </template>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('data', {
                users: []
            })
        })

        const fetchUsers = async (params) => {
            try {
                const resp = await fetch(`${params?.baseUrl}/api/v1/admin/users`, {
                    headers: {
                        authorization: params?.apiKey ?? '',
                        auth_type: 'jwt'
                    }
                })

                if (resp.status !== 200) throw await resp.text()
                return await resp.json()
            } catch (e) {
                console.error(e)
                return []
            }
        }
    </script>
</div>