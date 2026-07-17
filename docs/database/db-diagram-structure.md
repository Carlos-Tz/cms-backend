# Diagrama de base de datos utilizado

Sintaxis [DBML](https://dbml.dbdiagram.io). Solo se incluyen las tablas que
intervienen en la autenticación. La única tabla que permite iniciar sesión es
`users` (guard JWT `api`); `password_reset_tokens` y `sessions` son tablas de
soporte generadas por Laravel.

```dbml
Table users {
  id bigint [pk, increment]
  name varchar
  email varchar [unique, not null, note: 'Credencial de login']
  email_verified_at timestamp [null]
  password varchar [not null, note: 'Hash bcrypt; oculto en respuestas']
  remember_token varchar [null]
  created_at timestamp
  updated_at timestamp

  Note: 'Usuarios del panel admin. Autenticación vía JWT (guard api).'
}

Table password_reset_tokens {
  email varchar [pk]
  token varchar [not null]
  created_at timestamp [null]
}

Table sessions {
  id varchar [pk]
  user_id bigint [ref: > users.id, null]
  ip_address varchar(45) [null]
  user_agent text [null]
  payload longtext [not null]
  last_activity integer [not null]

  indexes {
    user_id
    last_activity
  }
}
```
