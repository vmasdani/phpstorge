# storge

## Prerequisites

1. Copy `.env.example` to `.env`, fill in appropriate values

## Running

1. Run php server

```
php -S 0.0.0.0:8000 -t public
```

2. Generate secret

```
http://localhost:8000/api/v1/secret
```

3. Put secret to `JWT_SECRET` in `.env`
```
JWT_SECRET=
```