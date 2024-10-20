<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="shrink-to-fit=no,width=device-width,height=device-height,initial-scale=1,user-scalable=1">
        <meta name="description" content="1024 bytes for everyone. What will you do with your 1k?">
        <title>The 1024 Club - 1k for Everyone</title>
        <link rel="stylesheet" type="text/css" href="/css/main.css" />
    </head>
    <body>
        <header>
            <h1>The 1024 Club</h1>
            <h2>1k for everybody</h2>
        </header>

        <nav>
            <ul>
                <li><a href="/">Home</a></li>
            </ul>
        </nav>

        <main>
            <section id="overview">
                <h2>Overview</h2>
                <p>Everyone deserves a little data to play with. Here at The 1024 Club
                    we give you a full 1024 bytes to play with as your very own. That's
                    1k, or about 1/11,796<sup>th</sup> the size of a floppy disk from
                    1986! What sort of magic can you dream up with yours?</p>
                <h3>Features List</h3>
                <ul>
                    <li>No user accounts or registration</li>
                    <li>API endpoint allocates a block of space for you based on the hash of your public key</li>
                    <li>Your private key signs data transactions to the API for CRUD of your 1k block</li>
                    <li>Ability to set MIME-type of your block (e.g., text, image, HTML)</li>
                </ul>
            </section>

            <section id="download-utils">
                <h2>Download Utilities</h2>
                <p>You can download the utility scripts needed to interact with the API:</p>
                <pre><code>
wget https://the1024.club/utils.zip
                </code></pre>
                <h3>Instructions to Unzip</h3>
                <p>After downloading the utils, unzip it using the following command:</p>
                <pre><code>
unzip utils.zip
                </code></pre>
                <p>This will extract the necessary utility scripts for interacting with the 1024 Club API.</p>
            </section>

            <section id="getting-started">
                <h2>Getting Started with the API</h2>
                <p>Follow these steps to start using your 1KB block via the API:</p>
                <ol>
                    <li>**Install Required Dependencies**: 
                        You need to install the required dependencies via Composer before running the utilities. Run the following commands:
                        <pre><code>
# If you don't have Composer installed, first install it
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Install sodium and cURL dependencies
php composer.phar require paragonie/sodium_compat
php composer.phar require guzzlehttp/guzzle
                        </code></pre>
                    </li>
                    
                    <li>**Generate a Key Pair**: 
                        Use the provided `generate_keys.php` script to generate an Ed25519 public/private key pair:
                        <pre><code>
                        
# The script will output the Base64-encoded public and private keys, store them in files for use
php generate_keys.php > keys.txt

# You can now copy the public and private keys into separate files:
cat keys.txt | grep 'Public Key' | cut -d ' ' -f3 > public_key.pem
cat keys.txt | grep 'Private Key' | cut -d ' ' -f3 > private_key.pem
                        </code></pre>
                    </li>

                    <li>**Create Your Block**: Use the `1kb_client.php` script to create your block on the site:
                        <pre><code>
# Create a new block using your public key
php 1kb_client.php create public_key.pem
                        </code></pre>
                    </li>

                    <li>**Update Your Block**: Sign your data with the private key, then use the `1kb_client.php` script to update your block:
                        <pre><code>
# Sign your data and update the block with plain text
php 1kb_client.php update public_key.pem private_key.pem "Your data here" "text/plain"

                         -or-

# Sign your data and update the block with html
php 1kb_client.php update public_key.pem private_key.pem "&lt;h1&gt;Hello, World!&lt;/h1&gt;" "text/html"

                         -or-

# Sign your data and update the block with an image:
php 1kb_client.php update public_key.pem private_key.pem "$(base64 /path/to/image.png)" "image/png"
                        </code></pre>
                    </li>

                    <li>**Retrieve Your Block**: Retrieve the data from your block using the `/retrieve` endpoint:
                        <pre><code>
# Retrieve data using the public key
php 1kb_client.php retrieve public_key.pem
                        </code></pre>
                    </li>
                </ol>
                <p>For more details on key generation and signing, see our <a href="#specification">Technical Specifications</a> section below.</p>
            </section>


            <section id="specification">
                <h2>Technical Specifications</h2>
                <p>This API uses Ed25519 key pairs for signing and verifying data. The API supports both URL-safe Base64 and non-URL-safe Base64-encoded public keys for all operations. Ensure you generate your keys with a supported tool such as OpenSSL or the `generate_keys.php` script.</p>
                <p>To sign your data, use your private key to generate a signature over the data, and then Base64-encode the signature for the API. Both the public key and signature must be Base64-encoded.</p>
                <p>The API supports various MIME types, including:</p>
                <ul>
                    <li><strong>text/plain</strong> for plain text</li>
                    <li><strong>text/html</strong> for HTML data</li>
                    <li><strong>image/png</strong> for PNG images</li>
                </ul>
            </section>

            <section id="recent-users">
                <h2>Last 10 Users' Public 1KB Pages</h2>
                <ul>
                    <?php
                    // Initialize database connection
                    try {
                        $db = new PDO('sqlite:/home/retrodig/1024clubdb/storage.db');
                        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    } catch (Exception $e) {
                        echo '<p>Failed to connect to the database: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        exit;
                    }

                    // Fetch last 10 public keys directly from the database
                    $stmt = $db->prepare("SELECT public_key FROM storage ORDER BY rowid DESC LIMIT 10");
                    $stmt->execute();
                    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($keys as $key) {
                        // Base64 encode the actual public key (binary)
                        $encodedPubKey = base64_encode($key['public_key']);

                        // Show link using the Base64-encoded public key
                        echo '<li><a href="/?action=retrieve&pubkey=' . rawurlencode($encodedPubKey) . '">Public Key: ' . htmlspecialchars($encodedPubKey) . '</a></li>';
                    }
                    ?>
                </ul>
            </section>
        </main>

        <aside>
            <h2>Did you know?</h2>
            <p><a href="https://hackaday.io">Hackaday.io</a> ran a contest in 2016
                for the best program in under 1k. Why not go
                <a href="https://hackaday.io/contest/18215-the-1kb-challenge">see the results of The 1kB Challenge</a>
                for yourself.</p>
        </aside>

        <footer>
            <p class="license-title">The MIT License (MIT)</p>
            <p class="license-copyright">©2020 The 1024 Club Developers (see AUTHORS.txt)</p>
            <p>Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:</p>
            <p>The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.</p>
            <p>THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.</p>
        </footer>
    </body>
</html>
