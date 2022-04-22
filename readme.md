# storge

Storge is a REST API based, offline capable, key-value database, written in [Laravel Lumen](https://lumen.laravel.com/docs/9.x) framework.  

It is a NoSQL abstraction of an SQL database (first class support is for MySQL/MariaDB) in order to bring cloud synchronization to apps with local offline NoSQL databases, like:
- [Hive](https://pub.dev/packages/hive) for Flutter
- IndexedDB/[idb-keyval](https://github.com/jakearchibald/idb-keyval) for web

while maintaining language agnosticism.

### Terminologies
- **Storage**: equivalent to SQL table
- **StorageRecord**: equivalent to SQL row

### Design choices

- Why MySQL? For flexibility, why not just use MongoDB/other NoSQL databases? **TODO**

```
┌────────────────┐
│User:           │       ┌─────────────┐        ┌────────────────────────────────────────────────────┐
│valian@xmail.com│◄───┬──┤Storage: note│◄───┬───┤StorageRecord                                       │
└────────────────┘    │  └─────────────┘    │   │value: {"title":"hello","body":"world","uuid":"a01"}│
                      │                     │   └────────────────────────────────────────────────────┘
                      │                     │
                      │                     │   ┌────────────────────────────────────────────────────┐
                      │                     │   │StorageRecord                                       │
                      │                     ├───┤value: {"title":"note2","body":"world","uuid":"a03"}│
                      │                     │   └────────────────────────────────────────────────────┘
                      │                     │
                      │                     │   ┌────────────────────────────────────────────────────┐
                      │                     │   │StorageRecord                                       │
                      │                     └───┤value: {"title":"note3","body":"world","uuid":"a04"}│
                      │                         └────────────────────────────────────────────────────┘
                      │
                      │ ┌───────────────┐       ┌────────────────────────────────────────────────────┐
                      └─┤ Storage:sales │◄───┬──┤StorageRecord                                       │
                        └───────────────┘    │  │value: {"kioskId":1,"itemId":1,"qty":5,"uuid":"b05"}│
                                             │  └────────────────────────────────────────────────────┘
                                             │
                                             │  ┌─────────────────────────────────────────────┐
                                             │  │StorageRecord                                │
                                             └──┤value: {"kioskId":3,"itemId":1,"qty":9,      │
                                                │        "discountedPrice":10500,"uuid":"b06"}│
                                                └─────────────────────────────────────────────┘
```

## Prerequisites

1. Copy `.env.example` to `.env`, fill in appropriate values

## Running


1. `.env` contents

A few things needs to be passed in order to make storge run correctly. Example is in `.env.example`

```sh
DB_CONNECTION=mysql # or sqlite, if you wish
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

BASE_URL=http://localhost:8000 # the URL where you host this app
GOOGLE_OAUTH_CLIENT_KEY=blah.apps.googleusercontent.com # for google sign in. Create oauth key first in google developer console
```

2. migrate database

```sh
./artisan migrate
```

3. Run php server

```sh
php -S 0.0.0.0:8000 -t public
```

## Authentication Scheme
There are 3 options for authenticating:
1. Google
2. Facebook (Todo)
3. Custom backend

```
                     ┌──────────────┐
           ┌───────►│ Google OAuth ├───────────────────────────┐
           │        └──────────────┘                           │
           │        Header: auth-type: google                  │
           │        Header: authorization: eyJsklfds..         │
           │                                                   │
           │                                                   │
           │                                                ┌──▼────┐
   ┌───────┴───┐    ┌────────────────┐                      │Storge │
   │Client     ├───►│ Facebook OAuth ├─────────────────────►│API    │
   │Mobile/Web │    └────────────────┘                      └──▲────┘
   └───────┬───┘    Header: auth-type: facebook                │
           │        Header: authorization: eyJsklfds..         │
           │                                                   │
           │                                                   │
           │        ┌────────────────┐                         │
           └───────►│Custom backend  ├─────────────────────────┘
                    └────────────────┘
                    Header: auth-type: api_key
                    Header: authorization: eyJkalsjd..
```
For custom backend, you can generate an API key through Storge management console, in order for the backend to freely communicate with Storge API. **Make sure you store your API token securely**.

## Synchronisation

Synchronisation is done through Protobuf v3 JSON. The spec is available [here](https://github.com/vmasdani/storge/blob/main/back/protos/masterstorge.proto) in `StorageProto`. You can [compile your proto files to the client that you use (Typescript, Dart, Java, etc.)](https://developers.google.com/protocol-buffers/docs/tutorials). The important data are:


### QUICKSTART: Sandbox mode, needs no auth
```
POST /api/v2/sandbox/sync
```
```json
{
  "key": "notes",
  "email": "valian@xmail.com",
  "storage_records": [
    {
      "baseModel": {
        "id": 1
      },
      "value": "{\"my\": \"first_data\"}",
      "created": 1649432105879,
      "updated": 1649432105879,
      "deleted": 1649432105879
    },
    {
      "value": "{\"my\": \"second_data\"}",
      "created": 1649432105880,
      "updated": 1649432105900
    }
  ]
}
```
Note that you need to supply `email` field in sandbox mode, in order to differentiate the api which needs actual authorization token, and the regular one which does not. The email in sandbox mode will be appended with `@sandbox` suffix at the end of the user email. For example `valian@xmail.com@sandbox`;

### With authentication
```
POST /api/v2/sync

Header: content-type: application/json
Header: auth-type: google
Header: authorization: <google oauth2 token i.e. eyJ12isdjfdi...>
```

```json
{
  "key": "notes",
  "storage_records": [
    {
      "baseModel": {
        "id": 1
      },
      "value": "{\"my\": \"first_data\"}",
      "created": 1649432105879,
      "updated": 1649432105879,
      "deleted": 1649432105879
    },
    {
      "value": "{\"my\": \"second_data\"}",
      "created": 1649432105880,
      "updated": 1649432105900
    }
  ]
}
```

- If you provide no ID, then the storage record will be treated as new (`CREATE` operation).
- If you provide ID, the system will compare the `updated` timestamp. If the record in the POST data is greater than the database, it will update the record in the database. If it's not, it will keep the version in the database.
- Deleting records can be done by passing the `deleted` flag. You can nullify the `value`, `created`, and `updated` if you wish, in order to save space.

## (WIP): generate secret

Generate secret

```
http://localhost:8000/api/v1/secret
```

Put secret to `JWT_SECRET` in `.env`

```
JWT_SECRET=
```

## Todos:
1. Rewrite to Laravel. Should not be a hard work, since the Lumen project [recommends Laravel instead of Lumen for new projects](https://lumen.laravel.com/docs/9.x#installation).
2. Create Facebook auth.
3. Create token based auth in order for other backends to use storge.