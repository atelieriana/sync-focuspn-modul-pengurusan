# Docker Compose Script
Gunakan docker-compose.yaml dibawah untuk melakukan running aplikasi
```shell
services:
  app:
    image: ibnuauliana/php83:debian-nginx-oci
    container_name: "app"
    working_dir: /application
    ports:
      - "80:80"
    volumes:
      - ./:/application
  database:
    image: postgres:18.4-alpine3.23
    container_name: "database"
    ports:
      - "5444:5432"
    volumes:
      - /home/ibnuauliana/Database/Postgres:/var/lib/postgresql
    environment:
      POSTGRES_USER: piutangnegara
      POSTGRES_PASSWORD: "P!sang#123"
      POSTGRES_DB: piutangnegara
```
