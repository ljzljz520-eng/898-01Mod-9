#!/bin/bash
set -e

echo "Initializing Laravel project..."

cd /var/www/html

echo "Ensuring Laravel writable directories (storage/, bootstrap/cache)..."
# Required by Laravel runtime
mkdir -p storage/logs \
         storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         bootstrap/cache

# Ensure log file exists (avoid permission issues on first write)
touch storage/logs/laravel.log 2>/dev/null || true

# Prefer correct ownership for php-fpm/nginx user (www-data), fallback to permissive chmod for dev env
if id www-data >/dev/null 2>&1; then
  chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
fi

# Make writable; if running on a filesystem that ignores chown, this still helps
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# Install dependencies if autoload is missing (vendor may exist but be incomplete)
if [ ! -f "vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Install and build frontend assets
if [ -f "package.json" ]; then
    echo "Installing Node.js dependencies..."
    npm config set registry https://registry.npmmirror.com || true
    npm ci || npm install || true
    
    echo "Building frontend assets..."
    npm run build || true
fi

# Copy .env if not exists
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    if [ -f ".env.example" ]; then
        cp .env.example .env
    else
        # Create minimal .env
        cat > .env <<EOF
APP_NAME=Forum
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=forum
DB_USERNAME=forum_user
DB_PASSWORD=forum_pass

SANCTUM_STATEFUL_DOMAINS=localhost:3000,localhost:3001
SESSION_DOMAIN=localhost
SESSION_DRIVER=file
EOF
    fi
else
    # Ensure SESSION_DRIVER is set in existing .env
    if ! grep -q "^SESSION_DRIVER=" .env; then
        echo "SESSION_DRIVER=file" >> .env
    elif grep -q "^SESSION_DRIVER=database" .env; then
        sed -i 's/^SESSION_DRIVER=database/SESSION_DRIVER=file/' .env
    fi
fi

# Generate app key if not set
if ! grep -q "APP_KEY=base64:" .env 2>/dev/null || [ -z "$(grep 'APP_KEY=base64:' .env)" ]; then
    echo "Generating application key..."
    php artisan key:generate --force || true
fi

# Wait for MySQL
echo "Waiting for MySQL to be ready..."
timeout=60
elapsed=0
until php -r "try { \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=forum', 'forum_user', 'forum_pass', [PDO::ATTR_TIMEOUT => 2]); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
    if [ $elapsed -ge $timeout ]; then
        echo "MySQL connection timeout, but continuing..."
        break
    fi
    sleep 2
    elapsed=$((elapsed + 2))
done

if php -r "try { \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=forum', 'forum_user', 'forum_pass', [PDO::ATTR_TIMEOUT => 2]); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; then
    echo "MySQL is up!"
else
    echo "MySQL connection test failed, but continuing..."
fi

# Check and create tables directly using SQL (bypass Laravel migration bug)
echo "Checking and creating database tables..."

# Function to check if table exists
check_table() {
    local table_name=$1
    php -r "
    try {
        \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=forum', 'forum_user', 'forum_pass');
        \$stmt = \$pdo->query(\"SHOW TABLES LIKE '${table_name}'\");
        exit(\$stmt->rowCount() > 0 ? 0 : 1);
    } catch (Exception \$e) {
        exit(1);
    }
    " 2>/dev/null
}

# Create migrations table if not exists
if ! check_table migrations; then
    echo "Creating migrations table..."
    php -r "
    \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=forum', 'forum_user', 'forum_pass');
    \$pdo->exec(\"
    CREATE TABLE IF NOT EXISTS migrations (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        migration varchar(255) NOT NULL,
        batch int NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    \");
    " 2>/dev/null || true
fi

# Create users table if not exists
if ! check_table users; then
    echo "Creating users table..."
    php -r "
    \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=forum', 'forum_user', 'forum_pass');
    \$pdo->exec(\"
    CREATE TABLE IF NOT EXISTS users (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        username varchar(50) NOT NULL,
        email varchar(100) NOT NULL,
        email_verified_at timestamp NULL DEFAULT NULL,
        password varchar(255) NOT NULL,
        avatar varchar(255) DEFAULT NULL,
        role enum('user','admin') NOT NULL DEFAULT 'user',
        status tinyint NOT NULL DEFAULT 1 COMMENT '1:正常 0:禁用',
        remember_token varchar(100) DEFAULT NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY users_username_unique (username),
        UNIQUE KEY users_email_unique (email),
        KEY users_username_index (username),
        KEY users_email_index (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    \");
    " 2>/dev/null || true
fi

# Create topics table if not exists
if ! check_table topics; then
    echo "Creating topics table..."
    php -r "
    \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=forum', 'forum_user', 'forum_pass');
    \$pdo->exec(\"
    CREATE TABLE IF NOT EXISTS topics (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint unsigned NOT NULL,
        title varchar(200) NOT NULL,
        content text NOT NULL,
        category varchar(50) NOT NULL DEFAULT 'general',
        view_count int NOT NULL DEFAULT 0,
        reply_count int NOT NULL DEFAULT 0,
        is_pinned tinyint NOT NULL DEFAULT 0,
        status tinyint NOT NULL DEFAULT 1 COMMENT '1:正常 0:删除',
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        deleted_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        KEY topics_user_id_index (user_id),
        KEY topics_category_index (category),
        KEY topics_created_at_index (created_at),
        CONSTRAINT topics_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    \");
    " 2>/dev/null || true
fi

# Create replies table if not exists
if ! check_table replies; then
    echo "Creating replies table..."
    php -r "
    \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=forum', 'forum_user', 'forum_pass');
    \$pdo->exec(\"
    CREATE TABLE IF NOT EXISTS replies (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        topic_id bigint unsigned NOT NULL,
        user_id bigint unsigned NOT NULL,
        content text NOT NULL,
        status tinyint NOT NULL DEFAULT 1 COMMENT '1:正常 0:删除',
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        deleted_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        KEY replies_topic_id_index (topic_id),
        KEY replies_user_id_index (user_id),
        KEY replies_created_at_index (created_at),
        CONSTRAINT replies_topic_id_foreign FOREIGN KEY (topic_id) REFERENCES topics (id) ON DELETE CASCADE,
        CONSTRAINT replies_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    \");
    " 2>/dev/null || true
fi

# Create personal_access_tokens table if not exists
if ! check_table personal_access_tokens; then
    echo "Creating personal_access_tokens table..."
    php -r "
    \$pdo = new PDO('mysql:host=mysql;port=3306;dbname=forum', 'forum_user', 'forum_pass');
    \$pdo->exec(\"
    CREATE TABLE IF NOT EXISTS personal_access_tokens (
        id bigint unsigned NOT NULL AUTO_INCREMENT,
        tokenable_type varchar(255) NOT NULL,
        tokenable_id bigint unsigned NOT NULL,
        name varchar(255) NOT NULL,
        token varchar(64) NOT NULL,
        abilities text,
        last_used_at timestamp NULL DEFAULT NULL,
        expires_at timestamp NULL DEFAULT NULL,
        created_at timestamp NULL DEFAULT NULL,
        updated_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY personal_access_tokens_token_unique (token),
        KEY personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    \");
    " 2>/dev/null || true
fi

# Insert migration records if not exists
echo "Updating migration records..."
php -r "
\$pdo = new PDO('mysql:host=mysql;port=3306;dbname=forum', 'forum_user', 'forum_pass');
\$migrations = [
    '2024_01_01_000001_create_users_table',
    '2024_01_01_000002_create_topics_table',
    '2024_01_01_000003_create_replies_table',
    // Mark Sanctum's default migration as already run to avoid duplicate table errors
    '2019_12_14_000001_create_personal_access_tokens_table'
];
\$stmt = \$pdo->prepare('INSERT IGNORE INTO migrations (migration, batch) VALUES (?, 1)');
foreach (\$migrations as \$migration) {
    \$stmt->execute([\$migration]);
}
" 2>/dev/null || true

# Run migrations to ensure everything is up to date (will skip existing tables)
echo "Running migrations to ensure schema is up to date..."
php artisan migrate --force --no-interaction 2>&1 | grep -v "ErrorException\|Array to string" || echo "Migration completed (some warnings may be ignored)"

# Seed database
echo "Seeding database..."
php artisan db:seed --force || true

echo "Laravel initialization complete!"
