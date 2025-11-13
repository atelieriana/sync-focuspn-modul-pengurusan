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
    image: ibnuauliana/oracle-database:21.3.0-ee
    container_name: "database"
    ports:
      - "1521:1521"
      - "5500:5500"
      - "2484:2484"
    volumes:
      - /home/ibnuaulianugrahaalihaq/Project/Database/Oracle:/opt/oracle/oradata
    environment:
      ORACLE_SID: "cdb1"
      ORACLE_PDB: "pdb1"
      ORACLE_PWD: "P!sang#123"
      ORACLE_EDITION: "enterprise"
      ORACLE_CHARACTERSET: "AL32UTF8"
      ENABLE_ARCHIVELOG: "true"
      ENABLE_FORCE_LOGGING: "true"
    ulimits:
      nofile:
        soft: 65536
        hard: 65536
  redis:
    image: redis:8.0-rc1-alpine3.21
    container_name: "redis"
    ports:
      - "6379:6379"
  minio:
    image: minio/minio:latest
    container_name: "minio"
    ports:
      - "9001:9001"
      - "9002:9002"
    volumes:
      - /home/ibnuaulianugrahaalihaq/Project/ObjectStorage:/data
    environment:
      MINIO_ROOT_USER: minioadmin
      MINIO_ROOT_PASSWORD: minioadmin
    command: server --address ":9002" --console-address ":9001" /data
```
