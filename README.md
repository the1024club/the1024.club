# the1024.club

Features:

- offer 1k storage to anyone
- no user accounts or registration
- space is assigned based on pub/priv key interactions
- api-style endpoints that allocate a space for you based on the hash of your pubkey
- private key sign transactions to the api for CRUD of your 1k
- ability to set mime-type of your 1k

This guide will help you set up a 1KB storage site using PHP and SQLite, with public/private key management for storage and retrieval.

## Prerequisites

- PHP 7.4 or higher
- SQLite extension for PHP
- Sodium extension for PHP (for public/private key cryptography)
- Composer (for autoloading)

## Step 1: Clone the Repository

Start by cloning the repository or creating the necessary project structure.

```bash
git clone https://github.com/the1024club/the1024.club.git
cd the1024.club
```

## Step 2: Install Dependencies

# If you don't have Composer installed, first install it
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"


Make sure to install the required libraries using Composer.

```bash
php composer.phar require paragonie/sodium_compat
```

## Step 3: Configure the Database

The script will automatically create a SQLite database and table (`storage`) when you first run it. You don't need to manually create the database.

Ensure the database directory is writable:

```bash
mkdir -p /path/to/databasedir
chmod -R 755 /path/to/databasedir
```

## Step 4: Configure PHP Settings

Modify your PHP settings to enable or disable debugging:

- To enable debugging, append `?debug=true` to your URLs.
- To disable debugging, set `debug=false` or omit the `debug` parameter.

## Step 5: Getting Started with using the API

Follow these steps to start using your 1KB block via the API:

### 1. Install Required Dependencies

You need to install the required dependencies via Composer before running the utilities. Run the following commands:

```bash

cd utils

# Install sodium and cURL dependencies
php composer.phar require paragonie/sodium_compat
php composer.phar require guzzlehttp/guzzle
```

### 2. Generate a Key Pair

Use the provided `generate_keys.php` script to generate an Ed25519 public/private key pair:

```bash
# The script will output the Base64-encoded public and private keys, store them in files for use
php generate_keys.php > keys.txt

# You can now copy the public and private keys into separate files:
cat keys.txt | grep 'Public Key' | cut -d ' ' -f3 > public_key.pem
cat keys.txt | grep 'Private Key' | cut -d ' ' -f3 > private_key.pem
```

### 3. Create Your Block

Use the `1kb_client.php` script to create your block on the site:

```bash
# Create a new block using your public key
php 1kb_client.php create public_key.pem
```

### 4. Update Your Block

Sign your data with the private key, then use the `1kb_client.php` script to update your block:

```bash
# Sign your data and update the block with plain text
php 1kb_client.php update public_key.pem private_key.pem "Your data here" "text/plain"

# -or-

# Sign your data and update the block with HTML
php 1kb_client.php update public_key.pem private_key.pem "<h1>Hello, World!</h1>" "text/html"

# -or-

# Sign your data and update the block with an image:
php 1kb_client.php update public_key.pem private_key.pem "$(base64 /path/to/image.png)" "image/png"
```

### 5. Retrieve Your Block

Retrieve the data from your block using the `/retrieve` endpoint:

```bash
# Retrieve data using the public key
php 1kb_client.php retrieve public_key.pem
```

## Step 6: Main Page

By default, `main.php` is loaded when no action is specified. You can customize it with your own content. This file will be displayed to users visiting the root URL of your site.

## Conclusion

Your 1KB storage site is now set up and ready to use. You can interact with it via API calls to store, retrieve, update, and delete small pieces of data. Be sure to manage your public and private keys securely!
