# API Sahabat Sehat

## Run The Project

Follow these step to run the migration locally

### 1\. Clone Repository

```bash
git clone https://github.com/laurentiusn/be-sahabat-sehat.git
```
```bash
cd .\be-sahabat-sehat\
```

### 2\. Install Dependency

```bash
composer install
```

### 3\. Konfigurasi Environment

```bash
cp .env.example .env
```

Buka file `.env` dan atur konfigurasi database

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=[nama_database_anda]
DB_USERNAME=[user_database_anda]
DB_PASSWORD=[password_database_anda]
```

Konfigurasi database default:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=be-sahabat-sehat
DB_USERNAME=root
DB_PASSWORD=
```

### 4\. Generate Application Key & JWT Secret
Generate **Application Key** dan **JWT Secret** di `.env`
```bash
php artisan key:generate
```
```bash
php artisan jwt:secret
```

### 5\. Migrasi

```bash
php artisan migrate
```

```bash
php artisan migrate:fresh --seed
```